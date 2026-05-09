<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\model\ImageTask;
use app\common\service\StorageService;
use GuzzleHttp\Client;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

/**
 * 配图异步轮询命令 - V2.9.1 M14a
 *
 * 用法:
 *   php think image:poll           # 单次运行，扫描并处理pending任务（最长60秒）
 *   php think image:poll --daemon  # 守护模式，持续运行（需配合supervisor/systemd）
 *
 * 设计约束:
 *   - 单次运行最长60秒，避免进程长期占用
 *   - 每3秒扫描一次pending任务
 *   - 每个任务最大30次轮询≈90秒超时
 *   - 失败任务自动重试，最多3次，间隔递增(30s/60s/120s)
 */
class ImagePollCommand extends Command
{
    protected Client $httpClient;
    protected int $startTime;
    protected int $maxRuntime = 60; // 单次运行最长60秒
    protected int $scanInterval = 3; // 扫描间隔3秒
    protected bool $daemon = false;

    protected function configure()
    {
        $this->setName('image:poll')
            ->setDescription('配图异步任务轮询（FLUX/DALL-E等AI配图进度查询与结果下载）')
            ->addOption('daemon', 'd', Option::VALUE_NONE, '守护模式，持续运行')
            ->addOption('max-runtime', 'r', Option::VALUE_OPTIONAL, '单次运行最大秒数', 60);
    }

    protected function execute(Input $input, Output $output)
    {
        $this->daemon = (bool) $input->getOption('daemon');
        $this->maxRuntime = (int) $input->getOption('max-runtime');
        $this->startTime = time();
        $this->httpClient = new Client([
            'timeout' => 10,
            'http_errors' => false,
        ]);

        $output->writeln('[' . date('Y-m-d H:i:s') . '] image:poll 启动');

        do {
            $processed = $this->processPendingTasks($output);

            if (!$this->daemon) {
                // 非守护模式：如果没有任务，提前退出
                if ($processed === 0) {
                    $output->writeln('无待处理任务，退出');
                    break;
                }
                // 检查单次运行时间
                if (time() - $this->startTime >= $this->maxRuntime) {
                    $output->writeln("单次运行已达{$this->maxRuntime}秒上限，退出");
                    break;
                }
            }

            // 守护模式或无任务时休眠
            if ($processed === 0 || $this->daemon) {
                sleep($this->scanInterval);
            }
        } while ($this->daemon || (time() - $this->startTime < $this->maxRuntime));

        $output->writeln('[' . date('Y-m-d H:i:s') . '] image:poll 结束');
        return 0;
    }

    /**
     * 处理所有待处理的任务
     */
    protected function processPendingTasks(Output $output): int
    {
        $tasks = ImageTask::getPendingTasks(20);
        if (empty($tasks)) {
            return 0;
        }

        $count = 0;
        foreach ($tasks as $task) {
            // 检查运行时间
            if (time() - $this->startTime >= $this->maxRuntime) {
                $output->writeln('运行时间即将超限，停止处理新任务');
                break;
            }

            $this->processSingleTask($task, $output);
            $count++;
        }

        return $count;
    }

    /**
     * 处理单个任务
     */
    protected function processSingleTask(array $task, Output $output): void
    {
        $taskId = $task['task_id'];
        $id = (int) $task['id'];
        $provider = $task['provider'];
        $pollUrl = $task['poll_url'];
        $attempts = (int) $task['attempts'];
        $maxAttempts = (int) $task['max_attempts'];
        $apiKey = '';

        // 获取对应Provider的API Key
        if ($provider === 'flux') {
            $apiKey = config('ai.image.providers.flux.api_key', '');
        } elseif ($provider === 'dalle') {
            $apiKey = config('ai.image.providers.dalle.api_key', '');
        }

        // 首次处理，标记为processing
        if ($attempts === 0) {
            ImageTask::markProcessing($id);
            $output->writeln("[Task #{$id}] 开始轮询 provider={$provider} task_id={$taskId}");
        }

        // 递增尝试次数
        ImageTask::incrementAttempts($id);
        $attempts++;

        $output->writeln("[Task #{$id}] 第{$attempts}/{$maxAttempts}次轮询...");

        try {
            if (empty($pollUrl)) {
                throw new \Exception('轮询URL为空');
            }

            $response = $this->httpClient->get($pollUrl, [
                'headers' => [
                    'X-Key' => $apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $status = $body['status'] ?? '';

            // FLUX API状态: Pending -> Processing -> Ready | Failed
            if ($status === 'Ready' && !empty($body['result']['sample'])) {
                $imageUrl = $body['result']['sample'];
                $output->writeln("[Task #{$id}] 配图完成 URL={$imageUrl}");

                // M17: 下载到本地
                $localPath = $this->downloadImage($imageUrl, $task);

                ImageTask::markCompleted($id, $body['result'], $localPath);

                // M17: 回写Content封面图为本地路径
                if ($localPath && !empty($task['related_type']) && !empty($task['related_id'])) {
                    $this->updateRelatedCover($task['related_type'], (int) $task['related_id'], $localPath, $output);
                }

                return;
            }

            if ($status === 'Failed') {
                throw new \Exception('Provider返回失败: ' . ($body['error'] ?? '未知错误'));
            }

            // 仍在处理中，未超时则等待
            if ($attempts < $maxAttempts) {
                sleep(3);
            } else {
                throw new \Exception("轮询超时（{$maxAttempts}次未获取结果）");
            }

        } catch (\Exception $e) {
            Log::error("[ImagePollCommand] Task #{$id} 轮询失败: " . $e->getMessage());
            $output->writeln("[Task #{$id}] 错误: " . $e->getMessage());

            // 判断是否可重试
            $shouldRetry = $this->shouldRetry($task);
            ImageTask::markFailed($id, $e->getMessage(), $shouldRetry);

            if ($shouldRetry) {
                $retryDelay = $this->getRetryDelay((int) $task['retry_count']);
                $output->writeln("[Task #{$id}] 将在{$retryDelay}秒后重试");
            }
        }
    }

    /**
     * 下载图片到本地 (M17 AI配图URL本地化)
     */
    protected function downloadImage(string $imageUrl, array $task): string
    {
        try {
            $tmpFile = tempnam(sys_get_temp_dir(), 'img_');
            $this->httpClient->get($imageUrl, [
                'sink' => $tmpFile,
                'timeout' => 30,
            ]);

            $ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'png';
            $savePath = 'uploads/ai_image/' . date('Ymd') . '/' . uniqid('ai_') . '.' . $ext;

            $result = StorageService::upload($tmpFile, $savePath);
            @unlink($tmpFile);

            if (!empty($result['path'])) {
                Log::info("[ImagePollCommand] 图片已本地化: {$result['path']}");
                return $result['path'];
            }
        } catch (\Throwable $e) {
            Log::error('[ImagePollCommand] 图片下载失败: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * 判断任务是否应重试
     */
    protected function shouldRetry(array $task): bool
    {
        $retryCount = (int) ($task['retry_count'] ?? 0);
        if ($retryCount >= 3) {
            return false;
        }

        // 只有网络/超时类错误才重试，业务错误不重试
        $errorMsg = strtolower($task['error_msg'] ?? '');
        $retryableErrors = ['timeout', 'connection', 'could not resolve', 'http', 'empty'];
        foreach ($retryableErrors as $keyword) {
            if (str_contains($errorMsg, $keyword)) {
                return true;
            }
        }

        // 默认重试（首次失败时）
        return $retryCount < 2;
    }

    /**
     * 获取重试延迟（递增: 30s/60s/120s）
     */
    protected function getRetryDelay(int $retryCount): int
    {
        $delays = [30, 60, 120];
        return $delays[$retryCount] ?? 120;
    }

    /**
     * 回写关联记录的封面图路径 (M17 AI配图URL本地化)
     */
    protected function updateRelatedCover(string $relatedType, int $relatedId, string $localPath, Output $output): void
    {
        try {
            if ($relatedType === 'content') {
                \think\facade\Db::name('content')
                    ->where('id', $relatedId)
                    ->update(['cover' => '/' . $localPath, 'update_time' => time()]);
                $output->writeln("[M17] Content#{$relatedId} 封面图已更新为本地路径: {$localPath}");
                Log::info("[ImagePollCommand] Content#{$relatedId} cover updated to: {$localPath}");
            }
        } catch (\Throwable $e) {
            Log::error("[ImagePollCommand] 回写封面图失败: " . $e->getMessage());
            $output->writeln("[M17] 回写封面图失败: " . $e->getMessage());
        }
    }
}

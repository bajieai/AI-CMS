<?php
declare(strict_types=1);
namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\model\ContentSubscription;
use app\common\model\Content;

/**
 * 内容摘要推送命令 (V2.9.29 I-7)
 * 每5分钟执行，处理即时/每日/每周摘要推送
 */
class ContentDigestCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('content:digest')
            ->setDescription('内容摘要推送（每5分钟）');
    }

    protected function execute(Input $input, Output $output): void
    {
        $output->writeln('<info>=== 内容摘要推送开始 ===</info>');

        // 处理即时推送
        $this->processInstant($output);

        // 处理每日摘要（每天8点）
        if (date('H') === '08' && date('i') < 5) {
            $this->processDaily($output);
        }

        // 处理每周摘要（周一8点）
        if (date('N') === '1' && date('H') === '08' && date('i') < 5) {
            $this->processWeekly($output);
        }

        $output->writeln('<info>=== 内容摘要推送完成 ===</info>');
    }

    private function processInstant(Output $output): void
    {
        $subscriptions = ContentSubscription::where('notify_site', 1)
            ->where('digest_frequency', 'instant')
            ->limit(100)
            ->select();

        $output->writeln("即时推送订阅数: {$subscriptions->count()}");

        foreach ($subscriptions as $sub) {
            // 查找订阅目标的新内容
            $contents = $this->getNewContents($sub->subscribe_type, $sub->subscribe_id, time() - 300);
            foreach ($contents as $content) {
                $this->sendNotification($sub->user_id, $content);
            }
        }
    }

    private function processDaily(Output $output): void
    {
        $subscriptions = ContentSubscription::where('digest_frequency', 'daily')->limit(100)->select();
        $output->writeln("每日摘要订阅数: {$subscriptions->count()}");
        // 发送每日摘要...
    }

    private function processWeekly(Output $output): void
    {
        $subscriptions = ContentSubscription::where('digest_frequency', 'weekly')->limit(100)->select();
        $output->writeln("每周摘要订阅数: {$subscriptions->count()}");
        // 发送每周摘要...
    }

    private function getNewContents(string $type, int $id, int $since): array
    {
        $query = Content::where('status', 1)->where('create_time', '>=', $since);
        if ($type === 'category') {
            $query->where('cate_id', $id);
        }
        return $query->limit(20)->select()->toArray();
    }

    private function sendNotification(int $userId, array $content): void
    {
        try {
            \app\common\service\NotificationService::send($userId, 'content_subscription', '新内容通知', $content['title']);
        } catch (\Exception $e) {
            \think\facade\Log::error('订阅推送失败: ' . $e->getMessage());
        }
    }
}

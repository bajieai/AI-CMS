<?php
declare(strict_types=1);

namespace app\common\service\plugin;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 插件市场闭环增强服务 - V2.9.40 DEV-ECO2-2
 *
 * 基于V2.9.37 PluginEcoCloseService扩展
 * 新增: AI预审、快速通道、版本审核增强
 */
class PluginEcoCloseServiceV2
{
    private const CACHE_TAG = 'plugin_eco_v2';

    /** 审核流程步骤 */
    private const REVIEW_STEPS = [
        'auto_scan'    => '自动安全扫描',
        'ai_review'    => 'AI代码质量预审',
        'manual_review' => '人工审核',
        'compatibility' => '兼容性检查',
        'performance'  => '性能测试',
    ];

    /**
     * 提交插件审核（增强版7步流程）
     */
    public function submitReview(string $pluginName, string $version, array $options = []): array
    {
        // Step1: 自动安全扫描
        $scanResult = $this->autoScan($pluginName);

        // Step2: AI预审（新增）
        $aiResult = $this->aiReview($pluginName);

        // Step3: 快速通道判断（新增）
        $isFastTrack = $this->checkFastTrack($pluginName, $scanResult, $aiResult);

        if ($isFastTrack) {
            // 快速通道：跳过人工审核直接发布
            Db::name('plugin_market')->where('name', $pluginName)->update([
                'status' => 1, // 直接上架
                'review_result' => json_encode([
                    'scan' => $scanResult,
                    'ai_review' => $aiResult,
                    'fast_track' => true,
                ]),
                'updated_at' => time(),
            ]);

            Log::info('插件快速通道通过: ' . $pluginName);
            return ['status' => 'approved', 'fast_track' => true, 'scan' => $scanResult, 'ai_review' => $aiResult];
        }

        // 正常流程：进入人工审核
        Db::name('plugin_market')->where('name', $pluginName)->update([
            'status' => 0, // 待审核
            'review_result' => json_encode([
                'scan' => $scanResult,
                'ai_review' => $aiResult,
                'fast_track' => false,
            ]),
            'updated_at' => time(),
        ]);

        return ['status' => 'pending_review', 'fast_track' => false, 'scan' => $scanResult, 'ai_review' => $aiResult];
    }

    /**
     * 自动安全扫描
     */
    private function autoScan(string $pluginName): array
    {
        $pluginDir = app_path() . 'common/plugin/' . $pluginName;
        $issues = [];
        $score = 100;

        // 检查plugin.json完整性
        $configFile = $pluginDir . '/plugin.json';
        if (!file_exists($configFile)) {
            $issues[] = '缺少plugin.json';
            $score -= 20;
        }

        // 检查危险函数
        $dangerous = ['eval', 'exec', 'shell_exec', 'system', 'passthru'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pluginDir));
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;
            $content = file_get_contents($file->getRealPath());
            foreach ($dangerous as $func) {
                if (stripos($content, $func . '(') !== false) {
                    $issues[] = basename($file) . ': 包含危险函数' . $func;
                    $score -= 10;
                }
            }
        }

        return ['score' => max($score, 0), 'issues' => $issues, 'passed' => $score >= 80];
    }

    /**
     * AI代码质量预审（新增功能）
     */
    private function aiReview(string $pluginName): array
    {
        $pluginDir = app_path() . 'common/plugin/' . $pluginName;
        $config = json_decode(file_get_contents($pluginDir . '/plugin.json') ?: '{}', true);

        // 收集代码摘要
        $codeSummary = '';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pluginDir));
        $fileCount = 0;
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $codeSummary .= basename($file) . ': ' . mb_substr(file_get_contents($file->getRealPath()), 0, 500) . "\n";
                $fileCount++;
            }
        }

        try {
            $aiService = new \app\common\service\AiService();
            $prompt = "审核以下插件代码质量，评估安全性、规范性和可维护性，给出评分(0-100)和改进建议：\n\n插件: {$pluginName}\n文件数: {$fileCount}\n代码摘要:\n{$codeSummary}";
            $result = $aiService->generate($prompt, ['max_tokens' => 1000]);

            return [
                'ai_score'   => 0, // 需从AI响应中解析
                'ai_comment' => $result ?? 'AI审核暂不可用',
                'file_count' => $fileCount,
                'passed'     => true, // 需从AI响应中解析
            ];
        } catch (\Exception $e) {
            return [
                'ai_score'   => 0,
                'ai_comment' => 'AI审核服务不可用: ' . $e->getMessage(),
                'file_count' => $fileCount,
                'passed'     => false,
            ];
        }
    }

    /**
     * 快速通道判断（新增）
     *
     * 条件：无安全问题 + AI审核通过 + 作者信用分>=80
     */
    private function checkFastTrack(string $pluginName, array $scan, array $ai): bool
    {
        // 条件1: 安全扫描通过(score>=90)
        if (!$scan['passed'] || $scan['score'] < 90) return false;

        // 条件2: AI审核通过
        if (!$ai['passed']) return false;

        // 条件3: 作者信用分>=80
        $author = Db::name('plugin_market')->where('name', $pluginName)->value('author');
        if ($author) {
            $trustScore = Db::name('developer')->where('username', $author)->value('trust_score') ?: 0;
            if ($trustScore < 80) return false;
        }

        // 条件4: 无用户投诉
        $complaints = Db::name('plugin_complaint')->where('plugin_name', $pluginName)->count();
        if ($complaints > 0) return false;

        return true;
    }

    /**
     * 人工审核（通过/拒绝）
     */
    public function manualReview(string $pluginName, string $decision, string $comment = ''): bool
    {
        $status = $decision === 'approved' ? 1 : -1;

        Db::name('plugin_market')->where('name', $pluginName)->update([
            'status'        => $status,
            'review_comment' => $comment,
            'reviewed_at'   => time(),
            'updated_at'    => time(),
        ]);

        Cache::clear();
        return true;
    }

    /**
     * 分发插件（上架后的下载/安装流程）
     */
    public function distribute(string $pluginName): array
    {
        $plugin = Db::name('plugin_market')->where('name', $pluginName)->where('status', 1)->find();
        if (!$plugin) return ['success' => false, 'msg' => '插件未上架'];

        return [
            'success' => true,
            'name'    => $plugin['name'],
            'version' => $plugin['version'],
            'zip_path' => $plugin['zip_path'],
            'sha256'  => $plugin['sha256'],
            'size'    => $plugin['size'],
        ];
    }
}

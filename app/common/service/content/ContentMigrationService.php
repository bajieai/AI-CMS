<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\content;

use app\common\model\Cate;
use app\common\model\Content;
use think\facade\Db;
use think\facade\Log;

/**
 * 内容模型迁移服务 (V2.9.29 C-6)
 * 
 * 将旧栏目迁移到内容模型体系，生成迁移报告
 */
class ContentMigrationService
{
    private ContentModelService $modelService;

    public function __construct()
    {
        $this->modelService = new ContentModelService();
    }

    /**
     * 执行迁移
     * 
     * @return array 迁移报告 [total, migrated, errors]
     */
    public function migrate(): array
    {
        $report = [
            'total'     => 0,
            'migrated'  => 0,
            'errors'    => [],
            'details'   => [],
        ];

        // 1. 迁移栏目：为无 content_model_code 的旧栏目设置默认值
        $cates = Cate::where('content_model_code', '')
            ->whereOr('content_model_code', null)
            ->select();

        $report['total'] = $cates->count();

        foreach ($cates as $cate) {
            try {
                // 根据 cate.type 推断默认模型code
                $modelCode = $this->inferModelCodeByType($cate->type);

                $cate->content_model_code = $modelCode;
                $cate->save();

                $report['migrated']++;
                $report['details'][] = [
                    'cate_id'   => $cate->id,
                    'cate_name' => $cate->name,
                    'model_code' => $modelCode,
                    'status'    => 'success',
                ];
            } catch (\Exception $e) {
                $report['errors'][] = [
                    'cate_id'   => $cate->id,
                    'cate_name' => $cate->name,
                    'error'     => $e->getMessage(),
                ];
                Log::error('内容模型迁移失败: cate_id=' . $cate->id . ', error=' . $e->getMessage());
            }
        }

        // 2. 清除缓存
        $this->modelService->clearCache();

        return $report;
    }

    /**
     * 根据栏目type推断模型code
     */
    private function inferModelCodeByType(int $type): string
    {
        $map = [
            1 => 'product',
            2 => 'case',
            3 => 'article',
            4 => 'download',
            5 => 'article',
            6 => 'article',
        ];
        return $map[$type] ?? 'article';
    }

    /**
     * 检查模板一致性
     * 
     * @return array 检查报告
     */
    public function checkTemplateConsistency(): array
    {
        $report = [
            'total_cates'   => 0,
            'consistent'    => 0,
            'inconsistent'  => [],
        ];

        $cates = Cate::select();
        $report['total_cates'] = $cates->count();

        foreach ($cates as $cate) {
            $listTemplate = $this->modelService->resolveListTemplate($cate->id);
            $detailTemplate = $this->modelService->resolveDetailTemplate($cate->id);

            // 检查模板文件是否存在（仅检查非系统默认模板）
            $viewPath = config('view.view_path', root_path('template/home/default/'));
            $listFile = $viewPath . str_replace('.', '/', $listTemplate) . '.html';
            $detailFile = $viewPath . str_replace('.', '/', $detailTemplate) . '.html';

            if (is_file($listFile) && is_file($detailFile)) {
                $report['consistent']++;
            } else {
                $report['inconsistent'][] = [
                    'cate_id'         => $cate->id,
                    'cate_name'       => $cate->name,
                    'list_template'   => $listTemplate,
                    'detail_template' => $detailTemplate,
                    'list_exists'     => is_file($listFile),
                    'detail_exists'   => is_file($detailFile),
                ];
            }
        }

        return $report;
    }

    /**
     * 生成迁移报告摘要
     */
    public function generateReportSummary(array $migrationReport, array $consistencyReport): string
    {
        $summary = "=== V2.9.29 内容模型迁移报告 ===\n\n";
        $summary .= "1. 栏目迁移\n";
        $summary .= "   总栏目数: {$migrationReport['total']}\n";
        $summary .= "   成功迁移: {$migrationReport['migrated']}\n";
        $summary .= "   失败数量: " . count($migrationReport['errors']) . "\n\n";

        $summary .= "2. 模板一致性检查\n";
        $summary .= "   总栏目数: {$consistencyReport['total_cates']}\n";
        $summary .= "   一致: {$consistencyReport['consistent']}\n";
        $summary .= "   不一致: " . count($consistencyReport['inconsistent']) . "\n\n";

        if (!empty($migrationReport['errors'])) {
            $summary .= "3. 迁移错误详情\n";
            foreach ($migrationReport['errors'] as $error) {
                $summary .= "   栏目ID {$error['cate_id']} ({$error['cate_name']}): {$error['error']}\n";
            }
        }

        if (!empty($consistencyReport['inconsistent'])) {
            $summary .= "\n4. 模板不一致详情\n";
            foreach ($consistencyReport['inconsistent'] as $item) {
                $summary .= "   栏目ID {$item['cate_id']} ({$item['cate_name']}): list={$item['list_template']}" . ($item['list_exists'] ? '' : '(缺失)') . " detail={$item['detail_template']}" . ($item['detail_exists'] ? '' : '(缺失)') . "\n";
            }
        }

        return $summary;
    }
}

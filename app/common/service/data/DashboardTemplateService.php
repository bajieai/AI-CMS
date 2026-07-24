<?php
declare(strict_types=1);

namespace app\common\service\data;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 数据大屏模板服务 - V2.9.40 DATA-DEEP2-1
 *
 * 预置模板管理：行业模板、用户自定义模板、模板克隆
 * 核心CRUD在 DashboardInteractionService 中实现
 * 本服务补充预置模板+行业分类
 */
class DashboardTemplateService
{
    private const CACHE_TAG = 'dashboard_template';

    /** 预置行业模板 */
    private const PRESET_TEMPLATES = [
        'ecommerce'  => ['name' => '电商运营大屏', 'modules' => ['实时访客', '销售统计', '商品排行', '地区分布']],
        'content'    => ['name' => '内容运营大屏', 'modules' => ['内容总览', '用户分析', 'AI能力', '质量监控']],
        'education'  => ['name' => '教育数据大屏', 'modules' => ['学员统计', '课程热度', '学习时长', '完成率']],
        'finance'    => ['name' => '财务看板大屏', 'modules' => ['收入趋势', '支出分析', '利润率', '应收账款']],
        'general'    => ['name' => '通用数据大屏', 'modules' => ['核心指标', '趋势图', '排行榜', '分布图']],
    ];

    /**
     * 初始化预置模板（首次安装时调用）
     */
    public function initPresetTemplates(): int
    {
        $count = 0;
        foreach (self::PRESET_TEMPLATES as $type => $tpl) {
            $exists = Db::name('data_dashboard_template')->where('type', 'preset_' . $type)->find();
            if (!$exists) {
                Db::name('data_dashboard_template')->insert([
                    'name'           => $tpl['name'],
                    'type'           => 'preset_' . $type,
                    'description'    => '预置行业模板 - ' . $tpl['name'],
                    'layout_config'  => json_encode(['type' => 'grid', 'columns' => 4]),
                    'module_config'  => json_encode(array_map(fn($m) => ['name' => $m], $tpl['modules'])),
                    'is_public'      => 1,
                    'is_preset'      => 1,
                    'use_count'      => 0,
                    'created_at'     => time(),
                    'updated_at'     => time(),
                ]);
                $count++;
            }
        }
        Cache::clear();
        return $count;
    }

    /**
     * 获取预置模板列表
     */
    public function getPresetTemplates(): array
    {
        return Cache::remember('preset_templates', function () {
            return Db::name('data_dashboard_template')
                ->where('is_preset', 1)
                ->order('use_count', 'desc')
                ->select()
                ->toArray();
        }, 3600);
    }

    /**
     * 克隆模板（从已有大屏克隆为新模板）
     */
    public function cloneTemplate(int $sourceId, string $newName): int
    {
        $source = Db::name('data_dashboard_template')->find($sourceId);
        if (!$source) return 0;

        $id = Db::name('data_dashboard_template')->insertGetId([
            'name'           => $newName,
            'type'           => 'custom',
            'description'    => '克隆自: ' . $source['name'],
            'layout_config'  => $source['layout_config'],
            'module_config'  => $source['module_config'],
            'is_public'      => 1,
            'is_preset'      => 0,
            'use_count'      => 0,
            'created_at'     => time(),
            'updated_at'     => time(),
        ]);

        return (int) $id;
    }
}

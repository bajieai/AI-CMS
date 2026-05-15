<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Log;

/**
 * 主题定制数据模型 - V2.9.7 Phase 1
 *
 * 存储每个主题的定制覆盖数据（CSS变量值）
 * 每个主题可以有多个变体，但只有一个激活
 */
class ThemeCustomization extends Model
{
    protected $name = 'theme_customization';

    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $field = [
        'id',
        'theme_id',
        'variant_name',
        'custom_data',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $type = [
        'custom_data' => 'json',
        'is_active'   => 'integer',
    ];

    /**
     * CSS变量白名单（仅允许覆盖这些变量，防XSS）
     */
    public const CSS_VAR_WHITELIST = [
        '--primary', '--secondary', '--accent',
        '--bg', '--bg-secondary',
        '--text', '--text-secondary', '--border',
        '--radius', '--shadow',
        '--font-heading', '--font-body',
        '--sidebar-pos', '--content-width', '--header-style',
        '--logo-url', '--logo-max-height',
        '--btn-primary-bg', '--btn-primary-hover',
    ];

    /**
     * 字体预设组合（6组中文字体）
     */
    public const FONT_PRESETS = [
        'noto-sans'    => ['heading' => "'Noto Sans SC', sans-serif", 'body' => "'Noto Sans SC', sans-serif", 'label' => '思源黑体'],
        'noto-serif'   => ['heading' => "'Noto Serif SC', serif", 'body' => "'Noto Sans SC', sans-serif", 'label' => '思源宋体(标题)'],
        'lxgw-wenkai'  => ['heading' => "'LXGW WenKai', cursive", 'body' => "'LXGW WenKai', cursive", 'label' => '霞鹜文楷'],
        'ma-shan'      => ['heading' => "'Ma Shan Zheng', cursive", 'body' => "'Noto Sans SC', sans-serif", 'label' => '马善政毛笔(标题)'],
        'system'       => ['heading' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif", 'body' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif", 'label' => '系统默认'],
        'inter-cn'     => ['heading' => "'Inter', 'Noto Sans SC', sans-serif", 'body' => "'Inter', 'Noto Sans SC', sans-serif", 'label' => 'Inter+思源'],
    ];

    /**
     * 布局预设选项
     */
    public const LAYOUT_PRESETS = [
        'sidebar_pos'   => ['left' => '左侧', 'right' => '右侧', 'none' => '无侧栏'],
        'content_width' => ['1200px' => '标准', '960px' => '窄屏', '100%' => '全宽'],
        'header_style'  => ['full' => '完整', 'minimal' => '简洁'],
    ];

    /**
     * V2.9.8 C-1/B-1: 系统级12套配色预设（5→12套扩展）
     */
    public const SYSTEM_PRESETS = [
        // === 第一轮5套（保持不变） ===
        [
            'key' => 'vibrant',
            'name' => '活力橙',
            'description' => '充满活力的暖橙色系',
            'preview_color' => '#F97316',
            'preview_gradient' => 'linear-gradient(135deg, #F97316, #FCD34D)',
            'css_vars' => [
                '--primary' => '#F97316', '--primary-hover' => '#EA580C',
                '--accent' => '#FCD34D', '--bg' => '#FFF7ED',
                '--bg-secondary' => '#FFEDD5', '--text' => '#431407',
                '--text-secondary' => '#9A3412', '--border' => '#FED7AA',
                '--shadow' => '0 2px 8px rgba(249,115,22,0.15)',
            ],
        ],
        [
            'key' => 'calm',
            'name' => '沉稳黑',
            'description' => '高端黑金风格',
            'preview_color' => '#1F2937',
            'preview_gradient' => 'linear-gradient(135deg, #1F2937, #374151)',
            'css_vars' => [
                '--primary' => '#1F2937', '--primary-hover' => '#374151',
                '--accent' => '#F59E0B', '--bg' => '#111827',
                '--bg-secondary' => '#1F2937', '--text' => '#F9FAFB',
                '--text-secondary' => '#9CA3AF', '--border' => '#374151',
                '--shadow' => '0 2px 16px rgba(0,0,0,0.3)',
            ],
        ],
        [
            'key' => 'fresh',
            'name' => '清新绿',
            'description' => '自然的绿色系',
            'preview_color' => '#10B981',
            'preview_gradient' => 'linear-gradient(135deg, #10B981, #6EE7B7)',
            'css_vars' => [
                '--primary' => '#10B981', '--primary-hover' => '#059669',
                '--accent' => '#6EE7B7', '--bg' => '#ECFDF5',
                '--bg-secondary' => '#D1FAE5', '--text' => '#064E3B',
                '--text-secondary' => '#047857', '--border' => '#A7F3D0',
                '--shadow' => '0 2px 8px rgba(16,185,129,0.15)',
            ],
        ],
        [
            'key' => 'warm',
            'name' => '暖木棕',
            'description' => '温暖的木质调',
            'preview_color' => '#8B5CF6',
            'preview_gradient' => 'linear-gradient(135deg, #8B5CF6, #C4B5FD)',
            'css_vars' => [
                '--primary' => '#8B5CF6', '--primary-hover' => '#7C3AED',
                '--accent' => '#C4B5FD', '--bg' => '#F5F3FF',
                '--bg-secondary' => '#EDE9FE', '--text' => '#1E1B4B',
                '--text-secondary' => '#5B21B6', '--border' => '#DDD6FE',
                '--shadow' => '0 2px 8px rgba(139,92,246,0.15)',
            ],
        ],
        [
            'key' => 'cool',
            'name' => '冷静蓝',
            'description' => '专业的蓝色系',
            'preview_color' => '#3B82F6',
            'preview_gradient' => 'linear-gradient(135deg, #3B82F6, #93C5FD)',
            'css_vars' => [
                '--primary' => '#3B82F6', '--primary-hover' => '#2563EB',
                '--accent' => '#93C5FD', '--bg' => '#EFF6FF',
                '--bg-secondary' => '#DBEAFE', '--text' => '#1E3A5F',
                '--text-secondary' => '#1D4ED8', '--border' => '#BFDBFE',
                '--shadow' => '0 2px 8px rgba(59,130,246,0.15)',
            ],
        ],
        // === V2.9.8 B-1: 第二轮新增7套 ===
        [
            'key' => 'tech_purple',
            'name' => '科技紫',
            'description' => '科技、SaaS、前沿风格',
            'preview_color' => '#7C3AED',
            'preview_gradient' => 'linear-gradient(135deg, #7C3AED, #A78BFA)',
            'css_vars' => [
                '--primary' => '#7C3AED', '--primary-hover' => '#5B21B6',
                '--accent' => '#A78BFA', '--bg' => '#FAFAFA',
                '--bg-secondary' => '#F5F3FF', '--text' => '#1E1B4B',
                '--text-secondary' => '#6D28D9', '--border' => '#DDD6FE',
                '--shadow' => '0 2px 8px rgba(124,58,237,0.12)',
            ],
        ],
        [
            'key' => 'rose_gold',
            'name' => '玫瑰金',
            'description' => '时尚、美妆、女性风格',
            'preview_color' => '#E11D48',
            'preview_gradient' => 'linear-gradient(135deg, #E11D48, #FDA4AF)',
            'css_vars' => [
                '--primary' => '#E11D48', '--primary-hover' => '#BE123C',
                '--accent' => '#FDA4AF', '--bg' => '#FFF1F2',
                '--bg-secondary' => '#FFE4E6', '--text' => '#4C0519',
                '--text-secondary' => '#9F1239', '--border' => '#FECDD3',
                '--shadow' => '0 2px 8px rgba(225,29,72,0.1)',
            ],
        ],
        [
            'key' => 'nature_green',
            'name' => '自然绿',
            'description' => '环保、健康、户外风格',
            'preview_color' => '#059669',
            'preview_gradient' => 'linear-gradient(135deg, #059669, #6EE7B7)',
            'css_vars' => [
                '--primary' => '#059669', '--primary-hover' => '#047857',
                '--accent' => '#6EE7B7', '--bg' => '#F0FDF4',
                '--bg-secondary' => '#DCFCE7', '--text' => '#022C22',
                '--text-secondary' => '#065F46', '--border' => '#BBF7D0',
                '--shadow' => '0 2px 6px rgba(5,150,105,0.1)',
            ],
        ],
        [
            'key' => 'deep_blue',
            'name' => '深海蓝',
            'description' => '金融、教育、专业风格',
            'preview_color' => '#1D4ED8',
            'preview_gradient' => 'linear-gradient(135deg, #1D4ED8, #60A5FA)',
            'css_vars' => [
                '--primary' => '#1D4ED8', '--primary-hover' => '#1E3A8A',
                '--accent' => '#60A5FA', '--bg' => '#FFFFFF',
                '--bg-secondary' => '#EFF6FF', '--text' => '#1E293B',
                '--text-secondary' => '#475569', '--border' => '#BFDBFE',
                '--shadow' => '0 1px 4px rgba(29,78,216,0.08)',
            ],
        ],
        [
            'key' => 'sunset_orange',
            'name' => '日落橙',
            'description' => '餐饮、生活、活力风格',
            'preview_color' => '#EA580C',
            'preview_gradient' => 'linear-gradient(135deg, #EA580C, #FDBA74)',
            'css_vars' => [
                '--primary' => '#EA580C', '--primary-hover' => '#C2410C',
                '--accent' => '#FDBA74', '--bg' => '#FFF7ED',
                '--bg-secondary' => '#FFEDD5', '--text' => '#431407',
                '--text-secondary' => '#9A3412', '--border' => '#FED7AA',
                '--shadow' => '0 2px 8px rgba(234,88,12,0.1)',
            ],
        ],
        [
            'key' => 'midnight_black',
            'name' => '极夜黑',
            'description' => '高端、设计、极简风格',
            'preview_color' => '#111827',
            'preview_gradient' => 'linear-gradient(135deg, #111827, #374151)',
            'css_vars' => [
                '--primary' => '#111827', '--primary-hover' => '#374151',
                '--accent' => '#6B7280', '--bg' => '#FFFFFF',
                '--bg-secondary' => '#F9FAFB', '--text' => '#111827',
                '--text-secondary' => '#6B7280', '--border' => '#E5E7EB',
                '--shadow' => '0 1px 2px rgba(0,0,0,0.05)',
            ],
        ],
        [
            'key' => 'sakura_pink',
            'name' => '樱花粉',
            'description' => '社交、女性、年轻风格',
            'preview_color' => '#DB2777',
            'preview_gradient' => 'linear-gradient(135deg, #DB2777, #F9A8D4)',
            'css_vars' => [
                '--primary' => '#DB2777', '--primary-hover' => '#BE185D',
                '--accent' => '#F9A8D4', '--bg' => '#FDF2F8',
                '--bg-secondary' => '#FCE7F3', '--text' => '#4C0519',
                '--text-secondary' => '#9D174D', '--border' => '#FBCFE8',
                '--shadow' => '0 2px 8px rgba(219,39,119,0.08)',
            ],
        ],
    ];

    /**
     * 获取主题的激活定制
     *
     * @param string $themeId 主题目录名
     * @return array|null 激活的定制数据，无则null
     */
    public static function getActiveCustomization(string $themeId): ?array
    {
        $record = self::where('theme_id', $themeId)
            ->where('is_active', 1)
            ->find();

        return $record ? $record->custom_data : null;
    }

    /**
     * 保存定制数据
     *
     * @param string $themeId 主题目录名
     * @param array  $data    CSS变量覆盖数据
     * @param string $variant 变体名称
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function saveCustomization(string $themeId, array $data, string $variant = 'default'): array
    {
        // 白名单过滤
        $filtered = [];
        foreach ($data as $key => $value) {
            if (in_array($key, self::CSS_VAR_WHITELIST, true)) {
                $filtered[$key] = $value;
            }
        }

        if (empty($filtered)) {
            return ['success' => false, 'message' => '无有效定制数据'];
        }

        // 查找或创建记录
        $record = self::where('theme_id', $themeId)
            ->where('variant_name', $variant)
            ->find();

        if ($record) {
            $record->custom_data = array_merge($record->custom_data ?? [], $filtered);
            $record->save();
        } else {
            $record = self::create([
                'theme_id'     => $themeId,
                'variant_name' => $variant,
                'custom_data'  => $filtered,
                'is_active'    => 0,
            ]);
        }

        Log::info("[ThemeCustom] 保存定制: theme={$themeId}, variant={$variant}, keys=" . implode(',', array_keys($filtered)));

        return ['success' => true, 'message' => '定制已保存', 'id' => $record->id];
    }

    /**
     * 激活某个变体（同时取消同主题其他变体）
     *
     * @param string $themeId 主题目录名
     * @param string $variant 变体名称
     * @return bool
     */
    public static function activateVariant(string $themeId, string $variant = 'default'): bool
    {
        // 取消同主题所有激活
        self::where('theme_id', $themeId)->update(['is_active' => 0]);

        // 激活指定变体
        $affected = self::where('theme_id', $themeId)
            ->where('variant_name', $variant)
            ->update(['is_active' => 1]);

        Log::info("[ThemeCustom] 激活变体: theme={$themeId}, variant={$variant}, affected={$affected}");

        return $affected > 0;
    }

    /**
     * 重置为默认（删除定制数据）
     *
     * @param string $themeId 主题目录名
     * @return bool
     */
    public static function resetToDefault(string $themeId): bool
    {
        $count = self::where('theme_id', $themeId)->delete();

        Log::info("[ThemeCustom] 重置定制: theme={$themeId}, deleted={$count}");

        return $count > 0;
    }

    /**
     * 另存为新变体
     *
     * @param string $themeId  主题目录名
     * @param string $newName  新变体名
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function saveAsVariant(string $themeId, string $newName): array
    {
        // 获取当前激活的定制
        $active = self::where('theme_id', $themeId)
            ->where('is_active', 1)
            ->find();

        if (!$active) {
            return ['success' => false, 'message' => '当前无激活定制'];
        }

        // 检查变体名是否已存在
        $exists = self::where('theme_id', $themeId)
            ->where('variant_name', $newName)
            ->find();

        if ($exists) {
            return ['success' => false, 'message' => '变体名已存在'];
        }

        // 创建新变体
        self::create([
            'theme_id'     => $themeId,
            'variant_name' => $newName,
            'custom_data'  => $active->custom_data,
            'is_active'    => 0,
        ]);

        Log::info("[ThemeCustom] 另存为变体: theme={$themeId}, variant={$newName}");

        return ['success' => true, 'message' => '已另存为变体: ' . $newName];
    }

    /**
     * 生成CSS覆盖代码
     *
     * @param array $customData 定制数据
     * @return string CSS代码
     */
    public static function generateOverrideCss(array $customData): string
    {
        if (empty($customData)) {
            return '';
        }

        $lines = [':root {'];
        foreach ($customData as $var => $value) {
            if (in_array($var, self::CSS_VAR_WHITELIST, true) && !empty($value)) {
                // 特殊处理logo-url：如果是URL需要url()
                if ($var === '--logo-url' && !empty($value) && !str_starts_with($value, 'url(')) {
                    $lines[] = "    {$var}: url('{$value}');";
                } else {
                    $lines[] = "    {$var}: {$value};";
                }
            }
        }
        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * 获取主题的所有变体
     *
     * @param string $themeId
     * @return array
     */
    public static function getVariants(string $themeId): array
    {
        return self::where('theme_id', $themeId)
            ->order('is_active', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }
}

<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateStore;
use app\common\model\TemplateStoreCategory;
use app\common\model\TemplateInstall;
use app\common\service\theme\ThemeFileService;
use think\facade\Db;
use think\facade\Log;

/**
 * 模板迁移服务
 * V2.9.12: 将旧模板系统数据迁移到新模板商店架构
 */
class TemplateMigrationService
{
    /**
     * 执行全量迁移（从旧模板目录到template_store）
     *
     * @param int $memberId 管理员ID
     * @return array ['success'=>bool, 'msg'=>string, 'migrated'=>int, 'skipped'=>int, 'errors'=>array]
     */
    public static function migrateFromLegacy(int $memberId = 0): array
    {
        $migrated = 0;
        $skipped  = 0;
        $errors   = [];

        // 扫描themes目录下所有已安装模板
        $themesDir = root_path() . 'template/themes/';
        if (!is_dir($themesDir)) {
            return ['success' => false, 'msg' => '模板目录不存在', 'migrated' => 0, 'skipped' => 0, 'errors' => []];
        }

        $dirs = array_filter(glob($themesDir . '*'), 'is_dir');

        foreach ($dirs as $dir) {
            $slug = basename($dir);

            // 跳过已存在于store的模板
            if (TemplateStore::where('theme_slug', $slug)->find()) {
                $skipped++;
                continue;
            }

            try {
                $themeJson = self::readThemeJson($dir);
                if (empty($themeJson)) {
                    $skipped++;
                    continue;
                }

                Db::startTrans();

                // 创建store记录
                $store = TemplateStore::create([
                    'theme_slug'  => $slug,
                    'theme_name' => $themeJson['name'] ?? $slug,
                    'category_id' => self::guessCategory($themeJson),
                    'description' => $themeJson['description'] ?? '',
                    'author'      => $themeJson['author'] ?? '官方',
                    'version'     => $themeJson['version'] ?? '1.0.0',
                    'price'       => 0,
                    'is_free'     => 1,
                    'status'      => TemplateStore::STATUS_ONLINE,
                    'is_featured' => 0,
                    'quality_score' => 0,
                    'install_count' => self::countActiveInstalls($slug),
                    'member_id'   => $memberId,
                ]);

                // 迁移安装记录
                $activeTheme = self::getActiveThemeSlug();
                if ($activeTheme === $slug) {
                    TemplateInstall::create([
                        'member_id'    => $memberId ?: 1,
                        'store_id'     => $store->id,
                        'theme_slug'   => $slug,
                        'is_active'    => 1,
                    ]);
                }

                Db::commit();
                $migrated++;
            } catch (\Exception $e) {
                Db::rollback();
                $errors[] = "迁移{$slug}失败: " . $e->getMessage();
                Log::error("TemplateMigration: {$slug} - " . $e->getMessage());
            }
        }

        return [
            'success'  => true,
            'msg'      => "迁移完成：成功{$migrated}个，跳过{$skipped}个",
            'migrated' => $migrated,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }

    /**
     * 读取theme.json
     */
    protected static function readThemeJson(string $dir): ?array
    {
        $file = $dir . '/theme.json';
        if (!file_exists($file)) {
            return null;
        }
        $content = file_get_contents($file);
        return json_decode($content, true) ?: null;
    }

    /**
     * 根据theme.json推测分类
     */
    protected static function guessCategory(array $themeJson): int
    {
        $tags   = $themeJson['tags'] ?? ($themeJson['category'] ?? '');
        $name   = $themeJson['name'] ?? '';

        if (is_string($tags)) {
            $tags = [$tags];
        }

        $keywords = implode(' ', (array)$tags) . ' ' . $name;

        // 匹配行业分类
        $map = [
            '科技' => ['科技', '技术', 'SaaS', '软件', 'IT'],
            '教育' => ['教育', '培训', '学校', '课程', '学习'],
            '电商' => ['电商', '商城', '购物', '零售', '产品'],
            '餐饮' => ['餐饮', '美食', '饭店', '咖啡'],
            '医疗' => ['医疗', '健康', '诊所', '医院'],
            '房产' => ['房产', '地产', '建筑', '装修'],
            '企业' => ['企业', '公司', '商务', '集团'],
            '博客' => ['博客', '文章', '写作', '资讯'],
        ];

        foreach ($map as $catName => $words) {
            foreach ($words as $word) {
                if (mb_stripos($keywords, $word) !== false) {
                    $cat = TemplateStoreCategory::where('name', $catName)->find();
                    if ($cat) {
                        return $cat->id;
                    }
                }
            }
        }

        // 默认分类
        $default = TemplateStoreCategory::where('name', '企业')->find();
        return $default ? $default->id : 1;
    }

    /**
     * 获取当前激活的模板slug
     */
    protected static function getActiveThemeSlug(): string
    {
        try {
            $config = Db::name('config')->where('name', 'default_theme')->find();
            return $config ? ($config['value'] ?? 'default') : 'default';
        } catch (\Exception $e) {
            return 'default';
        }
    }

    /**
     * 统计已安装使用数
     */
    protected static function countActiveInstalls(string $slug): int
    {
        $active = self::getActiveThemeSlug();
        return ($active === $slug) ? 1 : 0;
    }

    /**
     * 验证迁移数据完整性
     *
     * @return array ['valid'=>bool, 'issues'=>array]
     */
    public static function validateMigration(): array
    {
        $issues = [];

        // 检查store记录是否有对应的模板目录
        $stores = TemplateStore::where('status', '>=', 0)->select();
        foreach ($stores as $store) {
            $dir = root_path() . 'template/themes/' . $store->theme_slug;
            if (!is_dir($dir)) {
                $issues[] = "store#{$store->id}({$store->theme_slug}) 对应的模板目录不存在";
            }
        }

        // 检查安装记录是否有对应的store
        $installs = TemplateInstall::where('id', '>', 0)->select();
        foreach ($installs as $install) {
            if (!TemplateStore::find($install->store_id)) {
                $issues[] = "install#{$install->id} 对应的store不存在(store_id={$install->store_id})";
            }
        }

        return [
            'valid'  => empty($issues),
            'issues' => $issues,
        ];
    }
}

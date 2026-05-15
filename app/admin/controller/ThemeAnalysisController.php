<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\model\ThemeLog;
use app\common\model\ThemeRate;
use app\common\model\ThemeCustomization;
use app\common\model\ThemeInfo;
use think\facade\Db;

/**
 * 主题数据分析控制器 - V2.9.7 Phase 3
 *
 * 4个统计接口：
 * - installRanking   安装量排行Top 10
 * - installTrend     近30天安装趋势
 * - customPreference 定制偏好分析
 * - scoreDistribution 评分分布
 */
class ThemeAnalysisController extends AdminBaseController
{
    /**
     * 分析页面
     * GET /admin/theme_analysis/index
     */
    public function index()
    {
        return view('theme_analysis');
    }

    /**
     * 安装量排行 Top 10
     * GET /admin/theme_analysis/installRanking
     */
    public function installRanking()
    {
        $ranking = ThemeLog::where('action', 'install')
            ->group('theme_id')
            ->field('theme_id, COUNT(*) as install_count')
            ->order('install_count', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        // 获取主题名称映射
        $themeNames = $this->getThemeNames(array_column($ranking, 'theme_id'));

        $data = array_map(function ($item) use ($themeNames) {
            $item['theme_name'] = $themeNames[$item['theme_id']] ?? ('主题#' . $item['theme_id']);
            return $item;
        }, $ranking);

        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * 近30天安装趋势
     * GET /admin/theme_analysis/installTrend
     */
    public function installTrend()
    {
        $thirtyDaysAgo = time() - 30 * 86400;

        $trend = ThemeLog::where('action', 'install')
            ->where('create_time', '>=', $thirtyDaysAgo)
            ->field([
                'FROM_UNIXTIME(create_time, "%Y-%m-%d") as date',
                'COUNT(*) as count',
            ])
            ->group('date')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        // 补全缺失日期
        $dateMap = [];
        foreach ($trend as $item) {
            $dateMap[$item['date']] = (int) $item['count'];
        }

        $result = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', time() - $i * 86400);
            $result[] = [
                'date'  => $date,
                'count' => $dateMap[$date] ?? 0,
            ];
        }

        return json(['code' => 0, 'data' => $result]);
    }

    /**
     * 定制偏好分析
     * GET /admin/theme_analysis/customPreference
     */
    public function customPreference()
    {
        $customizations = ThemeCustomization::select()->toArray();
        if (empty($customizations)) {
            return json(['code' => 0, 'data' => [
                'total_themes' => 0,
                'customized_themes' => 0,
                'customization_rate' => 0,
                'color_changes' => 0,
                'font_changes' => 0,
                'layout_changes' => 0,
                'logo_changes' => 0,
                'avg_vars_per_theme' => 0,
            ]]);
        }

        $colorVars = ['--primary', '--secondary', '--accent', '--bg', '--bg-secondary', '--text', '--text-secondary', '--border', '--btn-primary-bg', '--btn-primary-hover'];
        $fontVars = ['--font-heading', '--font-body'];
        $layoutVars = ['--sidebar-pos', '--content-width', '--header-style'];
        $logoVars = ['--logo-url', '--logo-max-height'];

        $colorChanges = 0;
        $fontChanges = 0;
        $layoutChanges = 0;
        $logoChanges = 0;
        $totalVars = 0;
        $themeIds = [];

        foreach ($customizations as $custom) {
            $data = $custom['custom_data'] ?? [];
            $themeIds[$custom['theme_id']] = true;

            foreach ($data as $var => $value) {
                if (!empty($value)) {
                    $totalVars++;
                    if (in_array($var, $colorVars)) $colorChanges++;
                    elseif (in_array($var, $fontVars)) $fontChanges++;
                    elseif (in_array($var, $layoutVars)) $layoutChanges++;
                    elseif (in_array($var, $logoVars)) $logoChanges++;
                }
            }
        }

        $customizedThemes = count($themeIds);
        $installedCount   = ThemeInfo::where('is_installed', 1)->count();
        $customizationRate = $installedCount > 0
            ? round($customizedThemes / $installedCount * 100, 1) . '%'
            : '-';

        return json(['code' => 0, 'data' => [
            'total_customizations' => count($customizations),
            'customized_themes'    => $customizedThemes,
            'customization_rate'   => $customizationRate,
            'installed_count'      => $installedCount,
            'color_changes'        => $colorChanges,
            'font_changes'         => $fontChanges,
            'layout_changes'       => $layoutChanges,
            'logo_changes'         => $logoChanges,
            'avg_vars_per_theme'   => $customizedThemes > 0 ? round($totalVars / $customizedThemes, 1) : 0,
        ]]);
    }

    /**
     * 评分分布
     * GET /admin/theme_analysis/scoreDistribution
     */
    public function scoreDistribution()
    {
        $distribution = ThemeRate::group('rating')
            ->field('rating, COUNT(*) as count, AVG(rating) as avg_rating')
            ->order('rating', 'asc')
            ->select()
            ->toArray();

        // 补全1-5星
        $result = [];
        for ($i = 1; $i <= 5; $i++) {
            $found = null;
            foreach ($distribution as $item) {
                if ((int) $item['rating'] === $i) {
                    $found = $item;
                    break;
                }
            }
            $result[] = [
                'rating' => $i,
                'count'  => $found ? (int) $found['count'] : 0,
            ];
        }

        $avgRating = ThemeRate::avg('rating');

        return json(['code' => 0, 'data' => [
            'distribution' => $result,
            'avg_rating'   => round((float) $avgRating, 1),
            'total_rates'  => ThemeRate::count(),
        ]]);
    }

    /**
     * 导出CSV
     * GET /admin/theme_analysis/exportCsv?type=ranking|trend|custom|score
     */
    public function exportCsv()
    {
        $type = $this->request->param('type', 'ranking');
        $filename = 'theme_analysis_' . $type . '_' . date('Ymd') . '.csv';

        switch ($type) {
            case 'ranking':
                $headers = ['主题ID', '主题名称', '安装次数'];
                $data = ThemeLog::where('action', 'install')
                    ->group('theme_id')
                    ->field('theme_id, COUNT(*) as install_count')
                    ->order('install_count', 'desc')
                    ->limit(50)
                    ->select()
                    ->toArray();
                $names = $this->getThemeNames(array_column($data, 'theme_id'));
                $rows = array_map(function ($item) use ($names) {
                    return [$item['theme_id'], $names[$item['theme_id']] ?? '', $item['install_count']];
                }, $data);
                break;

            case 'custom':
                $headers = ['主题ID', '变体名', '是否激活', '定制变量数', '修改时间'];
                $customs = ThemeCustomization::select()->toArray();
                $rows = array_map(function ($item) {
                    return [
                        $item['theme_id'],
                        $item['variant_name'],
                        $item['is_active'] ? '是' : '否',
                        count($item['custom_data'] ?? []),
                        $item['updated_at'],
                    ];
                }, $customs);
                break;

            default:
                return json(['code' => 1, 'msg' => '不支持的导出类型']);
        }

        // 生成CSV
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        $csv .= implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(function ($v) { return '"' . str_replace('"', '""', (string) $v) . '"'; }, $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * 获取主题名称映射
     */
    protected function getThemeNames(array $themeIds): array
    {
        if (empty($themeIds)) return [];

        $names = [];
        foreach ($themeIds as $id) {
            $themeJson = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' .
                DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'theme.json';
            if (file_exists($themeJson)) {
                $info = json_decode(file_get_contents($themeJson), true);
                $names[$id] = $info['name'] ?? $id;
            } else {
                $names[$id] = $id;
            }
        }
        return $names;
    }
}

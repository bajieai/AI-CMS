<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\middleware\ThemePreviewMiddleware;
use app\common\model\ThemeInfo;
use app\common\model\ThemeLog;
use app\common\model\ThemeRate;
use app\common\service\ThemeMarketService;
use app\common\service\theme\RemoteTemplateSource;
use app\common\service\theme\ThemeUpdateService;
use app\common\service\TemplateService;
use think\facade\Log;

/**
 * 模板市场管理后台控制器 - V3.1 Sprint 15 增强版
 *
 * 功能：透传API + 安装 + 回滚 + 切换 + 预览
 */
class ThemeMarketController extends AdminBaseController
{
    /**
     * 主题市场首页（V3.1 全新UI）
     */
    public function index(): string
    {
        // 透传模式：后端仅获取模板列表，前端JS做筛选/排序
        $result = ThemeMarketService::getMarketList('frontend');
        $categories = config('ai.theme_industry_categories', []);

        // 获取当前激活主题
        $currentTheme = TemplateService::getActiveTheme();

        $this->app->view->assign([
            'templates'      => $result['templates'],
            'source'         => $result['source'],
            'fetched_at'     => $result['fetched_at'],
            'current_theme'  => $currentTheme,
            'categories'     => $categories,
        ]);

        return $this->app->view->fetch('/theme_market_index');
    }

    /**
     * V3.1 Sprint 15: 透传API — 获取模板列表（AJAX）
     */
    public function list(): \think\Response
    {
        try {
            $result = ThemeMarketService::getMarketList('frontend');
            return $this->success('ok', $result);
        } catch (\Throwable $e) {
            return $this->error('获取失败: ' . $e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 刷新远程列表（AJAX）
     */
    public function refresh(): \think\Response
    {
        try {
            $remoteSource = new RemoteTemplateSource();
            $remoteSource->refresh();
            $result = ThemeMarketService::getMarketList('frontend');
            return $this->success('刷新成功', $result);
        } catch (\Throwable $e) {
            return $this->error('刷新失败: ' . $e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 安装主题（AJAX）
     */
    public function install(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $code = trim($this->request->post('code', ''));
        $source = trim($this->request->post('source', 'prebuilt'));
        $downloadUrl = trim($this->request->post('download_url', ''));
        $type = trim($this->request->post('type', 'frontend'));
        $userId = (int) session('user_id');

        if (empty($code)) {
            return $this->error('请提供主题标识');
        }

        try {
            $result = ThemeMarketService::installTheme($code, $type, $source, $downloadUrl, $userId);

            $this->recordLog('安装主题', "code={$code}, source={$source}");
            return $this->success($result['message'], $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 切换主题（AJAX）
     */
    public function switch(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $code = trim($this->request->post('code', ''));
        $type = trim($this->request->post('type', 'frontend'));

        if (empty($code)) {
            return $this->error('请提供主题标识');
        }

        try {
            $result = ThemeMarketService::switchTheme($code, $type);
            $this->recordLog('切换主题', "code={$code}, type={$type}");
            return $this->success('切换成功', $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 卸载主题（AJAX）
     * V2.9.9-R4增强: 支持基于code卸载（优先code，兼容id）
     */
    public function uninstall(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $code = trim($this->request->post('code', ''));
        $id = (int) $this->request->post('id', 0);

        try {
            // 优先使用code卸载
            if (!empty($code)) {
                $theme = ThemeInfo::where('code', $code)->find();
                if (!$theme) {
                    return $this->error('主题不存在');
                }
                // 防删当前主题
                $configName = $theme->type === 'frontend' ? 'frontend_theme' : 'admin_theme';
                $currentTheme = \app\common\model\Config::where('name', $configName)->value('value');
                if ($theme->code === $currentTheme) {
                    return $this->error('不能卸载正在使用的主题');
                }
                // 删除目录
                $dir = $theme->type === 'frontend'
                    ? root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme->code
                    : root_path() . 'template' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . $theme->code;
                if (is_dir($dir) && $theme->code !== 'default') {
                    self::rrmdir($dir);
                }
                $theme->delete();
                $this->recordLog('卸载主题', "code={$code}");
            } else {
                ThemeMarketService::uninstallTheme($id);
                $this->recordLog('卸载主题', "id={$id}");
            }
            return $this->success('卸载成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * V2.9.9-R4: 上传zip安装主题（AJAX）
     * ZipSlip三重防护: realpath校验 + strpos检测.. + preg_match过滤危险字符
     */
    public function uploadAndInstall(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $file = $this->request->file('file');
        if (!$file) {
            return $this->error('请上传zip文件');
        }

        $ext = strtolower($file->getOriginalExtension());
        if ($ext !== 'zip') {
            return $this->error('仅支持zip格式');
        }

        $type = trim($this->request->post('type', 'frontend'));

        // 临时解压目录
        $tempDir = runtime_path() . 'theme_upload_' . uniqid() . DIRECTORY_SEPARATOR;
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            $zipPath = $tempDir . 'upload.zip';
            $file->move($tempDir, 'upload.zip');

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception('无法打开zip文件');
            }

            // 安全校验1: 检查zip内是否有危险路径
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                // ZipSlip防护: 禁止..路径穿越、绝对路径、特殊字符
                if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                    $zip->close();
                    throw new \Exception('zip文件包含非法路径: ' . $entryName);
                }
                if (preg_match('/[<>:"|?*\x00-\x1f]/', $entryName)) {
                    $zip->close();
                    throw new \Exception('zip文件包含非法字符: ' . $entryName);
                }
            }

            $zip->extractTo($tempDir . 'extracted');
            $zip->close();

            // 查找theme.json确定主题根目录
            $extractedDir = $tempDir . 'extracted';
            $themeJsonPath = '';
            $themeRoot = '';
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractedDir, \RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($iterator as $f) {
                if ($f->getFilename() === 'theme.json') {
                    $themeJsonPath = $f->getPathname();
                    $themeRoot = $f->getPath();
                    break;
                }
            }

            if (empty($themeJsonPath)) {
                throw new \Exception('zip中未找到theme.json');
            }

            // Schema校验: 必需字段
            $meta = json_decode(file_get_contents($themeJsonPath), true);
            if (!is_array($meta)) {
                throw new \Exception('theme.json解析失败');
            }
            $required = ['name', 'version', 'author', 'type'];
            foreach ($required as $field) {
                if (empty($meta[$field])) {
                    throw new \Exception("theme.json缺少必需字段: {$field}");
                }
            }

            $code = preg_replace('/[^a-zA-Z0-9_-]/', '', $meta['code'] ?? basename($themeRoot));
            if (empty($code)) {
                $code = 'theme_' . time();
            }

            // 安全校验2: code白名单
            if (str_contains($code, '..') || preg_match('/[<>:"|?*\\\/]/', $code)) {
                throw new \Exception('主题标识包含非法字符');
            }

            $targetDir = $type === 'frontend'
                ? root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $code
                : root_path() . 'template' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . $code;

            if (is_dir($targetDir)) {
                throw new \Exception("主题 {$code} 已存在");
            }

            // 安全校验3: realpath确认最终目录在目标范围内
            $realTarget = realpath(dirname($targetDir));
            $allowedBase = realpath($type === 'frontend'
                ? root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes'
                : root_path() . 'template' . DIRECTORY_SEPARATOR . 'admin');
            if ($realTarget !== $allowedBase) {
                throw new \Exception('目录安全校验失败');
            }

            // 移动到最终位置
            rename($themeRoot, $targetDir);

            // 同步到数据库
            ThemeMarketService::scanAndSync();

            // 记录日志
            $this->recordLog('安装主题', "code={$code}, source=zip_upload");

            // 更新安装次数
            ThemeInfo::where('code', $code)->where('type', $type)->inc('install_count', 1)->update();

            return $this->success('安装成功', ['code' => $code, 'name' => $meta['name']]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        } finally {
            // 清理临时目录
            if (is_dir($tempDir)) {
                self::rrmdir($tempDir);
            }
        }
    }

    /**
     * V3.1 Sprint 15: 生成预览URL（AJAX）
     */
    public function previewUrl(): \think\Response
    {
        $code = trim($this->request->param('code', ''));
        if (empty($code)) {
            return $this->error('参数错误');
        }

        $themeDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $code;
        if (!is_dir($themeDir)) {
            return $this->error('主题目录不存在');
        }

        $hash = ThemePreviewMiddleware::generateHash($code);
        $previewUrl = url('/index/index') . '?preview=' . $hash;

        return $this->success('ok', [
            'preview_url' => $previewUrl,
            'hash'        => $hash,
            'expires_in'  => 86400,
        ]);
    }

    /**
     * V2.9.9 F-3: 本地模板列表（AJAX）
     * V2.9.9-R4: 增加Cache缓存（mtime key, TTL=300s）
     */
    public function localList(): \think\Response
    {
        try {
            $themesDir = root_path() . 'template/themes/';
            $cacheKey = 'theme_local_list';
            $cacheMtimeKey = 'theme_local_list_mtime';

            // 计算目录最新mtime作为缓存失效标识
            $currentMtime = 0;
            if (is_dir($themesDir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($themesDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getMTime() > $currentMtime) {
                        $currentMtime = $file->getMTime();
                    }
                }
            }

            // 检查缓存是否有效
            $cachedMtime = cache($cacheMtimeKey);
            if ($cachedMtime && (int)$cachedMtime === $currentMtime) {
                $cached = cache($cacheKey);
                if ($cached) {
                    return $this->success('ok', $cached);
                }
            }

            $items = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($themesDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            $seen = [];
            foreach ($iterator as $file) {
                if ($file->getFilename() !== 'theme.json') continue;
                $dir = basename($file->getPath());
                if (isset($seen[$dir])) continue;
                $seen[$dir] = true;

                $data = json_decode(file_get_contents($file->getPathname()), true);
                if (!is_array($data)) continue;

                // Schema校验
                $jsonPath = $file->getPathname();
                $schemaResult = \app\common\service\theme\ThemeSchemaService::validate($jsonPath);

                $items[] = [
                    'code'           => $dir,
                    'name'           => $data['name'] ?? $dir,
                    'version'        => $data['version'] ?? '',
                    'description'    => $data['description'] ?? '',
                    'author'         => $data['author'] ?? '',
                    'type'           => $data['type'] ?? 'unknown',
                    'category'       => $data['category'] ?? '',
                    'tags'           => $data['tags'] ?? [],
                    'preview'        => $data['preview'] ?? '',
                    'mtime'          => $file->getMTime(),
                    'schema_summary' => [
                        'status'         => $schemaResult['status'],
                        'warnings_count' => count($schemaResult['warnings'] ?? []),
                        'errors_count'   => count($schemaResult['errors'] ?? []),
                        'checked_at'     => date('c'),
                    ],
                ];
            }

            $result = ['items' => $items, 'total' => count($items)];

            // 写入缓存
            cache($cacheKey, $result, 300);
            cache($cacheMtimeKey, $currentMtime, 300);

            return $this->success('ok', $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * V2.9.9 F-3: 本地模板详情（AJAX）
     */
    public function localDetail(): \think\Response
    {
        $code = trim($this->request->param('code', ''));
        if (empty($code)) {
            return $this->error('请提供主题标识');
        }

        $path = root_path() . 'template/themes/' . $code . '/theme.json';
        if (!file_exists($path)) {
            return $this->error('主题不存在');
        }

        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            return $this->error('theme.json 解析失败');
        }

        // Schema 校验
        $schemaResult = \app\common\service\theme\ThemeSchemaService::validate($path);

        $detail = [
            'code'        => $code,
            'name'        => $data['name'] ?? $code,
            'version'     => $data['version'] ?? '',
            'description' => $data['description'] ?? '',
            'author'      => $data['author'] ?? '',
            'type'        => $data['type'] ?? 'unknown',
            'category'    => $data['category'] ?? '',
            'tags'        => $data['tags'] ?? [],
            'preview'     => $data['preview'] ?? '',
            'supports'    => $data['supports'] ?? [],
            'colors'      => $data['colors'] ?? (object)[],
            'options'     => $data['options'] ?? (object)[],
            'layouts'     => $data['layouts'] ?? (object)[],
            'assets'      => $data['assets'] ?? (object)[],
            'schema_status' => $schemaResult['status'],
            'schema_warnings' => $schemaResult['warnings'] ?? [],
            'schema_errors' => $schemaResult['errors'] ?? [],
        ];

        return $this->success('ok', $detail);
    }

    /**
     * V3.1 Sprint 15: 获取备份列表（AJAX）
     */
    public function backups(): \think\Response
    {
        $code = trim($this->request->param('code', ''));
        if (empty($code)) {
            return $this->error('参数错误');
        }

        try {
            $backups = ThemeMarketService::getBackups($code);
            return $this->success('ok', ['backups' => $backups]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 回滚主题（AJAX）
     */
    public function rollback(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $code = trim($this->request->post('code', ''));
        $backupId = trim($this->request->post('backup_id', ''));
        $type = trim($this->request->post('type', 'frontend'));

        if (empty($code) || empty($backupId)) {
            return $this->error('参数错误');
        }

        try {
            $result = ThemeMarketService::rollbackTheme($code, $backupId, $type);
            $this->recordLog('回滚主题', "code={$code}, backup={$backupId}");
            return $this->success($result['message'], $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 扫描同步（兼容旧版）
     */
    public function scan(): \think\Response
    {
        try {
            $result = ThemeMarketService::scanAndSync();
            return $this->success("扫描完成：新增{$result['added']}个，更新{$result['updated']}个", $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 检查更新（增强版）
     */
    public function checkUpdate(): \think\Response
    {
        try {
            $updates = ThemeMarketService::checkUpdates();
            return $this->success('ok', ['updates' => $updates, 'count' => count($updates)]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    // ========== V3.1 Sprint 16: 评分收藏 ==========

    /**
     * 提交评分（AJAX）
     */
    public function rate(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $themeId = (int) $this->request->post('theme_id', 0);
        $rating  = (int) $this->request->post('rating', 0);
        $comment = trim($this->request->post('comment', ''));
        $userId  = (int) session('user_id');

        if ($themeId <= 0 || $rating < 1 || $rating > 5) {
            return $this->error('参数错误');
        }

        try {
            $result = ThemeRate::rate($userId, $themeId, $rating, $comment);
            // 记录日志
            $theme = ThemeInfo::find($themeId);
            ThemeLog::record($themeId, 'rate', $userId, [
                'code'    => $theme ? $theme->code : '',
                'rating'  => $rating,
                'comment' => $comment,
            ]);
            return $this->success('评分成功', $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 切换收藏（AJAX）
     */
    public function favorite(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $themeId = (int) $this->request->post('theme_id', 0);
        $userId  = (int) session('user_id');

        if ($themeId <= 0) {
            return $this->error('参数错误');
        }

        try {
            $result = ThemeRate::toggleFavorite($userId, $themeId);
            return $this->success('操作成功', $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取主题评分统计（AJAX）
     */
    public function rateStats(): \think\Response
    {
        $themeId = (int) $this->request->param('theme_id', 0);
        if ($themeId <= 0) {
            return $this->error('参数错误');
        }

        try {
            $stats = ThemeRate::getThemeStats($themeId);
            $userId = (int) session('user_id');
            $userRate = ThemeRate::getUserRate($userId, $themeId);
            return $this->success('ok', [
                'stats'     => $stats,
                'user_rate' => $userRate,
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    // ========== V3.1 Sprint 16: 版本检测 ==========

    /**
     * 获取更新红点通知（AJAX）
     */
    public function updateBadge(): \think\Response
    {
        try {
            $service = new ThemeUpdateService();
            $badge = $service->getBadge();
            return $this->success('ok', $badge);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 批量检测更新（AJAX）
     */
    public function updateCheck(): \think\Response
    {
        try {
            $service = new ThemeUpdateService();
            $result = $service->checkAll();
            return $this->success('ok', $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    // ========== V3.1 Sprint 16: 日志查询 ==========

    /**
     * 主题操作日志页面
     */
    public function logs(): string
    {
        $page = (int) $this->request->param('page', 1);
        $action = trim($this->request->param('action', ''));
        $themeId = (int) $this->request->param('theme_id', 0);

        $filter = [];
        if ($action) $filter['action'] = $action;
        if ($themeId) $filter['theme_id'] = $themeId;

        $result = ThemeLog::getAllLogs($filter, $page, 20);
        $actionMap = ThemeLog::getActionMap();

        $this->app->view->assign([
            'list'       => $result['list'],
            'total'      => $result['total'],
            'page'       => $page,
            'action'     => $action,
            'theme_id'   => $themeId,
            'action_map' => $actionMap,
        ]);

        return $this->app->view->fetch('/theme_market_logs');
    }

    /**
     * 主题操作日志API（AJAX分页）
     */
    public function logList(): \think\Response
    {
        $page = (int) $this->request->param('page', 1);
        $action = trim($this->request->param('action', ''));
        $themeId = (int) $this->request->param('theme_id', 0);

        $filter = [];
        if ($action) $filter['action'] = $action;
        if ($themeId) $filter['theme_id'] = $themeId;

        try {
            $result = ThemeLog::getAllLogs($filter, $page, 20);
            return $this->success('ok', $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    // ========== V3.1 Sprint 16: 分类管理 ==========

    /**
     * 分类管理页面
     */
    public function categories(): string
    {
        $categories = config('ai.theme_industry_categories', []);
        $this->app->view->assign('categories', $categories);
        return $this->app->view->fetch('/theme_market_categories');
    }

    /**
     * 保存分类配置（AJAX）
     */
    public function saveCategory(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $key = trim($this->request->post('key', ''));
        $name = trim($this->request->post('name', ''));
        $color = trim($this->request->post('color', '#6c757d'));
        $icon = trim($this->request->post('icon', 'bi-folder'));
        $sort = (int) $this->request->post('sort', 0);

        if (empty($key) || empty($name)) {
            return $this->error('标识和名称不能为空');
        }

        // 读取现有配置
        $configPath = config_path() . 'ai.php';
        $categories = config('ai.theme_industry_categories', []);

        $categories[$key] = [
            'name'  => $name,
            'color' => $color,
            'icon'  => $icon,
            'sort'  => $sort,
        ];

        // 按sort排序
        uasort($categories, function ($a, $b) {
            return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
        });

        // 写回配置文件（简单字符串替换）
        try {
            if (is_file($configPath) && is_writable($configPath)) {
                $content = file_get_contents($configPath);
                $pattern = "/('theme_industry_categories'\s*=>\s*\[)[^\]]*(\],)/s";
                $export = var_export($categories, true);
                $export = str_replace(["\r\n", "\r"], "\n", $export);
                $replacement = "'theme_industry_categories' => " . $export . ",";
                $content = preg_replace($pattern, $replacement, $content);
                if ($content) {
                    file_put_contents($configPath, $content, LOCK_EX);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('保存分类配置失败: ' . $e->getMessage());
        }

        $this->recordLog('修改分类', "key={$key}, name={$name}");
        return $this->success('保存成功', ['key' => $key]);
    }

    /**
     * 删除分类（AJAX）
     */
    public function deleteCategory(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $key = trim($this->request->post('key', ''));
        if (empty($key)) {
            return $this->error('参数错误');
        }

        $categories = config('ai.theme_industry_categories', []);
        if (!isset($categories[$key])) {
            return $this->error('分类不存在');
        }

        unset($categories[$key]);

        try {
            $configPath = config_path() . 'ai.php';
            if (is_file($configPath) && is_writable($configPath)) {
                $content = file_get_contents($configPath);
                $pattern = "/('theme_industry_categories'\s*=>\s*\[)[^\]]*(\],)/s";
                $export = var_export($categories, true);
                $export = str_replace(["\r\n", "\r"], "\n", $export);
                $replacement = "'theme_industry_categories' => " . $export . ",";
                $content = preg_replace($pattern, $replacement, $content);
                if ($content) {
                    file_put_contents($configPath, $content, LOCK_EX);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('删除分类配置失败: ' . $e->getMessage());
        }

        $this->recordLog('删除分类', "key={$key}");
        return $this->success('删除成功');
    }

    // ========== V3.1 Sprint 16: 主题详情 ==========

    /**
     * 获取主题详情（AJAX，含文件信息）
     */
    public function detail(): \think\Response
    {
        $code = trim($this->request->param('code', ''));
        $type = trim($this->request->param('type', 'frontend'));

        if (empty($code)) {
            return $this->error('参数错误');
        }

        $themeDir = $type === 'frontend'
            ? root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $code
            : root_path() . 'template' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . $code;

        if (!is_dir($themeDir)) {
            return $this->error('主题目录不存在');
        }

        // 获取theme.json
        $meta = [];
        $jsonFile = $themeDir . DIRECTORY_SEPARATOR . 'theme.json';
        if (is_file($jsonFile)) {
            $meta = json_decode(file_get_contents($jsonFile), true) ?: [];
        }

        // 计算文件大小和数量
        $fileCount = 0;
        $totalSize = 0;
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($themeDir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileCount++;
                $size = $file->getSize();
                $totalSize += $size;
                $relPath = str_replace($themeDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $files[] = [
                    'path' => $relPath,
                    'size' => $size,
                    'mtime'=> $file->getMTime(),
                ];
            }
        }

        // 按大小排序取前20
        usort($files, function ($a, $b) {
            return $b['size'] <=> $a['size'];
        });

        // 获取数据库记录
        $info = ThemeInfo::where('code', $code)->where('type', $type)->find();

        // 检测更新
        $updateInfo = ['has_update' => false];
        if ($info) {
            $service = new ThemeUpdateService();
            $updateInfo = $service->checkOne($code);
        }

        return $this->success('ok', [
            'code'       => $code,
            'type'       => $type,
            'meta'       => $meta,
            'file_count' => $fileCount,
            'total_size' => $totalSize,
            'total_size_formatted' => self::formatSize($totalSize),
            'files'      => array_slice($files, 0, 20),
            'mtime'      => filemtime($themeDir),
            'mtime_formatted' => date('Y-m-d H:i:s', filemtime($themeDir)),
            'db_info'    => $info ? $info->toArray() : null,
            'update'     => $updateInfo,
        ]);
    }

    /**
     * 格式化文件大小
     */
    protected static function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * V2.9.9-R4: 递归删除目录
     */
    protected static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}

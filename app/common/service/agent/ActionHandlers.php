<?php
declare(strict_types=1);

namespace app\common\service\agent;

use think\facade\Db;
use think\facade\Cache;

/**
 * AI 助手 · 24 个动作的数据访问实现（S1-T5）
 *
 * 约定：
 *  - 每个方法返回 ['data' => array]，不接受未经校验的原始输入
 *  - 所有查询走 ThinkPHP 参数化（禁止字符串拼 SQL，S-6）
 *  - 输出字段为【对外名】（PRD/白名单名），字段映射由 ActionExecutor::applyFieldMap 统一处理
 *  - 表字段真实名以 2026-11-16 SHOW COLUMNS 实测为准
 *
 * content.status 语义（实测 install.sql）：0 草稿 / 1 待审 / 2 已发布 / -1 已删除
 */
class ActionHandlers
{
    /** 状态词 → content.status 数值 */
    private const CONTENT_STATUS_MAP = ['draft' => 0, 'pending' => 1, 'published' => 2];

    /** 密钥类配置键（S-8：只回 SET/EMPTY 布尔，绝不回值） */
    private const SECRET_KEY_PATTERN = '/(api_key|secret|token|password|passwd|private_key|access_key)/i';

    // ==================== 内容域 ====================

    /** content.article.list（Q-01/Q-02/Q-04/Q-05） */
    public function contentArticleList(array $p): array
    {
        $q = Db::name('content')->where('model_identifier', 'article')->where('status', '<>', -1);

        if (!empty($p['status'])) {
            $st = $p['status'];
            if (is_string($st) && isset(self::CONTENT_STATUS_MAP[$st])) {
                $q->where('status', self::CONTENT_STATUS_MAP[$st]);
            } elseif (is_numeric($st)) {
                $q->where('status', (int)$st);
            }
        }
        if (!empty($p['cate_id'])) {
            $q->where('cate_id', (int)$p['cate_id']);
        }
        if (!empty($p['keyword'])) {
            $q->whereLike('title', '%' . $p['keyword'] . '%');
        }

        $total = (clone $q)->count();

        if (!empty($p['count_only'])) {
            return ['data' => ['total_count' => $total, 'rows' => []]];
        }

        $limit = max(1, min(50, (int)($p['limit'] ?? 5)));
        $rows = $q->field('id,title,status,create_time,cate_id')
            ->order('create_time desc')
            ->limit($limit)
            ->select()
            ->toArray();

        return ['data' => [
            'total_count' => $total,
            'rows' => array_map(fn($r) => [
                'id' => (int)$r['id'],
                'title' => (string)$r['title'],
                'status' => (int)$r['status'],
                'create_time' => $this->fmtTime($r['create_time']),
            ], $rows),
        ]];
    }

    /** content.article.read（Q-06） */
    public function contentArticleRead(array $p): array
    {
        $row = Db::name('content')->where('id', (int)$p['id'])->find();
        if (!$row) {
            throw new \RuntimeException('内容不存在：id=' . (int)$p['id']);
        }
        $digest = mb_substr(trim(strip_tags((string)($row['content'] ?? ''))), 0, 120);

        return ['data' => [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'content_digest' => $digest,
            'status' => (int)$row['status'],
            'create_time' => $this->fmtTime($row['create_time']),
        ]];
    }

    /** content.category.tree（Q-03）：一次 GROUP BY 聚合，避免 N+1 */
    public function contentCategoryTree(array $p): array
    {
        $cates = Db::name('cate')->field('id,name,parent_id')->order('sort asc,id asc')->select()->toArray();

        $counts = [];
        try {
            $agg = Db::name('content')
                ->field('cate_id, COUNT(*) AS c')
                ->where('status', '<>', -1)
                ->group('cate_id')
                ->select()
                ->toArray();
            foreach ($agg as $a) {
                $counts[(int)$a['cate_id']] = (int)$a['c'];
            }
        } catch (\Throwable $e) {
            // 聚合失败不阻断树结构返回
        }

        return ['data' => ['rows' => array_map(fn($c) => [
            'id' => (int)$c['id'],
            'name' => (string)$c['name'],
            'parent_id' => (int)$c['parent_id'],
            'article_count' => $counts[(int)$c['id']] ?? 0,
        ], $cates)]];
    }

    // ==================== 模板域 ====================

    /** template.store.list（Q-10） */
    public function templateStoreList(array $p): array
    {
        $q = Db::name('template_store');
        if (!empty($p['keyword'])) {
            $q->whereLike('name', '%' . $p['keyword'] . '%');
        }
        if (!empty($p['paid_only'])) {
            $q->where('price', '>', 0);
        }
        $total = (clone $q)->count();
        if (!empty($p['count_only'])) {
            return ['data' => ['total_count' => $total, 'rows' => []]];
        }
        $limit = max(1, min(50, (int)($p['limit'] ?? 5)));
        $rows = $q->field('id,name,price,status,version')->order('id desc')->limit($limit)->select()->toArray();
        return ['data' => [
            'total_count' => $total,
            'rows' => array_map(fn($r) => [
                'id' => (int)$r['id'],
                'name' => (string)$r['name'],
                'price' => (float)$r['price'],
                'status' => (int)$r['status'],
            ], $rows),
        ]];
    }

    /** template.store.detail */
    public function templateStoreDetail(array $p): array
    {
        $row = Db::name('template_store')->where('id', (int)$p['id'])->find();
        if (!$row) {
            throw new \RuntimeException('模板不存在：id=' . (int)$p['id']);
        }
        return ['data' => [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'price' => (float)$row['price'],
            'status' => (int)$row['status'],
            'version' => (string)($row['version'] ?? ''),
            'description' => mb_substr((string)($row['description'] ?? ''), 0, 200),
        ]];
    }

    /** template.install.list（Q-11）：JOIN store 取 version */
    public function templateInstallList(array $p): array
    {
        $limit = max(1, min(50, (int)($p['limit'] ?? 5)));
        $rows = Db::name('template_install')->order('id desc')->limit($limit)->select()->toArray();

        $storeVersions = [];
        $storeIds = array_filter(array_unique(array_column($rows, 'store_id')));
        if ($storeIds !== []) {
            $vs = Db::name('template_store')->whereIn('id', $storeIds)->column('version', 'id');
            $storeVersions = $vs ?: [];
        }

        return ['data' => ['rows' => array_map(fn($r) => [
            'id' => (int)$r['id'],
            'template_name' => (string)($r['theme_name'] ?? ''),
            'install_time' => $this->fmtTime($r['create_time'] ?? 0),
            'version' => (string)($storeVersions[(int)($r['store_id'] ?? 0)] ?? ''),
        ], $rows)]];
    }

    /** template.license.list（Q-12/Q-14） */
    public function templateLicenseList(array $p): array
    {
        $q = Db::name('template_license');
        if (!empty($p['status'])) {
            $q->where('status', $p['status']);
        }
        $limit = max(1, min(50, (int)($p['limit'] ?? 10)));
        $rows = $q->order('id desc')->limit($limit)->select()->toArray();
        return ['data' => ['rows' => array_map(fn($r) => [
            'id' => (int)$r['id'],
            'license_code' => (string)($r['license_code'] ?? ''),
            'status' => (string)($r['status'] ?? ''),
            'expires' => $this->fmtTime($r['expires_at'] ?? 0),
        ], $rows)]];
    }

    // ==================== 主题域 ====================

    /** theme.market.list */
    public function themeMarketList(array $p): array
    {
        $limit = max(1, min(50, (int)($p['limit'] ?? 10)));
        $rows = Db::name('theme_info')->where('is_market', 1)->order('install_count desc,id desc')
            ->limit($limit)->select()->toArray();
        return ['data' => ['rows' => array_map(fn($r) => [
            'id' => (int)$r['id'],
            'name' => (string)$r['name'],
            'version' => (string)($r['version'] ?? ''),
            'avg_rating' => (float)($r['avg_rating'] ?? 0),
            'install_count' => (int)($r['install_count'] ?? 0),
        ], $rows)]];
    }

    /** theme.record.list（Q-07/Q-08）；失败集 = status in (-1,-2,4) */
    public function themeRecordList(array $p): array
    {
        $q = Db::name('ai_theme_record');
        if (!empty($p['status'])) {
            if ($p['status'] === 'failed') {
                $q->whereIn('status', [-1, -2, 4]);
            } elseif (is_numeric($p['status'])) {
                $q->where('status', (int)$p['status']);
            }
        }
        $total = (clone $q)->count();
        if (!empty($p['count_only'])) {
            return ['data' => ['total_count' => $total, 'rows' => []]];
        }
        $limit = max(1, min(50, (int)($p['limit'] ?? 5)));
        $rows = $q->field('id,theme_name,status,quality_score,error_msg,created_at')
            ->order('id desc')->limit($limit)->select()->toArray();
        return ['data' => [
            'total_count' => $total,
            'rows' => array_map(fn($r) => [
                'id' => (int)$r['id'],
                'name' => (string)($r['theme_name'] ?? ''),
                'status' => (int)($r['status'] ?? 0),
                'quality_score' => (int)($r['quality_score'] ?? 0),
                'error_msg' => (string)($r['error_msg'] ?? ''),
                'create_time' => (string)($r['created_at'] ?? ''),
            ], $rows),
        ]];
    }

    /** theme.record.detail（Q-09） */
    public function themeRecordDetail(array $p): array
    {
        $row = Db::name('ai_theme_record')->where('id', (int)$p['id'])->find();
        if (!$row) {
            throw new \RuntimeException('主题生成记录不存在：id=' . (int)$p['id']);
        }
        $validate = $row['validate_result'] ?? null;
        if (is_string($validate)) {
            $decoded = json_decode($validate, true);
            $validate = $decoded !== null ? $decoded : ['raw' => $validate];
        }
        return ['data' => [
            'id' => (int)$row['id'],
            'name' => (string)($row['theme_name'] ?? ''),
            'status' => (int)($row['status'] ?? 0),
            'quality_score' => (int)($row['quality_score'] ?? 0),
            'validate_result' => $validate ?: new \stdClass(),
            'error_msg' => (string)($row['error_msg'] ?? ''),
        ]];
    }

    /** theme.palette.list */
    public function themePaletteList(array $p): array
    {
        $rows = Db::name('ai_theme_palette')->field('id,name,colors,industry_type')->order('id asc')->select()->toArray();
        return ['data' => ['rows' => array_map(fn($r) => [
            'id' => (int)$r['id'],
            'name' => (string)$r['name'],
            'colors' => (string)($r['colors'] ?? ''),
        ], $rows)]];
    }

    /** theme.version.history（D-4 裁定 a：操作历史，非文件版本快照） */
    public function themeVersionHistory(array $p): array
    {
        $q = Db::name('theme_log');
        if (!empty($p['theme_name'])) {
            // theme_log 只有 theme_id，按主题名反查 id
            $ids = Db::name('theme_info')->whereLike('name', '%' . $p['theme_name'] . '%')->column('id');
            if ($ids === []) {
                return ['data' => ['rows' => []]];
            }
            $q->whereIn('theme_id', $ids);
        }
        $limit = max(1, min(50, (int)($p['limit'] ?? 10)));
        $rows = $q->order('id desc')->limit($limit)->select()->toArray();

        $nameMap = [];
        $themeIds = array_filter(array_unique(array_column($rows, 'theme_id')));
        if ($themeIds !== []) {
            $nameMap = Db::name('theme_info')->whereIn('id', $themeIds)->column('name', 'id') ?: [];
        }

        return ['data' => ['rows' => array_map(fn($r) => [
            'id' => (int)$r['id'],
            'theme_name' => (string)($nameMap[(int)($r['theme_id'] ?? 0)] ?? ''),
            'action' => (string)($r['action'] ?? ''),
            'detail' => mb_substr((string)($r['detail'] ?? ''), 0, 200),
            'create_time' => $this->fmtTime($r['create_time'] ?? 0),
        ], $rows)]];
    }

    // ==================== 会员域 ====================

    /** member.list（Q-12/Q-13） */
    public function memberList(array $p): array
    {
        $q = Db::name('member');
        if (!empty($p['keyword'])) {
            $q->whereLike('username', '%' . $p['keyword'] . '%');
        }
        $total = (clone $q)->count();
        if (!empty($p['count_only'])) {
            return ['data' => ['total_count' => $total, 'rows' => []]];
        }
        $limit = max(1, min(50, (int)($p['limit'] ?? 5)));
        $rows = $q->field('id,username,create_time')->order('create_time desc')->limit($limit)->select()->toArray();
        return ['data' => [
            'total_count' => $total,
            'rows' => array_map(fn($r) => [
                'id' => (int)$r['id'],
                'username' => (string)$r['username'],
                'create_time' => $this->fmtTime($r['create_time']),
            ], $rows),
        ]];
    }

    /** member.detail */
    public function memberDetail(array $p): array
    {
        $row = Db::name('member')->where('id', (int)$p['id'])->find();
        if (!$row) {
            throw new \RuntimeException('会员不存在：id=' . (int)$p['id']);
        }
        return ['data' => [
            'id' => (int)$row['id'],
            'username' => (string)$row['username'],
            'email' => (string)($row['email'] ?? ''),
            'create_time' => $this->fmtTime($row['create_time']),
        ]];
    }

    // ==================== 配置/系统域 ====================

    /** config.get（Q-16）：S-8 密钥永不回值，只回 SET/EMPTY */
    public function configGet(array $p): array
    {
        $group = (string)($p['group'] ?? 'ai');
        $rows = Db::name('config')->where('group', $group)->field('name,value,type')->select()->toArray();

        $items = [];
        foreach ($rows as $r) {
            $name = (string)$r['name'];
            if (preg_match(self::SECRET_KEY_PATTERN, $name)) {
                $items[$name] = ((string)($r['value'] ?? '') !== '') ? 'SET' : 'EMPTY';
            } else {
                $items[$name] = $r['value'];
            }
        }

        // Q-16 必返回四字段（从 ai 组常用键派生）
        if ($group === 'ai') {
            $items['api_key_set'] = ($items['api_key'] ?? 'EMPTY') === 'SET';
            $items['api_base_set'] = ((string)($items['api_base'] ?? '')) !== '' && ($items['api_base'] ?? '') !== 'EMPTY';
            $items['model_name'] = $items['model_name'] ?? ($items['model'] ?? '');
            $items['provider'] = $items['provider'] ?? '';
            // 布尔化后移除原始 SET/EMPTY 串，避免歧义
            unset($items['api_key']);
        }

        return ['data' => ['group' => $group, 'items' => $items]];
    }

    /** system.version（Q-14）：D-2 裁定 a runtime:php */
    public function systemVersion(array $p): array
    {
        $version = 'unknown';
        $readme = @file_get_contents(dirname(__DIR__, 4) . '/README.md');
        if ($readme !== false && preg_match('/V(\d+\.\d+\.\d+)/', $readme, $m)) {
            $version = 'V' . $m[1];
        }
        $buildTime = date('Y-m-d H:i:s', (int)@filemtime(dirname(__DIR__, 4) . '/composer.lock') ?: time());

        return ['data' => ['version' => $version, 'build_time' => $buildTime]];
    }

    /** system.health（Q-15）：D-2 裁定 a，runtime 探测含 mysql/disk */
    public function systemHealth(array $p): array
    {
        $mysqlVersion = 'unknown';
        $serviceStatus = [];
        try {
            $r = Db::query('SELECT VERSION() AS v');
            $mysqlVersion = (string)($r[0]['v'] ?? 'unknown');
            $serviceStatus[] = ['service' => 'mysql', 'status' => 'ok'];
        } catch (\Throwable $e) {
            $serviceStatus[] = ['service' => 'mysql', 'status' => 'error'];
        }

        $diskFree = 'unknown';
        try {
            $free = @disk_free_space(dirname(__DIR__, 4));
            if ($free !== false) {
                $diskFree = round($free / 1073741824, 2) . ' GB';
            }
        } catch (\Throwable $e) {
        }
        $serviceStatus[] = ['service' => 'php', 'status' => 'ok'];

        return ['data' => [
            'php_version' => PHP_VERSION,
            'mysql_version' => $mysqlVersion,
            'disk_free' => $diskFree,
            'service_status' => $serviceStatus,
        ]];
    }

    // ==================== 日志/统计/页面域 ====================

    /** ai.log.list（Q-17）：model_id JOIN ai_model.name */
    public function aiLogList(array $p): array
    {
        $limit = max(1, min(50, (int)($p['limit'] ?? 10)));
        $rows = Db::name('ai_log')->order('id desc')->limit($limit)->select()->toArray();

        $modelIds = array_filter(array_unique(array_column($rows, 'model_id')));
        $modelNames = [];
        if ($modelIds !== []) {
            $modelNames = Db::name('ai_model')->whereIn('id', $modelIds)->column('name', 'id') ?: [];
        }

        $statusMap = [1 => 'success', 2 => 'failed', 3 => 'degraded'];

        return ['data' => ['rows' => array_map(fn($r) => [
            'id' => (int)$r['id'],
            'model_name' => (string)($modelNames[(int)($r['model_id'] ?? 0)] ?? ''),
            'action' => (string)($r['task_type'] ?? ''),
            'result' => $statusMap[(int)($r['status'] ?? 0)] ?? 'unknown',
            'duration_ms' => (int)($r['duration_ms'] ?? 0),
            'create_time' => $this->fmtTime($r['create_time'] ?? 0),
        ], $rows)]];
    }

    /** api.log.list（Q-18） */
    public function apiLogList(array $p): array
    {
        $limit = max(1, min(50, (int)($p['limit'] ?? 10)));
        $rows = Db::name('api_log')->order('id desc')->limit($limit)->select()->toArray();
        return ['data' => ['rows' => array_map(fn($r) => [
            'id' => (int)$r['id'],
            'endpoint' => (string)($r['endpoint'] ?? ''),
            'method' => (string)($r['method'] ?? ''),
            'status' => (int)($r['status_code'] ?? 0),
            'ip' => (string)($r['ip_address'] ?? ''),
            'create_time' => $this->fmtTime($r['create_time'] ?? 0),
        ], $rows)]];
    }

    /** stat.overview（Q-19）：4 项聚合，60s 缓存（性能规则） */
    public function statOverview(array $p): array
    {
        $data = Cache::remember('agent_stat_overview', function () {
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            return [
                'article_count' => (int)Db::name('content')->where('model_identifier', 'article')->where('status', '<>', -1)->count(),
                'member_count' => (int)Db::name('member')->count(),
                'theme_count' => (int)Db::name('ai_theme_record')->count(),
                'today_generate' => (int)Db::name('ai_theme_record')->where('created_at', '>=', date('Y-m-d 00:00:00'))->count(),
            ];
        }, 60);

        return ['data' => $data];
    }

    /** report.definition.list */
    public function reportDefinitionList(array $p): array
    {
        $rows = Db::name('report_definition')->field('id,report_name,report_type')->order('id asc')->select()->toArray();
        return ['data' => ['rows' => array_map(fn($r) => [
            'id' => (int)$r['id'],
            'name' => (string)($r['report_name'] ?? ''),
            'type' => (string)($r['report_type'] ?? ''),
        ], $rows)]];
    }

    /** page.context（Q-20）：路由 → 菜单映射（D-2 裁定 a：router:i8j_menu_item） */
    public function pageContext(array $p): array
    {
        $path = (string)($p['current_path'] ?? '');
        if ($path === '') {
            throw new \RuntimeException('Q-20-NO-CTX：没拿到当前页面，请刷新后重试');
        }
        $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?: $path, '/');

        $row = Db::name('menu_item')->where('url', $path)->find();
        $segments = array_values(array_filter(explode('/', $path)));
        $module = $segments[1] ?? ($segments[0] ?? '');

        // 该页面可用只读动作：按模块推断 + 兜底给出通用只读集
        $moduleActionMap = [
            'content' => ['content.article.list', 'content.category.tree', 'stat.overview'],
            'ai_theme' => ['theme.record.list', 'theme.record.detail', 'theme.palette.list'],
            'theme' => ['theme.market.list', 'theme.version.history'],
            'template_store' => ['template.store.list', 'template.install.list', 'template.license.list'],
            'member' => ['member.list', 'member.detail', 'stat.overview'],
            'system' => ['system.version', 'system.health', 'config.get'],
            'config' => ['config.get'],
            'ai' => ['ai.log.list', 'stat.overview'],
            'api' => ['api.log.list'],
            'report' => ['report.definition.list'],
        ];
        $available = $moduleActionMap[$module] ?? ['stat.overview', 'system.version', 'system.health'];

        return ['data' => [
            'page_name' => $row ? (string)$row['name'] : ($segments[1] ?? '未知页面'),
            'module' => $module,
            'available_actions' => $available,
        ]];
    }

    // ==================== 模拟数据域（A-6 物理隔离）====================

    /** mockdata.content.batch_create：只写 i8j_mock_content */
    public function mockdataContentBatchCreate(array $p): array
    {
        $count = max(1, min(500, (int)$p['count']));
        $dryRun = !empty($p['dry_run']);

        if ($dryRun) {
            return ['data' => [
                'created' => 0,
                'dry_run' => true,
                'diff_summary' => "将向 i8j_mock_content 插入 {$count} 条（dry-run 未落库）",
            ]];
        }

        $batchKey = 'mock_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
        $now = time();
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'title' => '模拟文章 ' . $batchKey . '-' . $i,
                'content' => '这是第 ' . $i . ' 条模拟内容，用于验证批量查询与统计链路。',
                'status' => 2,
                'cate_id' => 0,
                'batch_key' => $batchKey,
                'data_origin' => 'mock',
                'create_time' => $now,
            ];
        }
        // 分批插入，避免单条 SQL 过长
        foreach (array_chunk($rows, 100) as $chunk) {
            Db::name('mock_content')->insertAll($chunk);
        }

        return ['data' => [
            'created' => $count,
            'dry_run' => false,
            'diff_summary' => "已向 i8j_mock_content 插入 {$count} 条，batch_key={$batchKey}",
            'batch_key' => $batchKey,
        ]];
    }

    /** mockdata.template.batch_create：只写 i8j_mock_template */
    public function mockdataTemplateBatchCreate(array $p): array
    {
        $count = max(1, min(500, (int)$p['count']));
        $dryRun = !empty($p['dry_run']);

        if ($dryRun) {
            return ['data' => [
                'created' => 0,
                'dry_run' => true,
                'diff_summary' => "将向 i8j_mock_template 插入 {$count} 条（dry-run 未落库）",
            ]];
        }

        $batchKey = 'mocktpl_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
        $now = time();
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'name' => '模拟模板 ' . $batchKey . '-' . $i,
                'description' => '第 ' . $i . ' 条模拟模板数据',
                'price' => ($i % 3 === 0) ? 99.00 : 0.00,
                'status' => 1,
                'batch_key' => $batchKey,
                'data_origin' => 'mock',
                'create_time' => $now,
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            Db::name('mock_template')->insertAll($chunk);
        }

        return ['data' => [
            'created' => $count,
            'dry_run' => false,
            'diff_summary' => "已向 i8j_mock_template 插入 {$count} 条，batch_key={$batchKey}",
            'batch_key' => $batchKey,
        ]];
    }

    /** 时间戳/datetime 统一格式化为 Y-m-d H:i:s */
    private function fmtTime($v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        if (is_numeric($v)) {
            return date('Y-m-d H:i:s', (int)$v);
        }
        return (string)$v;
    }
}

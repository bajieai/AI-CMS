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

namespace app\install\controller;

use think\App;
use think\facade\Db;

/**
 * 安装向导控制器
 * 独立于认证体系，通过public/install.php入口访问
 */
class IndexController
{
    /** AI-CMS 当前版本号（安装页面统一使用此常量） */
    public const APP_VERSION = '2.9.41';

    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 安装首页 - 步骤1: 环境检测
     */
    public function index()
    {
        $checks = $this->checkEnvironment();
        return view('/step1', ['checks' => $checks, 'version' => self::APP_VERSION]);
    }

    /**
     * 步骤2: 数据库配置
     */
    public function step2()
    {
        if ($this->request()->isPost()) {
            $dbConfig = $this->request()->post();
            
            // 验证表前缀格式：必须以小写字母开头，含小写字母数字下划线，推荐下划线结尾
            $prefix = $dbConfig['prefix'] ?? 'i8j_';
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $prefix)) {
                return json(['code' => 1, 'msg' => '表前缀格式错误：必须以小写字母开头，只含小写字母、数字和下划线']);
            }
            if (!str_ends_with($prefix, '_')) {
                $prefix .= '_';
                $dbConfig['prefix'] = $prefix;
            }
            
            try {
                $dsn = "mysql:host={$dbConfig['hostname']};port={$dbConfig['hostport']};charset=utf8mb4";
                $pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                session('db_config', $dbConfig);
                return json(['code' => 0, 'msg' => '数据库连接成功']);
            } catch (\PDOException $e) {
                return json(['code' => 1, 'msg' => '数据库连接失败: ' . $e->getMessage()]);
            }
        }
        return view('/step2', ['version' => self::APP_VERSION]);
    }

    public function step3()
    {
        if ($this->request()->isPost()) {
            $admin = $this->request()->post();
            if (empty($admin['username']) || empty($admin['password'])) {
                return json(['code' => 1, 'msg' => '请填写管理员信息']);
            }
            session('admin_config', $admin);
            return json(['code' => 0, 'msg' => 'OK']);
        }
        return view('/step3', ['version' => self::APP_VERSION]);
    }

    public function getRewriteRules()
    {
        $rules = <<<'CONF'
rewrite ^/admin/?$ /admin.php last;
rewrite ^/admin/(.*)$ /admin.php?s=/$1 last;
rewrite ^/install/(.*)$ /install.php?s=/$1 last;
rewrite ^/api/(.*)$ /api.php?s=/$1 last;
if (!-e $request_filename) {
    rewrite ^(.*)$ /index.php?s=/$1 last;
}
CONF;
        return json(['code' => 0, 'data' => $rules, 'msg' => 'ok']);
    }

    public function step4()
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $phase = $this->request()->param('phase', '');
        $dbConfig = session('db_config');
        $adminConfig = session('admin_config');

        if (empty($dbConfig)) {
            return json(['code' => 1, 'msg' => '会话数据丢失，请重新开始安装']);
        }

        if ($phase !== '') {
            return $this->executePhase((int)$phase, $dbConfig, $adminConfig);
        }

        try {
            $pdo = $this->createPdoConnection($dbConfig);
            $this->executeAllSql($pdo, $dbConfig, $adminConfig);
            return json(['code' => 0, 'msg' => '安装成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '安装失败: ' . $e->getMessage()]);
        }
    }

    protected function executePhase(int $phase, array $dbConfig, array $adminConfig)
    {
        try {
            switch ($phase) {
                case 1:
                    $pdo = $this->createPdoConnection($dbConfig);
                    $dbName = $dbConfig['database'];
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$dbName}`");
                    session('install_pdo_connected', true);
                    return json(['code' => 0, 'msg' => '数据库创建成功', 'phase' => 1, 'next' => 2]);

                case 2:
                    $dbName = $dbConfig['database'];
                    $prefix = $dbConfig['prefix'] ?? 'i8j_';
                    $offset = (int)$this->request()->param('offset', 0);

                    if ($offset === 0) {
                        $pdo = $this->createPdoConnection($dbConfig);
                        $pdo->exec("USE `{$dbName}`");
                        try {
                            $stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}%'");
                            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                            if (!empty($tables)) {
                                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                                foreach ($tables as $table) {
                                    $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                                }
                                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                            }
                        } catch (\Exception $e) {
                        }
                        $this->prepareSqlCache($prefix);
                    } else {
                        $pdo = $this->createPdoConnection($dbConfig);
                        $pdo->exec("USE `{$dbName}`");
                    }

                    $result = $this->executeSqlBatch($pdo, $dbConfig, $offset, 800);
                    return json($result);

                case 3:
                    $pdo = $this->createPdoConnection($dbConfig);
                    $dbName = $dbConfig['database'];
                    $pdo->exec("USE `{$dbName}`");
                    $prefix = $dbConfig['prefix'] ?? 'i8j_';

                    if (!empty($adminConfig)) {
                        $hashedPassword = password_hash($adminConfig['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE `{$prefix}user` SET `username` = ?, `password` = ? WHERE `id` = 1");
                        $stmt->execute([$adminConfig['username'], $hashedPassword]);
                    }
                    $pdo->exec("UPDATE `{$prefix}config` SET `value` = '" . self::APP_VERSION . "' WHERE `name` = 'app_version'");
                    return json(['code' => 0, 'msg' => '管理员和版本号更新完成', 'phase' => 3, 'next' => 4]);

                case 4:
                    // 阶段4: 写入.env配置+创建安装锁+清理缓存
                    $this->writeEnvFile($dbConfig);
                    file_put_contents(root_path() . 'install.lock', date('Y-m-d H:i:s'));
                    $runtimeDir = root_path() . 'runtime/';
                    @array_map('unlink', glob($runtimeDir . 'install_sql_cache_*'));
                    @array_map('unlink', glob($runtimeDir . 'install_temp_*.sql'));
                    // 清除系统缓存，确保后台Logo等配置从数据库重新读取
                    @array_map('unlink', glob($runtimeDir . 'cache/*'));
                    session('db_config', null);
                    session('admin_config', null);
                    return json(['code' => 0, 'msg' => '安装成功！', 'phase' => 4, 'done' => true]);

                default:
                    return json(['code' => 1, 'msg' => '无效的安装阶段: ' . $phase]);
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '阶段' . $phase . '执行失败: ' . $e->getMessage(), 'phase' => $phase]);
        }
    }

    protected function createPdoConnection(array $dbConfig): \PDO
    {
        $dsn = "mysql:host={$dbConfig['hostname']};port={$dbConfig['hostport']};charset=utf8mb4";
        $pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            \PDO::ATTR_TIMEOUT => 30,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("SET SESSION wait_timeout = 600");
        return $pdo;
    }

    protected function prepareSqlCache(string $prefix): void
    {
        $sqlFile = root_path() . 'database/install.sql';
        if (!file_exists($sqlFile)) return;

        $cacheFile = root_path() . 'runtime/install_sql_cache_' . md5($prefix . filemtime($sqlFile)) . '.cache';
        if (file_exists($cacheFile)) return;

        $sql = file_get_contents($sqlFile);
        $sql = str_replace('{prefix}', $prefix, $sql);
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $rawStatements = $this->splitSql($sql);

        $validStatements = [];
        foreach ($rawStatements as $statement) {
            $stmt = trim($statement);
            if ($stmt === '') continue;
            if (preg_match('/^\s*--/', $stmt) && strpos($stmt, "\n") === false) continue;
            $hasCode = false;
            foreach (explode("\n", $stmt) as $line) {
                $line = trim($line);
                if ($line !== '' && !str_starts_with($line, '--')) { $hasCode = true; break; }
            }
            if (!$hasCode) continue;
            $validStatements[] = $stmt;
        }

        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0777, true);
        @file_put_contents($cacheFile, serialize($validStatements));
    }

    protected function executeSqlBatch(\PDO $pdo, array $dbConfig, int $offset, int $batchSize): array
    {
        $prefix = $dbConfig['prefix'] ?? 'i8j_';
        $sqlFile = root_path() . 'database/install.sql';

        if (!file_exists($sqlFile)) {
            return ['code' => 1, 'msg' => 'SQL file not found'];
        }

        $cacheFile = root_path() . 'runtime/install_sql_cache_' . md5($prefix . filemtime($sqlFile)) . '.cache';

        if (file_exists($cacheFile)) {
            $validStatements = unserialize(file_get_contents($cacheFile));
        } else {
            $sql = file_get_contents($sqlFile);
            $sql = str_replace('{prefix}', $prefix, $sql);
            $sql = str_replace(["\r\n", "\r"], "\n", $sql);
            $rawStatements = $this->splitSql($sql);

            $validStatements = [];
            foreach ($rawStatements as $statement) {
                $stmt = trim($statement);
                if ($stmt === '') continue;
                if (preg_match('/^\s*--/', $stmt) && strpos($stmt, "\n") === false) continue;
                $hasCode = false;
                foreach (explode("\n", $stmt) as $line) {
                    $line = trim($line);
                    if ($line !== '' && !str_starts_with($line, '--')) {
                        $hasCode = true;
                        break;
                    }
                }
                if (!$hasCode) continue;
                $validStatements[] = $stmt;
            }

            $cacheDir = dirname($cacheFile);
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0777, true);
            }
            @file_put_contents($cacheFile, serialize($validStatements));
        }

        $total = count($validStatements);
        $errors = [];
        $end = min($offset + $batchSize, $total);
        $buffer = [];

        for ($i = $offset; $i < $end; $i++) {
            $stmt = $validStatements[$i];
            $firstLine = strtolower(substr(ltrim($stmt), 0, 80));

            if (str_starts_with($firstLine, 'create ') || str_starts_with($firstLine, 'drop ')) {
                if (!empty($buffer)) {
                    $this->execMergedSql($pdo, implode("\n", $buffer), $errors);
                    $buffer = [];
                }
                $this->execSingleSql($pdo, $stmt, $errors);
            } else {
                $buffer[] = $stmt;
            }
        }

        if (!empty($buffer)) {
            $this->execMergedSql($pdo, implode("\n", $buffer), $errors);
        }

        $executed = $end;
        $percent = $total > 0 ? round($executed / $total * 100) : 100;
        $hasMore = $executed < $total;

        if (!empty($errors)) {
            $logContent = date('Y-m-d H:i:s') . " - SQL errors(" . count($errors) . ")\n";
            $logContent .= implode("\n", array_slice($errors, 0, 5)) . "\n\n";
            @file_put_contents(root_path() . 'runtime/log/install_sql_errors.log', $logContent, FILE_APPEND);
        }

        $result = [
            'code' => 0,
            'phase' => 2,
            'percent' => $percent,
            'executed' => $executed,
            'total' => $total,
        ];

        if ($hasMore) {
            $result['msg'] = "正在创建数据库表... {$percent}%";
            $result['next_offset'] = $executed;
            $result['has_more'] = true;
        } else {
            $result['msg'] = "数据库表创建完成";
            $result['next'] = 3;
            $result['has_more'] = false;
        }

        return $result;
    }

    protected function execSingleSql(\PDO $pdo, string $sql, array &$errors): void
    {
        try {
            $pdo->exec($sql);
        } catch (\PDOException $e) {
            $this->collectSqlError($e, $errors);
        }
    }

    protected function execMergedSql(\PDO $pdo, string $sql, array &$errors): void
    {
        if (trim($sql) === '') return;
        try {
            $pdo->exec($sql);
        } catch (\PDOException $e) {
            $this->collectSqlError($e, $errors);
        }
    }

    protected function collectSqlError(\PDOException $e, array &$errors): void
    {
        $errInfo = $e->errorInfo ?? [];
        $errMsg = $errInfo[2] ?? $e->getMessage();
        $sqlState = $errInfo[0] ?? '';
        if (in_array($sqlState, ['42S21', '42S01']) ||
            strpos($errMsg, 'Duplicate column') !== false ||
            strpos($errMsg, 'Duplicate key') !== false ||
            strpos($errMsg, 'already exists') !== false) {
            return;
        }
        $errors[] = substr($errMsg, 0, 200);
    }

    /**
     * 一次性执行全部安装（兼容旧流程）
     */
    protected function executeAllSql(\PDO $pdo, array $dbConfig, array $adminConfig)
    {
        $dbName = $dbConfig['database'];
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        $prefix = $dbConfig['prefix'] ?? 'i8j_';
        $sqlFile = root_path() . 'database/install.sql';
        if (!file_exists($sqlFile)) {
            throw new \Exception('SQL file not found: database/');
        }

        $sql = file_get_contents($sqlFile);
        $sql = str_replace('{prefix}', $prefix, $sql);
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $rawStatements = $this->splitSql($sql);

        $errors = [];
        foreach ($rawStatements as $statement) {
            $stmt = trim($statement);
            if ($stmt === '') continue;
            if (preg_match('/^\s*--/', $stmt) && strpos($stmt, "\n") === false) continue;
            $hasCode = false;
            foreach (explode("\n", $stmt) as $line) {
                $line = trim($line);
                if ($line !== '' && !str_starts_with($line, '--')) {
                    $hasCode = true;
                    break;
                }
            }
            if (!$hasCode) continue;

            try {
                $pdo->exec($stmt);
            } catch (\PDOException $e) {
                $errInfo = $e->errorInfo ?? [];
                $errMsg = $errInfo[2] ?? $e->getMessage();
                $sqlState = $errInfo[0] ?? '';
                if (in_array($sqlState, ['42S21', '42S01']) ||
                    strpos($errMsg, 'Duplicate column') !== false ||
                    strpos($errMsg, 'Duplicate key') !== false ||
                    strpos($errMsg, 'already exists') !== false) {
                    continue;
                }
                $errors[] = substr($errMsg, 0, 200);
            }
        }

        if (!empty($errors)) {
            $logContent = date('Y-m-d H:i:s') . " - 安装SQL错误(" . count($errors) . "条):\n";
            $logContent .= implode("\n", array_slice($errors, 0, 10)) . "\n\n";
            @file_put_contents(root_path() . 'runtime/log/install_sql_errors.log', $logContent, FILE_APPEND);
        }

        if (!empty($adminConfig)) {
            $hashedPassword = password_hash($adminConfig['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE `{$prefix}user` SET `username` = ?, `password` = ? WHERE `id` = 1");
            $stmt->execute([$adminConfig['username'], $hashedPassword]);
        }
        $pdo->exec("UPDATE `{$prefix}config` SET `value` = '" . self::APP_VERSION . "' WHERE `name` = 'app_version'");

        $this->writeEnvFile($dbConfig);
        file_put_contents(root_path() . 'install.lock', date('Y-m-d H:i:s'));
    }

    /**
     * 步骤5: 安装完成
     */
    public function step5()
    {
        if (!file_exists(root_path() . 'install.lock')) {
            return redirect('/install.php');
        }
        return view('/step5', ['version' => self::APP_VERSION]);
    }

    /**
     * 环境检测
     */
    protected function checkEnvironment(): array
    {
        $checks = [];
        $checks[] = ['name' => 'PHP版本 >= 8.2', 'status' => version_compare(PHP_VERSION, '8.2.0', '>='), 'current' => '当前: ' . PHP_VERSION];
        $checks[] = ['name' => 'PDO MySQL扩展（数据库连接）', 'status' => extension_loaded('pdo_mysql'), 'current' => extension_loaded('pdo_mysql') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'JSON扩展', 'status' => extension_loaded('json'), 'current' => extension_loaded('json') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'GD扩展（图片处理）', 'status' => extension_loaded('gd'), 'current' => extension_loaded('gd') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'cURL扩展（AI调用）', 'status' => extension_loaded('curl'), 'current' => extension_loaded('curl') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'mbstring扩展（UTF-8编码）', 'status' => extension_loaded('mbstring'), 'current' => extension_loaded('mbstring') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'bcmath扩展（精确计算）', 'status' => extension_loaded('bcmath'), 'current' => extension_loaded('bcmath') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'ZIP扩展（插件/模板安装）', 'status' => extension_loaded('zip'), 'current' => extension_loaded('zip') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'fileinfo扩展（文件检测）', 'status' => extension_loaded('fileinfo'), 'current' => extension_loaded('fileinfo') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'OPcache扩展（性能优化，强烈建议）', 'status' => extension_loaded('Zend OPcache') || extension_loaded('opcache'), 'current' => (extension_loaded('Zend OPcache') || extension_loaded('opcache')) ? '已开启' : '未开启（建议安装）'];

        $dirs = ['runtime' => root_path() . 'runtime', 'public/uploads' => public_path() . 'uploads'];
        foreach ($dirs as $name => $path) {
            $writable = is_writable(dirname($path)) || is_writable($path);
            $checks[] = ['name' => "目录可写: {$name}", 'status' => $writable, 'current' => $writable ? '可写' : '不可写'];
        }

        // 检查全量安装SQL文件是否存在
        $installSql = root_path() . 'database/install.sql';
        $checks[] = ['name' => '安装SQL文件', 'status' => file_exists($installSql), 'current' => file_exists($installSql) ? '存在' : '缺失'];

        return $checks;
    }

    /**
     * 写入.env配置文件
     */
    protected function writeEnvFile(array $dbConfig): void
    {
        $envContent = "# AI-CMS V" . self::APP_VERSION . " 环境配置（安装向导自动生成）\n";
        $envContent .= "APP_DEBUG = false\n\n";
        $envContent .= "DATABASE_TYPE = mysql\n";
        $envContent .= "DATABASE_HOSTNAME = {$dbConfig['hostname']}\n";
        $envContent .= "DATABASE_DATABASE = {$dbConfig['database']}\n";
        $envContent .= "DATABASE_USERNAME = {$dbConfig['username']}\n";
        $envContent .= "DATABASE_PASSWORD = {$dbConfig['password']}\n";
        $envContent .= "DATABASE_HOSTPORT = {$dbConfig['hostport']}\n";
        $envContent .= "DATABASE_CHARSET = utf8mb4\n";
        $envContent .= "DATABASE_PREFIX = {$dbConfig['prefix']}\n\n";
        $envContent .= "CACHE_DRIVER = file\n\n";
        $envContent .= "AI_DEEPSEEK_BASE_URL = https://api.deepseek.com\n";
        $envContent .= "AI_DEEPSEEK_API_KEY = \n";
        $envContent .= "AI_DEEPSEEK_MODEL = deepseek-chat\n";

        file_put_contents(root_path() . '.env', $envContent);
    }

    protected function request()
    {
        return $this->app->request;
    }

    /**
     * 智能拆分SQL语句（考虑引号和注释内的分号）
     * 支持：单引号字符串、双引号字符串、反引号标识符、-- 注释、/* 注释
     */
    protected function splitSql(string $sql): array
    {
        $statements = [];
        $current = '';
        $len = strlen($sql);
        $i = 0;
        $inSingleQuote = false;  // 单引号内
        $inDoubleQuote = false; // 双引号内
        $inBacktick = false;    // 反引号内

        while ($i < $len) {
            $char = $sql[$i];
            $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

            // 处理引号状态切换
            if (!$inDoubleQuote && !$inBacktick && $char === "'" && ($i === 0 || $sql[$i - 1] !== '\\')) {
                $inSingleQuote = !$inSingleQuote;
                $current .= $char;
                $i++;
                continue;
            }
            if (!$inSingleQuote && !$inBacktick && $char === '"') {
                $inDoubleQuote = !$inDoubleQuote;
                $current .= $char;
                $i++;
                continue;
            }
            if (!$inSingleQuote && !$inDoubleQuote && $char === '`') {
                $inBacktick = !$inBacktick;
                $current .= $char;
                $i++;
                continue;
            }

            // 在引号内，直接追加字符
            if ($inSingleQuote || $inDoubleQuote || $inBacktick) {
                $current .= $char;
                $i++;
                continue;
            }

            // 处理 -- 注释（到行尾）
            if ($char === '-' && $next === '-') {
                // 追加到行尾
                while ($i < $len && $sql[$i] !== "\n") {
                    $current .= $sql[$i];
                    $i++;
                }
                continue;
            }

            // 处理 /* */ 注释
            if ($char === '/' && $next === '*') {
                $current .= '/*';
                $i += 2;
                while ($i < $len) {
                    if ($sql[$i] === '*' && ($i + 1 < $len) && $sql[$i + 1] === '/') {
                        $current .= '*/';
                        $i += 2;
                        break;
                    }
                    $current .= $sql[$i];
                    $i++;
                }
                continue;
            }

            // 分号 = 语句结束
            if ($char === ';') {
                $current .= ';';
                $statements[] = $current;
                $current = '';
                $i++;
                // 跳过分号后的空白和换行
                while ($i < $len && ($sql[$i] === ' ' || $sql[$i] === "\n" || $sql[$i] === "\r" || $sql[$i] === "\t")) {
                    $i++;
                }
                continue;
            }

            $current .= $char;
            $i++;
        }

        // 追加最后未以分号结尾的语句
        $trimmed = trim($current);
        if ($trimmed !== '') {
            $statements[] = $current;
        }

        return $statements;
    }
}

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
        return view('/step1', ['checks' => $checks]);
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
        return view('/step2');
    }

    /**
     * 步骤3: 创建管理员
     */
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
        return view('/step3');
    }

    /**
     * 步骤4: 执行安装（分阶段模式）
     * 支持 phase 参数逐阶段执行，前端逐步请求并显示进度
     * phase=1: 创建数据库+选择数据库
     * phase=2: 执行建表SQL（最耗时阶段，显示进度百分比）
     * phase=3: 更新管理员+版本号+写.env+创建安装锁
     * phase=空: 一次性执行全部（兼容旧流程）
     */
    public function step4()
    {
        // 安装可能耗时较长（大量建表+数据导入），取消执行时间限制
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $phase = $this->request()->param('phase', '');
        $dbConfig = session('db_config');
        $adminConfig = session('admin_config');

        if (empty($dbConfig)) {
            return json(['code' => 1, 'msg' => '会话数据丢失，请重新开始安装']);
        }

        // 分阶段执行
        if ($phase !== '') {
            return $this->executePhase((int)$phase, $dbConfig, $adminConfig);
        }

        // 兼容旧流程：一次性执行全部
        try {
            $pdo = $this->createPdoConnection($dbConfig);
            $this->executeAllSql($pdo, $dbConfig, $adminConfig);
            return json(['code' => 0, 'msg' => '安装成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '安装失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 分阶段执行安装
     */
    protected function executePhase(int $phase, array $dbConfig, array $adminConfig)
    {
        try {
            switch ($phase) {
                case 1:
                    // 阶段1: 创建数据库并选择
                    $pdo = $this->createPdoConnection($dbConfig);
                    $dbName = $dbConfig['database'];
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$dbName}`");
                    // 缓存PDO连接信息到session（后续阶段复用）
                    session('install_pdo_connected', true);
                    return json(['code' => 0, 'msg' => '数据库创建成功', 'phase' => 1, 'next' => 2]);

                case 2:
                    // 阶段2: 执行建表SQL（优先用 mysql 命令行 source，极快；不可用时回退 PDO 分批）
                    $dbName = $dbConfig['database'];
                    $prefix = $dbConfig['prefix'] ?? 'i8j_';

                    // 优先使用 mysql 命令行 source 方式（eyoucms/dedecms 同款方案）
                    $mysqlResult = $this->executeSqlViaMysqlCli($dbConfig, $dbName, $prefix);
                    if ($mysqlResult !== null) {
                        // mysql 命令行方式成功或失败，直接返回结果
                        if ($mysqlResult['code'] === 0) {
                            return json(['code' => 0, 'msg' => '数据库表创建完成', 'phase' => 2, 'percent' => 100, 'next' => 3]);
                        }
                        // mysql 命令行执行失败，回退到 PDO 方式
                    }

                    // 回退：PDO 分批执行（每批20条）
                    $pdo = $this->createPdoConnection($dbConfig);
                    $pdo->exec("USE `{$dbName}`");

                    $offset = (int)$this->request()->param('offset', 0);
                    $batchSize = 20;

                    $result = $this->executeSqlBatch($pdo, $dbConfig, $offset, $batchSize);
                    return json($result);

                case 3:
                    // 阶段3: 更新管理员密码+版本号
                    $pdo = $this->createPdoConnection($dbConfig);
                    $dbName = $dbConfig['database'];
                    $pdo->exec("USE `{$dbName}`");
                    $prefix = $dbConfig['prefix'] ?? 'i8j_';

                    if (!empty($adminConfig)) {
                        $hashedPassword = password_hash($adminConfig['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE `{$prefix}user` SET `username` = ?, `password` = ? WHERE `id` = 1");
                        $stmt->execute([$adminConfig['username'], $hashedPassword]);
                    }
                    $pdo->exec("UPDATE `{$prefix}config` SET `value` = '2.9.41' WHERE `name` = 'app_version'");
                    return json(['code' => 0, 'msg' => '管理员和版本号更新完成', 'phase' => 3, 'next' => 4]);

                case 4:
                    // 阶段4: 写入.env配置+创建安装锁
                    $this->writeEnvFile($dbConfig);
                    file_put_contents(root_path() . 'install.lock', date('Y-m-d H:i:s'));
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

    /**
     * 使用 mysql 命令行工具 source 方式执行 SQL（速度极快，10-50倍于 PDO 逐条执行）
     * eyoucms/dedecms 都使用这种方式安装
     */
    protected function executeSqlViaMysqlCli(array $dbConfig, string $dbName, string $prefix): ?array
    {
        $sqlFile = root_path() . 'database/install.sql';

        if (!file_exists($sqlFile)) {
            return ['code' => 1, 'msg' => 'SQL file not found'];
        }

        // 将 {prefix} 替换为实际前缀，生成临时 SQL 文件
        $sqlContent = file_get_contents($sqlFile);
        $sqlContent = str_replace('{prefix}', $prefix, $sqlContent);

        // 写入临时文件（mysql source 需要实际文件路径）
        $tempFile = root_path() . 'runtime/install_temp_' . md5($dbName) . '.sql';
        if (!is_dir(dirname($tempFile))) {
            @mkdir(dirname($tempFile), 0777, true);
        }
        file_put_contents($tempFile, $sqlContent);

        // 搜索常见 mysql 命令行工具路径
        $mysqlBin = $this->findMysqlBin();
        if ($mysqlBin === null) {
            // mysql 命令不可用，回退到 PDO
            @unlink($tempFile);
            return null;
        }

        // 执行 mysql source 命令
        $host = $dbConfig['hostname'];
        $port = $dbConfig['hostport'];
        $user = $dbConfig['username'];
        $pass = $dbConfig['password'];

        $cmd = sprintf(
            '%s -h%s -P%s -u%s -p%s --default-character-set=utf8mb4 %s -e "source %s" 2>&1',
            escapeshellarg($mysqlBin),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($dbName),
            escapeshellarg($tempFile)
        );

        $output = [];
        $returnVar = 0;
        @exec($cmd, $output, $returnVar);

        // 清理临时文件
        @unlink($tempFile);

        if ($returnVar === 0) {
            return ['code' => 0, 'msg' => 'SQL executed via mysql CLI'];
        }

        // mysql 命令行执行失败
        $errorMsg = implode("\n", array_slice($output, 0, 5));
        return ['code' => 1, 'msg' => 'mysql CLI failed: ' . $errorMsg];
    }

    /**
     * 搜索 mysql 命令行工具路径
     * 支持：Linux 标准路径、宝塔面板、小皮面板（phpStudy）、Docker、Windows 常见路径
     */
    protected function findMysqlBin(): ?string
    {
        $candidates = [];

        // 1. 系统环境 PATH 中直接可用
        $candidates[] = 'mysql';

        // 2. Windows 小皮面板（phpStudy）常见路径
        $phpStudyDirs = glob('D:/phpstudy_pro/Extensions/MySQL*', GLOB_ONLYDIR);
        if (!empty($phpStudyDirs)) {
            foreach ($phpStudyDirs as $dir) {
                $candidates[] = $dir . '/bin/mysql.exe';
            }
        }

        // 3. 宝塔面板常见路径
        $btDirs = [
            '/www/server/mysql/bin/mysql',
            '/www/server/mysql-8.0/bin/mysql',
            '/www/server/mysql-5.7/bin/mysql',
        ];
        $candidates = array_merge($candidates, $btDirs);

        // 4. Linux 标准路径
        $linuxPaths = [
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
            '/usr/local/mysql/bin/mysql',
            '/opt/mysql/bin/mysql',
        ];
        $candidates = array_merge($candidates, $linuxPaths);

        // 5. Docker 环境
        $candidates[] = '/usr/bin/mysql';

        foreach ($candidates as $path) {
            // Windows 下 .exe 文件
            if (DIRECTORY_SEPARATOR === '\\' && substr($path, -4) !== '.exe') {
                continue;  // 在 Windows 上跳过 Linux 格式的路径
            }

            if (is_executable($path) || (DIRECTORY_SEPARATOR === '\\' && file_exists($path))) {
                return $path;
            }
        }

        // 6. 用 which/where 命令搜索
        $searchCmd = DIRECTORY_SEPARATOR === '\\' ? 'where mysql' : 'which mysql';
        $result = [];
        @exec($searchCmd . ' 2>/dev/null', $result);
        if (!empty($result) && file_exists($result[0])) {
            return $result[0];
        }

        return null;
    }

    /**
     * 创建PDO连接
     */
    protected function createPdoConnection(array $dbConfig): \PDO
    {
        $dsn = "mysql:host={$dbConfig['hostname']};port={$dbConfig['hostport']};charset=utf8mb4";
        return new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]);
    }

    /**
     * 分批执行SQL语句（带进度百分比）
     */
    protected function executeSqlBatch(\PDO $pdo, array $dbConfig, int $offset, int $batchSize): array
    {
        $prefix = $dbConfig['prefix'] ?? 'i8j_';
        $sqlFile = root_path() . 'database/install.sql';

        if (!file_exists($sqlFile)) {
            return ['code' => 1, 'msg' => 'SQL file not found'];
        }

        // 读取并预处理SQL
        $sql = file_get_contents($sqlFile);
        $sql = str_replace('{prefix}', $prefix, $sql);
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $rawStatements = $this->splitSql($sql);

        // 过滤有效语句
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

        $total = count($validStatements);
        $errors = [];

        // 执行从offset开始的batchSize条语句
        $end = min($offset + $batchSize, $total);
        for ($i = $offset; $i < $end; $i++) {
            try {
                $pdo->exec($validStatements[$i]);
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

        $executed = $end;
        $percent = $total > 0 ? round($executed / $total * 100) : 100;
        $hasMore = $executed < $total;

        // 记录错误到日志
        if (!empty($errors)) {
            $logContent = date('Y-m-d H:i:s') . " - SQL批次错误(" . count($errors) . "条, offset={$offset}):\n";
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
        $pdo->exec("UPDATE `{$prefix}config` SET `value` = '2.9.41' WHERE `name` = 'app_version'");

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
        return view('/step5');
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
        $envContent = "# AI-CMS V2.9.41 环境配置（安装向导自动生成）\n";
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

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
     * 步骤4: 执行安装
     */
    public function step4()
    {
        $dbConfig = session('db_config');
        $adminConfig = session('admin_config');

        if (empty($dbConfig)) {
            return redirect('/install.php');
        }

        try {
            // 1. 创建数据库
            $dsn = "mysql:host={$dbConfig['hostname']};port={$dbConfig['hostport']};charset=utf8mb4";
            $pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]);
            $dbName = $dbConfig['database'];
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // 2. 执行建表SQL（全量安装脚本）
            $prefix = $dbConfig['prefix'] ?? 'i8j_';
            $sqlFile = root_path() . 'database/install.sql';
            if (!file_exists($sqlFile)) {
                throw new \Exception('安装SQL文件不存在: database/install.sql，请确认代码完整性。');
            }
            $sql = file_get_contents($sqlFile);
            $sql = str_replace('{prefix}', $prefix, $sql);

            // 统一换行符（兼容Windows CRLF和Unix LF）
            $sql = str_replace(["\r\n", "\r"], "\n", $sql);

            // 智能拆分SQL语句（考虑引号内的分号不拆分）
            $rawStatements = $this->splitSql($sql);

            $executed = 0;
            $errors = [];
            foreach ($rawStatements as $statement) {
                $stmt = trim($statement);
                if ($stmt === '') continue;
                // 跳过纯注释
                if (preg_match('/^\s*--/', $stmt) && strpos($stmt, "\n") === false) continue;
                // 检查是否全为注释行
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
                    $executed++;
                } catch (\PDOException $e) {
                    // ALTER TABLE ADD COLUMN IF NOT EXISTS 等幂等语句可能因重复执行报错，忽略
                    $errInfo = $e->errorInfo ?? [];
                    $sqlState = $errInfo[0] ?? '';
                    $errMsg = $errInfo[2] ?? $e->getMessage();
                    // 1060: Duplicate column, 1061: Duplicate key, 1050: Table exists
                    if (in_array($sqlState, ['42S21', '42S01']) || 
                        strpos($errMsg, 'Duplicate column') !== false ||
                        strpos($errMsg, 'Duplicate key') !== false ||
                        strpos($errMsg, 'already exists') !== false) {
                        // 幂等语句重复执行，跳过
                        continue;
                    }
                    $errors[] = "SQL错误: " . substr($errMsg, 0, 200) . " | 语句: " . substr($stmt, 0, 100);
                }
            }

            if (!empty($errors)) {
                // 记录错误到日志文件但不中断安装（非致命错误）
                $logContent = date('Y-m-d H:i:s') . " - 安装SQL部分错误(" . count($errors) . "条):\n";
                $logContent .= implode("\n", array_slice($errors, 0, 10)) . "\n\n";
                @file_put_contents(root_path() . 'runtime/log/install_sql_errors.log', $logContent, FILE_APPEND);
            }

            // 3. 更新管理员密码
            if (!empty($adminConfig)) {
                $hashedPassword = password_hash($adminConfig['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE `{$prefix}user` SET `username` = ?, `password` = ? WHERE `id` = 1");
                $stmt->execute([$adminConfig['username'], $hashedPassword]);
            }

            // 3.5 更新系统版本号为 V2.9.40
            $pdo->exec("UPDATE `{$prefix}config` SET `value` = '2.9.40' WHERE `name` = 'app_version'");

            // 4. 写入.env
            $this->writeEnvFile($dbConfig);

            // 5. 创建安装锁
            file_put_contents(root_path() . 'install.lock', date('Y-m-d H:i:s'));

            return json(['code' => 0, 'msg' => '安装成功']);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '安装失败: ' . $e->getMessage()]);
        }
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
        $checks[] = ['name' => 'OPcache扩展（性能优化，强烈建议）', 'status' => extension_loaded('opcache'), 'current' => extension_loaded('opcache') ? '已开启' : '未开启（建议安装）'];

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
        $envContent = "# AI-CMS V2.9.40 环境配置（安装向导自动生成）\n";
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

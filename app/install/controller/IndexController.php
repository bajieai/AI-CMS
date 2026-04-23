<?php
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
            $pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $dbName = $dbConfig['database'];
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // 2. 执行建表SQL
            $prefix = $dbConfig['prefix'] ?? 'i8j_';
            $sqlFile = root_path() . 'database/migrations/install.sql';
            $sql = file_get_contents($sqlFile);
            $sql = str_replace('{prefix}', $prefix, $sql);

            // 统一换行符（兼容Windows CRLF和Unix LF）
            $sql = str_replace(["\r\n", "\r"], "\n", $sql);
            $rawStatements = array_filter(
                array_map('trim', explode(";\n", $sql)),
                function ($s): bool {
                    $t = trim($s);
                    if ($t === '') return false;
                    // 跳过只有注释（包括段标题 --...）的行，跳过后找第一条实际SQL
                    $lines = explode("\n", $t);
                    foreach ($lines as $line) {
                        $line = rtrim($line, "\r");
                        if ($line !== '' && !preg_match('/^\s*--/', $line)) {
                            return true; // 找到了实际SQL
                        }
                    }
                    return false; // 全部是注释
                }
            );

            foreach ($rawStatements as $statement) {
                $stmt = trim($statement);
                if (!empty($stmt)) {
                    $pdo->exec($stmt);
                }
            }

            // 3. 更新管理员密码
            if (!empty($adminConfig)) {
                $hashedPassword = password_hash($adminConfig['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE `{$prefix}user` SET `username` = ?, `password` = ? WHERE `id` = 1");
                $stmt->execute([$adminConfig['username'], $hashedPassword]);
            }

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
        $checks[] = ['name' => 'PDO MySQL扩展', 'status' => extension_loaded('pdo_mysql'), 'current' => extension_loaded('pdo_mysql') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'JSON扩展', 'status' => extension_loaded('json'), 'current' => extension_loaded('json') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'GD扩展（图片处理）', 'status' => extension_loaded('gd'), 'current' => extension_loaded('gd') ? '已安装' : '未安装'];
        $checks[] = ['name' => 'cURL扩展（AI调用）', 'status' => extension_loaded('curl'), 'current' => extension_loaded('curl') ? '已安装' : '未安装'];

        $dirs = ['runtime' => root_path() . 'runtime', 'public/uploads' => public_path() . 'uploads'];
        foreach ($dirs as $name => $path) {
            $writable = is_writable(dirname($path)) || is_writable($path);
            $checks[] = ['name' => "目录可写: {$name}", 'status' => $writable, 'current' => $writable ? '可写' : '不可写'];
        }

        return $checks;
    }

    /**
     * 写入.env配置文件
     */
    protected function writeEnvFile(array $dbConfig): void
    {
        $envContent = "# AI-CMS V2.0 环境配置（安装向导自动生成）\n";
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
}

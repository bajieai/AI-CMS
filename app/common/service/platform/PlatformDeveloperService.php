<?php
declare(strict_types=1);

namespace app\common\service\platform;

use app\common\model\PlatformApp;
use app\common\model\Member;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 开放平台开发者服务
 * V2.9.38 OPEN-PLAT-1
 * 开发者账号与CMS管理员分离，复用ApiOpenPlatformService API Key管理
 */
class PlatformDeveloperService
{
    protected const CACHE_TAG = 'platform_dev';
    protected const CACHE_TTL = 3600;

    /**
     * 注册开发者
     */
    public function registerDeveloper(array $data): int
    {
        // 开发者使用Member表，type字段标记为developer
        $member = new Member();
        $member->save([
            'username' => $data['username'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => password_hash($data['password'] ?? '', PASSWORD_DEFAULT),
            'nickname' => $data['company'] ?? $data['username'] ?? '',
            'type' => 'developer',
            'status' => 1,
            'reg_time' => time(),
            'reg_ip' => request()->ip(),
        ]);
        $developerId = (int) $member->id;
        
        // 存储开发者扩展信息
        Db::name('system_config')->insert([
            'config_key' => 'developer_info_' . $developerId,
            'config_value' => json_encode([
                'company' => $data['company'] ?? '',
                'contact' => $data['contact'] ?? '',
                'phone' => $data['phone'] ?? '',
                'developer_type' => $data['developer_type'] ?? 'individual', // individual/enterprise
                'verified' => false,
                'registered_at' => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        Cache::clear();
        return $developerId;
    }

    /**
     * 认证开发者(企业/个人)
     */
    public function authenticateDeveloper(int $developerId, array $authData): bool
    {
        $key = 'developer_info_' . $developerId;
        $info = Db::name('system_config')->where('config_key', $key)->value('config_value');
        $data = $info ? json_decode($info, true) : [];
        $data['verified'] = true;
        $data['verified_type'] = $authData['type'] ?? 'individual';
        $data['verified_at'] = date('Y-m-d H:i:s');
        $data['company'] = $authData['company'] ?? $data['company'] ?? '';
        $data['business_license'] = $authData['business_license'] ?? '';
        $data['id_card'] = $authData['id_card'] ?? '';
        
        $exists = Db::name('system_config')->where('config_key', $key)->find();
        if ($exists) {
            Db::name('system_config')->where('config_key', $key)->update(['config_value' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
        } else {
            Db::name('system_config')->insert(['config_key' => $key, 'config_value' => json_encode($data, JSON_UNESCAPED_UNICODE), 'created_at' => date('Y-m-d H:i:s')]);
        }
        
        Cache::clear();
        return true;
    }

    /**
     * 开发者仪表盘
     */
    public function getDashboard(int $developerId): array
    {
        return Cache::remember('dev_dashboard_' . $developerId, function() use ($developerId) {
            $appCount = PlatformApp::where('developer_id', $developerId)->count();
            $publishedApps = PlatformApp::where('developer_id', $developerId)->where('status', PlatformApp::STATUS_PUBLISHED)->count();
            $totalInstalls = PlatformApp::where('developer_id', $developerId)->sum('install_count');
            $totalRating = PlatformApp::where('developer_id', $developerId)->avg('avg_rating');
            
            // API调用量(从ApiOpenPlatformService日志)
            $apiCalls = Db::name('api_call_log')->where('developer_id', $developerId)->count();
            $apiCallsToday = Db::name('api_call_log')->where('developer_id', $developerId)->whereTime('created_at', 'today')->count();
            
            // 趋势(最近30天)
            $trend = Db::name('api_call_log')
                ->where('developer_id', $developerId)
                ->whereTime('created_at', '-30 days')
                ->field("DATE(created_at) as date, COUNT(*) as count")
                ->group('date')
                ->select()
                ->toArray();
            
            return [
                'app_count' => $appCount,
                'published_apps' => $publishedApps,
                'total_installs' => $totalInstalls,
                'avg_rating' => round((float)$totalRating, 1),
                'api_calls' => $apiCalls,
                'api_calls_today' => $apiCallsToday,
                'trend' => $trend,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 创建应用
     */
    public function createApp(int $developerId, array $data): int
    {
        $apiKey = md5(uniqid() . microtime());
        $apiSecret = md5($apiKey . bin2hex(random_bytes(16)));
        
        $app = new PlatformApp();
        $app->save([
            'app_name' => $data['app_name'] ?? '',
            'app_identifier' => $data['app_identifier'] ?? ('app_' . uniqid()),
            'app_type' => $data['app_type'] ?? PlatformApp::TYPE_WEB,
            'developer_id' => $developerId,
            'description' => $data['description'] ?? '',
            'app_config' => $data['app_config'] ?? null,
            'required_permissions' => $data['required_permissions'] ?? null,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'status' => PlatformApp::STATUS_PENDING,
            'version' => $data['version'] ?? '1.0.0',
            'category' => $data['category'] ?? '',
            'tags' => $data['tags'] ?? '',
        ]);
        Cache::clear();
        return (int) $app->id;
    }

    /**
     * 更新应用
     */
    public function updateApp(int $appId, int $developerId, array $data): bool
    {
        $app = PlatformApp::where('id', $appId)->where('developer_id', $developerId)->find();
        if (!$app) return false;
        $app->save($data);
        Cache::clear();
        return true;
    }

    /**
     * 删除应用
     */
    public function deleteApp(int $appId, int $developerId): bool
    {
        $app = PlatformApp::where('id', $appId)->where('developer_id', $developerId)->find();
        if (!$app) return false;
        $app->delete();
        Cache::clear();
        return true;
    }

    /**
     * 重置App Secret
     */
    public function resetAppSecret(int $appId, int $developerId): string
    {
        $app = PlatformApp::where('id', $appId)->where('developer_id', $developerId)->find();
        if (!$app) throw new \RuntimeException('App not found');
        $newSecret = md5($app->api_key . bin2hex(random_bytes(16)));
        $app->save(['api_secret' => $newSecret]);
        return $newSecret;
    }

    /**
     * 获取沙箱数据
     */
    public function getSandboxData(int $developerId): array
    {
        return Cache::remember('sandbox_' . $developerId, function() use ($developerId) {
            return [
                'test_contents' => Db::name('content')->where('is_test', 1)->limit(10)->select()->toArray(),
                'test_categories' => Db::name('category')->limit(20)->select()->toArray(),
                'api_endpoints' => $this->getAvailableEndpoints(),
            ];
        }, 300);
    }

    /**
     * 重置沙箱
     */
    public function resetSandbox(int $developerId): bool
    {
        Cache::delete('sandbox_' . $developerId);
        return true;
    }

    /**
     * 获取沙箱日志
     */
    public function getSandboxLogs(int $developerId, int $page = 1, int $limit = 20): array
    {
        $query = Db::name('api_call_log')->where('developer_id', $developerId)->order('id', 'desc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 获取可用API端点
     */
    protected function getAvailableEndpoints(): array
    {
        return [
            ['method' => 'GET', 'path' => '/api/v1/contents', 'desc' => '获取内容列表'],
            ['method' => 'GET', 'path' => '/api/v1/contents/{id}', 'desc' => '获取内容详情'],
            ['method' => 'POST', 'path' => '/api/v1/contents', 'desc' => '创建内容'],
            ['method' => 'PUT', 'path' => '/api/v1/contents/{id}', 'desc' => '更新内容'],
            ['method' => 'DELETE', 'path' => '/api/v1/contents/{id}', 'desc' => '删除内容'],
            ['method' => 'GET', 'path' => '/api/v1/categories', 'desc' => '获取分类列表'],
            ['method' => 'POST', 'path' => '/api/v1/files/upload', 'desc' => '上传文件'],
            ['method' => 'GET', 'path' => '/api/v1/users/{id}', 'desc' => '获取用户信息'],
            ['method' => 'POST', 'path' => '/api/v1/ai/write', 'desc' => 'AI写作'],
            ['method' => 'POST', 'path' => '/api/v1/ai/translate', 'desc' => 'AI翻译'],
            ['method' => 'POST', 'path' => '/api/v1/ai/quality', 'desc' => 'AI质检'],
        ];
    }

    /**
     * 获取开发者信息
     */
    public function getDeveloperInfo(int $developerId): array
    {
        $info = Db::name('system_config')->where('config_key', 'developer_info_' . $developerId)->value('config_value');
        return $info ? json_decode($info, true) : [];
    }

    /**
     * 开发者列表(后台管理)
     */
    public function listDevelopers(int $page = 1, int $limit = 20): array
    {
        $query = Member::where('type', 'developer');
        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
        // 附加开发者信息
        foreach ($list as &$dev) {
            $info = $this->getDeveloperInfo($dev['id']);
            $dev['company'] = $info['company'] ?? '';
            $dev['verified'] = $info['verified'] ?? false;
            $dev['app_count'] = PlatformApp::where('developer_id', $dev['id'])->count();
        }
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }
}


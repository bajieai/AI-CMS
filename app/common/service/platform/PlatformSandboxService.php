<?php
declare(strict_types=1);

namespace app\common\service\platform;

use think\facade\Db;

/**
 * 开放平台沙箱服务
 * V2.9.38 OPEN-PLAT-1
 */
class PlatformSandboxService
{
    protected PlatformDeveloperService $devService;

    public function __construct()
    {
        $this->devService = new PlatformDeveloperService();
    }

    public function getSandbox(int $developerId): array
    {
        return $this->devService->getSandboxData($developerId);
    }

    public function reset(int $developerId): bool
    {
        return $this->devService->resetSandbox($developerId);
    }

    public function getLogs(int $developerId, int $page = 1): array
    {
        return $this->devService->getSandboxLogs($developerId, $page);
    }

    public function mockApiCall(int $developerId, string $method, string $path, array $params = []): array
    {
        Db::name('api_call_log')->insert([
            'developer_id' => $developerId,
            'method' => $method,
            'path' => $path,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'ip' => request()->ip(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        return [
            'code' => 0,
            'msg' => 'success',
            'data' => ['sandbox' => true, 'method' => $method, 'path' => $path],
        ];
    }
}

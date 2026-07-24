<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\platform\PlatformDeveloperService;
use app\common\service\platform\PlatformAppMarketService;
use app\common\service\platform\ApiDocEnhanceService;
use think\facade\Json;

/**
 * 开放平台开发者控制器
 * V2.9.38 OPEN-PLAT-1
 */
class PlatformDeveloperController extends AdminBaseController
{
    protected PlatformDeveloperService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new PlatformDeveloperService();
    }

    public function index()
    {
        $page = (int) $this->request->param('page', 1);
        $result = $this->service->listDevelopers($page);
        return $this->view('platform_developer/index', $result);
    }

    public function audit()
    {
        $developerId = (int) $this->request->param('developer_id', 0);
        if ($this->request->isPost()) {
            $approved = (bool) $this->request->param('approved', false);
            $this->service->authenticateDeveloper($developerId, $this->request->post());
            return Json::success($approved ? '已认证通过' : '已更新');
        }
        $info = $this->service->getDeveloperInfo($developerId);
        return $this->view('platform_developer/audit', ['info' => $info, 'developer_id' => $developerId]);
    }

    public function sandbox()
    {
        $developerId = (int) $this->request->param('developer_id', 0);
        $sandboxService = new \app\common\service\platform\PlatformSandboxService();
        $data = $sandboxService->getSandbox($developerId);
        $logs = $sandboxService->getLogs($developerId);
        return $this->view('platform_developer/sandbox', ['sandbox' => $data, 'logs' => $logs, 'developer_id' => $developerId]);
    }
}

/**
 * 开放平台应用控制器
 * V2.9.38 OPEN-PLAT-4
 */
class PlatformAppController extends AdminBaseController
{
    protected PlatformAppMarketService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new PlatformAppMarketService();
    }

    public function index()
    {
        $result = $this->service->getMarketList($this->request->param());
        return $this->view('platform_app/index', $result);
    }

    public function pending()
    {
        $page = (int) $this->request->param('page', 1);
        $result = $this->service->getPendingApps($page);
        return $this->view('platform_app/pending', $result);
    }

    public function audit()
    {
        $appId = (int) $this->request->param('app_id', 0);
        $approved = (bool) $this->request->param('approved', false);
        $remark = $this->request->param('remark', '');
        $this->service->auditApp($appId, $approved, $remark);
        return Json::success($approved ? '审核通过' : '已拒绝');
    }

    public function publish()
    {
        $appId = (int) $this->request->param('app_id', 0);
        $this->service->publishApp($appId);
        return Json::success('已发布');
    }

    public function offline()
    {
        $appId = (int) $this->request->param('app_id', 0);
        $this->service->offlineApp($appId);
        return Json::success('已下架');
    }
}

/**
 * API文档控制器
 * V2.9.38 OPEN-PLAT-3
 */
class ApiDocController extends AdminBaseController
{
    protected ApiDocEnhanceService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new ApiDocEnhanceService();
    }

    public function index()
    {
        $spec = $this->service->generateOpenApiSpec();
        $changelog = $this->service->getChangeLog();
        return $this->view('api_doc/index', ['spec' => $spec, 'changelog' => $changelog]);
    }

    public function swagger()
    {
        $html = $this->service->getSwaggerUi();
        return response($html)->header('Content-Type', 'text/html');
    }

    public function test()
    {
        if ($this->request->isPost()) {
            $method = $this->request->param('method', 'GET');
            $path = $this->request->param('path', '');
            $params = $this->request->param('params', []);
            $apiKey = $this->request->param('api_key', '');
            $apiSecret = $this->request->param('api_secret', '');
            $result = $this->service->testApi($method, $path, $params, $apiKey, $apiSecret);
            return Json::success('ok', $result);
        }
        return $this->view('api_doc/test');
    }

    public function search()
    {
        $keyword = $this->request->param('keyword', '');
        $results = $this->service->searchDocs($keyword);
        return Json::success('ok', ['list' => array_values($results)]);
    }

    public function addChangelog()
    {
        $version = $this->request->param('version', '');
        $title = $this->request->param('title', '');
        $content = $this->request->param('content', '');
        $this->service->addChangeLog($version, $title, $content);
        return Json::success('已添加');
    }
}

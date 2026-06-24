<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\ApiKey;
use app\common\model\ApiLog;
use app\common\service\api\ApiDocGenerator;

/**
 * API密钥管理后台控制器 - V2.9.29 Sprint D-5
 */
class ApiKeyController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $list = ApiKey::order('id', 'desc')->paginate(15);
        $this->assign('list', $list);
        return $this->view('/api_key_list');
    }

    public function create()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $name = $this->request->post('name', '');
        $scopes = $this->request->post('scopes', '');
        $ipWhitelist = $this->request->post('ip_whitelist', '');
        $rateLimit = (int) $this->request->post('rate_limit', 100);

        $apiKey = bin2hex(random_bytes(16));
        $apiSecret = bin2hex(random_bytes(32));

        $key = ApiKey::create([
            'name' => $name,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'scopes' => $scopes,
            'ip_whitelist' => $ipWhitelist,
            'rate_limit' => $rateLimit,
            'is_active' => 1,
        ]);
        return $this->success('创建成功', ['api_key' => $apiKey, 'api_secret' => $apiSecret, 'id' => $key->id]);
    }

    public function revoke(int $id = 0)
    {
        ApiKey::where('id', $id)->update(['is_active' => 0]);
        return $this->success('已吊销');
    }

    public function logs()
    {
        $list = ApiLog::order('id', 'desc')->paginate(20);
        $this->assign('list', $list);
        return $this->view('/api_logs');
    }

    public function doc()
    {
        $generator = new ApiDocGenerator();
        $doc = $generator->generateOpenAPISpec();
        $this->assign('doc', $doc);
        return $this->view('/api_doc');
    }
}

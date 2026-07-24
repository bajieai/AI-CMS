<?php
declare(strict_types=1);

namespace app\api\controller;

use app\api\controller\BaseController;
use app\common\service\template\TemplateOpenApiService;

/**
 * 模板API开放平台 — V2.9.33 DEV-3
 * 公开API(3) + 管理API(5) + 统计API(3) = 11个接口
 */
class TemplateOpenApiController extends BaseController
{
    private TemplateOpenApiService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new TemplateOpenApiService();
    }

    // ===== 公开API（无需认证）=====

    /**
     * 获取模板列表
     */
    public function list()
    {
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        $filter = [
            'category_id' => $this->request->get('category_id', 0),
            'keyword' => $this->request->get('keyword', ''),
        ];

        $result = $this->service->listTemplates($page, $limit, $filter);
        return json(['success' => true, 'data' => $result]);
    }

    /**
     * 获取模板详情
     */
    public function detail(int $id)
    {
        $result = $this->service->getTemplateDetail($id);
        if (!$result) {
            return json(['success' => false, 'message' => '模板不存在']);
        }
        return json(['success' => true, 'data' => $result]);
    }

    /**
     * 获取模板分类
     */
    public function categories()
    {
        $result = $this->service->getCategories();
        return json(['success' => true, 'data' => $result]);
    }

    /**
     * 获取模板排行
     */
    public function ranking()
    {
        $type = $this->request->get('type', 'install');
        $limit = (int) $this->request->get('limit', 10);
        $result = $this->service->getRanking($type, $limit);
        return json(['success' => true, 'data' => $result]);
    }

    // ===== 管理API（需API密钥认证）=====

    /**
     * 验证API密钥
     */
    private function authApp(): ?array
    {
        $appKey = $this->request->header('X-App-Key', '');
        $signature = $this->request->header('X-Signature', '');

        if (empty($appKey) || empty($signature)) {
            return null;
        }

        $app = $this->service->findApp($appKey);
        if (!$app) {
            return null;
        }

        $params = $this->request->param();
        unset($params['signature']);

        if (!$this->service->verifySignature($params, $signature, $app['app_secret'])) {
            return null;
        }

        if (!$this->service->checkRateLimit($appKey)) {
            return null;
        }

        return $app;
    }

    /**
     * 上传模板
     */
    public function upload()
    {
        $app = $this->authApp();
        if (!$app) {
            return json(['success' => false, 'message' => '认证失败或频率超限']);
        }

        $data = $this->request->post();
        $result = $this->service->uploadTemplate($data, $app['member_id'] ?? 0);
        return json($result);
    }

    /**
     * 更新模板
     */
    public function update(int $id)
    {
        $app = $this->authApp();
        if (!$app) {
            return json(['success' => false, 'message' => '认证失败或频率超限']);
        }

        $data = $this->request->post();
        $result = $this->service->updateTemplate($id, $data, $app['member_id'] ?? 0);
        return json($result);
    }

    /**
     * 删除模板
     */
    public function delete(int $id)
    {
        $app = $this->authApp();
        if (!$app) {
            return json(['success' => false, 'message' => '认证失败或频率超限']);
        }

        $result = $this->service->deleteTemplate($id, $app['member_id'] ?? 0);
        return json($result);
    }

    /**
     * 发布新版本
     */
    public function publish(int $id)
    {
        $app = $this->authApp();
        if (!$app) {
            return json(['success' => false, 'message' => '认证失败或频率超限']);
        }

        $version = $this->request->post('version', '');
        $result = $this->service->publishVersion($id, $version, $app['member_id'] ?? 0);
        return json($result);
    }

    // ===== 统计API（需API密钥认证）=====

    /**
     * 安装统计
     */
    public function installStats(int $id)
    {
        $app = $this->authApp();
        if (!$app) {
            return json(['success' => false, 'message' => '认证失败或频率超限']);
        }

        $result = $this->service->getInstallStats($id, $app['member_id'] ?? 0);
        return json(['success' => true, 'data' => $result]);
    }

    /**
     * 评分统计
     */
    public function ratingStats(int $id)
    {
        $app = $this->authApp();
        if (!$app) {
            return json(['success' => false, 'message' => '认证失败或频率超限']);
        }

        $result = $this->service->getRatingStats($id, $app['member_id'] ?? 0);
        return json(['success' => true, 'data' => $result]);
    }

    /**
     * 收入统计
     */
    public function revenueStats(int $id)
    {
        $app = $this->authApp();
        if (!$app) {
            return json(['success' => false, 'message' => '认证失败或频率超限']);
        }

        $result = $this->service->getRevenueStats($id, $app['member_id'] ?? 0);
        return json(['success' => true, 'data' => $result]);
    }
}

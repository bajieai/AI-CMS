<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 COMPLIANCE-1: 隐私合规后台控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\compliance\GdprService;
use app\common\service\compliance\PrivacyPolicyService;
use app\common\service\compliance\CookieConsentService;
use think\App;

/**
 * 隐私合规后台控制器 - V2.9.39 COMPLIANCE-1
 */
class PrivacyController extends AdminBaseController
{
    protected GdprService $gdprService;
    protected PrivacyPolicyService $policyService;
    protected CookieConsentService $cookieService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->gdprService = new GdprService();
        $this->policyService = new PrivacyPolicyService();
        $this->cookieService = new CookieConsentService();
    }

    // ===== GDPR 仪表盘 =====

    /**
     * GDPR 合规仪表盘
     */
    public function index()
    {
        $dashboard = $this->gdprService->getDashboard();
        $stats = $this->cookieService->getConsentStats();

        return $this->view('/privacy/index', [
            'dashboard' => $dashboard,
            'cookie_stats' => $stats,
        ]);
    }

    // ===== 隐私政策管理 =====

    /**
     * 隐私政策列表
     */
    public function policyList()
    {
        $page = (int) $this->request->get('page', 1);
        $result = $this->policyService->getVersionList($page);

        return $this->view('/privacy/policy_list', $result);
    }

    /**
     * 创建隐私政策
     */
    public function policyCreate()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['creator_id'] = session('user_id');
            $result = $this->policyService->create($data);

            if ($result['id']) {
                $this->recordLog('privacy_policy_create', "创建隐私政策 v{$result['version']}");
                return $this->success('创建成功', $result);
            }
            return $this->error('创建失败');
        }

        return $this->view('/privacy/policy_create');
    }

    /**
     * 编辑隐私政策
     */
    public function policyEdit()
    {
        $id = (int) $this->request->get('id', 0);

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->policyService->update($id, $data);

            if ($result) {
                $this->recordLog('privacy_policy_edit', "编辑隐私政策 #{$id}");
                return $this->success('更新成功');
            }
            return $this->error('更新失败');
        }

        $policy = $this->policyService->getDetail($id);
        return $this->view('/privacy/policy_edit', ['policy' => $policy]);
    }

    /**
     * 发布隐私政策
     */
    public function policyPublish()
    {
        $id = (int) $this->request->post('id', 0);
        $result = $this->policyService->publish($id);

        if ($result['success']) {
            $this->recordLog('privacy_policy_publish', "发布隐私政策 v{$result['version']}");
            return $this->success('发布成功', $result);
        }
        return $this->error($result['msg'] ?? '发布失败');
    }

    /**
     * 删除隐私政策
     */
    public function policyDelete()
    {
        $id = (int) $this->request->post('id', 0);
        $result = $this->policyService->delete($id);

        if ($result['success']) {
            $this->recordLog('privacy_policy_delete', "删除隐私政策 #{$id}");
            return $this->success('删除成功');
        }
        return $this->error($result['msg'] ?? '删除失败');
    }

    // ===== 数据主体权利请求 =====

    /**
     * 请求列表
     */
    public function requestList()
    {
        $params = $this->request->get();
        $result = $this->gdprService->getRequestList($params);

        return $this->view('/privacy/request_list', $result);
    }

    /**
     * 请求详情
     */
    public function requestDetail()
    {
        $id = (int) $this->request->get('id', 0);
        $detail = $this->gdprService->getRequestDetail($id);

        return $this->view('/privacy/request_detail', ['detail' => $detail]);
    }

    /**
     * 处理请求
     */
    public function requestProcess()
    {
        $id = (int) $this->request->post('id', 0);
        $action = $this->request->post('action', '');
        $note = $this->request->post('note', '');
        $handlerId = (int) session('user_id');

        $result = $this->gdprService->processRequest($id, $handlerId, $action, $note);

        if ($result['success']) {
            $this->recordLog('privacy_request_process', "处理隐私请求 #{$id} ({$action})");
            return $this->success('处理成功', $result);
        }
        return $this->error($result['msg'] ?? '处理失败');
    }

    // ===== Cookie 同意管理 =====

    /**
     * Cookie分类管理
     */
    public function cookieCategories()
    {
        $categories = $this->cookieService->getCategories();

        return $this->view('/privacy/cookie_categories', ['categories' => $categories]);
    }

    /**
     * Cookie统计
     */
    public function cookieStats()
    {
        $stats = $this->cookieService->getConsentStats();

        return $this->view('/privacy/cookie_stats', ['stats' => $stats]);
    }

    /**
     * 添加Cookie定义
     */
    public function cookieAdd()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $id = $this->cookieService->addCookieDefinition($data);

            if ($id) {
                $this->recordLog('cookie_definition_add', "添加Cookie定义 #{$id}");
                return $this->success('添加成功', ['id' => $id]);
            }
            return $this->error('添加失败');
        }

        $categories = $this->cookieService->getCategories();
        return $this->view('/privacy/cookie_add', ['categories' => $categories]);
    }

    // ===== 用户同意历史 =====

    /**
     * 用户同意历史
     */
    public function consentHistory()
    {
        $userId = (int) $this->request->get('user_id', 0);
        $page = (int) $this->request->get('page', 1);

        $result = $this->gdprService->getConsentHistory($userId, $page);

        return $this->view('/privacy/consent_history', $result);
    }
}

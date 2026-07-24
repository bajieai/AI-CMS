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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\mini\MiniManageService;
use app\common\service\mini\MiniTemplateService;
use think\App;

/**
 * 小程序管理后台
 */
class MiniAppController extends AdminBaseController
{
    protected MiniManageService $manageService;
    protected MiniTemplateService $templateService;

    protected array $noNeedLogin = [];
    protected array $noNeedPermission = [];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->manageService = new MiniManageService();
        $this->templateService = new MiniTemplateService();
    }

    /**
     * 小程序配置
     */
    public function config()
    {
        $configGroups = $this->manageService->getConfigList();
        $this->app->view->assign('configGroups', $configGroups);
        return $this->view('/mini_app/config');
    }

    /**
     * 保存配置
     */
    public function saveConfig(): \think\Response
    {
        $data = $this->request->param();
        // 移除非配置字段
        unset($data['__token__'], $data['s'], $data['_method']);
        $result = $this->manageService->saveConfig($data);
        return $this->success($result['msg'], $result['data']);
    }

    /**
     * 页面列表
     */
    public function pages()
    {
        $pageList = $this->manageService->getPageList();
        $components = $this->templateService->getComponents();
        $this->app->view->assign('pageList', $pageList);
        $this->app->view->assign('components', $components);
        return $this->view('/mini_app/pages');
    }

    /**
     * 保存页面配置
     */
    public function savePage(): \think\Response
    {
        $pageName = $this->request->param('page_name', '');
        $configJson = $this->request->param('config', '');
        $config = json_decode($configJson, true);
        if (!is_array($config)) {
            return $this->error('配置格式错误');
        }
        $result = $this->manageService->savePage($pageName, $config);
        return $this->success($result['msg'], $result['data']);
    }

    /**
     * 发布管理
     */
    public function publish()
    {
        $publishInfo = $this->manageService->getPublishInfo();
        $this->app->view->assign('publishInfo', $publishInfo);
        return $this->view('/mini_app/publish');
    }

    /**
     * 上传代码到微信
     */
    public function uploadCode(): \think\Response
    {
        $result = $this->manageService->uploadCode();
        return $this->success($result['msg'], $result['data']);
    }

    /**
     * 统计数据
     */
    public function stats(): \think\Response
    {
        $days = (int) $this->request->param('days', 30);
        $data = $this->manageService->getStats($days);
        return $this->success('success', $data);
    }
}

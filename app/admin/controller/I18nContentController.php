<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\i18n\I18nContentManageService;
use app\common\service\i18n\MultilingualRouteService;
use app\common\service\i18n\TranslationProjectService;

/**
 * 多语言内容管理后台控制器 - V2.9.40 I18N-V3-3
 */
class I18nContentController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 翻译组列表
     */
    public function index()
    {
        $service = new I18nContentManageService();
        $list = $service->getGroupList($this->request->get('page', 1), $this->request->get('limit', 20));

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
        }

        $this->assign('list', $list);
        return $this->view('/i18n/content_index');
    }

    /**
     * 创建翻译组
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $name = $this->request->post('name', '');
            if (empty($name)) return json(['code' => 1, 'msg' => '请输入组名']);

            $service = new I18nContentManageService();
            $id = $service->createGroup($name, $this->request->post('description', ''));
            return json(['code' => 0, 'msg' => '创建成功', 'data' => ['id' => $id]]);
        }

        return $this->view('/i18n/content_create');
    }

    /**
     * 翻译组详情（所有语言版本）
     */
    public function detail(int $id)
    {
        $service = new I18nContentManageService();
        $versions = $service->getGroupVersions($id);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $versions]);
        }

        $this->assign('versions', $versions);
        $this->assign('group_id', $id);
        return $this->view('/i18n/content_detail');
    }

    /**
     * 关联内容到翻译组
     */
    public function linkContent()
    {
        $groupId = (int) $this->request->post('group_id', 0);
        $contentId = (int) $this->request->post('content_id', 0);
        $lang = $this->request->post('lang', 'zh');

        if ($groupId <= 0 || $contentId <= 0) {
            return json(['code' => 1, 'msg' => '参数不完整']);
        }

        $service = new I18nContentManageService();
        $service->linkContent($groupId, $contentId, $lang);
        return json(['code' => 0, 'msg' => '关联成功']);
    }

    /**
     * 同步更新到翻译版本
     */
    public function sync()
    {
        $groupId = (int) $this->request->post('group_id', 0);
        $fields = $this->request->post('fields', ['title', 'content', 'description']);

        $service = new I18nContentManageService();
        $result = $service->syncToTranslations($groupId, $fields);
        return json($result);
    }

    /**
     * 多语言路由管理
     */
    public function routes()
    {
        $routeService = new MultilingualRouteService();
        $strategy = $routeService->getStrategy();
        $options = $routeService->getStrategyOptions();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => ['strategy' => $strategy, 'options' => $options]]);
        }

        $this->assign('strategy', $strategy);
        $this->assign('options', $options);
        return $this->view('/i18n/route_index');
    }

    /**
     * 更新路由策略
     */
    public function updateRouteStrategy()
    {
        $strategy = $this->request->post('strategy', 'subdirectory');
        $routeService = new MultilingualRouteService();
        $result = $routeService->setStrategy($strategy);
        return json(['code' => $result ? 0 : 1, 'msg' => $result ? '策略已更新' : '无效策略']);
    }
}

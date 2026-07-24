<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplatePromotionActivityService;

/**
 * 模板促销活动管理 — V2.9.33 T5-3
 */
class TemplatePromotionActivityController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 活动列表
     */
    public function index()
    {
        $service = new TemplatePromotionActivityService();
        $data = $service->getList($this->request->get());

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'menuActive' => 'template_promotion_activity',
        ]);

        return $this->view('/template_store/promotion_activity');
    }

    /**
     * 创建/编辑活动
     */
    public function save()
    {
        $data = $this->request->post();
        $id = (int) ($data['id'] ?? 0);

        $service = new TemplatePromotionActivityService();
        $result = $service->save($data, $id);

        if ($result['success']) {
            return $this->success($id > 0 ? '活动已更新' : '活动已创建');
        }
        return $this->error('保存失败');
    }

    /**
     * 终止活动
     */
    public function terminate(int $id)
    {
        $service = new TemplatePromotionActivityService();
        $result = $service->terminate($id);
        return $result['success'] ? $this->success('活动已终止') : $this->error('终止失败');
    }

    /**
     * 活动效果
     */
    public function effect(int $id)
    {
        $service = new TemplatePromotionActivityService();
        $stats = $service->getEffectStats($id);

        $this->assign([
            'stats' => $stats,
            'menuActive' => 'template_promotion_activity',
        ]);

        return $this->view('/template_store/promotion_effect');
    }
}

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
use app\common\service\MemberLevelService;

/**
 * 会员等级管理后台控制器
 */
class MemberLevelController extends AdminBaseController
{
    /**
     * 等级列表
     */
    public function index()
    {
        $list = MemberLevelService::getList();
        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
        }
        $this->assign('list', $list);
        return $this->view('/member_level_index');
    }

    /**
     * 添加等级页面
     */
    public function add()
    {
        return $this->edit(0);
    }

    /**
     * 编辑等级页面
     */
    public function edit(int $id = 0)
    {
        $level = $id ? \app\common\model\MemberLevel::find($id) : null;
        // V2.8: VIP免费阅读范围全局配置
        $vipFreeReadMode = (int) \app\common\service\ConfigService::get('vip_free_read_mode', 0);
        $this->assign([
            'info' => $level,
            'vip_free_read_mode' => $vipFreeReadMode,
        ]);
        return $this->view('/member_level_edit');
    }

    /**
     * 保存等级
     */
    public function save()
    {
        $data = [
            'id'                      => (int) $this->request->post('id', 0),
            'name'                    => $this->request->post('name', ''),
            'min_points'              => (int) $this->request->post('min_points', 0),
            'price'                   => (float) $this->request->post('price', 0),
            'discount'                => (float) $this->request->post('discount', 1.00),
            'points_rate'             => (float) $this->request->post('points_rate', 1.00),
            'daily_ai_quota'          => (int) $this->request->post('daily_ai_quota', 0),
            'exclusive_content_ids'   => $this->parseExclusiveContentIds($this->request->post('exclusive_content_ids', '')),
            'allow_download'          => (int) $this->request->post('allow_download', 0),
            'allow_comment_no_review' => (int) $this->request->post('allow_comment_no_review', 0),
            'icon'                    => $this->request->post('icon', ''),
            'sort'                    => (int) $this->request->post('sort', 0),
            'is_default'              => (int) $this->request->post('is_default', 0),
            'is_vip'                  => (int) $this->request->post('is_vip', 0),
        ];

        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '等级名称不能为空']);
        }

        try {
            $level = MemberLevelService::save($data);
            return json(['code' => 0, 'msg' => '保存成功', 'data' => ['id' => $level->id]]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 删除等级
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            MemberLevelService::delete($id);
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * V2.9.9: 解析专属内容ID列表（逗号分隔转JSON数组）
     */
    protected function parseExclusiveContentIds(string $input): string
    {
        if (empty($input)) {
            return '[]';
        }
        $ids = array_filter(array_map('intval', explode(',', $input)), function ($v) {
            return $v > 0;
        });
        return json_encode(array_values($ids), JSON_UNESCAPED_UNICODE);
    }
}

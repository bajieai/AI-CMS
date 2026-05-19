<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\SeoKeywordService;
use app\common\model\SeoKeyword;
use app\common\model\SeoKeywordGroup;

/**
 * SEO关键词管理后台控制器
 */
class SeoKeywordController extends AdminBaseController
{
    /**
     * 关键词列表
     */
    public function index()
    {
        $groupId = (int) $this->request->get('group_id', 0);
        $keyword = $this->request->get('keyword', '');
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);

        $list = SeoKeywordService::getList($groupId, $keyword, $page, $limit);
        $total = SeoKeyword::count();
        $groups = SeoKeywordService::getGroups();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list, 'count' => $total]);
        }
        $this->assign('list', $list);
        $this->assign('group_id', $groupId);
        $this->assign('groups', $groups);
        return $this->view('/seo_keyword_index');
    }

    public function add()
    {
        return $this->edit(0);
    }

    public function edit(int $id = 0)
    {
        $info = $id ? SeoKeyword::find($id) : null;
        $groups = SeoKeywordService::getGroups();
        $this->assign('info', $info);
        $this->assign('groups', $groups);
        return $this->view('/seo_keyword_edit');
    }

    /**
     * 保存关键词
     */
    public function save()
    {
        $data = [
            'id'            => (int) $this->request->post('id', 0),
            'keyword'       => $this->request->post('keyword', ''),
            'group_id'      => (int) $this->request->post('group_id', 0),
            'search_volume' => (int) $this->request->post('search_volume', 0),
            'difficulty'    => (int) $this->request->post('difficulty', 50),
            'is_sensitive'  => (int) $this->request->post('is_sensitive', 0),
            'status'        => (int) $this->request->post('status', 1),
        ];

        if (empty($data['keyword'])) {
            return json(['code' => 1, 'msg' => '关键词不能为空']);
        }

        if (!empty($data['id'])) {
            $kw = SeoKeyword::find($data['id']);
            if (!$kw) return json(['code' => 1, 'msg' => '关键词不存在']);
            $kw->save($data);
        } else {
            // 检查重复
            if (SeoKeyword::where('keyword', $data['keyword'])->find()) {
                return json(['code' => 1, 'msg' => '关键词已存在']);
            }
            SeoKeyword::create($data);
        }

        return json(['code' => 0, 'msg' => '保存成功']);
    }

    /**
     * 批量导入
     */
    public function import()
    {
        $keywords = $this->request->post('keywords', '');
        $groupId = (int) $this->request->post('group_id', 0);

        if (empty($keywords)) {
            return json(['code' => 1, 'msg' => '请输入关键词']);
        }

        $list = preg_split('/[\n,，]/', $keywords);
        $count = SeoKeywordService::import($list, $groupId);

        return json(['code' => 0, 'msg' => "成功导入{$count}个关键词"]);
    }

    /**
     * 删除关键词
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        $kw = SeoKeyword::find($id);
        if (!$kw) return json(['code' => 1, 'msg' => '关键词不存在']);
        $kw->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    // ==================== 关键词分组管理 ====================

    /**
     * 分组列表
     */
    public function group()
    {
        $list = SeoKeywordGroup::order('sort', 'asc')->select();
        $this->assign('list', $list);
        return $this->view('/seo_keyword_group');
    }

    /**
     * 保存分组
     */
    public function saveGroup()
    {
        $data = [
            'id'   => (int) $this->request->post('id', 0),
            'name' => $this->request->post('name', ''),
            'sort' => (int) $this->request->post('sort', 0),
        ];

        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '分组名称不能为空']);
        }

        if (!empty($data['id'])) {
            $group = SeoKeywordGroup::find($data['id']);
            if (!$group) return json(['code' => 1, 'msg' => '分组不存在']);
            $group->save($data);
        } else {
            SeoKeywordGroup::create($data);
        }

        return json(['code' => 0, 'msg' => '保存成功']);
    }

    /**
     * 删除分组
     */
    public function deleteGroup()
    {
        $id = (int) $this->request->post('id', 0);
        $group = SeoKeywordGroup::find($id);
        if (!$group) return json(['code' => 1, 'msg' => '分组不存在']);

        // 将该分组下的关键词移回未分组
        SeoKeyword::where('group_id', $id)->update(['group_id' => 0]);
        $group->delete();

        return json(['code' => 0, 'msg' => '删除成功，该分组下关键词已移回未分组']);
    }
}

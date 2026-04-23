<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Tag;
use app\common\service\CacheService;
use think\facade\Config as ThinkConfig;

/**
 * 标签管理控制器
 */
class TagController extends AdminBaseController
{
    /**
     * 标签列表
     */
    public function index()
    {
        $list = Tag::order('sort', 'asc')->order('id', 'desc')->paginate(20);

        $this->assign(['list' => $list]);
        return $this->view('/tag_list');
    }

    /**
     * 添加标签
     */
    public function add()
    {
        if ($this->request->isGet()) {
            $this->assign(['info' => null]);
            return $this->view('/tag_edit');
        }

        $data = $this->request->post();
        $tag = new Tag();
        if ($tag->save($data)) {
            $this->recordLog('添加标签', $data['name'] ?? '', $data);
            return $this->success('添加成功', ['redirect' => '/admin/tag/index']);
        }
        return $this->error('添加失败');
    }

    /**
     * 编辑标签
     */
    public function edit(int $id)
    {
        $info = Tag::find($id);
        if (empty($info)) {
            return $this->error('标签不存在');
        }

        if ($this->request->isGet()) {
            $this->assign(['info' => $info]);
            return $this->view('/tag_edit');
        }

        $data = $this->request->post();
        if ($info->save($data)) {
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.tag', 'i8j_tag'));
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
            return $this->success('更新成功');
        }
        return $this->error('更新失败');
    }

    /**
     * 删除标签
     */
    public function delete(int $id)
    {
        $info = Tag::find($id);
        if (empty($info)) {
            return $this->error('标签不存在');
        }

        if ($info->delete()) {
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }
}

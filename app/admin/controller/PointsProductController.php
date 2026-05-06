<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\PointsProduct;

/**
 * 积分商品管理控制器 - V2.6
 */
class PointsProductController extends AdminBaseController
{
    /**
     * 积分商品列表
     */
    public function index()
    {
        $this->app->view->assign('menuActive', 'points_product');
        $list = PointsProduct::order('sort', 'desc')->paginate(20);
        $this->assign('list', $list);
        return $this->view('/points_product_index');
    }

    /**
     * 添加/编辑积分商品
     */
    public function edit(int $id = 0)
    {
        $info = $id ? PointsProduct::find($id) : null;
        $this->assign('info', $info);
        return $this->view('/points_product_edit');
    }

    /**
     * 保存积分商品
     */
    public function save()
    {
        $data = [
            'id' => (int) $this->request->post('id', 0),
            'title' => $this->request->post('title', ''),
            'description' => $this->request->post('description', ''),
            'image' => $this->request->post('image', ''),
            'points' => (int) $this->request->post('points', 0),
            'stock' => (int) $this->request->post('stock', 0),
            'type' => $this->request->post('type', 'virtual'),
            'config' => $this->request->post('config', ''),
            'sort' => (int) $this->request->post('sort', 0),
            'is_enabled' => (int) $this->request->post('is_enabled', 1),
        ];

        if (empty($data['title'])) {
            return json(['code' => 1, 'msg' => '商品名称不能为空']);
        }

        try {
            if (!empty($data['id'])) {
                $model = PointsProduct::find($data['id']);
                if (!$model) throw new \Exception('商品不存在');
            } else {
                $model = new PointsProduct();
                $model->create_time = time();
            }

            $model->title = $data['title'];
            $model->description = $data['description'];
            $model->image = $data['image'];
            $model->points = $data['points'];
            $model->stock = $data['stock'];
            $model->type = $data['type'];
            $model->config = $data['config'];
            $model->sort = $data['sort'];
            $model->is_enabled = $data['is_enabled'];
            $model->update_time = time();
            $model->save();

            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 删除积分商品
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        $model = PointsProduct::find($id);
        if (!$model) return json(['code' => 1, 'msg' => '商品不存在']);

        $model->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }
}

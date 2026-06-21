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
use app\common\model\ContentModel;
use app\common\model\ContentModelTemplateMap;
use app\common\model\TemplateStore;
use app\common\service\admin\ContentModelRecommendService;

/**
 * V2.9.27 S-5: 内容模型-模板映射管理控制器
 */
class ContentModelMapController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $modelId = (int) $this->request->get('model_id', 0);
        $models = ContentModel::where('status', 1)->order('sort', 'asc')->select();

        $query = ContentModelTemplateMap::with(['contentModel', 'template']);
        if ($modelId > 0) {
            $query->where('model_id', $modelId);
        }
        $list = $query->order('model_id', 'asc')
            ->order('is_default', 'desc')
            ->order('priority', 'desc')
            ->paginate(20);

        $this->assign([
            'list' => $list,
            'models' => $models,
            'model_id' => $modelId,
        ]);
        return $this->view('/content_model_map_index');
    }

    public function edit(int $id = 0)
    {
        $info = $id > 0 ? ContentModelTemplateMap::find($id) : null;

        if ($this->request->isGet()) {
            $models = ContentModel::where('status', 1)->order('sort', 'asc')->select();
            $templates = TemplateStore::where('status', 1)->order('id', 'desc')->select();

            $this->assign([
                'info' => $info,
                'models' => $models,
                'templates' => $templates,
            ]);
            return $this->view('/content_model_map_edit');
        }

        $data = $this->request->post();
        ContentModelRecommendService::saveMap($data);
        $this->recordLog($id > 0 ? '编辑模型模板映射' : '添加模型模板映射', '');
        return $this->success('保存成功', ['redirect' => '/admin/content_model_map/index']);
    }

    public function delete(int $id)
    {
        $result = ContentModelRecommendService::deleteMap($id);
        if ($result) {
            $this->recordLog('删除模型模板映射', 'ID:' . $id);
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }

    public function setDefault(int $id)
    {
        $map = ContentModelTemplateMap::find($id);
        if (!$map) {
            return $this->error('映射不存在');
        }
        ContentModelRecommendService::setDefault($map->model_id, $id);
        return $this->success('设置成功');
    }

    public function toggleStatus(int $id)
    {
        $map = ContentModelTemplateMap::find($id);
        if (!$map) {
            return $this->error('映射不存在');
        }
        $map->status = $map->status ? 0 : 1;
        $map->save();
        ContentModelRecommendService::clearCache($map->model_id);
        return $this->success('操作成功', ['status' => $map->status]);
    }
}

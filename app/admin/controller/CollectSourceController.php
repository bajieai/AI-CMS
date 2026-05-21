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
use app\common\model\CollectSource;
use app\common\service\CollectService;

/**
 * 采集源管理后台控制器 - V2.5新增
 */
class CollectSourceController extends AdminBaseController
{
    public function index()
    {
        $list = CollectSource::order('id', 'desc')
            ->paginate(['list_rows' => 20, 'path' => '/admin/collect_source/index']);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list->toArray()]);
        }

        $this->assign('list', $list);
        return $this->view('/collect_source_index');
    }

    public function add()
    {
        return $this->edit(0);
    }

    public function edit(int $id = 0)
    {
        $source = $id ? CollectSource::find($id) : null;
        $this->assign('info', $source);
        return $this->view('/collect_source_edit');
    }

    public function save()
    {
        $data = [
            'id'               => (int) $this->request->post('id', 0),
            'name'             => $this->request->post('name', ''),
            'type'             => $this->request->post('type', 'rss'),
            'url'              => $this->request->post('url', ''),
            'rules'            => $this->request->post('rules', ''),
            'cate_id'          => (int) $this->request->post('cate_id', 0),
            'interval_minutes' => (int) $this->request->post('interval_minutes', 60),
            'is_enabled'       => (int) $this->request->post('is_enabled', 1),
        ];

        if (empty($data['name']) || empty($data['url'])) {
            return json(['code' => 1, 'msg' => '名称和URL不能为空']);
        }

        try {
            if ($data['id'] > 0) {
                $source = CollectSource::find($data['id']);
                if ($source) { $source->save($data); }
            } else {
                unset($data['id']);
                CollectSource::create($data);
            }
            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            CollectSource::destroy($id);
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    public function run()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            $result = CollectService::runCollect($id);
            return json(['code' => 0, 'msg' => "采集完成：新增{$result['added']}篇", 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}

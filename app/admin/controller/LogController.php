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
use app\common\model\Log as LogModel;
use app\common\service\ExportService;
use think\facade\Db;

/**
 * 操作日志控制器
 */
class LogController extends AdminBaseController
{
    /**
     * 日志列表（增强筛选）
     */
    public function index()
    {
        $query = LogModel::with('user');

        // 模块筛选
        if ($this->request->get('module')) {
            $query->where('module', $this->request->get('module'));
        }
        // 操作类型筛选
        if ($this->request->get('action')) {
            $query->where('action', 'like', '%' . $this->request->get('action') . '%');
        }
        // 时间范围筛选
        if ($this->request->get('start_time')) {
            $query->where('create_time', '>=', strtotime($this->request->get('start_time')));
        }
        if ($this->request->get('end_time')) {
            $query->where('create_time', '<=', strtotime($this->request->get('end_time') . ' 23:59:59'));
        }

        $list = $query->order('id', 'desc')->paginate(20, false, ['query' => $this->request->get()]);

        // 获取模块列表（用于筛选下拉）
        $modules = LogModel::group('module')->column('module');

        $this->assign([
            'list' => $list,
            'modules' => $modules,
            'params' => $this->request->get(),
        ]);
        return $this->view('/log_list');
    }

    /**
     * 导出日志
     */
    public function export()
    {
        $query = LogModel::with('user');

        if ($this->request->get('module')) {
            $query->where('module', $this->request->get('module'));
        }
        if ($this->request->get('start_time')) {
            $query->where('create_time', '>=', strtotime($this->request->get('start_time')));
        }
        if ($this->request->get('end_time')) {
            $query->where('create_time', '<=', strtotime($this->request->get('end_time') . ' 23:59:59'));
        }

        $list = $query->order('id', 'desc')->limit(10000)->select()->toArray();

        $headers = ['ID', '用户', '模块', '操作', '操作对象', 'IP', '时间'];
        $rows = [];
        foreach ($list as $item) {
            $rows[] = [
                $item['id'],
                $item['username'] ?? $item['user']['nickname'] ?? '-',
                $item['module'],
                $item['action'],
                $item['target'] ?? '',
                $item['ip'],
                is_numeric($item['create_time']) ? date('Y-m-d H:i:s', (int)$item['create_time']) : date('Y-m-d H:i:s', strtotime($item['create_time'])),
            ];
        }

        $exportService = new ExportService();
        return $exportService->toExcel('操作日志', $headers, $rows);
    }

    /**
     * 清理日志
     */
    public function cleanup()
    {
        $days = (int) $this->request->post('days', 90);
        if ($days < 7) {
            return $this->error('至少保留7天的日志');
        }
        $count = LogModel::where('create_time', '<', time() - 86400 * $days)->delete();
        $this->recordLog('清理日志', "清理{$days}天前的日志，共{$count}条");
        return $this->success("已清理{$count}条日志");
    }
}

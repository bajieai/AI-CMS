<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\SlowQueryService;
use app\common\service\IndexOptimizeService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 PERF-2: 数据库优化控制器
 */
class DbOptimizeController extends AdminBaseController
{
    protected SlowQueryService $slowQueryService;
    protected IndexOptimizeService $indexService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->slowQueryService = new SlowQueryService();
        $this->indexService = new IndexOptimizeService();
    }

    public function index()
    {
        $dbStats = $this->slowQueryService->getDbStats();
        View::assign($dbStats);
        return $this->view('/db_optimize/index');
    }

    public function slowQueries()
    {
        $list = $this->slowQueryService->getTopSlowQueries();
        return json(['code' => 0, 'data' => $list]);
    }

    public function addIndex()
    {
        $table = $this->request->post('table', '');
        $column = $this->request->post('column', '');
        if (empty($table) || empty($column)) {
            return json(['code' => 1, 'msg' => '参数缺失']);
        }
        try {
            \think\facade\Db::execute("ALTER TABLE `{$table}` ADD INDEX `idx_{$column}` (`{$column}`)");
            return json(['code' => 0, 'msg' => '索引添加成功']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '索引添加失败: ' . $e->getMessage()]);
        }
    }
}

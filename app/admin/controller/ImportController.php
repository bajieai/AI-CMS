<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ConfigService;
use app\common\service\CacheService;
use think\facade\Cache;

/**
 * 导入管理后台控制器（对接已有ImportService）
 */
class ImportController extends AdminBaseController
{
    /**
     * 导入页面
     */
    public function index()
    {
        $categories = \app\common\model\Cate::order('sort', 'asc')->column('name', 'id');
        $this->assign('categories', $categories);
        $this->assign('history', []);
        return $this->view('/import_index');
    }

    /**
     * 执行CSV导入
     */
    public function import()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 1, 'msg' => '请选择文件']);
        }

        try {
            $importService = new \app\common\service\ImportService();
            $result = $importService->importCsv($file->getPathname());
            return json(['code' => 0, 'msg' => '导入成功', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}

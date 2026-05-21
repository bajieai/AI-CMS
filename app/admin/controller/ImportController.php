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
        $categories = \app\common\model\Cate::where('status', 1)->order('sort', 'asc')->select();
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
            $user = $this->getCurrentUser();
            $importService = new \app\common\service\ImportService();
            $result = $importService->importCsv($file->getPathname(), (int) ($user['id'] ?? 0));
            return json(['code' => 0, 'msg' => '导入成功', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}

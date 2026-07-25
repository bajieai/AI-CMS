<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplatePackageService;
use app\common\service\template\TemplatePackageValidator;

/**
 * 模板打包导出后台控制器 - V2.9.29 Sprint D-2
 */
class TemplatePackExportController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function export(int $id = 0)
    {
        if ($this->request->isPost()) {
            $templateId = (int) $this->request->post('id', 0);
            $service = new TemplatePackageService();
            $result = $service->export($templateId);
            if ($result) {
                return $this->success('导出成功', $result);
            }
            return $this->error('导出失败');
        }
        $this->assign('id', $id);
        return $this->view('/template_export');
    }

    public function import()
    {
        if ($this->request->isPost()) {
            $file = $this->request->file('package');
            if (!$file) return $this->error('请上传文件');

            $validator = new TemplatePackageValidator();
            $validateResult = $validator->validate($file);
            if (!$validateResult['valid']) {
                return $this->error('校验失败: ' . $validateResult['message']);
            }

            $service = new TemplatePackageService();
            $result = $service->import($file);
            return $result ? $this->success('导入成功') : $this->error('导入失败');
        }
        return $this->view('/template_import');
    }
}

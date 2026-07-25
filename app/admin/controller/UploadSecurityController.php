<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\FileUploadSecurityService;
use app\common\service\VirusScanService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 SEC-4: 文件上传安全控制器
 */
class UploadSecurityController extends AdminBaseController
{
    protected FileUploadSecurityService $uploadSecurityService;
    protected VirusScanService $virusScanService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->uploadSecurityService = new FileUploadSecurityService();
        $this->virusScanService = new VirusScanService();
    }

    /**
     * 上传安全管理页
     */
    public function index()
    {
        $stats = $this->uploadSecurityService->getScanStats();

        View::assign(['stats' => $stats]);

        return $this->view('/upload_security/index');
    }
}

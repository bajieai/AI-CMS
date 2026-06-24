<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiContentDiagnosisService;

/**
 * 内容质量诊断控制器 - V2.9.29 Sprint I-4
 */
class ContentDiagnosisController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function diagnose(int $id = 0)
    {
        $service = new AiContentDiagnosisService();
        $report = $service->diagnose($id);
        $this->assign('report', $report);
        $this->assign('content_id', $id);
        return $this->view('/content_diagnosis_report');
    }
}

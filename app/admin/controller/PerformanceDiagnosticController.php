<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\sys\PerformanceDiagnosticService;

class PerformanceDiagnosticController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $report = PerformanceDiagnosticService::generateReport();
        return $this->view('/sys/performance', compact('report'));
    }

    public function report()
    {
        $report = PerformanceDiagnosticService::generateReport();
        return json(['code' => 0, 'data' => $report]);
    }

    public function suggestions()
    {
        $suggestions = PerformanceDiagnosticService::getSuggestions();
        return json(['code' => 0, 'data' => $suggestions]);
    }
}

<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\operation\OperationAssistantService;

/**
 * AI运营助手控制器 — V2.9.34 OPS2-5
 */
class OperationAssistantController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new OperationAssistantService();
        $suggestions = $service->getSuggestions();
        $smartAlerts = $service->getSmartAlerts();
        $knowledgeBase = $service->getKnowledgeBase();
        $this->assign('suggestions', $suggestions);
        $this->assign('smartAlerts', $smartAlerts);
        $this->assign('knowledgeBase', $knowledgeBase);
        $this->assign('menuActive', 'operation_assistant');
        return $this->view('/operation_assistant/index');
    }

    public function dailyReport()
    {
        $service = new OperationAssistantService();
        $result = $service->generateDailyReport();
        return json($result);
    }

    public function weeklyReport()
    {
        $service = new OperationAssistantService();
        $result = $service->generateWeeklyReport();
        return json($result);
    }

    public function monthlyReport()
    {
        $service = new OperationAssistantService();
        $result = $service->generateMonthlyReport();
        return json($result);
    }

    public function suggestions()
    {
        $service = new OperationAssistantService();
        $result = $service->getSuggestions();
        return json($result);
    }

    public function smartAlerts()
    {
        $service = new OperationAssistantService();
        $result = $service->getSmartAlerts();
        return json($result);
    }

    public function knowledgeBase()
    {
        $service = new OperationAssistantService();
        $result = $service->getKnowledgeBase();
        return json($result);
    }
}

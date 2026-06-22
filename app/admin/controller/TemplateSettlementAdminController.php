<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateSettlementService;

/**
 * 模板结算管理控制器 — V2.9.28 M-7
 */
class TemplateSettlementAdminController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 结算列表（收益明细）
     */
    public function index()
    {
        $params = $this->request->get();
        $developerId = (int)($params['developer_id'] ?? 0);

        $service = new TemplateSettlementService();
        $data = $service->getEarningsDetail($developerId, $params, 20);

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'menuActive' => 'template_settlement_admin',
        ]);

        return $this->view('/template_store/settlement_list');
    }

    /**
     * 结算规则配置
     */
    public function rule()
    {
        $service = new TemplateSettlementService();

        if ($this->request->isPost()) {
            $developerId = (int)$this->request->post('developer_id', 0);
            $data = [
                'commission_rate' => (float)$this->request->post('commission_rate', 30),
                'min_withdraw' => (float)$this->request->post('min_withdraw', 100),
                'settle_cycle' => (int)$this->request->post('settle_cycle', 1),
                'status' => 1,
            ];
            $result = $service->saveSettlementRule($developerId, $data);
            if ($result['success']) {
                $this->recordLog('保存结算规则', "开发者ID:{$developerId}");
                return $this->success($result['message']);
            }
            return $this->error($result['message']);
        }

        $developerId = (int)$this->request->get('developer_id', 0);
        $rule = $developerId > 0 ? \app\common\model\TemplateSettlementRule::where('developer_id', $developerId)->find() : null;

        $this->assign([
            'rule' => $rule,
            'developerId' => $developerId,
            'menuActive' => 'template_settlement_admin',
        ]);

        return $this->view('/template_store/settlement_rule');
    }

    /**
     * 提现列表
     */
    public function withdrawList()
    {
        $params = $this->request->get();
        $service = new TemplateSettlementService();
        $data = $service->getWithdrawList($params, 20);

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'menuActive' => 'template_settlement_admin',
        ]);

        return $this->view('/template_store/withdraw_list');
    }

    /**
     * 审核提现
     */
    public function approveWithdraw(int $id)
    {
        $remark = $this->request->post('admin_remark', '');
        $service = new TemplateSettlementService();
        $result = $service->approveWithdraw($id, $this->adminInfo['id'] ?? 0, $remark);
        if ($result['success']) {
            $this->recordLog('审核提现', "提现ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 确认到账
     */
    public function confirmWithdraw(int $id)
    {
        $service = new TemplateSettlementService();
        $result = $service->confirmWithdraw($id, $this->adminInfo['id'] ?? 0);
        if ($result['success']) {
            $this->recordLog('确认提现到账', "提现ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 驳回提现
     */
    public function rejectWithdraw(int $id)
    {
        $remark = $this->request->post('admin_remark', '');
        if (empty($remark)) return $this->error('请填写驳回原因');

        $service = new TemplateSettlementService();
        $result = $service->rejectWithdraw($id, $this->adminInfo['id'] ?? 0, $remark);
        if ($result['success']) {
            $this->recordLog('驳回提现', "提现ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 月度对账报表
     */
    public function monthlyReport()
    {
        $year = (int)$this->request->get('year', (int)date('Y'));
        $month = (int)$this->request->get('month', (int)date('m'));

        $service = new TemplateSettlementService();
        $report = $service->getMonthlyReport($year, $month);

        $this->assign([
            'report' => $report,
            'menuActive' => 'template_settlement_admin',
        ]);

        return $this->view('/template_store/settlement_report');
    }
}

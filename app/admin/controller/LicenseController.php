<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\License;
use app\common\service\LicenseService;

/**
 * V2.9.4 许可证管理控制器
 */
class LicenseController extends AdminBaseController
{
    /**
     * 许可证列表
     */
    public function index()
    {
        $productType = $this->request->get('product_type', '');
        $status = $this->request->get('status', '');
        $page = (int) $this->request->get('page', 1);

        $filters = array_filter([
            'product_type' => $productType,
            'status' => $status,
        ]);

        $list = LicenseService::getList($filters, $page, 20);

        $this->assign('licenses', $list);
        $this->assign('filters', $filters);

        return $this->view('/license_list');
    }

    /**
     * 手动发放许可证
     */
    public function issue()
    {
        $data = $this->request->post();
        if (empty($data['product_type']) || empty($data['product_code'])) {
            return json(['code' => 1, 'msg' => '产品类型和编码必填']);
        }

        try {
            $license = LicenseService::issue(
                $data['product_type'],
                $data['product_code'],
                (int) ($data['user_id'] ?? 0),
                $data['license_type'] ?? 'standard',
                $data['bind_domain'] ?? '',
                !empty($data['valid_until']) ? strtotime($data['valid_until']) : 0
            );
            return json(['code' => 0, 'msg' => '发放成功', 'data' => ['license_code' => $license->license_code]]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '发放失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 吊销许可证
     */
    public function revoke()
    {
        $id = (int) $this->request->post('id', 0);
        $license = License::find($id);
        if (!$license) {
            return json(['code' => 1, 'msg' => '许可证不存在']);
        }

        try {
            $license->revoke();
            return json(['code' => 0, 'msg' => '已吊销']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 激活许可证
     */
    public function activate()
    {
        $id = (int) $this->request->post('id', 0);
        $license = License::find($id);
        if (!$license) {
            return json(['code' => 1, 'msg' => '许可证不存在']);
        }

        try {
            $license->activate();
            return json(['code' => 0, 'msg' => '已激活']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}

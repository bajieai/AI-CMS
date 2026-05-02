<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ConfigService;
use app\common\service\CacheService;
use think\facade\Cache;

/**
 * 积分规则配置后台控制器
 */
class PointsRuleController extends AdminBaseController
{
    /**
     * 积分规则配置页面
     */
    public function index()
    {
        $rules = ConfigService::getGroup('points');
        if ($this->request->isAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $rules]);
        }
        $this->assign('rules', $rules);
        return $this->view('/points_rule_index');
    }

    /**
     * 保存积分规则
     */
    public function save()
    {
        $rules = $this->request->post('rules', []);
        if (empty($rules) || !is_array($rules)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            foreach ($rules as $key => $value) {
                ConfigService::set($key, (int) $value, 'points');
            }
            Cache::tag(CacheService::TAG_CONFIG)->clear();
            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}

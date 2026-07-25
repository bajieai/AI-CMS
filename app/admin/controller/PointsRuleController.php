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
        if ($this->isRealAjax()) {
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
                // 保留小数支持（如 points_consume_ratio=0.1），整数配置也兼容
                $typedValue = is_numeric($value) && strpos(strval($value), '.') !== false
                    ? (float) $value
                    : (int) $value;
                ConfigService::set($key, $typedValue, 'points');
            }
            Cache::clear();
            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}

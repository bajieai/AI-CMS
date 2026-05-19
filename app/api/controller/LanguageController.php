<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\Language;
use think\facade\Request;
use think\facade\Cache;
use think\facade\Cookie;

/**
 * 多语言前台API - V2.9新增
 */
class LanguageController extends BaseController
{
    /**
     * 获取语言列表
     * GET /api/language
     */
    public function index()
    {
        $cacheKey = 'api_language_list';
        $list = Cache::get($cacheKey);
        if (!$list) {
            $list = Language::where('is_enabled', 1)
                ->order('sort', 'asc')
                ->field('id, code, name, is_default')
                ->select()
                ->toArray();
            Cache::set($cacheKey, $list, 3600);
        }
        return $this->success(['list' => $list]);
    }

    /**
     * 切换当前语言
     * POST /api/language/switch
     */
    public function switch()
    {
        $lang = Request::post('lang', '');
        if (empty($lang)) {
            return $this->error('语言代码不能为空');
        }

        $language = Language::where('code', $lang)->where('is_enabled', 1)->find();
        if (!$language) {
            return $this->error('语言不存在或已禁用');
        }

        Cookie::set('lang', $lang, 86400 * 30);

        return $this->success(['lang' => $lang, 'name' => $language->name], '语言切换成功');
    }

    /**
     * 获取当前语言
     * GET /api/language/current
     */
    public function current()
    {
        $lang = Cookie::get('lang', '');
        if (empty($lang)) {
            $default = Language::where('is_default', 1)->where('is_enabled', 1)->find();
            $lang = $default ? $default->code : 'zh-CN';
        }
        return $this->success(['lang' => $lang]);
    }
}

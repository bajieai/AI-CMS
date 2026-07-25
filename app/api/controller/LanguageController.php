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

namespace app\api\controller;

use app\common\model\Language;
use think\facade\Request;
use think\facade\Cache;
use think\facade\Cookie;

/**
 * 多语言前台API
 * @api_group 多语言
 * @api_desc 语言列表、切换、当前语言查询接口
 */
class LanguageController extends BaseController
{
    /**
     * 获取语言列表
     * @api 语言列表
     * @api_desc 获取系统中已启用的语言列表（缓存1小时）
     * @return json 返回语言列表(code/name/is_default)
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
     * 切换语言
     * @api 切换语言
     * @api_desc 切换当前用户的语言偏好，设置Cookie(30天有效期)
     * @param string $lang 语言代码(如zh-CN/en/ja/ko)
     * @return json 返回切换后的语言信息
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
     * @api 当前语言
     * @api_desc 获取当前用户的语言设置（优先Cookie，回退到默认语言）
     * @return json 返回当前语言代码
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

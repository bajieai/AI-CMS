<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Content;
use app\common\model\TemplateStore;
use think\facade\Db;
use think\facade\Cache;

/**
 * 全局搜索控制器 — V2.9.30 UX-3
 */
class SearchController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function global()
    {
        $keyword = $this->request->post('keyword', '');
        if (mb_strlen($keyword) < 2) {
            return $this->success('请输入至少2个字符', []);
        }

        $cacheKey = 'global_search:' . md5($keyword);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $this->success('搜索成功', $cached);
        }

        $results = [
            'contents' => [],
            'templates' => [],
            'plugins' => [],
        ];

        // 搜索内容
        try {
            $results['contents'] = Content::where('title', 'like', "%{$keyword}%")
                ->field('id, title')
                ->limit(5)
                ->select()->toArray();
        } catch (\Exception $e) {}

        // 搜索模板
        try {
            $results['templates'] = TemplateStore::where('name', 'like', "%{$keyword}%")
                ->field('id, name')
                ->limit(5)
                ->select()->toArray();
        } catch (\Exception $e) {}

        // 搜索插件
        try {
            $results['plugins'] = Db::name('plugin_package')
                ->where('name', 'like', "%{$keyword}%")
                ->field('id, name')
                ->limit(5)
                ->select()->toArray();
        } catch (\Exception $e) {}

        Cache::set($cacheKey, $results, 120);
        return $this->success('搜索成功', $results);
    }
}

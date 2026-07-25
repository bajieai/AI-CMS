<?php
declare(strict_types=1);

namespace app\api\controller\h5;

use think\facade\Cache;
use think\facade\Db;
use think\response\Json;

/**
 * H5首页聚合接口
 */
class HomeController extends H5BaseController
{
    /**
     * 首页聚合接口
     * 合并多个数据源，一次请求返回首页所需全部数据
     */
    public function index(): Json
    {
        $cacheKey = 'h5_home_' . $this->lang;
        $data = Cache::remember($cacheKey, function () {
            return [
                'banners' => $this->getBanners(),
                'recommend' => $this->getRecommend(),
                'hot' => $this->getHot(),
                'categories' => $this->getCategories(),
                'notice' => $this->getNotice(),
            ];
        }, 300);

        return $this->success($data);
    }

    protected function getBanners(): array
    {
        return Db::name('banner')->where('status', 1)->order('sort', 'asc')->limit(5)->select()->toArray();
    }

    protected function getRecommend(): array
    {
        return Db::name('content')->where('status', 1)->where('is_recommend', 1)->order('create_time', 'desc')->limit(10)->field('id,title,cover,description,create_time')->select()->toArray();
    }

    protected function getHot(): array
    {
        return Db::name('content')->where('status', 1)->order('view_count', 'desc')->limit(10)->field('id,title,cover,description,view_count')->select()->toArray();
    }

    protected function getCategories(): array
    {
        return Db::name('category')->where('status', 1)->where('parent_id', 0)->order('sort', 'asc')->field('id,name,icon')->select()->toArray();
    }

    protected function getNotice(): array
    {
        return Db::name('notice')->where('status', 1)->order('create_time', 'desc')->limit(3)->field('id,title,create_time')->select()->toArray();
    }
}

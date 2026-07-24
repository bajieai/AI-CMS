<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint T3: 模板商店前台模板
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\BaseController;
use app\common\service\template\TemplateStoreService;
use app\common\service\template\TemplatePromotionService;
use app\common\service\template\TemplateInstallLogService;
use think\facade\Cache;

/**
 * 前台模板商店控制器 - V2.9.31 T3-3
 * 提供模板浏览、搜索、详情、分类筛选等前台功能
 */
class TemplateStoreController extends BaseController
{
    /**
     * 模板商店首页（列表页）
     */
    public function index()
    {
        $service = new TemplateStoreService();
        $promoService = new TemplatePromotionService();

        $params = $this->request->get();
        $data = $service->getList($params);
        $categories = $service->getCategories();

        // 批量计算优惠价格
        $templateIds = array_column($data['list'], 'id');
        $prices = $promoService->batchCalculatePrices($templateIds);

        // 推荐模板
        $featured = $service->getFeatured(6);
        $featuredIds = array_column($featured, 'id');
        $featuredPrices = $promoService->batchCalculatePrices($featuredIds);

        // 获取热门标签（从tags字段提取）
        $hotTags = $this->getHotTags();

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'pages' => $data['pages'],
            'categories' => $categories,
            'prices' => $prices,
            'featured' => $featured,
            'featured_prices' => $featuredPrices,
            'hot_tags' => $hotTags,
            'params' => $params,
        ]);

        return $this->view('/template_store/index');
    }

    /**
     * 模板详情页
     */
    public function detail($slug = '')
    {
        $service = new TemplateStoreService();
        $promoService = new TemplatePromotionService();
        $logService = new TemplateInstallLogService();

        if (empty($slug)) {
            $slug = $this->request->param('slug', '');
        }
        if (empty($slug)) {
            $id = (int) $this->request->param('id', 0);
            $store = $service->getDetail($id);
        } else {
            $store = $service->getBySlug($slug);
        }

        if (empty($store) || $store->status !== \app\common\model\TemplateStore::STATUS_ONLINE) {
            return $this->error('模板不存在或已下架');
        }

        // 计算优惠价格
        $priceInfo = $promoService->calculatePrice(
            (float) ($store->original_price ?: $store->price),
            $promoService->getActivePromo($store->id)
        );

        // 安装统计
        $installStats = $logService->getStoreStats($store->id);

        // 相关模板（同分类）
        $related = $service->getByCategory((int) $store->category_id, 4);

        // 评论列表
        $reviews = $store->reviews()->audited()->order('id', 'desc')->limit(10)->select();

        $this->assign([
            'store' => $store,
            'price_info' => $priceInfo,
            'install_stats' => $installStats,
            'related' => $related,
            'reviews' => $reviews,
        ]);

        return $this->view('/template_store/detail');
    }

    /**
     * 高级搜索页
     */
    public function search()
    {
        $service = new TemplateStoreService();
        $promoService = new TemplatePromotionService();

        $conditions = $this->request->get();
        $result = $service->advancedSearch($conditions);

        $templateIds = array_column($result['list'], 'id');
        $prices = $promoService->batchCalculatePrices($templateIds);

        $this->assign([
            'list' => $result['list'],
            'total' => $result['total'],
            'page' => $result['page'],
            'page_size' => $result['page_size'],
            'prices' => $prices,
            'conditions' => $conditions,
        ]);

        return $this->view('/template_store/search');
    }

    /**
     * AJAX 获取模板列表（用于筛选/分页）
     */
    public function ajaxList()
    {
        $service = new TemplateStoreService();
        $promoService = new TemplatePromotionService();

        $params = $this->request->get();
        $data = $service->getList($params);

        $templateIds = array_column($data['list'], 'id');
        $prices = $promoService->batchCalculatePrices($templateIds);

        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'list' => $data['list'],
                'total' => $data['total'],
                'page' => $data['page'],
                'pages' => $data['pages'],
                'prices' => $prices,
            ],
        ]);
    }

    /**
     * 获取热门标签
     */
    private function getHotTags(int $limit = 15): array
    {
        $cacheKey = 'template_store_hot_tags';
        $tags = Cache::get($cacheKey);
        if ($tags !== null) {
            return $tags;
        }

        $stores = \app\common\model\TemplateStore::online()
            ->where('tags', '<>', '')
            ->field('tags')
            ->limit(200)
            ->select()
            ->toArray();

        $tagCount = [];
        foreach ($stores as $s) {
            $arr = array_filter(array_map('trim', explode(',', $s['tags'])));
            foreach ($arr as $t) {
                $tagCount[$t] = ($tagCount[$t] ?? 0) + 1;
            }
        }
        arsort($tagCount);
        $tags = array_slice(array_keys($tagCount), 0, $limit);

        Cache::set($cacheKey, $tags, 3600);
        return $tags;
    }
}

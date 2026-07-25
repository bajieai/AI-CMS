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

namespace app\common\service\mini;

use think\facade\Cache;
use think\facade\Db;

/**
 * MINI-2 内容输出
 * 小程序内容列表/详情/搜索/分类/标签/推荐/热门/关联
 */
class MiniContentService
{
    protected const TAG = 'mini_content';

    /**
     * 内容列表 (游标分页)
     */
    public function getContentList(int $modelId = 0, int $categoryId = 0, int $page = 1, int $limit = 20): array
    {
        $cacheKey = sprintf('mini:list:%d:%d:%d:%d', $modelId, $categoryId, $page, $limit);

        return Cache::remember($cacheKey, function () use ($modelId, $categoryId, $page, $limit) {
            $query = Db::name('content')
                ->where('status', 1)
                ->where('delete_time', 0)
                ->field('id,title,seo_title,seo_description,thumb,author,views,likes,create_time,model_id,cate_id');

            if ($modelId > 0) {
                $query->where('model_id', $modelId);
            }
            if ($categoryId > 0) {
                $query->where('cate_id', $categoryId);
            }

            $total = $query->count();
            $list = $query->order('id', 'desc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            // 追加分类名称
            if (!empty($list)) {
                $cateIds = array_unique(array_column($list, 'cate_id'));
                $cateMap = Db::name('cate')->whereIn('id', $cateIds)->column('name', 'id');
                foreach ($list as &$item) {
                    $item['cate_name'] = $cateMap[$item['cate_id']] ?? '';
                    $item['thumb'] = $this->fixUrl($item['thumb'] ?? '');
                }
            }

            return [
                'list'  => $list,
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
                'has_more' => ($page * $limit) < $total,
            ];
        }, 300);
    }

    /**
     * 内容详情
     */
    public function getContentDetail(int $id): array
    {
        $cacheKey = 'mini:detail:' . $id;

        return Cache::remember($cacheKey, function () use ($id) {
            $content = Db::name('content')
                ->where('id', $id)
                ->where('status', 1)
                ->where('delete_time', 0)
                ->find();

            if (!$content) {
                return null;
            }

            $content['thumb'] = $this->fixUrl($content['thumb'] ?? '');
            $content['content_wxml'] = $this->htmlToWxml($content['content'] ?? '');
            $content['tags'] = $this->getContentTags($id);

            // 分类信息
            $cate = Db::name('cate')->where('id', $content['cate_id'])->find();
            $content['cate_name'] = $cate['name'] ?? '';

            return $content;
        }, 600);
    }

    /**
     * 搜索内容
     */
    public function searchContents(string $keyword, int $page = 1): array
    {
        $cacheKey = 'mini:search:' . md5($keyword) . ':' . $page;

        return Cache::remember($cacheKey, function () use ($keyword, $page) {
            $limit = 20;
            $query = Db::name('content')
                ->where('status', 1)
                ->where('delete_time', 0)
                ->whereLike('title', '%' . $keyword . '%');

            $total = $query->count();
            $list = $query->field('id,title,thumb,author,views,create_time')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                $item['thumb'] = $this->fixUrl($item['thumb'] ?? '');
            }

            return [
                'list'     => $list,
                'total'    => $total,
                'page'     => $page,
                'keyword'  => $keyword,
                'has_more' => ($page * $limit) < $total,
            ];
        }, 300);
    }

    /**
     * 获取分类列表
     */
    public function getCategories(int $modelId = 0): array
    {
        $cacheKey = 'mini:categories:' . $modelId;

        return Cache::remember($cacheKey, function () use ($modelId) {
            $query = Db::name('cate')->where('status', 1);
            if ($modelId > 0) {
                $query->where('model_id', $modelId);
            }
            return $query->field('id,name,pid,seo_title')
                ->order('sort', 'asc')
                ->select()
                ->toArray();
        }, 1800);
    }

    /**
     * 获取标签列表
     */
    public function getTags(): array
    {
        return Cache::remember('mini:tags', function () {
            return Db::name('tag')
                ->where('status', 1)
                ->field('id,name')
                ->order('count', 'desc')
                ->limit(100)
                ->select()
                ->toArray();
        }, 1800);
    }

    /**
     * 推荐内容
     */
    public function getRecommend(int $modelId = 0, int $limit = 10): array
    {
        $cacheKey = 'mini:recommend:' . $modelId . ':' . $limit;

        return Cache::remember($cacheKey, function () use ($modelId, $limit) {
            $query = Db::name('content')
                ->where('status', 1)
                ->where('delete_time', 0)
                ->where('is_recommend', 1);

            if ($modelId > 0) {
                $query->where('model_id', $modelId);
            }

            $list = $query->field('id,title,thumb,views,likes,create_time')
                ->order('id', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                $item['thumb'] = $this->fixUrl($item['thumb'] ?? '');
            }

            return $list;
        }, 300);
    }

    /**
     * 热门内容
     */
    public function getHot(int $modelId = 0, int $limit = 10): array
    {
        $cacheKey = 'mini:hot:' . $modelId . ':' . $limit;

        return Cache::remember($cacheKey, function () use ($modelId, $limit) {
            $query = Db::name('content')
                ->where('status', 1)
                ->where('delete_time', 0);

            if ($modelId > 0) {
                $query->where('model_id', $modelId);
            }

            $list = $query->field('id,title,thumb,views,likes,create_time')
                ->order('views', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                $item['thumb'] = $this->fixUrl($item['thumb'] ?? '');
            }

            return $list;
        }, 300);
    }

    /**
     * 相关内容
     */
    public function getRelated(int $contentId, int $limit = 5): array
    {
        $cacheKey = 'mini:related:' . $contentId . ':' . $limit;

        return Cache::remember($cacheKey, function () use ($contentId, $limit) {
            $content = Db::name('content')->where('id', $contentId)->find();
            if (!$content) {
                return [];
            }

            $list = Db::name('content')
                ->where('status', 1)
                ->where('delete_time', 0)
                ->where('id', '<>', $contentId)
                ->where('cate_id', $content['cate_id'])
                ->field('id,title,thumb,views,create_time')
                ->order('id', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                $item['thumb'] = $this->fixUrl($item['thumb'] ?? '');
            }

            return $list;
        }, 300);
    }

    /**
     * 获取自定义字段
     */
    public function getCustomFields(int $contentId): array
    {
        $cacheKey = 'mini:fields:' . $contentId;

        return Cache::remember($cacheKey, function () use ($contentId) {
            $content = Db::name('content')->where('id', $contentId)->find();
            if (!$content || empty($content['ext_data'])) {
                return [];
            }

            $extData = json_decode($content['ext_data'], true);
            if (!is_array($extData)) {
                return [];
            }

            return $extData;
        }, 600);
    }

    /**
     * HTML转WXML标签
     * 将富文本HTML转换为小程序rich-text支持的标签格式
     */
    public function htmlToWxml(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // 移除script和style
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

        // 修复img标签: 补全域名+移除style属性
        $html = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', function ($matches) {
            $src = $this->fixUrl($matches[1]);
            return '<img src="' . $src . '" />';
        }, $html);

        // 移除不支持的属性 (保留class)
        $html = preg_replace('/\s+(on\w+|style|target|data-[\w-]+)=["\'][^"\']*["\']/i', '', $html);

        // 转换a标签为span (rich-text不支持a)
        $html = preg_replace('/<a\s/i', '<span ', $html);
        $html = preg_replace('/<\/a>/i', '</span>', $html);

        return $html;
    }

    /**
     * 获取内容标签
     */
    protected function getContentTags(int $contentId): array
    {
        $tagIds = Db::name('content_tag')->where('content_id', $contentId)->column('tag_id');
        if (empty($tagIds)) {
            return [];
        }
        return Db::name('tag')->whereIn('id', $tagIds)->column('name', 'id');
    }

    /**
     * 修复URL (补全域名)
     */
    protected function fixUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        $domain = Cache::get('cms_site_domain', '');
        if (empty($domain)) {
            $domain = request()->domain();
        }
        return rtrim($domain, '/') . '/' . ltrim($url, '/');
    }
}

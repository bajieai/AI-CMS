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

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Cate;
use app\common\model\Content;
use app\common\service\CateService;
use app\common\service\seo\SchemaMarkupService;
use app\home\service\ListRenderService;

/**
 * 前台分类控制器
 */
class CateController extends FrontBaseController
{
    /**
     * 分类列表页
     * 路由：/product, /info 等（通过append传入type参数）
     */
    public function listing(int $forceCateId = 0)
    {
        $typeSlug = $this->request->param('type', 'product');
        $typeMap = ['product' => 1, 'case' => 2, 'info' => 3, 'download' => 4, 'job' => 5, 'page' => 6];
        $type = $typeMap[$typeSlug] ?? 1;
        $cateId = $forceCateId > 0 ? $forceCateId : (int) $this->request->param('cate_id', 0);

        // V2.9.44: 单段路由入口 listingBySlug 传入 forceCateId 时，
        // 从分类数据覆盖 type/typeSlug（因为 param('type') 默认是 product）
        if ($forceCateId > 0) {
            $forceCate = Cate::find($forceCateId);
            if ($forceCate) {
                $type = (int) $forceCate->type;
                $typeSlugMap = [1 => 'product', 2 => 'case', 3 => 'info', 4 => 'download', 5 => 'job', 6 => 'page'];
                $typeSlug = $typeSlugMap[$type] ?? 'info';
            }
        }

        // V2.9.44: 如果 cate_id 为 0 但有 seoUrl 路由参数，通过 seoUrl 反查分类ID
        // 注意：路由参数名是 seoUrl（驼峰），不是 seo_url（下划线）
        if ($cateId === 0) {
            $seoUrl = $this->request->param('seoUrl', '');
            if (!empty($seoUrl)) {
                $seoCate = Cate::where('seo_url', $seoUrl)->where('type', $type)->where('status', 1)->find();
                if ($seoCate && !$seoCate->isEmpty()) {
                    $cateId = (int) $seoCate->id;
                }
            }
        }

        // V2.9.42: 单页类型不再有列表页概念，/page 展示单页分类卡片
        // /page?cate_id=X 重定向到 /page/X
        if ($type === 6) {
            if ($cateId > 0) {
                return redirect('/page/' . $cateId, 301);
            }

            $cateService = new CateService();
            $cateList = $cateService->getCatelist($typeSlug, 100, 0);
            $cates = $cateService->getTree($cateList->toArray());

            $pageCates = Cate::where('type', 6)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select();

            $schemaService = new SchemaMarkupService();
            $breadcrumbs = [
                ['name' => '首页', 'url' => request()->domain()],
                ['name' => '单页', 'url' => request()->url(true)],
            ];
            $breadcrumbSchema = $schemaService->generateBreadcrumb($breadcrumbs);
            $webPageSchema = $schemaService->generateWebPage([
                'title'       => '单页',
                'description' => '',
                'url'         => request()->url(true),
            ]);
            $schemaMarkup = $schemaService->toJsonLd([$breadcrumbSchema, $webPageSchema]);

            $this->assign([
                'type' => $type,
                'cate_id' => $cateId,
                'cates' => $cates,
                'cate_tree_html' => $this->renderCateTree($cates, $typeSlug, $cateId),
                'page_cates' => $pageCates,
                'type_slug' => $typeSlug,
                'current_cate' => null,
                'schema_markup' => $schemaMarkup,
            ]);

            $template = ListRenderService::resolveTemplate('/list', null);
            return $this->view($template);
        }

        // 获取分类列表（树形结构）
        $cateService = new CateService();
        $cateList = $cateService->getCatelist($typeSlug, 100, 0);
        $cates = $cateService->getTree($cateList->toArray());

        // 获取内容列表（预加载cate用于URL生成）
        $query = Content::with('cate')->where('status', 2)->where('type', $type);
        if ($cateId > 0) {
            $query->where('cate_id', $cateId);
        }
        // V2.9.44: 支持URL参数 pagesize 自定义每页条数，默认12
        $pageSize = (int) $this->request->param('pagesize', 12);
        $pageSize = max(1, min(100, $pageSize)); // 限制1-100
        $list = $query->order('id', 'desc')->paginate($pageSize);

        // 获取当前分类SEO信息
        $currentCate = null;
        if ($cateId > 0) {
            $currentCate = Cate::find($cateId);
        }

        // V2.9.15: 栏目页 Schema.org 结构化标记 (BreadcrumbList + WebPage)
        $schemaService = new SchemaMarkupService();
        $breadcrumbs = [['name' => '首页', 'url' => request()->domain()]];
        if ($currentCate) {
            $breadcrumbs[] = ['name' => $currentCate->name, 'url' => request()->url(true)];
        }
        $breadcrumbSchema = $schemaService->generateBreadcrumb($breadcrumbs);
        $webPageSchema = $schemaService->generateWebPage([
            'title'       => $currentCate ? $currentCate->name : '内容列表',
            'description' => $currentCate ? ($currentCate->description ?: '') : '',
            'url'         => request()->url(true),
        ]);
        $schemaMarkup = $schemaService->toJsonLd([$breadcrumbSchema, $webPageSchema]);

        $this->assign([
            'type' => $type,
            'cate_id' => $cateId,
            'cates' => $cates,
            'cate_tree_html' => $this->renderCateTree($cates, $typeSlug, $cateId),
            'list' => $list,
            'page_cates' => null,
            'type_slug' => $typeSlug,
            'current_cate' => $currentCate,
            'schema_markup' => $schemaMarkup,
        ]);

        $template = ListRenderService::resolveTemplate('/list', $currentCate);
        return $this->view($template);
    }

    /**
     * 递归渲染分类树HTML（避免模板递归include导致编译死循环）
     */
    protected function renderCateTree(array $cates, string $typeSlug, int $cateId): string
    {
        $html = '';
        foreach ($cates as $cate) {
            $hasChildren = !empty($cate['children']);
            $isActive = ($cateId == $cate['id']);
            $isParentActive = false;
            if ($hasChildren && !$isActive) {
                foreach ($cate['children'] as $child) {
                    if ($cateId == $child['id']) {
                        $isParentActive = true;
                        break;
                    }
                }
            }
            $showChildren = $isActive || $isParentActive;
            $padding = ($cate['level'] * 1.2 + 1);
            // V2.9.44: 优先使用单段URL /{seo_url}，无 seo_url 时回退到 ?cate_id=X
            $cateUrl = '';
            if (!empty($cate['seo_url'])) {
                $cateUrl = '/' . $cate['seo_url'];
            } else {
                $cateUrl = '/' . $typeSlug . '?cate_id=' . $cate['id'];
            }
            $html .= '<a href="' . $cateUrl . '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ' . ($isActive ? 'active' : '') . '" style="padding-left:' . $padding . 'rem">';
            $html .= '<span>' . htmlspecialchars((string) $cate['name']) . '</span>';
            if ($hasChildren) {
                $html .= '<i class="bi bi-chevron-' . ($showChildren ? 'down' : 'right') . ' small cate-toggle" data-target="cate-children-' . $cate['id'] . '"></i>';
            }
            $html .= '</a>';
            if ($hasChildren) {
                $html .= '<div class="cate-children" id="cate-children-' . $cate['id'] . '" style="' . (!$showChildren ? 'display:none' : '') . '">';
                $html .= $this->renderCateTree($cate['children'], $typeSlug, $cateId);
                $html .= '</div>';
            }
        }
        return $html;
    }

    /**
     * V2.9.42: 单页面直达展示
     * 路由：/page/{cateId}
     * 直接展示单页分类关联的content内容，跳过列表页
     */
    public function singlePage(int $cateId)
    {
        $cate = Cate::find($cateId);
        if (empty($cate) || $cate->status != 1) {
            abort(404, '页面不存在');
        }

        // 如果不是单页类型，跳转到列表页
        if ($cate->type != 6) {
            return redirect('/page?cate_id=' . $cateId);
        }

        // 获取关联的content记录
        // V2.9.42 修复：直接通过 cate_id+type=6 查找，避免 OPcache 缓存导致 content_id 读取异常
        $content = Content::where('cate_id', $cateId)
            ->where('type', 6)
            ->where('status', 2)
            ->find();

        // 如果 cate_id 方式没找到，尝试通过 content_id 查找（兼容旧数据）
        if (!$content || $content->isEmpty()) {
            if ($cate->content_id > 0) {
                $content = Content::where('id', $cate->content_id)
                    ->where('status', 2)
                    ->find();
            }
        }

        // V2.9.15: Schema.org 结构化标记
        $schemaService = new SchemaMarkupService();
        $breadcrumbs = [
            ['name' => '首页', 'url' => request()->domain()],
            ['name' => $cate->name, 'url' => request()->url(true)],
        ];
        $breadcrumbSchema = $schemaService->generateBreadcrumb($breadcrumbs);
        $webPageSchema = $schemaService->generateWebPage([
            'title'       => $cate->seo_title ?: $cate->name,
            'description' => $cate->seo_description ?: '',
            'url'         => request()->url(true),
        ]);
        $schemaMarkup = $schemaService->toJsonLd([$breadcrumbSchema, $webPageSchema]);

        // SEO数据
        $seoTitle = $cate->seo_title ?: $cate->name;
        $seoKeywords = $cate->seo_keywords ?: '';
        $seoDescription = $cate->seo_description ?: '';

        // V2.9.42 直接查 DB 获取 name（绕过模型可能的获取器覆盖）
        $cateName = \think\facade\Db::name('cate')->where('id', $cateId)->value('name');
        $contentTitle = $content->title ?? '';

        $this->assign([
            'cate'           => $cate,
            'content'        => $content,
            'cate_name_raw'  => $cateName,
            'page_content'   => ($content && !$content->isEmpty()) ? $content->content : '',
            'seo_title'      => $seoTitle,
            'seo_keywords'   => $seoKeywords,
            'seo_description'=> $seoDescription,
            'schema_markup'  => $schemaMarkup,
        ]);

        return $this->view('/single_page');
    }

    /**
     * V2.9.43: 单页面英文URL直达展示
     * 路由：/page/{seoUrl}（如 /page/about）
     * 通过英文别名查找分类后复用 singlePage()
     */
    public function singlePageBySlug(string $seoUrl)
    {
        $cate = Cate::where('seo_url', $seoUrl)->where('status', 1)->find();
        if (!$cate || $cate->isEmpty()) {
            abort(404, '页面不存在');
        }
        return $this->singlePage($cate->id);
    }

    /**
     * V2.9.44: 单段URL路由统一入口 /{seoUrl}
     * 通过 seoUrl 反查分类，根据分类类型自动分发：
     *   - 单页(type=6) → singlePage()
     *   - 其他类型 → listing()
     */
    public function dispatch()
    {
        $seoUrl = $this->request->param('seoUrl', '');
        if (empty($seoUrl)) {
            abort(404, '页面不存在');
        }

        $cate = Cate::where('seo_url', $seoUrl)->where('status', 1)->find();
        if (!$cate || $cate->isEmpty()) {
            abort(404, '页面不存在');
        }

        // 单页类型直接调用 singlePage
        if ($cate->type == 6) {
            return $this->singlePage($cate->id);
        }

        // 其他类型调用 listing
        return $this->listing($cate->id);
    }

    /**
     * V2.9.44: 旧版两段URL路由入口 /{type}/{seoUrl}
     * 兼容旧版 /info/news /product/products 等
     * 如果分类有 seo_url，301重定向到规范的单段URL
     */
    public function listingBySlug(string $type = '')
    {
        $seoUrl = $this->request->param('seoUrl', '');
        if (empty($seoUrl)) {
            $seoUrl = $this->request->param('seo_url', '');
        }
        if (empty($seoUrl)) {
            abort(404, '页面不存在');
        }

        $cate = Cate::where('seo_url', $seoUrl)->where('status', 1)->find();
        if (!$cate || $cate->isEmpty()) {
            abort(404, '分类不存在');
        }

        // 301重定向到规范的单段URL
        return redirect('/' . $seoUrl, 301);
    }
}

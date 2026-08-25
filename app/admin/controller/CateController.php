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
use app\common\model\Cate;
use app\common\model\Content;
use app\common\model\ContentModel;
use app\common\support\ContentTypeMap;
use app\common\service\CacheService;
use app\common\service\CateService;
use think\facade\Config as ThinkConfig;

/**
 * 分类管理控制器
 */
class CateController extends AdminBaseController
{
    /**
     * 分类列表
     */
    public function index()
    {
        $list = Cate::order('sort', 'asc')->order('id', 'asc')->select();
        $service = new CateService();
        $tree = $service->getTree($list->toArray());
        // 注入预览链接及统一内容类型名称，供后台分类列表使用
        $tree = $this->injectCateListMeta($tree);

        $this->assign(['list' => $tree]);
        return $this->view('/cate_list');
    }

    /**
     * V2.9.44: 递归注入分类URL到树形结构
     * V2.9.44: 使用单段URL /{seoUrl}（去掉模型前缀）
     */
    private function injectCateListMeta(array $tree): array
    {
        $typeNames = ContentTypeMap::categoryNameByType();
        $typeColors = ContentTypeMap::badgeColorByType();
        foreach ($tree as &$item) {
            $item['type_name'] = $typeNames[(int) $item['type']] ?? '未知';
            $item['type_color'] = $typeColors[(int) $item['type']] ?? 'bg-secondary';
        }
        unset($item);

        return $this->injectCateUrl($tree);
    }

    private function injectCateUrl(array $tree): array
    {
        $typeMap = ContentTypeMap::slugByType();
        $pageType = ContentTypeMap::pageType();
        foreach ($tree as &$item) {
            $typeSlug = $typeMap[(int) $item['type']] ?? 'info';
            $seoUrl = $item['seo_url'] ?? '';
            if ((int) $item['type'] === $pageType) {
                // 单页：有英文名用 /about，无英文名用 /page/6
                $item['url'] = !empty($seoUrl) ? "/{$seoUrl}" : "/page/{$item['id']}";
            } elseif (!empty($seoUrl)) {
                // 有英文名用单段URL /news /products
                $item['url'] = "/{$seoUrl}";
            } else {
                // 无英文名回退到旧格式
                $item['url'] = "/{$typeSlug}?cate_id={$item['id']}";
            }
            if (!empty($item['children'])) {
                $item['children'] = $this->injectCateUrl($item['children']);
            }
        }
        return $tree;
    }

    /**
     * 添加分类
     */
    public function add()
    {
        if ($this->request->isGet()) {
            $cates = Cate::where('status', 1)->select();
            $service = new CateService();
            $tree = $service->getTree($cates->toArray());

            // 动态读取内容模型列表，用于分类类型下拉选项
            $models = ContentModel::where('is_deleted', 0)
                ->where('is_enabled', 1)
                ->order('sort', 'asc')
                ->field('id, type, model_name, model_description, model_icon')
                ->select();

            $this->assign([
                'cates' => $tree,
                'info' => null,
                'page_content' => '',
                'available_templates' => $this->scanTemplates(),
                'models' => $models,
            ]);
            return $this->view('/cate_edit');
        }

        $data = $this->request->post();
        // V2.9.43: seo_url 格式校验 + 唯一性校验
        $seoUrl = trim($data['seo_url'] ?? '');
        if (!empty($seoUrl)) {
            if (!preg_match('/^[a-zA-Z0-9\-]+$/', $seoUrl)) {
                return $this->error('英文URL别名只能包含英文字母、数字和短横线');
            }
            $exist = Cate::where('seo_url', $seoUrl)->find();
            if ($exist) {
                return $this->error('英文URL别名已存在，请更换');
            }
        }
        $data['seo_url'] = $seoUrl;
        $cate = new Cate();
        if ($cate->save($data)) {
            $this->recordLog('添加分类', $data['name'] ?? '', $data);
            // V2.9.42: 单页分类自动创建content记录
            $this->syncPageContent($cate, $data);
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.cate', 'cms_cate'));
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'cms_content'));
            return $this->success('添加成功', ['redirect' => '/admin/cate/index']);
        }
        return $this->error('添加失败');
    }

    /**
     * 编辑分类
     */
    public function edit(int $id)
    {
        $info = Cate::find($id);
        if (empty($info)) {
            return $this->error('分类不存在');
        }

        if ($this->request->isGet()) {
            $cates = Cate::where('status', 1)->where('id', '<>', $id)->select();
            $service = new CateService();
            $tree = $service->getTree($cates->toArray());

            // V2.9.42: 单页分类注入关联的内容正文
            $pageContent = '';
            if ((int) $info->type === ContentTypeMap::pageType() && $info->content_id > 0) {
                $pageContent = Content::where('id', $info->content_id)->value('content') ?? '';
            }

            // 动态读取内容模型列表，用于分类类型下拉选项
            $models = ContentModel::where('is_deleted', 0)
                ->where('is_enabled', 1)
                ->order('sort', 'asc')
                ->field('id, type, model_name, model_description, model_icon')
                ->select();

            $this->assign([
                'cates' => $tree,
                'info' => $info,
                'page_content' => $pageContent,
                'available_templates' => $this->scanTemplates(),
                'models' => $models,
            ]);
            return $this->view('/cate_edit');
        }

        $data = $this->request->post();
        // V2.9.43: seo_url 格式校验 + 唯一性校验（排除自身）
        $seoUrl = trim($data['seo_url'] ?? '');
        if (!empty($seoUrl)) {
            if (!preg_match('/^[a-zA-Z0-9\-]+$/', $seoUrl)) {
                return $this->error('英文URL别名只能包含英文字母、数字和短横线');
            }
            $exist = Cate::where('seo_url', $seoUrl)->where('id', '<>', $id)->find();
            if ($exist) {
                return $this->error('英文URL别名已存在，请更换');
            }
        }
        $data['seo_url'] = $seoUrl;

        // V2.9.47: 分类绑定模型的兜底——确保 type 与 model_id 一致。
        // 兼容旧版仅提交 type 的场景：若 model_id 缺失或不属于当前 type，自动取该 type 的默认模型。
        $type = (int) ($data['type'] ?? $info->type ?? 0);
        $modelId = (int) ($data['model_id'] ?? 0);
        if ($modelId <= 0) {
            // 未提交 model_id：沿用当前分类已绑定的（需校验是否属于新 type）
            $modelId = (int) ($info->model_id ?? 0);
        }
        $modelValid = $modelId > 0
            && ContentModel::where('id', $modelId)->where('type', $type)->count() > 0;
        if (!$modelValid) {
            $defaultModel = ContentModel::getDefaultByType($type);
            if ($defaultModel) {
                $modelId = (int) $defaultModel->id;
            }
        }
        $data['model_id'] = $modelId;
        // 同步写 content 表冗余的 model_id 一致化
        if ($type > 0) {
            $data['type'] = $type;
        }

        if ($info->save($data)) {
            $this->recordLog('编辑分类', $info->name ?? '', $data);
            // V2.9.42: 单页分类自动同步content记录
            $this->syncPageContent($info, $data);
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.cate', 'cms_cate'));
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'cms_content'));
            return $this->success('更新成功');
        }
        return $this->error('更新失败');
    }

    /**
     * 删除分类
     */
    public function delete(int $id)
    {
        $info = Cate::find($id);
        if (empty($info)) {
            return $this->error('分类不存在');
        }

        // 检查是否有子分类
        $childCount = Cate::where('parent_id', $id)->count();
        if ($childCount > 0) {
            return $this->error('该分类下有子分类，无法删除');
        }

        if ($info->delete()) {
            // V2.9.42: 单页分类删除时同步删除关联的content记录
            if ((int) $info->type === ContentTypeMap::pageType() && $info->content_id > 0) {
                Content::where('id', $info->content_id)->where('type', ContentTypeMap::pageType())->delete();
            }
            $this->recordLog('删除分类', $info->name ?? '');
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.cate', 'cms_cate'));
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'cms_content'));
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }

    /**
     * V2.9.42: 同步单页内容到content表
     * 当分类type=6时，自动创建/更新关联的content记录
     */
    protected function syncPageContent(Cate $cate, array $data): void
    {
        if ((int) $cate->type !== ContentTypeMap::pageType()) {
            return;
        }

        $pageContent = $data['page_content'] ?? '';
        $now = time();

        if ($cate->content_id > 0) {
            // 更新已有content记录
            Content::where('id', $cate->content_id)->where('type', ContentTypeMap::pageType())->update([
                'title'        => $cate->name,
                'content'      => $pageContent,
                'type'         => ContentTypeMap::pageType(),
                'cate_id'      => $cate->id,
                'status'       => 2,
                'seo_title'    => $data['seo_title'] ?? '',
                'seo_keywords' => $data['seo_keywords'] ?? '',
                'seo_description' => $data['seo_description'] ?? '',
                'update_time'  => $now,
            ]);
        } else {
            // 创建新content记录
            $content = new Content();
            $content->save([
                'title'           => $cate->name,
                'content'         => $pageContent,
                'type'            => ContentTypeMap::pageType(),
                'model_id'        => ContentTypeMap::pageType(),
                'model_identifier'=> 'model_page',
                'cate_id'         => $cate->id,
                'status'          => 2,
                'seo_title'       => $data['seo_title'] ?? '',
                'seo_keywords'    => $data['seo_keywords'] ?? '',
                'seo_description' => $data['seo_description'] ?? '',
                'create_time'     => $now,
                'update_time'     => $now,
            ]);
            // 回写content_id到分类
            $cate->content_id = $content->id;
            $cate->save();
        }
    }

    /**
     * V2.9.44: 检查英文URL别名是否已存在（AJAX查重）
     * 支持排除当前编辑的分类ID
     */
    public function checkSeoUrl()
    {
        $seoUrl = trim($this->request->get('seo_url', ''));
        $excludeId = (int) $this->request->get('exclude_id', 0);

        if (empty($seoUrl)) {
            return json(['exists' => false, 'suggestion' => '']);
        }

        $query = Cate::where('seo_url', $seoUrl);
        if ($excludeId > 0) {
            $query = $query->where('id', '<>', $excludeId);
        }
        $exist = $query->find();

        if ($exist) {
            // 冲突时生成建议别名（加后缀2,3,4...）
            $suggestion = $seoUrl . '2';
            $suffix = 2;
            while (Cate::where('seo_url', $suggestion)->when($excludeId > 0, function($q) use ($excludeId) {
                $q->where('id', '<>', $excludeId);
            })->find()) {
                $suffix++;
                $suggestion = $seoUrl . $suffix;
                if ($suffix > 20) break;
            }
            return json(['exists' => true, 'suggestion' => $suggestion]);
        }

        return json(['exists' => false, 'suggestion' => '']);
    }

    /**
     * 扫描模板目录获取可用模板列表
     * 参考 eyoucms：扫描 themes/default/pc/ 下所有 .html 文件
     */
    private function scanTemplates(): array
    {
        $themePath = app()->getRootPath() . 'template/themes/default/pc/';
        $listTemplates = [];
        $detailTemplates = [];

        if (is_dir($themePath)) {
            $files = scandir($themePath);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'html') continue;
                // 过滤掉 _partials 目录、layout 文件、公共组件
                if (strpos($file, '_') === 0 || in_array($file, ['layout.html', 'header.html', 'footer.html', 'nav.html', 'pagination.html'])) continue;

                // 列表模板：以 list_ 开头
                if (strpos($file, 'list_') === 0 || $file === 'list.html') {
                    $listTemplates[] = $file;
                }
                // 详情/单页模板
                elseif (strpos($file, 'detail_') === 0 || $file === 'detail.html' || strpos($file, 'single_') === 0) {
                    $detailTemplates[] = $file;
                }
            }
            sort($listTemplates);
            sort($detailTemplates);
        }

        return ['list' => $listTemplates, 'detail' => $detailTemplates];
    }
}

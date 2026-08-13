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

namespace app\common\taglib;

use think\template\TagLib;

/**
 * I8j自定义标签库
 * 仅负责标签编译（将模板标签编译为PHP代码），数据查询委托给ContentService
 * 
 * 支持的标签：
 * {i8j:infolist type="info" limit="10" order="id desc"}...{/i8j:infolist}
 * {i8j:catelist type="info" limit="100"}...{/i8j:catelist}
 */
class I8j extends TagLib
{
    /**
     * 标签定义
     */
    protected $tags = [
        // 内容列表标签（支持分页：page="1" pagesize="10"；分类筛选：cate_id="3"；指定ID：id="1,2,3"；偏移：offset="1"；循环变量：field="vo"）
        'infolist' => [
            'attr' => 'type,limit,order,page,pagesize,cate_id,id,offset,field',
            'close' => 1,
        ],
        // 分类列表标签
        'catelist' => [
            'attr' => 'type,limit,parent',
            'close' => 1,
        ],
        // 媒体资源列表标签
        'medialist' => [
            'attr' => 'filetype,limit,order',
            'close' => 1,
        ],
        // 轮播图列表标签
        'bannerlist' => [
            'attr' => 'limit,status',
            'close' => 1,
        ],
        // 友情链接列表标签
        'linklist' => [
            'attr' => 'limit,status,group',
            'close' => 1,
        ],
        // 评论列表标签
        'commentlist' => [
            'attr' => 'content_id,limit,status',
            'close' => 1,
        ],
        // 自定义变量标签
        'customvar' => [
            'attr' => 'name,default',
            'close' => 0,
        ],
        // 表单渲染标签
        'form' => [
            'attr' => 'code',
            'close' => 0,
        ],
    ];

    /**
     * {i8j:infolist type="info" cate_id="3" limit="10" order="id desc"}
     * {i8j:infolist type="info" id="1,2,3"}
     * {i8j:infolist type="info" pagesize="4" order="id desc"}    ← 分页：每页4条
     * 编译为：调用ContentService::getInfolist/getByIds获取数据，然后用{volist}遍历
     * V2.9.42: 新增cate_id(分类筛选)+id(指定ID)属性
     * V2.9.44: 新增pagesize分页支持，自动从URL读取page参数
     *          指定pagesize后，标签内可用 {$pageNav|raw} 输出分页导航
     */
    public function tagInfolist(array $tag, string $content): string
    {
        $type = $tag['type'] ?? '';
        // type 支持变量引用：type="$type_slug" → 运行时读取模板变量
        $typeExpr = '';
        if ($type !== '') {
            if (str_starts_with(trim((string) $type), '$')) {
                $typeExpr = '(string) (' . trim((string) $type) . ')';
            } else {
                $typeExpr = '"' . trim((string) $type) . '"';
            }
        } else {
            $typeExpr = '""';
        }
        $limit = $tag['limit'] ?? 10;
        $order = $tag['order'] ?? 'id desc';
        $offset = isset($tag['offset']) ? (int) $tag['offset'] : 0;
        $ids = isset($tag['id']) ? trim($tag['id']) : '';

        // cate_id 支持三种写法：
        //   1) cate_id="3"           静态数字分类ID
        //   2) cate_id="current"     自动跟随当前分类（优先 cate_id 参数，其次 seoUrl 单段路由反查，最后模板变量 $cate_id）
        //   3) cate_id="$cate_id"    引用模板作用域变量（编译期保留为PHP变量）
        $cateIdExpr = '0';
        if (isset($tag['cate_id'])) {
            $rawCateId = trim((string) $tag['cate_id']);
            if ($rawCateId === 'current') {
                // current 模式：动态解析当前分类ID。
                // 优先 request cate_id 参数（/product?cate_id=5）；其次 seoUrl 单段路由（/news）反查分类；
                // 最后回退到模板作用域变量 $cate_id（控制器 assign 的当前分类，可能为 0 表示"全部"）。
                $cateIdExpr = '\app\common\taglib\I8j::resolveCurrentCateId($cate_id ?? 0)';
            } elseif (str_starts_with($rawCateId, '$')) {
                $cateIdExpr = '(int) (' . $rawCateId . ')';
            } else {
                $cateIdExpr = (string) (int) $rawCateId;
            }
        }

        // 循环变量名，默认 field，可通过 field="vo" 自定义（兼容旧 partial 模板用 $vo）
        $fieldVar = isset($tag['field']) && trim((string) $tag['field']) !== '' ? trim((string) $tag['field']) : 'field';

        // V2.9.44: pagesize 指定后启用分页，page 从 URL 自动读取
        $pageSize = isset($tag['pagesize']) ? (int) $tag['pagesize'] : 0;
        // 兼容旧 page 属性（固定页码），不推荐使用
        $page = isset($tag['page']) ? (int) $tag['page'] : 0;

        $parse = '<?php ';
        if (!empty($ids)) {
            // id 属性优先：指定ID列表查询
            $parse .= '$__LIST__ = app("app\\common\\service\\ContentService")->getByIds("' . $ids . '", ' . (int) $limit . ', "' . $order . '"); ';
        } elseif ($pageSize > 0) {
            // 分页模式：自动从URL读取page参数
            $parse .= '$pageNav = ""; ';
            $parse .= '$__PAGE_RESULT__ = app("app\\common\\service\\ContentService")->getInfolist(' . $typeExpr . ', ' . (int) $limit . ', "' . $order . '", 0, ' . $pageSize . ', ' . $cateIdExpr . ', ' . $offset . ', true); ';
            $parse .= 'if (is_object($__PAGE_RESULT__) && method_exists($__PAGE_RESULT__, "items")) { ';
            $parse .= '  $__LIST__ = $__PAGE_RESULT__->items(); ';
            $parse .= '  $pageNav = $__PAGE_RESULT__->render(); ';
            $parse .= '} else { ';
            $parse .= '  $__LIST__ = $__PAGE_RESULT__; ';
            $parse .= '} ';
        } else {
            // 非分页模式
            $parse .= '$__PAGE__ = app("app\\common\\service\\ContentService")->getInfolist(' . $typeExpr . ', ' . (int) $limit . ', "' . $order . '", ' . $page . ', ' . $pageSize . ', ' . $cateIdExpr . ', ' . $offset . '); ';
            $parse .= '$__LIST__ = (is_object($__PAGE__) && method_exists($__PAGE__, "items")) ? $__PAGE__->items() : $__PAGE__; ';
        }
        $parse .= '?>';
        $parse .= '{volist name="__LIST__" id="' . $fieldVar . '" key="i"}';
        $parse .= $content;
        $parse .= '{/volist}';

        return $parse;
    }

    /**
     * {i8j:catelist type="info" limit="100" parent="0"}
     * 编译为：调用CateService::getCatelist获取数据，然后用{volist}遍历
     */
    public function tagCatelist(array $tag, string $content): string
    {
        $type = $tag['type'] ?? '';
        $limit = $tag['limit'] ?? 100;
        $parent = $tag['parent'] ?? 0;

        $parse = '<?php ';
        $parse .= '$__LIST__ = app("app\\common\\service\\CateService")->getCatelist("' . $type . '", ' . (int) $limit . ', ' . (int) $parent . '); ';
        $parse .= '?>';
        $parse .= '{volist name="__LIST__" id="field" key="i"}';
        $parse .= $content;
        $parse .= '{/volist}';

        return $parse;
    }

    /**
     * {i8j:medialist filetype="image" limit="10" order="id desc"}
     * 编译为：调用MediaService::getMediaList获取数据，然后用{volist}遍历
     */
    public function tagMedialist(array $tag, string $content): string
    {
        $filetype = $tag['filetype'] ?? 'image';
        $limit = $tag['limit'] ?? 10;
        $order = $tag['order'] ?? 'id desc';

        $parse = '<?php ';
        $parse .= '$__LIST__ = app("app\\common\\service\\MediaService")->getMediaList("' . $filetype . '", ' . (int) $limit . ', "' . $order . '"); ';
        $parse .= '?>';
        $parse .= '{volist name="__LIST__" id="field" key="i"}';
        $parse .= $content;
        $parse .= '{/volist}';

        return $parse;
    }

    /**
     * {i8j:bannerlist limit="5" status="1"}
     * 编译为：调用BannerService::getBannerList获取数据，然后用{volist}遍历
     */
    public function tagBannerlist(array $tag, string $content): string
    {
        $limit = $tag['limit'] ?? 5;
        $status = $tag['status'] ?? 1;

        $parse = '<?php ';
        $parse .= '$__LIST__ = app("app\\common\\service\\BannerService")->getBannerList(' . (int) $limit . ', ' . (int) $status . '); ';
        $parse .= '?>';
        $parse .= '{volist name="__LIST__" id="field" key="i"}';
        $parse .= $content;
        $parse .= '{/volist}';

        return $parse;
    }

    /**
     * {i8j:linklist limit="10" status="1" group="1"}
     * 编译为：调用LinkService::getLinkList获取数据，然后用{volist}遍历
     */
    public function tagLinklist(array $tag, string $content): string
    {
        $limit = $tag['limit'] ?? 10;
        $status = $tag['status'] ?? 1;
        $group = isset($tag['group']) ? (int) $tag['group'] : 0;

        $parse = '<?php ';
        $parse .= '$__LIST__ = app("app\\common\\service\\LinkService")->getLinkList(' . (int) $limit . ', ' . (int) $status . ', ' . $group . '); ';
        $parse .= '?>';
        $parse .= '{volist name="__LIST__" id="field"}';
        $parse .= $content;
        $parse .= '{/volist}';

        return $parse;
    }

    /**
     * {i8j:friendlink limit="10" status="1" group="1"}
     * linklist的别名（语法糖），向后兼容
     */
    public function tagFriendlink(array $tag, string $content): string
    {
        return $this->tagLinklist($tag, $content);
    }

    /**
     * {i8j:commentlist content_id="1" limit="10" status="1"}
     * 编译为：调用CommentService::getList获取数据，然后用{volist}遍历
     * 注意：content_id必须(int)强制转换，防止SQL注入
     */
    public function tagCommentlist(array $tag, string $content): string
    {
        $contentId = isset($tag['content_id']) ? (int) $tag['content_id'] : 0;
        $limit = isset($tag['limit']) ? (int) $tag['limit'] : 10;
        $status = isset($tag['status']) ? (int) $tag['status'] : 1;

        $parse = '<?php ';
        $parse .= '$__COMMENT_LIST__ = app("app\\common\\service\\CommentService")->getList(' . $contentId . ', ' . $status . ', 1, ' . $limit . '); ';
        $parse .= '?>';
        $parse .= '{volist name="__COMMENT_LIST__" id="field"}';
        $parse .= $content;
        $parse .= '{/volist}';

        return $parse;
    }

    /**
     * {i8j:customvar name="company_phone" default="" /}
     * 编译为：输出自定义变量值
     */
    public function tagCustomvar(array $tag): string
    {
        $name = $tag['name'] ?? '';
        $default = $tag['default'] ?? '';

        $parse = '<?php echo htmlspecialchars($custom["' . $name . '"] ?? "' . $default . '", ENT_QUOTES, "UTF-8"); ?>';

        return $parse;
    }

    /**
     * 解析当前分类ID（供 cate_id="current" 使用）
     * 优先级：
     *   1) 请求参数 cate_id（/product?cate_id=5）
     *   2) 请求参数 seoUrl（单段路由 /news），反查分类
     *   3) 传入的模板变量 $cate_id（控制器 assign，可能为 0 表示"全部"）
     *
     * @param int $fallbackCateId 模板作用域变量 $cate_id 的值
     */
    public static function resolveCurrentCateId(int $fallbackCateId = 0): int
    {
        $request = request();

        // 1) cate_id 请求参数
        $cateId = (int) $request->param('cate_id', 0);
        if ($cateId > 0) {
            return $cateId;
        }

        // 2) seoUrl 单段路由（/news、/about 等），反查分类
        $seoUrl = (string) $request->param('seoUrl', '');
        if ($seoUrl === '') {
            $seoUrl = (string) $request->param('seo_url', '');
        }
        if ($seoUrl !== '') {
            $cate = \app\common\model\Cate::where('seo_url', $seoUrl)
                ->where('status', 1)
                ->find();
            if ($cate && !$cate->isEmpty()) {
                return (int) $cate->id;
            }
        }

        // 3) 回退到控制器传入的模板变量
        return $fallbackCateId > 0 ? $fallbackCateId : 0;
    }
}

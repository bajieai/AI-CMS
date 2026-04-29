<?php
declare(strict_types=1);

namespace app\common\taglib;

use think\template\TagLib;

/**
 * I8j自定义标签库
 * 仅负责标签编译（将模板标签编译为PHP代码），数据查询委托给ContentService
 * 
 * 支持的标签：
 * {i8j:infolist type="news" limit="10" order="id desc"}...{/i8j:infolist}
 * {i8j:catelist type="news" limit="100"}...{/i8j:catelist}
 */
class I8j extends TagLib
{
    /**
     * 标签定义
     */
    protected $tags = [
        // 内容列表标签（支持分页：page="1" pagesize="10"）
        'infolist' => [
            'attr' => 'type,limit,order,page,pagesize',
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
    ];

    /**
     * {i8j:infolist type="news" limit="10" order="id desc"}
     * {i8j:infolist type="news" page="1" pagesize="10" order="id desc"}
     * 编译为：调用ContentService::getInfolist获取数据，然后用{volist}遍历
     * 若传page参数，额外暴露 $__PAGE__ 变量供分页渲染
     */
    public function tagInfolist(array $tag, string $content): string
    {
        $type = $tag['type'] ?? '';
        $limit = $tag['limit'] ?? 10;
        $order = $tag['order'] ?? 'id desc';
        $page = isset($tag['page']) ? (int) $tag['page'] : 0;
        $pageSize = isset($tag['pagesize']) ? (int) $tag['pagesize'] : 10;

        $parse = '<?php ';
        $parse .= '$__PAGE__ = app("app\\common\\service\\ContentService")->getInfolist("' . $type . '", ' . (int) $limit . ', "' . $order . '", ' . $page . ', ' . $pageSize . '); ';
        $parse .= '$__LIST__ = (is_object($__PAGE__) && method_exists($__PAGE__, "items")) ? $__PAGE__->items() : $__PAGE__; ';
        $parse .= '?>';
        $parse .= '{volist name="__LIST__" id="field" key="i"}';
        $parse .= $content;
        $parse .= '{/volist}';

        return $parse;
    }

    /**
     * {i8j:catelist type="news" limit="100" parent="0"}
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
}

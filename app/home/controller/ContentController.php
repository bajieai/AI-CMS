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

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Content;
use app\common\model\Cate;
use app\common\model\Member;
use app\common\model\MemberLevel;
use app\common\service\SeoService;
use app\common\service\PaidService;
use app\common\service\SocialShareService;
use app\common\service\LanguageService;
use app\common\service\GeoService;
use think\facade\Db;

/**
 * 前台内容控制器 - V2.5增强
 * 新增：min_level_id内容等级限制、allow_download下载权限检查
 */
class ContentController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * 内容详情页
     * 路由：/product/{id}, /news/{id} 等（通过append传入type参数）
     */
    public function detail(int $id)
    {
        $info = Content::with(['cate', 'user', 'ext', 'tags'])->find($id);

        if (empty($info) || $info->status != 2) {
            abort(404, '内容不存在');
        }

        // V2.5：检查min_level_id内容等级限制
        if (!PaidService::checkLevelAccess($this->memberInfo['id'] ?? 0, $info)) {
            if (!$this->isMemberLogin) {
                return redirect('/member/login?redirect=' . urlencode(request()->url()));
            }
            $this->assign('info', $info);
            return $this->view('/level_deny');
        }

        // 增加浏览量
        $info->inc('views')->update();

        // 获取相关内容（V2.9.5 N+1优化：预加载分类）
        $related = Content::with(['cate'])->where('cate_id', $info->cate_id)
            ->where('id', '<>', $id)
            ->where('status', 2)
            ->limit(4)
            ->select();

        // V2.6: 获取章节列表（如果是付费内容或标记有章节）
        $chapters = [];
        $chapterAccess = [];
        if (!empty($info->is_paid) || Content::where('parent_id', $id)->where('is_chapter', 1)->count() > 0) {
            $chapters = Content::where('parent_id', $id)
                ->where('is_chapter', 1)
                ->where('status', 2)
                ->order('chapter_sort', 'asc')
                ->order('id', 'asc')
                ->select();

            $memberId = $this->memberInfo['id'] ?? 0;
            foreach ($chapters as $chapter) {
                $chapterAccess[$chapter->id] = PaidService::canAccessChapter($memberId, $id, $chapter->id);
            }
        }

        $typeMap = [1 => 'product', 2 => 'case', 3 => 'news', 4 => 'download', 5 => 'job', 6 => 'page'];
        $typeUrl = '/' . ($typeMap[$info->type] ?? 'info');

        // V2.3 JSON-LD结构化数据
        $seoService = new SeoService();
        $jsonLd = $seoService->buildJsonLd([
            'type'        => 'Article',
            'title'       => $info->seo_title ?: $info->title,
            'description' => $info->seo_description ?: $info->excerpt,
            'url'         => request()->url(true),
            'cover'       => $info->cover,
            'create_time' => $info->create_time,
            'update_time' => $info->update_time,
        ]);

        // V2.6: 付费内容安全展示（预览/完整）
        $memberId = $this->memberInfo['id'] ?? 0;
        $safeContent = PaidService::getSafeContent($info, $memberId);

        // V2.9.9: 社交分享OG元数据与分享链接
        $ogMeta = [];
        $shareLinks = [];
        if (\think\facade\Config::get('social_share.enabled', 1)) {
            $ogMeta = SocialShareService::generateOgMeta($info);
            $shareLinks = SocialShareService::generateContentShareLinks($info);
        }

        // V2.9.9: 内容多语言翻译版本
        $contentTranslations = [];
        if ($info->translation_of > 0) {
            $contentTranslations = LanguageService::getTranslationsOf($info->translation_of);
        } else {
            $contentTranslations = LanguageService::getTranslationsOf($info->id);
        }

        // V2.9.9: AI-GEO生成式引擎优化数据
        $geoData = GeoService::generate($info);

        $this->assign([
            'info'          => $info,
            'related'       => $related,
            'chapters'      => $chapters,
            'chapter_access'=> $chapterAccess,
            'type_url'      => $typeUrl,
            'jsonLd'        => $jsonLd,
            'can_download'  => $this->checkDownloadPermission(),
            'safe_content'  => $safeContent,
            'is_unlocked'   => $safeContent['is_unlocked'] ?? false,
            'og_meta'       => $ogMeta,
            'share_links'   => $shareLinks,
            'content_translations' => $contentTranslations,
            'geo_data'      => $geoData,
        ]);

        return $this->view('/detail');
    }

    /**
     * V2.6：下载附件（支持付费下载）
     */
    public function download(int $id)
    {
        $info = Content::find($id);
        if (empty($info) || $info->status != 2) {
            abort(404, '内容不存在');
        }

        // V2.6: 优先使用content表的download_url字段
        $fileUrl = $info->download_url ?: '';
        if (empty($fileUrl)) {
            // 降级到扩展数据字段
            $ext = $info->ext;
            if ($ext) {
                $extData = is_array($ext->data) ? $ext->data : [];
                $fileUrl = $extData['file_url'] ?? '';
            }
        }
        if (empty($fileUrl)) {
            return $this->error('该内容无附件可下载');
        }

        $memberId = $this->memberInfo['id'] ?? 0;

        // 检查下载权限（会员等级）
        if (!$this->checkDownloadPermission()) {
            if (!$this->isMemberLogin) {
                return $this->error('请先登录', 1, ['login_url' => '/member/login']);
            }
            return $this->error('您的会员等级无下载权限，请升级等级');
        }

        // V2.6: 检查付费下载权限
        $downloadPrice = (float) $info->download_price;
        if ($downloadPrice > 0) {
            // 独立下载售价
            if (!PaidService::canAccessDownload($memberId, $id)) {
                return $this->error('该附件需单独付费下载', 2, ['price' => $downloadPrice]);
            }
        } elseif (!empty($info->is_paid)) {
            // 跟随内容付费
            if (!PaidService::canAccess($memberId, $id)) {
                return $this->error('请先购买该内容');
            }
        }

        // 记录下载次数
        try {
            $info->inc('download_count')->update();
        } catch (\Throwable) {}

        return redirect($fileUrl);
    }

    /**
     * V2.5：检查当前会员是否有下载权限
     */
    protected function checkDownloadPermission(): bool
    {
        if (!$this->isMemberLogin) {
            return false;
        }

        try {
            $member = Member::find($this->memberInfo['id']);
            if (!$member || empty($member->level_id)) {
                return false;
            }
            $level = MemberLevel::find($member->level_id);
            return $level && !empty($level->allow_download);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * V3.1: 社交分享统计
     * POST /home/content/share
     */
    public function share()
    {
        $contentId = (int) $this->request->post('content_id', 0);
        $platform = $this->request->post('platform', ''); // weibo/qq/wechat/twitter/copy
        if ($contentId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $content = Content::find($contentId);
        if (empty($content) || $content->status != 2) {
            return json(['code' => 1, 'msg' => '内容不存在']);
        }

        // 记录分享行为到visit_log
        try {
            Db::name('visit_log')->insert([
                'content_id'  => $contentId,
                'ip'          => request()->ip(),
                'source_type' => 'social',
                'referrer'    => $platform,
                'device'      => 'share',
                'user_agent'  => request()->header('user-agent', ''),
                'create_time' => time(),
            ]);
        } catch (\Throwable) {}

        return json(['code' => 0, 'msg' => '记录成功']);
    }
}

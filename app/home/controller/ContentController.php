<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Content;
use app\common\model\Cate;
use app\common\model\Member;
use app\common\model\MemberLevel;
use app\common\service\SeoService;
use app\common\service\PaidService;

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
                $this->redirect('/member/login?redirect=' . urlencode(request()->url()));
            }
            $this->assign('info', $info);
            return $this->view('/level_deny');
        }

        // 增加浏览量
        $info->inc('views')->update();

        // 获取相关内容
        $related = Content::where('cate_id', $info->cate_id)
            ->where('id', '<>', $id)
            ->where('status', 2)
            ->limit(4)
            ->select();

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

        $this->assign([
            'info'     => $info,
            'related'  => $related,
            'type_url' => $typeUrl,
            'jsonLd'   => $jsonLd,
            'can_download' => $this->checkDownloadPermission(),
        ]);

        return $this->view('/detail');
    }

    /**
     * V2.5：下载附件
     * 检查会员等级的allow_download权限
     */
    public function download(int $id)
    {
        $info = Content::find($id);
        if (empty($info) || $info->status != 2) {
            abort(404, '内容不存在');
        }

        $ext = $info->ext;
        $fileUrl = '';
        if ($ext) {
            $extData = is_array($ext->data) ? $ext->data : [];
            $fileUrl = $extData['file_url'] ?? '';
        }
        if (empty($fileUrl)) {
            return $this->error('该内容无附件可下载');
        }

        // 检查下载权限
        if (!$this->checkDownloadPermission()) {
            if (!$this->isMemberLogin) {
                return $this->error('请先登录', 1, ['login_url' => '/member/login']);
            }
            return $this->error('您的会员等级无下载权限，请升级等级');
        }

        // 检查付费内容权限
        if (!PaidService::canAccess($this->memberInfo['id'] ?? 0, $id)) {
            return $this->error('请先购买该内容');
        }

        // 记录下载日志（可选）
        try {
            \think\facade\Db::name('content_ext')->where('content_id', $id)->inc('download_count')->update();
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
}

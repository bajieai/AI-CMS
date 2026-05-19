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
use app\common\service\ConfigService;
use app\common\service\PaidService;

/**
 * 前台章节阅读控制器 - V2.7
 */
class ChapterController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * 章节阅读页
     * 路由: /chapter/read/:parent_id/:chapter_id
     */
    public function read(int $parentId, int $chapterId)
    {
        $parent = Content::with(['cate'])->find($parentId);
        if (!$parent || $parent->status != 2) {
            abort(404, '内容不存在');
        }

        $chapter = Content::find($chapterId);
        if (!$chapter || $chapter->parent_id != $parentId || $chapter->status != 2) {
            abort(404, '章节不存在');
        }

        $memberId = $this->memberInfo['id'] ?? 0;
        $canAccess = PaidService::canAccessChapter($memberId, $parentId, $chapterId);

        // 获取所有章节列表及权限
        $chapters = PaidService::getChapterListWithAccess($parentId, $memberId);

        // 内容处理：无权限则截断展示
        $content = $chapter->content;
        $isLocked = false;
        if (!$canAccess && !empty($chapter->is_paid)) {
            $previewLength = (int) ConfigService::get('chapter_preview_length', 200);
            $content = mb_substr(strip_tags($content), 0, $previewLength) . '...';
            $isLocked = true;
        }

        // 上下章
        $prevChapter = null;
        $nextChapter = null;
        foreach ($chapters as $i => $ch) {
            if ($ch['id'] == $chapterId) {
                if ($i > 0) $prevChapter = $chapters[$i - 1];
                if (isset($chapters[$i + 1])) $nextChapter = $chapters[$i + 1];
                break;
            }
        }

        $this->assign([
            'parent'       => $parent,
            'chapter'      => $chapter,
            'content'      => $content,
            'chapters'     => $chapters,
            'can_access'   => $canAccess,
            'is_locked'    => $isLocked,
            'is_vip'       => $this->isVip(),
            'prev_chapter' => $prevChapter,
            'next_chapter' => $nextChapter,
        ]);

        return $this->view('/chapter_read');
    }

    /**
     * 购买单章（AJAX）
     */
    public function buyChapter()
    {
        if (!$this->isMemberLogin) {
            return json(['code' => 2, 'msg' => '请先登录']);
        }

        $chapterId = (int) $this->request->post('chapter_id', 0);
        if ($chapterId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            PaidService::buyChapter($this->memberInfo['id'], $chapterId);
            return json(['code' => 0, 'msg' => '购买成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 购买整本（AJAX）
     */
    public function buyBook()
    {
        if (!$this->isMemberLogin) {
            return json(['code' => 2, 'msg' => '请先登录']);
        }

        $parentId = (int) $this->request->post('parent_id', 0);
        if ($parentId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            PaidService::buyWholeBook($this->memberInfo['id'], $parentId);
            return json(['code' => 0, 'msg' => '购买成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 检查当前会员是否为VIP
     */
    protected function isVip(): bool
    {
        if (!$this->isMemberLogin || empty($this->memberInfo['id'])) {
            return false;
        }
        $member = \app\common\model\Member::find($this->memberInfo['id']);
        if (!$member || !$member->level_id || $member->vip_expire_time <= time()) {
            return false;
        }
        $level = \app\common\model\MemberLevel::find($member->level_id);
        return $level && $level->is_vip;
    }
}

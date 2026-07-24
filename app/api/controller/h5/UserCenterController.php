<?php
declare(strict_types=1);

namespace app\api\controller\h5;

use think\facade\Db;
use think\facade\Cache;
use think\response\Json;
use app\common\service\h5\H5UserConfigService;

/**
 * H5用户中心控制器 - V2.9.40
 * 提供用户中心首页、个人资料、头像上传、订单、收藏、评论、消息、会员信息
 */
class UserCenterController extends H5BaseController
{
    /**
     * 用户中心首页数据（个人信息+统计）
     */
    public function index(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $member = Db::name('member')
            ->where('id', $this->memberId)
            ->field('id,username,nickname,avatar,email,phone,level_id,points,total_points,signin_count,vip_expire_time,last_login_time,create_time')
            ->find();
        if (!$member) {
            return $this->error('用户不存在');
        }
        // 统计数据（60秒缓存，减少每页查询压力）
        $stats = Cache::remember('h5_uc_stats_' . $this->memberId, function () {
            return [
                'order_count'     => Db::name('order')->where('member_id', $this->memberId)->count(),
                'favorite_count'  => Db::name('member_favorite')->where('member_id', $this->memberId)->count(),
                'comment_count'   => Db::name('comment')->where('user_id', $this->memberId)->where('status', 1)->whereNull('deleted_at')->count(),
                'unread_count'    => Db::name('notification')->where('receiver_id', $this->memberId)->where('is_read', 0)->count(),
                'like_count'      => Db::name('member_like')->where('member_id', $this->memberId)->count(),
            ];
        }, 60);
        return $this->success([
            'member' => $member,
            'stats'  => $stats,
        ]);
    }

    /**
     * GET获取个人资料 / PUT更新个人资料
     */
    public function profile(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        if ($this->request->isGet()) {
            $member = Db::name('member')
                ->where('id', $this->memberId)
                ->field('id,username,nickname,avatar,email,phone,gender,birthday,bio,level_id,points,total_points,create_time')
                ->find();
            if (!$member) {
                return $this->error('用户不存在');
            }
            // 加载用户偏好配置
            $preferences = H5UserConfigService::getConfigByType($this->memberId, 'preference');
            $member['preferences'] = $preferences;
            return $this->success($member);
        }
        // PUT 更新
        $data = [];
        $fields = ['nickname', 'email', 'phone', 'gender', 'birthday', 'bio'];
        foreach ($fields as $field) {
            $val = $this->request->param($field);
            if ($val !== null && $val !== '') {
                $data[$field] = $val;
            }
        }
        // 昵称XSS过滤
        if (isset($data['nickname'])) {
            $data['nickname'] = htmlspecialchars(strip_tags($data['nickname']), ENT_QUOTES, 'UTF-8');
        }
        // 简介XSS过滤
        if (isset($data['bio'])) {
            $data['bio'] = htmlspecialchars(strip_tags($data['bio']), ENT_QUOTES, 'UTF-8');
        }
        if (empty($data)) {
            return $this->error('没有需要更新的字段');
        }
        $data['update_time'] = time();
        Db::name('member')->where('id', $this->memberId)->update($data);
        return $this->success(null, '资料更新成功');
    }

    /**
     * POST上传头像（base64或文件）
     */
    public function avatar(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $avatarUrl = '';
        // 优先处理文件上传
        $file = $this->request->file('avatar');
        if ($file) {
            $savePath = public_path('uploads') . DIRECTORY_SEPARATOR . 'avatar';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0755, true);
            }
            $result = \think\facade\Filesystem::disk('public')->putFile('avatar', $file);
            if ($result) {
                $avatarUrl = '/uploads/' . str_replace('\\', '/', $result);
            }
        } else {
            // base64上传
            $base64 = $this->request->param('avatar', '');
            if ($base64) {
                if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $base64, $matches)) {
                    $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        return $this->error('不支持的头像格式');
                    }
                    $binary = base64_decode($matches[2]);
                    if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
                        return $this->error('头像文件无效或过大（最大5MB）');
                    }
                    $filename = 'avatar_' . $this->memberId . '_' . time() . '.' . $ext;
                    $savePath = public_path('uploads') . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR . $filename;
                    if (!is_dir(dirname($savePath))) {
                        mkdir(dirname($savePath), 0755, true);
                    }
                    file_put_contents($savePath, $binary);
                    $avatarUrl = '/uploads/avatar/' . $filename;
                } else {
                    return $this->error('无效的base64头像数据');
                }
            }
        }
        if (!$avatarUrl) {
            return $this->error('头像上传失败');
        }
        Db::name('member')->where('id', $this->memberId)->update([
            'avatar'      => $avatarUrl,
            'update_time' => time(),
        ]);
        return $this->success(['avatar' => $avatarUrl], '头像上传成功');
    }

    /**
     * GET订单列表（分页）
     */
    public function orders(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('page_size', 10);
        $status = $this->request->param('status', '');
        $query = Db::name('order')->where('member_id', $this->memberId);
        if ($status !== '') {
            $query->where('status', (int)$status);
        }
        $total = (clone $query)->count();
        $list = $query->order('create_time', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $this->success([
            'list'      => $list,
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * GET收藏列表（分页）
     */
    public function favorites(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('page_size', 10);
        $query = Db::name('member_favorite')
            ->alias('f')
            ->join('content c', 'f.content_id = c.id')
            ->where('f.member_id', $this->memberId)
            ->where('c.status', 1);
        $total = (clone $query)->count();
        $list = $query->order('f.create_time', 'desc')
            ->page($page, $pageSize)
            ->field('c.id,c.title,c.cover,c.description,f.create_time')
            ->select()
            ->toArray();
        return $this->success([
            'list'      => $list,
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * GET评论列表（分页）
     */
    public function comments(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('page_size', 10);
        $query = Db::name('comment')
            ->alias('c')
            ->join('content ct', 'c.content_id = ct.id')
            ->where('c.user_id', $this->memberId)
            ->where('c.status', 1)
            ->whereNull('c.deleted_at');
        $total = (clone $query)->count();
        $list = $query->order('c.create_time', 'desc')
            ->page($page, $pageSize)
            ->field('c.id,c.content_id,c.content,c.likes,c.create_time,ct.title as content_title')
            ->select()
            ->toArray();
        return $this->success([
            'list'      => $list,
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * GET消息通知列表
     */
    public function notifications(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('page_size', 10);
        $onlyUnread = (int)$this->request->param('only_unread', 0);
        $query = Db::name('notification')->where('receiver_id', $this->memberId);
        if ($onlyUnread) {
            $query->where('is_read', 0);
        }
        $total = (clone $query)->count();
        $unreadCount = Db::name('notification')->where('receiver_id', $this->memberId)->where('is_read', 0)->count();
        $list = $query->order('create_time', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $this->success([
            'list'         => $list,
            'total'        => $total,
            'unread_count' => $unreadCount,
            'page'         => $page,
            'page_size'    => $pageSize,
        ]);
    }

    /**
     * PUT标记消息已读
     */
    public function markRead(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $id = (int)$this->request->param('id', 0);
        $all = (int)$this->request->param('all', 0);
        if ($all) {
            // 全部已读
            Db::name('notification')
                ->where('receiver_id', $this->memberId)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
            return $this->success(null, '全部已标记为已读');
        }
        if ($id <= 0) {
            return $this->error('请指定消息ID或使用全部已读');
        }
        Db::name('notification')
            ->where('id', $id)
            ->where('receiver_id', $this->memberId)
            ->update(['is_read' => 1]);
        return $this->success(null, '已标记为已读');
    }

    /**
     * GET会员信息
     */
    public function membership(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $member = Db::name('member')
            ->where('id', $this->memberId)
            ->field('id,username,nickname,avatar,level_id,points,total_points,signin_count,vip_expire_time,create_time')
            ->find();
        if (!$member) {
            return $this->error('用户不存在');
        }
        // 会员等级信息
        $level = null;
        if (!empty($member['level_id'])) {
            $level = Db::name('member_level')->where('id', $member['level_id'])->find();
        }
        // VIP状态
        $isVip = !empty($member['vip_expire_time']) && $member['vip_expire_time'] > time();
        $member['is_vip'] = $isVip;
        $member['vip_remaining_days'] = $isVip ? (int)ceil(($member['vip_expire_time'] - time()) / 86400) : 0;
        // 下一等级信息
        $nextLevel = null;
        if ($level) {
            $nextLevel = Db::name('member_level')
                ->where('min_points', '>', $member['total_points'])
                ->order('min_points', 'asc')
                ->find();
        }
        return $this->success([
            'member'     => $member,
            'level'      => $level,
            'next_level' => $nextLevel,
        ]);
    }
}

<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 COMPLIANCE-1: GDPR 合规服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\compliance;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\admin\model\PrivacyConsent;
use app\admin\model\PrivacyRequest;

/**
 * GDPR 合规服务 - V2.9.39 COMPLIANCE-1
 * Cookie同意管理 + 数据主体权利 + 请求处理
 */
class GdprService
{
    protected const CACHE_TAG = 'gdpr';
    protected const CACHE_TTL = 3600;

    /**
     * 记录用户同意
     */
    public function recordConsent(int $userId, int $policyId, string $source, array $consentTypes = []): array
    {
        $now = time();
        $data = [
            'user_id'        => $userId,
            'policy_id'      => $policyId,
            'consent_given'  => PrivacyConsent::STATUS_GRANTED,
            'consent_types'  => json_encode($consentTypes, JSON_UNESCAPED_UNICODE),
            'source'         => $source,
            'ip_address'     => request()->ip(),
            'user_agent'     => substr(request()->header('user-agent', ''), 0, 255),
            'create_time'    => $now,
            'update_time'    => $now,
        ];

        $id = Db::name('privacy_consent')->insertGetId($data);

        Cache::clear();

        Log::info('[GDPR] 用户同意记录', ['user_id' => $userId, 'policy_id' => $policyId, 'source' => $source]);

        return ['id' => $id, 'success' => true];
    }

    /**
     * 撤回用户同意
     */
    public function revokeConsent(int $userId, ?int $policyId = null): bool
    {
        $query = Db::name('privacy_consent')
            ->where('user_id', $userId)
            ->where('consent_given', PrivacyConsent::STATUS_GRANTED);

        if ($policyId !== null) {
            $query->where('policy_id', $policyId);
        }

        $result = $query->update([
            'consent_given' => PrivacyConsent::STATUS_REVOKED,
            'update_time'   => time(),
        ]);

        Cache::clear();

        Log::info('[GDPR] 用户撤销同意', ['user_id' => $userId, 'affected' => $result]);

        return $result > 0;
    }

    /**
     * 检查用户是否已同意
     */
    public function hasConsent(int $userId, ?int $policyId = null): bool
    {
        $cacheKey = 'gdpr_consent_' . $userId . '_' . ($policyId ?? 0);

        return Cache::remember($cacheKey, function () use ($userId, $policyId) {
            $query = Db::name('privacy_consent')
                ->where('user_id', $userId)
                ->where('consent_given', PrivacyConsent::STATUS_GRANTED);

            if ($policyId !== null) {
                $query->where('policy_id', $policyId);
            }

            return $query->count() > 0;
        }, self::CACHE_TTL);
    }

    /**
     * 获取用户同意历史
     */
    public function getConsentHistory(int $userId, int $page = 1, int $limit = 20): array
    {
        $query = PrivacyConsent::where('user_id', $userId)->order('create_time', 'desc');

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    // ===== 数据主体权利请求处理 =====

    /**
     * 创建数据主体权利请求
     */
    public function createRequest(int $userId, string $type, string $description = '', array $extraData = []): array
    {
        $validTypes = [
            PrivacyRequest::TYPE_ACCESS,
            PrivacyRequest::TYPE_RECTIFICATION,
            PrivacyRequest::TYPE_ERASURE,
            PrivacyRequest::TYPE_RESTRICTION,
            PrivacyRequest::TYPE_PORTABILITY,
            PrivacyRequest::TYPE_OBJECTION,
        ];

        if (!in_array($type, $validTypes, true)) {
            return ['success' => false, 'msg' => '无效的请求类型'];
        }

        // 检查是否有重复待处理的请求
        $existing = PrivacyRequest::where('user_id', $userId)
            ->where('type', $type)
            ->whereIn('status', [PrivacyRequest::STATUS_PENDING, PrivacyRequest::STATUS_PROCESSING])
            ->find();

        if ($existing) {
            return ['success' => false, 'msg' => '已有相同类型的请求正在处理中'];
        }

        $request = PrivacyRequest::create([
            'user_id'     => $userId,
            'type'        => $type,
            'description' => $description,
            'extra_data'  => json_encode($extraData, JSON_UNESCAPED_UNICODE),
            'status'      => PrivacyRequest::STATUS_PENDING,
            'ip_address'  => request()->ip(),
            'create_time' => time(),
            'update_time' => time(),
        ]);

        Log::info('[GDPR] 新增数据主体请求', ['request_id' => $request->id, 'user_id' => $userId, 'type' => $type]);

        return ['success' => true, 'id' => $request->id];
    }

    /**
     * 处理数据主体请求
     */
    public function processRequest(int $requestId, int $handlerId, string $action, string $note = ''): array
    {
        $request = PrivacyRequest::find($requestId);
        if (!$request) {
            return ['success' => false, 'msg' => '请求不存在'];
        }

        if (!in_array($request->status, [PrivacyRequest::STATUS_PENDING, PrivacyRequest::STATUS_PROCESSING])) {
            return ['success' => false, 'msg' => '当前状态不允许处理'];
        }

        switch ($action) {
            case 'start':
                $request->status = PrivacyRequest::STATUS_PROCESSING;
                $request->handler_id = $handlerId;
                $request->handler_note = $note;
                break;

            case 'complete':
                $result = $this->executeDataSubjectRight($request, $handlerId);
                $request->status = PrivacyRequest::STATUS_COMPLETED;
                $request->handler_id = $handlerId;
                $request->handler_note = $note;
                $request->result_data = json_encode($result, JSON_UNESCAPED_UNICODE);
                $request->completed_at = date('Y-m-d H:i:s');
                break;

            case 'reject':
                $request->status = PrivacyRequest::STATUS_REJECTED;
                $request->handler_id = $handlerId;
                $request->handler_note = $note;
                break;

            default:
                return ['success' => false, 'msg' => '无效的操作'];
        }

        $request->save();

        Log::info('[GDPR] 处理数据主体请求', [
            'request_id' => $requestId,
            'action'     => $action,
            'handler_id' => $handlerId,
        ]);

        return ['success' => true, 'status' => $request->status];
    }

    /**
     * 执行数据主体权利
     */
    protected function executeDataSubjectRight(PrivacyRequest $request, int $handlerId): array
    {
        $userId = $request->user_id;

        switch ($request->type) {
            case PrivacyRequest::TYPE_ACCESS:
                return $this->executeDataAccess($userId);

            case PrivacyRequest::TYPE_ERASURE:
                return $this->executeDataErasure($userId);

            case PrivacyRequest::TYPE_PORTABILITY:
                return $this->executeDataPortability($userId);

            default:
                return ['message' => '请求类型需要人工处理', 'type' => $request->type];
        }
    }

    /**
     * 执行数据访问权
     */
    protected function executeDataAccess(int $userId): array
    {
        $tables = [
            'member'       => 'member',
            'content'      => 'content',
            'comment'      => 'comment',
            'order'        => 'paid_order',
            'login_log'    => 'login_log',
            'private_msg'  => 'private_message',
        ];

        $result = [];
        foreach ($tables as $label => $table) {
            try {
                $count = Db::name($table)->where('user_id', $userId)->count();
                $result[$label] = ['table' => $table, 'count' => $count];
            } catch (\Throwable $e) {
                $result[$label] = ['table' => $table, 'error' => $e->getMessage()];
            }
        }

        return ['action' => 'data_access', 'data' => $result];
    }

    /**
     * 执行数据删除权（被遗忘权）
     */
    protected function executeDataErasure(int $userId): array
    {
        $tables = [
            'comment',
            'private_message',
            'member_favorite',
            'member_like',
        ];

        $deleted = [];
        foreach ($tables as $table) {
            try {
                $count = Db::name($table)->where('user_id', $userId)->delete();
                $deleted[$table] = $count;
            } catch (\Throwable $e) {
                $deleted[$table] = 'error: ' . $e->getMessage();
            }
        }

        // 会员表匿名化处理（不完全删除，保留审计记录）
        try {
            Db::name('member')->where('id', $userId)->update([
                'username'  => 'deleted_user_' . $userId,
                'nickname'  => '已注销用户',
                'email'     => null,
                'phone'     => null,
                'avatar'    => '',
                'status'    => 0,
                'is_deleted' => 1,
            ]);
            $deleted['member'] = 'anonymized';
        } catch (\Throwable $e) {
            $deleted['member'] = 'error: ' . $e->getMessage();
        }

        // 撤销所有同意
        $this->revokeConsent($userId);

        return ['action' => 'data_erasure', 'deleted' => $deleted];
    }

    /**
     * 执行数据可携带权
     */
    protected function executeDataPortability(int $userId): array
    {
        $data = [];

        // 会员信息
        $member = Db::name('member')->where('id', $userId)->find();
        if ($member) {
            unset($member['password'], $member['salt']);
            $data['profile'] = $member;
        }

        // 内容
        $data['contents'] = Db::name('content')->where('user_id', $userId)->select()->toArray();

        // 评论
        $data['comments'] = Db::name('comment')->where('user_id', $userId)->select()->toArray();

        // 订单
        $data['orders'] = Db::name('paid_order')->where('user_id', $userId)->select()->toArray();

        return ['action' => 'data_portability', 'data' => $data];
    }

    /**
     * 获取请求列表（后台）
     */
    public function getRequestList(array $params = []): array
    {
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        $status = $params['status'] ?? null;
        $type = $params['type'] ?? null;
        $userId = $params['user_id'] ?? null;

        $query = PrivacyRequest::order('create_time', 'desc');

        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }
        if (!empty($type)) {
            $query->where('type', $type);
        }
        if (!empty($userId)) {
            $query->where('user_id', (int) $userId);
        }

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 获取请求详情
     */
    public function getRequestDetail(int $requestId): ?array
    {
        $request = PrivacyRequest::find($requestId);
        return $request ? $request->toArray() : null;
    }

    /**
     * 获取GDPR合规仪表盘数据
     */
    public function getDashboard(): array
    {
        $cacheKey = 'gdpr_dashboard';

        return Cache::remember($cacheKey, function () {
            $totalRequests = PrivacyRequest::count();
            $pendingRequests = PrivacyRequest::where('status', PrivacyRequest::STATUS_PENDING)->count();
            $processingRequests = PrivacyRequest::where('status', PrivacyRequest::STATUS_PROCESSING)->count();
            $completedRequests = PrivacyRequest::where('status', PrivacyRequest::STATUS_COMPLETED)->count();

            $totalConsents = PrivacyConsent::count();
            $activeConsents = PrivacyConsent::where('consent_given', PrivacyConsent::STATUS_GRANTED)->count();

            // 按请求类型统计
            $typeStats = PrivacyRequest::field('type, count(*) as count')
                ->group('type')
                ->select()
                ->toArray();

            // 最近30天请求趋势
            $recentRequests = PrivacyRequest::where('create_time', '>=', time() - 86400 * 30)
                ->field('from_unixtime(create_time, "%Y-%m-%d") as date, count(*) as count')
                ->group('date')
                ->order('date', 'asc')
                ->select()
                ->toArray();

            return [
                'total_requests'      => $totalRequests,
                'pending_requests'    => $pendingRequests,
                'processing_requests' => $processingRequests,
                'completed_requests'  => $completedRequests,
                'total_consents'      => $totalConsents,
                'active_consents'     => $activeConsents,
                'type_stats'          => $typeStats,
                'recent_requests'     => $recentRequests,
            ];
        }, self::CACHE_TTL);
    }
}

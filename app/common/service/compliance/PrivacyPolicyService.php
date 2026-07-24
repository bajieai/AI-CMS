<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 COMPLIANCE-1: 隐私政策管理服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\compliance;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 隐私政策管理服务 - V2.9.39 COMPLIANCE-1
 * 版本管理 + 发布 + 通知
 */
class PrivacyPolicyService
{
    protected const CACHE_TAG = 'privacy_policy';
    protected const CACHE_TTL = 3600;

    protected string $table = 'privacy_policy';

    /**
     * 创建隐私政策
     */
    public function create(array $data): array
    {
        $version = $this->generateNextVersion();

        $id = Db::name($this->table)->insertGetId([
            'title'         => $data['title'] ?? '隐私政策',
            'version'       => $version,
            'content'       => $data['content'] ?? '',
            'summary'       => $data['summary'] ?? '',
            'effective_date' => $data['effective_date'] ?? date('Y-m-d'),
            'status'        => 0, // 草稿
            'creator_id'    => $data['creator_id'] ?? 0,
            'create_time'   => time(),
            'update_time'   => time(),
        ]);

        Cache::clear();

        Log::info('[PrivacyPolicy] 创建隐私政策', ['id' => $id, 'version' => $version]);

        return ['id' => $id, 'version' => $version];
    }

    /**
     * 更新隐私政策
     */
    public function update(int $id, array $data): bool
    {
        $update = [];
        $fields = ['title', 'content', 'summary', 'effective_date'];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }

        if (empty($update)) {
            return false;
        }

        $update['update_time'] = time();

        $result = Db::name($this->table)->where('id', $id)->update($update);

        Cache::clear();

        return $result > 0;
    }

    /**
     * 发布隐私政策
     */
    public function publish(int $id): array
    {
        $policy = Db::name($this->table)->find($id);
        if (!$policy) {
            return ['success' => false, 'msg' => '隐私政策不存在'];
        }

        if ($policy['status'] === 1) {
            return ['success' => false, 'msg' => '该隐私政策已发布'];
        }

        Db::startTrans();
        try {
            // 将之前的已发布版本标记为历史
            Db::name($this->table)
                ->where('status', 1)
                ->update(['status' => 2, 'update_time' => time()]);

            // 发布新版本
            Db::name($this->table)->where('id', $id)->update([
                'status'        => 1,
                'published_at'  => date('Y-m-d H:i:s'),
                'effective_date' => $policy['effective_date'] ?: date('Y-m-d'),
                'update_time'   => time(),
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('[PrivacyPolicy] 发布失败', ['error' => $e->getMessage()]);
            return ['success' => false, 'msg' => '发布失败: ' . $e->getMessage()];
        }

        Cache::clear();

        // 通知所有用户政策已更新
        $this->notifyPolicyUpdate($id, $policy['version']);

        Log::info('[PrivacyPolicy] 隐私政策已发布', ['id' => $id, 'version' => $policy['version']]);

        return ['success' => true, 'version' => $policy['version']];
    }

    /**
     * 获取当前生效的隐私政策
     */
    public function getActivePolicy(): ?array
    {
        return Cache::remember('active_policy', function () {
            return Db::name($this->table)
                ->where('status', 1)
                ->order('published_at', 'desc')
                ->find();
        }, self::CACHE_TTL);
    }

    /**
     * 获取隐私政策详情
     */
    public function getDetail(int $id): ?array
    {
        return Db::name($this->table)->find($id);
    }

    /**
     * 获取版本列表
     */
    public function getVersionList(int $page = 1, int $limit = 20): array
    {
        $query = Db::name($this->table)->order('create_time', 'desc');

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 比较两个版本差异
     */
    public function compareVersions(int $id1, int $id2): array
    {
        $p1 = Db::name($this->table)->find($id1);
        $p2 = Db::name($this->table)->find($id2);

        if (!$p1 || !$p2) {
            return ['success' => false, 'msg' => '版本不存在'];
        }

        return [
            'success'   => true,
            'version_1' => [
                'version'        => $p1['version'],
                'effective_date' => $p1['effective_date'],
                'content_length' => mb_strlen($p1['content']),
            ],
            'version_2' => [
                'version'        => $p2['version'],
                'effective_date' => $p2['effective_date'],
                'content_length' => mb_strlen($p2['content']),
            ],
            'changed'   => $p1['content'] !== $p2['content'],
        ];
    }

    /**
     * 删除隐私政策（仅允许删除草稿）
     */
    public function delete(int $id): array
    {
        $policy = Db::name($this->table)->find($id);
        if (!$policy) {
            return ['success' => false, 'msg' => '隐私政策不存在'];
        }

        if ($policy['status'] !== 0) {
            return ['success' => false, 'msg' => '仅草稿状态可删除'];
        }

        Db::name($this->table)->delete($id);
        Cache::clear();

        return ['success' => true];
    }

    /**
     * 生成下一个版本号
     */
    protected function generateNextVersion(): string
    {
        $latest = Db::name($this->table)
            ->order('id', 'desc')
            ->value('version');

        if (empty($latest)) {
            return '1.0';
        }

        $parts = explode('.', $latest);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);

        // 同一天内小版本递增，不同天大版本递增
        $latestDate = Db::name($this->table)->order('id', 'desc')->value('create_time');
        if ($latestDate && date('Y-m-d', (int) $latestDate) !== date('Y-m-d')) {
            $major++;
            $minor = 0;
        } else {
            $minor++;
        }

        return $major . '.' . $minor;
    }

    /**
     * 通知用户政策已更新
     */
    protected function notifyPolicyUpdate(int $policyId, string $version): void
    {
        try {
            // 通过系统通知告知所有用户
            $memberCount = Db::name('member')->where('status', 1)->count();
            $batchSize = 500;

            for ($offset = 0; $offset < $memberCount; $offset += $batchSize) {
                $userIds = Db::name('member')
                    ->where('status', 1)
                    ->limit($offset, $batchSize)
                    ->column('id');

                if (empty($userIds)) {
                    break;
                }

                $rows = [];
                $now = time();
                foreach ($userIds as $uid) {
                    $rows[] = [
                        'user_id'    => $uid,
                        'type'       => 'privacy_policy_update',
                        'title'      => '隐私政策已更新',
                        'content'    => "隐私政策已更新至版本 {$version}，请查看最新内容。",
                        'link'       => '/privacy/policy',
                        'is_read'    => 0,
                        'create_time' => $now,
                    ];
                }

                Db::name('notification')->insertAll($rows);
            }

            Log::info('[PrivacyPolicy] 政策更新通知已发送', ['policy_id' => $policyId, 'notified' => $memberCount]);
        } catch (\Throwable $e) {
            Log::error('[PrivacyPolicy] 通知发送失败', ['error' => $e->getMessage()]);
        }
    }
}

<?php
declare(strict_types=1);

namespace app\common\service\ops;

use think\facade\Db;

/**
 * 用户分群服务
 * V2.9.38 OPS-DEEP-2
 */
class UserSegmentService
{
    public function createSegment(array $data): int
    {
        $id = Db::name('user_segment')->insertGetId(array_merge($data, [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));
        return (int) $id;
    }

    public function updateSegment(int $id, array $data): bool
    {
        Db::name('user_segment')->where('id', $id)->update(array_merge($data, ['updated_at' => date('Y-m-d H:i:s')]));
        return true;
    }

    public function deleteSegment(int $id): bool
    {
        Db::name('user_segment')->where('id', $id)->delete();
        return true;
    }

    public function copySegment(int $id): int
    {
        $segment = Db::name('user_segment')->find($id);
        if (!$segment) return 0;
        unset($segment['id']);
        $segment['name'] .= ' (副本)';
        $segment['created_at'] = date('Y-m-d H:i:s');
        $segment['updated_at'] = date('Y-m-d H:i:s');
        return (int) Db::name('user_segment')->insertGetId($segment);
    }

    public function mergeSegments(array $ids): int
    {
        // 合并多个分群的规则
        $segments = Db::name('user_segment')->whereIn('id', $ids)->select()->toArray();
        $mergedRules = [];
        foreach ($segments as $seg) {
            $rules = json_decode($seg['rules'] ?? '[]', true);
            $mergedRules = array_merge($mergedRules, $rules);
        }
        $newId = $this->createSegment([
            'name' => '合并分群_' . date('md'),
            'rules' => json_encode($mergedRules, JSON_UNESCAPED_UNICODE),
            'compute_mode' => 'manual',
        ]);
        return $newId;
    }

    /**
     * 计算分群(实时/定时/增量)
     */
    public function computeSegment(int $segmentId, string $mode = 'realtime'): array
    {
        $segment = Db::name('user_segment')->find($segmentId);
        if (!$segment) return [];
        $rules = json_decode($segment['rules'] ?? '[]', true);
        $members = $this->evaluateRules($rules);
        
        // 更新分群大小
        $count = count($members);
        Db::name('user_segment')->where('id', $segmentId)->update([
            'member_count' => $count, 'last_computed_at' => date('Y-m-d H:i:s'),
        ]);
        
        return ['segment_id' => $segmentId, 'member_count' => $count, 'members' => array_slice($members, 0, 100)];
    }

    public function getSegmentSize(int $segmentId): int
    {
        $segment = Db::name('user_segment')->find($segmentId);
        return $segment ? (int) $segment['member_count'] : 0;
    }

    public function getSegmentMembers(int $segmentId, int $page = 1, int $limit = 20): array
    {
        $segment = Db::name('user_segment')->find($segmentId);
        if (!$segment) return [];
        $rules = json_decode($segment['rules'] ?? '[]', true);
        $members = $this->evaluateRules($rules);
        $total = count($members);
        $offset = ($page - 1) * $limit;
        $list = array_slice($members, $offset, $limit);
        return ['total' => $total, 'list' => $list, 'page' => $page];
    }

    public function getSegmentProfile(int $segmentId): array
    {
        $segment = Db::name('user_segment')->find($segmentId);
        if (!$segment) return [];
        $rules = json_decode($segment['rules'] ?? '[]', true);
        $members = $this->evaluateRules($rules);
        
        // 统计画像
        $profile = [
            'total' => count($members),
            'gender' => [], 'age_groups' => [], 'regions' => [],
            'active_hours' => [], 'content_preferences' => [], 'tags' => [],
        ];
        
        if (!empty($members)) {
            $memberData = Db::name('member')->whereIn('id', $members)->select()->toArray();
            foreach ($memberData as $m) {
                $gender = $m['gender'] ?? 'unknown';
                $profile['gender'][$gender] = ($profile['gender'][$gender] ?? 0) + 1;
            }
        }
        
        return $profile;
    }

    public function compareSegments(array $segmentIds): array
    {
        $comparison = [];
        foreach ($segmentIds as $id) {
            $comparison[$id] = $this->getSegmentProfile($id);
        }
        return $comparison;
    }

    protected function evaluateRules(array $rules): array
    {
        $query = Db::name('member')->where('status', 1);
        foreach ($rules as $rule) {
            $type = $rule['type'] ?? 'attribute';
            $field = $rule['field'] ?? '';
            $op = $rule['operator'] ?? 'eq';
            $value = $rule['value'] ?? '';
            
            switch ($type) {
                case 'attribute':
                    $this->applyAttributeRule($query, $field, $op, $value);
                    break;
                case 'behavior':
                    $this->applyBehaviorRule($query, $field, $op, $value);
                    break;
                case 'content':
                    // 内容偏好规则
                    break;
                case 'time':
                    if ($field === 'last_active') {
                        $days = (int) $value;
                        $query->whereTime('last_login', '-' . $days . ' days');
                    }
                    break;
            }
        }
        return $query->column('id');
    }

    protected function applyAttributeRule($query, string $field, string $op, $value): void
    {
        switch ($op) {
            case 'eq': $query->where($field, '=', $value); break;
            case 'ne': $query->where($field, '<>', $value); break;
            case 'gt': $query->where($field, '>', $value); break;
            case 'lt': $query->where($field, '<', $value); break;
            case 'in': $query->whereIn($field, explode(',', $value)); break;
            case 'like': $query->where($field, 'like', '%' . $value . '%'); break;
        }
    }

    protected function applyBehaviorRule($query, string $field, string $op, $value): void
    {
        // 行为规则: 查recommend_log
        $query->whereIn('id', function($q) use ($field, $op, $value) {
            $q->name('recommend_log')->field('user_id');
            if ($field === 'view_count') {
                $q->group('user_id')->having('COUNT(*) ' . $op . ' ' . (int)$value);
            }
        });
    }
}

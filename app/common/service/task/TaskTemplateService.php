<?php
declare(strict_types=1);

namespace app\common\service\task;

use think\facade\Db;

class TaskTemplateService
{
    public function getTemplates(string $category = ''): array
    {
        $q = Db::name('task_template')->where('status', 1);
        if ($category) $q->where('category', $category);
        return $q->order('sort_order', 'asc')->select()->toArray();
    }

    public function getTemplateById(int $id): ?array
    {
        $t = Db::name('task_template')->where('id', $id)->find();
        return $t ?: null;
    }

    public function createTemplate(array $data): array
    {
        try {
            $id = Db::name('task_template')->insertGetId([
                'name' => $data['name'] ?? '', 'category' => $data['category'] ?? '',
                'description' => $data['description'] ?? '',
                'task_data' => json_encode($data['task_data'] ?? [], JSON_UNESCAPED_UNICODE),
                'subtasks' => json_encode($data['subtasks'] ?? [], JSON_UNESCAPED_UNICODE),
                'milestones' => json_encode($data['milestones'] ?? [], JSON_UNESCAPED_UNICODE),
                'assign_rules' => json_encode($data['assign_rules'] ?? [], JSON_UNESCAPED_UNICODE),
                'audit_flow' => json_encode($data['audit_flow'] ?? [], JSON_UNESCAPED_UNICODE),
                'variables' => json_encode($data['variables'] ?? [], JSON_UNESCAPED_UNICODE),
                'attachments' => json_encode($data['attachments'] ?? [], JSON_UNESCAPED_UNICODE),
                'sort_order' => $data['sort_order'] ?? 0, 'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            return ['code' => 0, 'msg' => '创建成功', 'data' => ['id' => $id]];
        } catch (\Throwable $e) { return ['code' => 1, 'msg' => '创建失败: ' . $e->getMessage()]; }
    }

    public function updateTemplate(int $id, array $data): array
    {
        try {
            $u = [];
            foreach (['name','category','description'] as $f) if (isset($data[$f])) $u[$f] = $data[$f];
            foreach (['task_data','subtasks','milestones','assign_rules','audit_flow','variables','attachments'] as $f)
                if (isset($data[$f])) $u[$f] = json_encode($data[$f], JSON_UNESCAPED_UNICODE);
            $u['update_time'] = date('Y-m-d H:i:s');
            Db::name('task_template')->where('id', $id)->update($u);
            return ['code' => 0, 'msg' => '更新成功'];
        } catch (\Throwable $e) { return ['code' => 1, 'msg' => '更新失败: ' . $e->getMessage()]; }
    }

    public function deleteTemplate(int $id): array
    {
        try { Db::name('task_template')->where('id', $id)->delete(); return ['code' => 0, 'msg' => '删除成功']; }
        catch (\Throwable $e) { return ['code' => 1, 'msg' => '删除失败: ' . $e->getMessage()]; }
    }

    public function createTaskFromTemplate(int $templateId, array $variables = []): array
    {
        try {
            $tpl = $this->getTemplateById($templateId);
            if (!$tpl) return ['code' => 1, 'msg' => '模板不存在'];
            $td = json_decode($tpl['task_data'] ?? '{}', true) ?: [];
            $name = $td['name'] ?? $tpl['name'];
            foreach ($variables as $k => $v) $name = str_replace('{' . $k . '}', $v, $name);
            $taskId = Db::name('task')->insertGetId([
                'title' => $name, 'description' => $td['description'] ?? $tpl['description'],
                'task_type' => $td['task_type'] ?? 'content_audit', 'priority' => $td['priority'] ?? 2,
                'status' => 'pending', 'assignee_id' => $td['assignee_id'] ?? 0,
                'progress' => 0, 'create_time' => date('Y-m-d H:i:s'),
            ]);
            $subtasks = json_decode($tpl['subtasks'] ?? '[]', true) ?: [];
            foreach ($subtasks as $sub) {
                $st = $sub['title'] ?? '';
                foreach ($variables as $k => $v) $st = str_replace('{' . $k . '}', $v, $st);
                Db::name('task')->insertGetId([
                    'title' => $st, 'parent_id' => $taskId,
                    'task_type' => $td['task_type'] ?? 'content_audit',
                    'priority' => $sub['priority'] ?? 2, 'status' => 'pending',
                    'progress' => 0, 'create_time' => date('Y-m-d H:i:s'),
                ]);
            }
            Db::name('task_template')->where('id', $templateId)->inc('usage_count')->update();
            return ['code' => 0, 'msg' => '创建成功', 'data' => ['task_id' => $taskId]];
        } catch (\Throwable $e) { return ['code' => 1, 'msg' => '创建失败: ' . $e->getMessage()]; }
    }

    public function batchCreateFromTemplate(int $templateId, array $variableSets): array
    {
        $s = 0; $errs = [];
        foreach ($variableSets as $i => $vars) {
            $r = $this->createTaskFromTemplate($templateId, $vars);
            if ($r['code'] === 0) $s++; else $errs[] = "第{$i}组: " . $r['msg'];
        }
        return ['code' => 0, 'msg' => "成功{$s}条", 'data' => ['success' => $s, 'errors' => $errs]];
    }

    public function getTemplateStats(): array
    {
        $total = Db::name('task_template')->where('status', 1)->count();
        $totalUsage = Db::name('task_template')->where('status', 1)->sum('usage_count');
        $topUsed = Db::name('task_template')->where('status', 1)->order('usage_count', 'desc')->limit(5)->select()->toArray();
        return ['total' => $total, 'total_usage' => $totalUsage, 'top_used' => $topUsed];
    }
}

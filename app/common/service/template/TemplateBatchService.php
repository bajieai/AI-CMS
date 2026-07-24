<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateStore;
use app\common\model\TemplateBatchLog;
use think\facade\Db;

class TemplateBatchService
{
    public function batchPublish(array $templateIds, int $adminId, string $adminName): array
    {
        return $this->executeBatch('batch_publish', $templateIds, ['status' => 1], $adminId, $adminName);
    }

    public function batchUnpublish(array $templateIds, int $adminId, string $adminName): array
    {
        return $this->executeBatch('batch_unpublish', $templateIds, ['status' => 0], $adminId, $adminName);
    }

    public function batchSetPrice(array $templateIds, float $price, ?float $originalPrice, int $adminId, string $adminName): array
    {
        $data = ['price' => $price];
        if ($originalPrice !== null) $data['original_price'] = $originalPrice;
        return $this->executeBatch('batch_set_price', $templateIds, $data, $adminId, $adminName);
    }

    public function batchAddTags(array $templateIds, array $tagIds, int $adminId, string $adminName): array
    {
        return $this->executeBatch('batch_add_tags', $templateIds, ['tag_ids' => $tagIds], $adminId, $adminName);
    }

    public function batchSetCategory(array $templateIds, int $categoryId, int $adminId, string $adminName): array
    {
        return $this->executeBatch('batch_set_category', $templateIds, ['category_id' => $categoryId], $adminId, $adminName);
    }

    public function batchSetIndustry(array $templateIds, string $industry, int $adminId, string $adminName): array
    {
        return $this->executeBatch('batch_set_industry', $templateIds, ['industry' => $industry], $adminId, $adminName);
    }

    public function previewAction(string $action, array $templateIds, array $params = []): array
    {
        $templates = TemplateStore::whereIn('id', $templateIds)
            ->field('id, name, status, price, category_id, industry')
            ->select()->toArray();
        return [
            'action' => $action,
            'count' => count($templates),
            'templates' => $templates,
            'params' => $params,
        ];
    }

    private function executeBatch(string $action, array $templateIds, array $data, int $adminId, string $adminName): array
    {
        $success = 0;
        $failed = 0;
        $errors = [];

        Db::transaction(function () use ($templateIds, $data, &$success, &$failed, &$errors) {
            foreach ($templateIds as $id) {
                try {
                    $tpl = TemplateStore::find($id);
                    if ($tpl) {
                        $tpl->save($data);
                        $success++;
                    } else {
                        $failed++;
                        $errors[] = "模板ID {$id} 不存在";
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "模板ID {$id}: " . $e->getMessage();
                }
            }
        });

        TemplateBatchLog::create([
            'operator_id' => $adminId,
            'operator_name' => $adminName,
            'action' => $action,
            'target_ids' => json_encode($templateIds),
            'params' => json_encode($data),
            'result' => json_encode(['success' => $success, 'failed' => $failed, 'errors' => $errors]),
        ]);

        return ['success' => $success, 'failed' => $failed, 'errors' => $errors];
    }
}

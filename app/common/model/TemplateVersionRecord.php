<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class TemplateVersionRecord extends Model
{
    protected $name = 'template_version_record';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'file_snapshot' => 'json',
        'file_diff'     => 'json',
    ];

    public static function getHistory(int $templateId, int $limit = 20): array
    {
        return self::where('template_id', $templateId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    public static function getLatestVersion(int $templateId): ?array
    {
        $record = self::where('template_id', $templateId)
            ->where('status', 'published')
            ->order('created_at', 'desc')
            ->find();
        return $record ? $record->toArray() : null;
    }

    public static function createVersion(
        int $templateId, string $version, string $changelog,
        array $fileSnapshot, array $fileDiff, int $operatorId, string $operatorName,
        int $grayscalePercent = 100, string $status = 'draft'
    ): int {
        $record = self::create([
            'template_id'       => $templateId,
            'version'           => $version,
            'changelog'         => $changelog,
            'file_snapshot'     => $fileSnapshot,
            'file_diff'         => $fileDiff,
            'grayscale_percent' => $grayscalePercent,
            'status'            => $status,
            'operator_id'       => $operatorId,
            'operator_name'     => $operatorName,
        ]);
        return (int)$record->id;
    }
}

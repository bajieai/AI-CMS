<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class TemplatePriceLog extends Model
{
    protected $name = 'template_price_log';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    public static function logChange(
        int $templateId, int $operatorId, string $operatorName,
        string $action, float $oldPrice, float $newPrice, string $reason = ''
    ): void {
        self::create([
            'template_id'   => $templateId,
            'operator_id'   => $operatorId,
            'operator_name' => $operatorName,
            'action'        => $action,
            'old_price'     => $oldPrice,
            'new_price'     => $newPrice,
            'reason'        => $reason,
        ]);
    }

    public static function getHistory(int $templateId, int $limit = 30): array
    {
        return self::where('template_id', $templateId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}

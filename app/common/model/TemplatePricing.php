<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;

class TemplatePricing extends Model
{
    protected $name = 'template_pricing';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = [
        'template_id' => 'integer', 'price' => 'float', 'original_price' => 'float',
        'trial_days' => 'integer', 'is_active' => 'integer', 'sort' => 'integer',
    ];

    const BILLING_ONE_TIME = 'one_time';
    const BILLING_RECURRING = 'recurring';
    const BILLING_FREE = 'free';
    const BILLING_TRIAL = 'trial';

    public static array $billingMap = [
        self::BILLING_ONE_TIME => '一次性买断',
        self::BILLING_RECURRING => '订阅制',
        self::BILLING_FREE => '免费',
        self::BILLING_TRIAL => '试用',
    ];

    public function template() { return $this->belongsTo(TemplateStore::class, 'template_id'); }
    public function scopeActive($query) { return $query->where('is_active', 1); }

    public static function getPricing(int $templateId): ?self
    {
        return self::where('template_id', $templateId)->where('is_active', 1)->find();
    }
}

<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;

class TemplateLicense extends Model
{
    protected $name = 'template_license';
    protected $autoWriteTimestamp = false;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = [
        'template_id' => 'integer', 'order_id' => 'integer', 'member_id' => 'integer',
        'expires_at' => 'integer', 'status' => 'integer', 'create_time' => 'integer',
    ];
    protected $json = ['domains'];

    const TYPE_PERMANENT = 'permanent';
    const TYPE_YEARLY = 'yearly';
    const TYPE_LIFETIME = 'lifetime';
    const STATUS_ACTIVE = 1;
    const STATUS_EXPIRED = 0;
    const STATUS_REVOKED = -1;

    public function template() { return $this->belongsTo(TemplateStore::class, 'template_id'); }
    public function member() { return $this->belongsTo(Member::class, 'member_id'); }

    public static function generateLicenseCode(): string
    {
        return 'TPL-' . strtoupper(bin2hex(random_bytes(8))) . '-' . date('Ymd');
    }

    public static function checkLicense(int $memberId, int $templateId): bool
    {
        return self::where('member_id', $memberId)
            ->where('template_id', $templateId)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) { $q->where('expires_at', 0)->whereOr('expires_at', '>', time()); })
            ->count() > 0;
    }
}

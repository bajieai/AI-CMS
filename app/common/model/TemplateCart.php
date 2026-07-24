<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;

class TemplateCart extends Model
{
    protected $name = 'template_cart';
    protected $autoWriteTimestamp = false;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = [
        'member_id' => 'integer', 'template_id' => 'integer', 'quantity' => 'integer',
    ];

    public function template() { return $this->belongsTo(TemplateStore::class, 'template_id'); }
    public function member() { return $this->belongsTo(Member::class, 'member_id'); }
}

<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 轮播图模型
 */
class Banner extends Model
{
    protected $name = 'banner';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'sort' => 'integer',
        'status' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
    ];

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [0 => '禁用', 1 => '启用'];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 获取是否有效期内
     */
    public function getIsActiveAttr($value, $data): bool
    {
        $now = time();
        if ($data['start_time'] > 0 && $now < $data['start_time']) {
            return false;
        }
        if ($data['end_time'] > 0 && $now > $data['end_time']) {
            return false;
        }
        return $data['status'] === 1;
    }
}

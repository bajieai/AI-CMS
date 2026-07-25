<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

class Plugin extends Model
{
    protected $name = 'plugin';
    protected $autoWriteTimestamp = false;

    public const STATUS_UNINSTALLED = 0;
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 2;
    public const STATUS_FAILED = 3;
}

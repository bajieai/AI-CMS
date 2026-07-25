<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

class PluginHook extends Model
{
    protected $name = 'plugin_hook';
    protected $autoWriteTimestamp = false;
}

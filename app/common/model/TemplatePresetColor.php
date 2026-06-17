<?php
/**
 * V2.9.23 C-4: 预设配色方案模型
 */

namespace app\common\model;

use think\Model;

class TemplatePresetColor extends Model
{
    protected $name = 'template_preset_color';
    protected $pk = 'id';

    protected $schema = [
        'id' => 'int',
        'name' => 'string',
        'description' => 'string',
        'colors' => 'json',
        'industry_tags' => 'string',
        'is_system' => 'int',
        'sort' => 'int',
        'create_time' => 'int',
    ];

    protected $json = ['colors'];
    protected $jsonAssoc = true;
}

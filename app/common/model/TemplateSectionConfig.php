<?php
/**
 * V2.9.23 C-2: 前台区块配置模型
 */

namespace app\common\model;

use think\Model;

class TemplateSectionConfig extends Model
{
    protected $name = 'template_section_config';
    protected $pk = 'id';

    protected $schema = [
        'id' => 'int',
        'theme_slug' => 'string',
        'member_id' => 'int',
        'page_type' => 'string',
        'sections' => 'json',
        'create_time' => 'int',
        'update_time' => 'int',
    ];

    protected $json = ['sections'];
    protected $jsonAssoc = true;

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}

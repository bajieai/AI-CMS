<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板备份记录模型 - V2.9.12
 */
class TemplateBackup extends Model
{
    protected $name = 'template_backup';
    protected $pk = 'id';

    protected $schema = [
        'id'           => 'int',
        'member_id'    => 'int',
        'theme_slug'   => 'string',
        'backup_name'  => 'string',
        'backup_file'  => 'string',
        'backup_size'  => 'int',
        'config_json'  => 'text',
        'is_auto'      => 'int',
        'create_time'  => 'int',
    ];

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 获取某主题的所有备份
     */
    public static function getBackups(int $memberId, string $themeSlug): array
    {
        return self::where('member_id', $memberId)
            ->where('theme_slug', $themeSlug)
            ->order('create_time', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 创建备份记录
     */
    public static function addBackup(int $memberId, string $themeSlug, string $name, string $file, int $size, array $config = [], bool $isAuto = false): self
    {
        return self::create([
            'member_id'   => $memberId,
            'theme_slug'  => $themeSlug,
            'backup_name' => $name,
            'backup_file' => $file,
            'backup_size' => $size,
            'config_json' => json_encode($config, JSON_UNESCAPED_UNICODE),
            'is_auto'     => $isAuto ? 1 : 0,
        ]);
    }

    /**
     * 获取备份的配置数据
     */
    public function getConfig(): array
    {
        if (empty($this->config_json)) return [];
        return json_decode($this->config_json, true) ?: [];
    }
}

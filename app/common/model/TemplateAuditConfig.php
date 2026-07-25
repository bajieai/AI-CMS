<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 审核配置模型 — V2.9.28 M-5
 */
class TemplateAuditConfig extends Model
{
    protected $name = 'template_audit_config';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    const LEVEL_SINGLE = 1;  // 单级审核
    const LEVEL_TWO = 2;     // 两级审核
    const LEVEL_THREE = 3;   // 三级审核

    /**
     * 获取模板的审核配置（回退到全局默认）
     */
    public static function getForTemplate(int $templateId): array
    {
        $config = self::where('template_id', $templateId)->find();
        if (!$config) {
            $config = self::where('template_id', 0)->find();
        }
        if (!$config) {
            // 返回默认值
            return [
                'audit_level' => self::LEVEL_TWO,
                'first_reviewer_id' => 0,
                'final_reviewer_id' => 0,
                'need_file_diff' => 1,
            ];
        }
        return $config->toArray();
    }
}

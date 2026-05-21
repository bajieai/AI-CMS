<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 媒体资源模型
 */
class Media extends Model
{
    protected $name = 'media';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'user_id' => 'integer',
        'filesize' => 'integer',
        'cate_id' => 'integer',
        'download_count' => 'integer',
    ];

    /**
     * 获取文件类型文本
     */
    public function getFiletypeTextAttr($value, $data): string
    {
        $map = ['image' => '图片', 'video' => '视频', 'file' => '文件'];
        return $map[$data['filetype']] ?? '未知';
    }

    /**
     * 获取格式化文件大小
     */
    public function getFilesizeTextAttr($value, $data): string
    {
        $size = $data['filesize'] ?? 0;
        if ($size >= 1073741824) {
            return round($size / 1073741824, 2) . ' GB';
        }
        if ($size >= 1048576) {
            return round($size / 1048576, 2) . ' MB';
        }
        if ($size >= 1024) {
            return round($size / 1024, 2) . ' KB';
        }
        return $size . ' B';
    }

    /**
     * 关联上传用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

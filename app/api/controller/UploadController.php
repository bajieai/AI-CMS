<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\api\controller;

use app\common\service\UploadService;

/**
 * 上传接口控制器
 */
class UploadController
{
    /**
     * 图片上传
     * POST /api/upload/image
     */
    public function image()
    {
        if (empty(session('user_id'))) {
            return json(['code' => 2, 'msg' => '请先登录', 'data' => null]);
        }

        $file = request()->file('file');
        if (empty($file)) {
            return json(['code' => 1, 'msg' => '请选择文件', 'data' => null]);
        }

        try {
            $service = new UploadService();
            $result = $service->uploadImage($file);
            return json([
                'code' => 0,
                'msg' => '上传成功',
                'data' => ['url' => $result['url']],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 4, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }
}

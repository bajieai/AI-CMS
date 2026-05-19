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

namespace app\admin\controller;

use app\common\service\ContentQualityService;

/**
 * V2.9.4 内容质量检测API控制器
 */
class QualityCheckController extends AdminBaseController
{
    /**
     * 执行质量检测（AJAX接口）
     */
    public function check()
    {
        $title = $this->request->post('title', '');
        $content = $this->request->post('content', '');
        $keywords = $this->request->post('keywords', '');

        if (empty($content)) {
            return json(['code' => 1, 'msg' => '内容不能为空']);
        }

        $result = ContentQualityService::check($title, $content, $keywords);

        if (!$result['success']) {
            return json(['code' => 1, 'msg' => $result['msg']]);
        }

        return json(['code' => 0, 'data' => $result]);
    }
}

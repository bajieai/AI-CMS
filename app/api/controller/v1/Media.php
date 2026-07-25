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

namespace app\api\controller\v1;

use app\common\model\Media as MediaModel;
use app\common\traits\ApiScopeCheck;
use think\Request;

/**
 * 媒体资源API
 * @api_group V1-媒体
 * @api_desc RESTful媒体资源接口，支持按类型筛选
 */
class Media
{
    use ApiScopeCheck;

    /**
     * 媒体列表
     * @api 媒体资源列表
     * @api_desc 分页获取媒体资源，可按文件类型筛选
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param string $filetype 文件类型筛选(image/video等)
     * @return json 返回媒体资源列表
     * @api_auth yes (scope: media:read)
     */
    public function index(Request $request)
    {
        $this->requireScope('media:read');

        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 10);
        $filetype = $request->get('filetype', '');

        $query = MediaModel::order('id', 'desc');
        if ($filetype !== '') {
            $query->where('filetype', $filetype);
        }

        $list = $query->page($page, $limit)->select();
        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }
}

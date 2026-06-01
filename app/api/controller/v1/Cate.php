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

use app\common\model\Cate as CateModel;
use app\common\traits\ApiScopeCheck;

/**
 * 分类API
 * @api_group V1-分类
 * @api_desc RESTful分类接口，支持列表和树形结构查询
 */
class Cate
{
    use ApiScopeCheck;

    /**
     * 分类列表
     * @api 分类列表
     * @api_desc 获取所有启用的分类，按排序字段升序排列
     * @return json 返回分类列表
     * @api_auth yes (scope: cate:read)
     */
    public function index()
    {
        $this->requireScope('cate:read');

        $list = CateModel::where('status', 1)->order('sort', 'asc')->select();
        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }

    /**
     * 分类树
     * @api 分类树形结构
     * @api_desc 获取所有分类的树形结构（父子关系嵌套）
     * @return json 返回树形分类数据
     * @api_auth yes (scope: cate:read)
     */
    public function tree()
    {
        $this->requireScope('cate:read');

        $list = CateModel::where('status', 1)->order('sort', 'asc')->select()->toArray();
        $tree = $this->buildTree($list);
        return json(['code' => 0, 'msg' => 'success', 'data' => $tree]);
    }

    protected function buildTree(array $data, int $parentId = 0): array
    {
        $tree = [];
        foreach ($data as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($data, $item['id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
}

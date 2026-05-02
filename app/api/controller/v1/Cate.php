<?php
declare(strict_types=1);

namespace app\api\controller\v1;

use app\common\model\Cate as CateModel;
use app\common\traits\ApiScopeCheck;

class Cate
{
    use ApiScopeCheck;

    public function index()
    {
        $this->requireScope('cate:read');

        $list = CateModel::where('status', 1)->order('sort', 'asc')->select();
        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }

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

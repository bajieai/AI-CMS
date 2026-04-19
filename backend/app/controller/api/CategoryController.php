<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\Category;
use app\exception\BusinessException;

/**
 * 分类控制器
 */
class CategoryController extends BaseController
{
    /**
     * 分类列表
     */
    public function index(): \think\Response
    {
        $flat = $this->request->param('flat', false);
        
        if ($flat) {
            $list = Category::getFlatList();
        } else {
            $list = Category::getTree();
        }
        
        return $this->success($list);
    }

    /**
     * 分类树
     */
    public function tree(): \think\Response
    {
        $tree = Category::getTree();
        
        return $this->success($tree);
    }

    /**
     * 创建分类
     */
    public function save(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['name'], $input);
        $this->validateData([
            'name' => 'require|max:50',
            'parent_id' => 'integer',
            'sort' => 'integer',
        ], $input);
        
        $parentId = (int) ($input['parent_id'] ?? 0);
        
        // 检查循环
        if ($parentId > 0) {
            $parent = Category::find($parentId);
            if (!$parent) {
                throw new BusinessException('父分类不存在', 400, ['parent_id' => '父分类不存在']);
            }
        }
        
        $data = [
            'name' => trim($input['name']),
            'slug' => $input['slug'] ?? $this->generateSlug($input['name']),
            'parent_id' => $parentId,
            'level' => $parentId > 0 ? Category::find($parentId)->level + 1 : 1,
            'description' => $input['description'] ?? '',

            'sort_order' => (int) ($input['sort'] ?? 0),
            'status' => (int) ($input['status'] ?? Category::STATUS_ENABLED),

            'content_count' => 0,
        ];
        
        $category = Category::create($data);
        
        return $this->success($category->getInfo(), '分类创建成功', 201);
    }

    /**
     * 更新分类
     */
    public function update(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少分类ID', 400);
        }
        
        $category = Category::find($id);
        
        if (!$category) {
            throw new BusinessException('分类不存在', 404);
        }
        
        $input = $this->getInput();
        
        if (isset($input['name'])) {
            $category->name = trim($input['name']);
            if (isset($input['slug']) || !$category->slug) {
                $category->slug = $input['slug'] ?? $this->generateSlug($input['name']);
            }
        }
        
        if (isset($input['parent_id'])) {
            $newParentId = (int) $input['parent_id'];
            
            // 不能将自己设为父分类
            if ($newParentId === $category->id) {
                throw new BusinessException('不能将自己设为父分类', 400, ['parent_id' => '不能将自己设为父分类']);
            }
            
            // 检查是否会创建循环
            if ($category->wouldCreateCycle($newParentId)) {
                throw new BusinessException('不能将子分类设为父分类，会创建循环', 400, ['parent_id' => '不能将子分类设为父分类']);
            }
            
            $category->parent_id = $newParentId;
            $category->level = $newParentId > 0 ? Category::find($newParentId)->level + 1 : 1;
        }
        
        if (isset($input['description'])) {
            $category->description = $input['description'];
        }
        

        
        if (isset($input['sort'])) {
            $category->sort_order = (int) $input['sort'];
        }
        
        if (isset($input['status'])) {
            $category->status = (int) $input['status'];
        }
        

        
        $category->save();
        
        // 更新所有后代的level
        if (isset($input['parent_id'])) {
            $this->updateDescendantLevels($category);
        }
        
        return $this->success($category->getInfo(), '分类更新成功');
    }

    /**
     * 删除分类
     */
    public function delete(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少分类ID', 400);
        }
        
        $category = Category::find($id);
        
        if (!$category) {
            throw new BusinessException('分类不存在', 404);
        }
        
        // 检查是否有子分类
        if ($category->children()->count() > 0) {
            throw new BusinessException('请先删除子分类', 400, ['children' => '该分类下存在子分类']);
        }
        
        // 检查是否有文章
        if ($category->articles()->count() > 0) {
            throw new BusinessException('该分类下存在文章，无法删除', 400, ['articles' => '该分类下存在文章']);
        }
        
        $category->delete();
        
        return $this->success(null, '删除成功');
    }

    /**
     * 查看分类
     */
    public function read(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少分类ID', 400);
        }
        
        $category = Category::with(['parent:id,name', 'children'])->find($id);
        
        if (!$category) {
            throw new BusinessException('分类不存在', 404);
        }
        
        $info = $category->getInfo();
        $info['parent'] = $category->parent ? [
            'id' => $category->parent->id,
            'name' => $category->parent->name,
        ] : null;
        $info['children'] = $category->children->toArray();
        
        return $this->success($info);
    }

    /**
     * 生成slug
     */
    protected function generateSlug(string $name): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}]/u', '-', $name);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = strtolower($slug);
        
        // 确保唯一
        $count = 0;
        $originalSlug = $slug;
        while (Category::where('slug', '=', $slug)->count() > 0) {
            $count++;
            $slug = $originalSlug . '-' . $count;
        }
        
        return $slug;
    }

    /**
     * 更新后代分类的层级
     */
    protected function updateDescendantLevels(Category $category): void
    {
        $children = $category->children;
        
        foreach ($children as $child) {
            $child->level = $category->level + 1;
            $child->save();
            $this->updateDescendantLevels($child);
        }
    }
}

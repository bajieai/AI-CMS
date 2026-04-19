<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\Tag;
use app\exception\BusinessException;

/**
 * 标签控制器
 */
class TagController extends BaseController
{
    /**
     * 标签列表
     */
    public function index(): \think\Response
    {
        $pageParams = $this->getPageParams();
        $keyword = $this->getSearchParams();
        
        $query = Tag::where('status', '=', Tag::STATUS_ENABLED);
        
        if ($keyword) {
            $query->whereLike('name', "%{$keyword}%");
        }
        
        $total = $query->count();
        // 注意：由于数据库可能没有 content_count 列，改为按 id 倒序排列
        $list = $query->order('id', 'desc')
            ->page($pageParams['page'], $pageParams['per_page'])
            ->select();
        
        return $this->paginate($list->toArray(), $total, $pageParams['page'], $pageParams['per_page']);
    }

    /**
     * 搜索标签
     */
    public function search(): \think\Response
    {
        $keyword = $this->getSearchParams();
        $limit = min(50, max(1, (int) $this->request->param('limit', 20)));
        
        if (!$keyword) {
            return $this->success([]);
        }
        
        $tags = Tag::search($keyword, $limit);
        
        return $this->success($tags);
    }

    /**
     * 创建标签
     */
    public function save(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['name'], $input);
        $this->validateData([
            'name' => 'require|max:30',
        ], $input);
        
        $name = trim($input['name']);
        
        // 检查是否已存在
        $exists = Tag::where('name', '=', $name)->find();
        if ($exists) {
            throw new BusinessException('标签已存在', 400, ['name' => '标签已存在']);
        }
        
        $data = [
            'name' => $name,
            'slug' => $input['slug'] ?? $this->generateSlug($name),
            'status' => Tag::STATUS_ENABLED,
            'content_count' => 0,
        ];
        
        $tag = Tag::create($data);
        
        return $this->success($tag->getInfo(), '标签创建成功', 201);
    }

    /**
     * 更新标签
     */
    public function update(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少标签ID', 400);
        }
        
        $tag = Tag::find($id);
        
        if (!$tag) {
            throw new BusinessException('标签不存在', 404);
        }
        
        $input = $this->getInput();
        
        if (isset($input['name'])) {
            $name = trim($input['name']);
            
            // 检查名称是否重复
            $exists = Tag::where('name', '=', $name)->where('id', '<>', $id)->find();
            if ($exists) {
                throw new BusinessException('标签名称已存在', 400, ['name' => '标签名称已存在']);
            }
            
            $tag->name = $name;
        }
        
        if (isset($input['slug'])) {
            $tag->slug = $input['slug'];
        }
        
        if (isset($input['status'])) {
            $tag->status = (int) $input['status'];
        }
        
        $tag->save();
        
        return $this->success($tag->getInfo(), '标签更新成功');
    }

    /**
     * 删除标签
     */
    public function delete(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少标签ID', 400);
        }
        
        $tag = Tag::find($id);
        
        if (!$tag) {
            throw new BusinessException('标签不存在', 404);
        }
        
        // 删除关联
        $tag->articles()->detach();
        $tag->delete();
        
        return $this->success(null, '删除成功');
    }

    /**
     * 查看标签
     */
    public function read(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少标签ID', 400);
        }
        
        $tag = Tag::with('articles')->find($id);
        
        if (!$tag) {
            throw new BusinessException('标签不存在', 404);
        }
        
        return $this->success($tag->getInfo());
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
        while (Tag::where('slug', '=', $slug)->count() > 0) {
            $count++;
            $slug = $originalSlug . '-' . $count;
        }
        
        return $slug;
    }
}

<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\Article;
use app\model\Category;
use app\model\Tag;
use app\exception\BusinessException;
use think\facade\Db;

/**
 * 信息控制器
 */
class ArticleController extends BaseController
{
    /**
     * 信息列表
     */
    public function index(): \think\Response
    {
        $pageParams = $this->getPageParams();
        $filters = $this->getFilterParams(['status', 'category_id', 'user_id', 'is_top', 'is_recommend']);
        $keyword = $this->getSearchParams();
        $sort = $this->getSortParams();
        
        $query = Article::with(['user:id,username,nickname', 'category:id,name', 'tags:id,name']);
        
        // 搜索
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('title', "%{$keyword}%")
                    ->whereOr('like', 'content', "%{$keyword}%");
            });
        }
        
        // 筛选
        if (!empty($filters['status'])) {
            $query->where('status', '=', (int) $filters['status']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', '=', (int) $filters['category_id']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', '=', (int) $filters['user_id']);
        }
        if (!empty($filters['is_top'])) {
            $query->where('is_top', '=', (int) $filters['is_top']);
        }
        if (!empty($filters['is_recommend'])) {
            $query->where('is_recommend', '=', (int) $filters['is_recommend']);
        }
        
        // 排序
        foreach ($sort as $field => $order) {
            $query->order($field, $order);
        }
        
        // 默认排序
        if (!isset($sort['created_at'])) {
            $query->order('created_at', 'desc');
        }
        
        $total = $query->count();
        $list = $query->page($pageParams['page'], $pageParams['per_page'])->select();
        
        // 处理数据
        $data = [];
        foreach ($list as $article) {
            $item = $article->toArray();
            $item['status_text'] = $article->getStatusText();
            $item['can_edit'] = $article->canEdit();
            $item['can_delete'] = $article->canDelete();
            $data[] = $item;
        }
        
        return $this->paginate($data, $total, $pageParams['page'], $pageParams['per_page']);
    }

    /**
     * 创建信息
     */
    public function save(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['title'], $input);
        $this->validateData([
            'title' => 'require|max:200',
            'category_id' => 'integer',
            'cover_image' => 'url',
        ], $input);
        
        $data = [
            'title' => trim($input['title']),
            'slug' => $input['slug'] ?? $this->generateSlug($input['title']),
            'content' => $input['content'] ?? '',
            'summary' => $input['summary'] ?? make_summary($input['content'] ?? '', 200),
            'cover_image' => $input['cover_image'] ?? '',
            'category_id' => $input['category_id'] ?? 0,
            'user_id' => $this->request->user_id,
            'status' => Article::STATUS_DRAFT,
            'is_top' => (int) ($input['is_top'] ?? 0),
            'is_recommend' => (int) ($input['is_recommend'] ?? 0),
            'published_at' => null,
        ];
        
        $article = Article::create($data);
        
        // 设置标签
        if (!empty($input['tags'])) {
            $tagIds = $this->processTags($input['tags']);
            $article->setTags($tagIds);
        }
        
        // 更新分类信息计数
        if ($article->category_id) {
            Category::find($article->category_id)?->updateContentCount();
        }
        
        $article->load(['user:id,username,nickname', 'category:id,name', 'tags']);
        
        return $this->success($article, '信息创建成功', 201);
    }

    /**
     * 更新信息
     */
    public function update(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少信息ID', 400);
        }
        
        $article = Article::find($id);
        
        if (!$article) {
            throw new BusinessException('信息不存在', 404);
        }
        
        if (!$article->canEdit()) {
            throw new BusinessException('当前状态不允许编辑', 400);
        }
        
        $input = $this->getInput();
        $oldCategoryId = $article->category_id;
        
        // 更新字段
        if (isset($input['title'])) {
            $article->title = trim($input['title']);
            $article->slug = $input['slug'] ?? $this->generateSlug($input['title']);
        }
        if (isset($input['content'])) {
            $article->content = $input['content'];
        }
        if (isset($input['summary'])) {
            $article->summary = $input['summary'];
        }
        if (isset($input['cover_image'])) {
            $article->cover_image = $input['cover_image'];
        }
        if (isset($input['category_id'])) {
            $article->category_id = (int) $input['category_id'];
        }
        if (isset($input['is_top'])) {
            $article->is_top = (int) $input['is_top'];
        }
        if (isset($input['is_recommend'])) {
            $article->is_recommend = (int) $input['is_recommend'];
        }
        
        $article->save();
        
        // 更新标签
        if (isset($input['tags'])) {
            $tagIds = $this->processTags($input['tags']);
            $article->setTags($tagIds);
        }
        
        // 更新分类计数
        if ($oldCategoryId != $article->category_id) {
            Category::find($oldCategoryId)?->updateContentCount();
            Category::find($article->category_id)?->updateContentCount();
        }
        
        $article->load(['user:id,username,nickname', 'category:id,name', 'tags']);
        
        return $this->success($article, '信息更新成功');
    }

    /**
     * 删除信息
     */
    public function delete(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少信息ID', 400);
        }
        
        $article = Article::find($id);
        
        if (!$article) {
            throw new BusinessException('信息不存在', 404);
        }
        
        if (!$article->canDelete()) {
            throw new BusinessException('当前状态不允许删除', 400);
        }
        
        $categoryId = $article->category_id;
        
        // 删除信息
        $article->tags()->detach();
        $article->delete();
        
        // 更新分类计数
        if ($categoryId) {
            Category::find($categoryId)?->updateContentCount();
        }
        
        return $this->success(null, '删除成功');
    }

    /**
     * 批量删除
     */
    public function batchDelete(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['ids'], $input);
        
        $ids = is_array($input['ids']) ? $input['ids'] : explode(',', $input['ids']);
        
        $deleted = 0;
        $failed = [];
        
        foreach ($ids as $id) {
            $article = Article::find((int) $id);
            if ($article && $article->canDelete()) {
                $article->tags()->detach();
                $article->delete();
                $deleted++;
            } else {
                $failed[] = $id;
            }
        }
        
        return $this->success([
            'deleted' => $deleted,
            'failed' => $failed,
        ], '批量删除完成');
    }

    /**
     * 批量操作
     */
    public function batch(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['ids', 'action'], $input);
        
        $ids = is_array($input['ids']) ? $input['ids'] : explode(',', $input['ids']);
        $action = $input['action'];
        
        $result = ['success' => 0, 'failed' => 0];
        
        Db::startTrans();
        try {
            switch ($action) {
                case 'publish':
                    foreach ($ids as $id) {
                        $article = Article::find((int) $id);
                        if ($article && $article->canTransition(Article::STATUS_PUBLISHED)) {
                            $article->transitionTo(Article::STATUS_PUBLISHED, '批量发布', $this->request->user_id);
                            $result['success']++;
                        } else {
                            $result['failed']++;
                        }
                    }
                    break;
                    
                case 'archive':
                    foreach ($ids as $id) {
                        $article = Article::find((int) $id);
                        if ($article && $article->canTransition(Article::STATUS_ARCHIVED)) {
                            $article->transitionTo(Article::STATUS_ARCHIVED, '批量归档', $this->request->user_id);
                            $result['success']++;
                        } else {
                            $result['failed']++;
                        }
                    }
                    break;
                    
                case 'delete':
                    foreach ($ids as $id) {
                        $article = Article::find((int) $id);
                        if ($article && $article->canDelete()) {
                            $article->tags()->detach();
                            $article->delete();
                            $result['success']++;
                        } else {
                            $result['failed']++;
                        }
                    }
                    break;
                    
                default:
                    throw new BusinessException('不支持的操作', 400);
            }
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new BusinessException($e->getMessage(), 500);
        }
        
        return $this->success($result, '批量操作完成');
    }

    /**
     * 查看信息
     */
    public function read(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少信息ID', 400);
        }
        
        $article = Article::with(['user:id,username,nickname,avatar', 'category', 'tags'])->find($id);
        
        if (!$article) {
            throw new BusinessException('信息不存在', 404);
        }
        
        // 增加浏览数
        $article->incrementViewCount();
        
        return $this->success($article->getFullInfo());
    }

    /**
     * 发布信息
     */
    public function publish(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少信息ID', 400);
        }
        
        $article = Article::find($id);
        
        if (!$article) {
            throw new BusinessException('信息不存在', 404);
        }
        
        if (!$article->canTransition(Article::STATUS_PUBLISHED)) {
            throw new BusinessException('当前状态不允许发布');
        }
        
        $input = $this->getInput();
        $reason = $input['reason'] ?? '';
        
        $article->transitionTo(Article::STATUS_PUBLISHED, $reason, $this->request->user_id);
        
        // 更新分类计数
        Category::find($article->category_id)?->updateContentCount();
        
        return $this->success($article, '发布成功');
    }

    /**
     * 归档信息
     */
    public function archive(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少信息ID', 400);
        }
        
        $article = Article::find($id);
        
        if (!$article) {
            throw new BusinessException('信息不存在', 404);
        }
        
        if (!$article->canTransition(Article::STATUS_ARCHIVED)) {
            throw new BusinessException('当前状态不允许归档');
        }
        
        $input = $this->getInput();
        $reason = $input['reason'] ?? '';
        
        $article->transitionTo(Article::STATUS_ARCHIVED, $reason, $this->request->user_id);
        
        // 更新分类计数
        Category::find($article->category_id)?->updateContentCount();
        
        return $this->success(null, '归档成功');
    }

    /**
     * 提交审核
     */
    public function submit(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少信息ID', 400);
        }
        
        $article = Article::find($id);
        
        if (!$article) {
            throw new BusinessException('信息不存在', 404);
        }
        
        if (!$article->canTransition(Article::STATUS_PENDING)) {
            throw new BusinessException('当前状态不允许提交审核');
        }
        
        $input = $this->getInput();
        $reason = $input['reason'] ?? '';
        
        $article->transitionTo(Article::STATUS_PENDING, $reason, $this->request->user_id);
        
        return $this->success(null, '提交审核成功');
    }

    /**
     * 拒绝信息
     */
    public function reject(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少信息ID', 400);
        }
        
        $article = Article::find($id);
        
        if (!$article) {
            throw new BusinessException('信息不存在', 404);
        }
        
        if (!$article->canTransition(Article::STATUS_REJECTED)) {
            throw new BusinessException('当前状态不允许拒绝');
        }
        
        $input = $this->getInput();
        $reason = $input['reason'] ?? '审核未通过';
        
        $article->transitionTo(Article::STATUS_REJECTED, $reason, $this->request->user_id);
        
        return $this->success(null, '已拒绝');
    }

    /**
     * 生成slug
     */
    protected function generateSlug(string $title): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}]/u', '-', $title);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = strtolower($slug);
        
        // 确保唯一
        $count = 0;
        $originalSlug = $slug;
        while (Article::where('slug', '=', $slug)->count() > 0) {
            $count++;
            $slug = $originalSlug . '-' . $count;
        }
        
        return $slug;
    }

    /**
     * 处理标签
     */
    protected function processTags(array $tags): array
    {
        $tagIds = [];
        
        foreach ($tags as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) {
                continue;
            }
            
            if (is_numeric($tagName)) {
                $tagIds[] = (int) $tagName;
            } else {
                $tag = Tag::findOrCreate($tagName);
                $tagIds[] = $tag->id;
            }
        }
        
        return array_unique($tagIds);
    }
}

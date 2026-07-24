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

namespace app\api\controller\mini;

use app\api\controller\BaseController;
use app\common\service\mini\MiniContentService;
use think\facade\Cache;

/**
 * 小程序内容API
 */
class ContentController extends BaseController
{
    protected MiniContentService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new MiniContentService();
    }

    /**
     * 统一JSON响应
     */
    protected function miniJson(mixed $data, string $message = 'success', int $code = 0): \think\Response
    {
        return json([
            'code'       => $code,
            'message'    => $message,
            'data'       => $data,
            'timestamp'  => time(),
            'request_id' => $this->requestId(),
        ]);
    }

    protected function requestId(): string
    {
        return bin2hex(random_bytes(8));
    }

    protected function getUserId(): int
    {
        $input = json_decode($this->request->getInput(), true);
        return (int) ($input['mini_user_id'] ?? 0);
    }

    /**
     * 内容列表
     */
    public function list(): \think\Response
    {
        $modelId = (int) $this->request->param('model_id', 0);
        $categoryId = (int) $this->request->param('category_id', 0);
        $page = (int) $this->request->param('page', 1);
        $limit = (int) $this->request->param('limit', 20);
        $limit = min($limit, 50);

        $data = $this->service->getContentList($modelId, $categoryId, $page, $limit);
        return $this->miniJson($data);
    }

    /**
     * 内容详情
     */
    public function detail(int $id): \think\Response
    {
        $data = $this->service->getContentDetail($id);
        if ($data === null) {
            return $this->miniJson(null, '内容不存在', 1);
        }
        return $this->miniJson($data);
    }

    /**
     * 搜索
     */
    public function search(): \think\Response
    {
        $keyword = $this->request->param('keyword', '');
        $page = (int) $this->request->param('page', 1);
        $data = $this->service->searchContents($keyword, $page);
        return $this->miniJson($data);
    }

    /**
     * 分类列表
     */
    public function category(): \think\Response
    {
        $modelId = (int) $this->request->param('model_id', 0);
        $data = $this->service->getCategories($modelId);
        return $this->miniJson($data);
    }

    /**
     * 标签列表
     */
    public function tag(): \think\Response
    {
        $data = $this->service->getTags();
        return $this->miniJson($data);
    }

    /**
     * 推荐内容
     */
    public function recommend(): \think\Response
    {
        $modelId = (int) $this->request->param('model_id', 0);
        $limit = (int) $this->request->param('limit', 10);
        $data = $this->service->getRecommend($modelId, $limit);
        return $this->miniJson($data);
    }

    /**
     * 热门内容
     */
    public function hot(): \think\Response
    {
        $modelId = (int) $this->request->param('model_id', 0);
        $limit = (int) $this->request->param('limit', 10);
        $data = $this->service->getHot($modelId, $limit);
        return $this->miniJson($data);
    }

    /**
     * 相关内容
     */
    public function related(int $id): \think\Response
    {
        $limit = (int) $this->request->param('limit', 5);
        $data = $this->service->getRelated($id, $limit);
        return $this->miniJson($data);
    }

    /**
     * 自定义字段
     */
    public function fields(int $id): \think\Response
    {
        $data = $this->service->getCustomFields($id);
        return $this->miniJson($data);
    }

    /**
     * 关联内容
     */
    public function relation(int $id): \think\Response
    {
        $limit = (int) $this->request->param('limit', 5);
        $data = $this->service->getRelated($id, $limit);
        return $this->miniJson($data);
    }
}

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
use app\common\service\mini\MiniTemplateService;

/**
 * 小程序入口
 * 首页数据聚合 + 页面数据
 */
class MiniController extends BaseController
{
    protected MiniContentService $contentService;
    protected MiniTemplateService $templateService;

    public function __construct()
    {
        parent::__construct(app());
        $this->contentService = new MiniContentService();
        $this->templateService = new MiniTemplateService();
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
            'request_id' => bin2hex(random_bytes(8)),
        ]);
    }

    /**
     * 首页数据聚合
     */
    public function index(): \think\Response
    {
        $data = [
            'banner'     => $this->contentService->getRecommend(0, 5),
            'categories' => $this->contentService->getCategories(),
            'recommend'  => $this->contentService->getRecommend(0, 10),
            'hot'        => $this->contentService->getHot(0, 10),
            'latest'     => $this->contentService->getContentList(0, 0, 1, 10),
            'theme'      => $this->templateService->getThemeConfig(),
        ];

        return $this->miniJson($data);
    }

    /**
     * 页面数据
     */
    public function page(string $name): \think\Response
    {
        $pageData = [];

        switch ($name) {
            case 'index':
                $pageData = [
                    'banner'     => $this->contentService->getRecommend(0, 5),
                    'recommend'  => $this->contentService->getRecommend(0, 10),
                    'hot'        => $this->contentService->getHot(0, 10),
                    'latest'     => $this->contentService->getContentList(0, 0, 1, 10),
                ];
                break;
            case 'list':
                $modelId = (int) $this->request->param('model_id', 0);
                $categoryId = (int) $this->request->param('category_id', 0);
                $page = (int) $this->request->param('page', 1);
                $pageData = $this->contentService->getContentList($modelId, $categoryId, $page);
                break;
            case 'category':
                $pageData = [
                    'categories' => $this->contentService->getCategories(),
                    'tags'       => $this->contentService->getTags(),
                ];
                break;
            default:
                $pageData = [];
        }

        $rendered = $this->templateService->renderPage($name, $pageData);
        return $this->miniJson($rendered);
    }
}

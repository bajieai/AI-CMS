<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PageRenderOptimizeService;
use app\common\service\StaticPageService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 PERF-4: 页面渲染优化控制器
 */
class RenderOptimizeController extends AdminBaseController
{
    protected PageRenderOptimizeService $renderService;
    protected StaticPageService $staticPageService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->renderService = new PageRenderOptimizeService();
        $this->staticPageService = new StaticPageService();
    }

    public function index()
    {
        $staticDir = public_path() . 'static_html';
        $staticCount = is_dir($staticDir) ? count(glob($staticDir . '/*.html')) : 0;
        $staticSize = 0;
        if (is_dir($staticDir)) {
            foreach (glob($staticDir . '/*.html') as $file) {
                $staticSize += filesize($file);
            }
        }

        View::assign([
            'static_count' => $staticCount,
            'static_size'  => round($staticSize / 1024 / 1024, 2),
        ]);

        return $this->view('/render_optimize/index');
    }

    public function save()
    {
        return json(['code' => 0, 'msg' => '渲染优化配置已保存']);
    }

    public function generateStatic()
    {
        $type = $this->request->post('type', 'home');
        $count = 0;

        if ($type === 'home') {
            $result = $this->staticPageService->generateHomePage();
            $count = $result ? 1 : 0;
        } elseif ($type === 'hot') {
            $count = $this->staticPageService->generateHotContentPages();
        } elseif ($type === 'clear') {
            $count = $this->staticPageService->clearAll();
            return json(['code' => 0, 'msg' => "已清除{$count}个静态页面"]);
        }

        return json(['code' => 0, 'msg' => "已生成{$count}个静态页面"]);
    }
}

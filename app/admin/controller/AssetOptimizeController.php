<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\perf\AssetOptimizeService;
use think\facade\Json;

/**
 * 静态资源优化控制器
 * V2.9.38 PERF-II-4
 */
class AssetOptimizeController extends AdminBaseController
{
    protected AssetOptimizeService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new AssetOptimizeService();
    }

    public function index()
    {
        $config = $this->service->getOptimizeConfig();
        return $this->view('asset_optimize/index', ['config' => $config]);
    }

    public function compress()
    {
        $type = $this->request->param('type', 'css');
        $content = $this->request->param('content', '');
        $result = match($type) {
            'css' => $this->service->compressCss($content),
            'js' => $this->service->compressJs($content),
            'html' => $this->service->compressHtml($content),
            default => $content,
        };
        return Json::success('压缩完成', ['original_size' => strlen($content), 'compressed_size' => strlen($result), 'content' => $result]);
    }

    public function merge()
    {
        $files = $this->request->param('files', []);
        $type = $this->request->param('type', 'css');
        $result = $this->service->mergeAssets($files, $type);
        return Json::success('合并完成', ['size' => strlen($result), 'content' => $result]);
    }

    public function cdnUpload()
    {
        $filePath = $this->request->param('file_path', '');
        $url = $this->service->uploadToCdn($filePath);
        return Json::success('上传成功', ['cdn_url' => $url]);
    }
}

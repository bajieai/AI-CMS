<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Content as ContentModel;
use app\common\service\ExportService;
use app\common\service\ImportService;
use think\Request;

class ExportController extends AdminBaseController
{
    public function index(Request $request)
    {
        if ($request->isPost()) {
            $type = $request->post('type', 'content');
            $format = $request->post('format', 'xlsx');

            $service = new ExportService;
            $headers = ['ID', '标题', '类型', '状态', '分类ID', '浏览量', '创建时间'];

            $generator = function () {
                $contents = ContentModel::where('status', '>=', 0)->cursor();
                foreach ($contents as $item) {
                    yield [
                        $item->id,
                        $item->title,
                        $item->type_text,
                        $item->status_text,
                        $item->cate_id,
                        $item->views,
                        date('Y-m-d H:i:s', $item->create_time),
                    ];
                }
            };

            if ($format === 'xlsx') {
                $service->toExcel('content_export', $headers, $generator());
            } else {
                $service->toCsv('content_export', $headers, $generator());
            }
        }

        return $this->view('/export_index');
    }
}
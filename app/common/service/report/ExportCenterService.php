<?php
declare(strict_types=1);

namespace app\common\service\report;

use app\common\service\ExportService;
use think\facade\Db;
use think\facade\Cache;

class ExportCenterService
{
    private const CACHE_TAG = 'export_center';

    public function export(array $params): array
    {
        $format = $params['format'] ?? 'excel';
        $dataSource = $params['data_source'] ?? 'content';
        $fields = $params['fields'] ?? ['*'];
        $tableMap = ['content' => 'content', 'member' => 'member', 'template_store' => 'template_store', 'push_channel' => 'push_channel', 'order' => 'paid_content_record'];
        $table = $tableMap[$dataSource] ?? 'content';
        $query = Db::name($table);
        if (!empty($params['start_date'])) $query->where('create_time', '>=', strtotime($params['start_date']));
        if (!empty($params['end_date'])) $query->where('create_time', '<', strtotime($params['end_date']) + 86400);
        $data = $query->field($fields)->select()->toArray();
        $fileName = $dataSource . '_' . date('YmdHis');

        switch ($format) {
            case 'csv':
                return $this->exportCsv($data, $fileName);
            case 'json':
                return $this->exportJson($data, $fileName);
            case 'excel':
            default:
                return $this->exportExcel($data, $fileName);
        }
    }

    public function createScheduledExport(array $config): array
    {
        $id = Db::name('export_schedule')->insertGetId(array_merge($config, ['status' => 1, 'create_time' => time()]));
        return ['success' => true, 'id' => $id];
    }

    public function getExportHistory(): array
    {
        return Db::name('export_log')->order('create_time', 'desc')->paginate(20)->toArray();
    }

    private function exportExcel(array $data, string $fileName): array
    {
        $exportService = new ExportService();
        $filePath = $exportService->exportGeneric($data, $fileName, 'xlsx');
        Db::name('export_log')->insert(['file_name' => $fileName, 'file_path' => $filePath, 'format' => 'excel', 'rows' => count($data), 'create_time' => time()]);
        return ['success' => true, 'file' => $filePath, 'rows' => count($data)];
    }

    private function exportCsv(array $data, string $fileName): array
    {
        $exportService = new ExportService();
        $filePath = $exportService->exportCsv($data, $fileName);
        Db::name('export_log')->insert(['file_name' => $fileName, 'file_path' => $filePath, 'format' => 'csv', 'rows' => count($data), 'create_time' => time()]);
        return ['success' => true, 'file' => $filePath, 'rows' => count($data)];
    }

    private function exportJson(array $data, string $fileName): array
    {
        $filePath = runtime_path() . 'export/' . $fileName . '.json';
        if (!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0777, true);
        file_put_contents($filePath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        Db::name('export_log')->insert(['file_name' => $fileName, 'file_path' => $filePath, 'format' => 'json', 'rows' => count($data), 'create_time' => time()]);
        return ['success' => true, 'file' => $filePath, 'rows' => count($data)];
    }
}

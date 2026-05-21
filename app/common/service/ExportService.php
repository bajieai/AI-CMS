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

namespace app\common\service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 数据报表导出服务 - V2.6
 * 基于PhpSpreadsheet生成Excel报表
 */
class ExportService
{
    /**
     * 导出内容数据报表
     * @param array $data 内容数据数组
     * @param string $filename 下载文件名（不含扩展名）
     */
    public static function exportContent(array $data, string $filename = 'content_report'): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 表头
        $headers = ['ID', '标题', '分类', '类型', '状态', '浏览量', '点赞数', '评论数', '创建时间'];
        $col = 1;
        foreach ($headers as $title) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1', $title);
            $col++;
        }

        // 数据
        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['id'] ?? '');
            $sheet->setCellValue('B' . $row, $item['title'] ?? '');
            $sheet->setCellValue('C' . $row, $item['cate_name'] ?? '');
            $sheet->setCellValue('D' . $row, $item['type_name'] ?? '');
            $sheet->setCellValue('E' . $row, $item['status_text'] ?? '');
            $sheet->setCellValue('F' . $row, $item['views'] ?? 0);
            $sheet->setCellValue('G' . $row, $item['like_count'] ?? 0);
            $sheet->setCellValue('H' . $row, $item['comment_count'] ?? 0);
            $sheet->setCellValue('I' . $row, !empty($item['create_time']) ? date('Y-m-d H:i:s', $item['create_time']) : '');
            $row++;
        }

        // 自动列宽
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        self::download($spreadsheet, $filename);
    }

    /**
     * 导出付费订单报表
     */
    public static function exportPaidOrders(array $data, string $filename = 'paid_order_report'): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['订单号', '会员ID', '内容ID', '类型', '金额', '支付方式', '状态', '支付时间', '创建时间'];
        $col = 1;
        foreach ($headers as $title) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1', $title);
            $col++;
        }

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['order_sn'] ?? '');
            $sheet->setCellValue('B' . $row, $item['member_id'] ?? '');
            $sheet->setCellValue('C' . $row, $item['content_id'] ?? '');
            $sheet->setCellValue('D' . $row, $item['type'] ?? '');
            $sheet->setCellValue('E' . $row, $item['price'] ?? 0);
            $sheet->setCellValue('F' . $row, $item['pay_type'] ?? '');
            $sheet->setCellValue('G' . $row, ($item['status'] ?? 0) == 1 ? '已支付' : '待支付');
            $sheet->setCellValue('H' . $row, !empty($item['paid_at']) ? date('Y-m-d H:i:s', $item['paid_at']) : '');
            $sheet->setCellValue('I' . $row, !empty($item['create_time']) ? date('Y-m-d H:i:s', $item['create_time']) : '');
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        self::download($spreadsheet, $filename);
    }

    /**
     * 通用Excel导出（实例方法，兼容ExportController）
     */
    public function toExcel(string $filename, array $headers, iterable $data): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $col = 1;
        foreach ($headers as $title) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1', $title);
            $col++;
        }

        $row = 2;
        foreach ($data as $item) {
            $col = 1;
            foreach ($item as $value) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row, $value);
                $col++;
            }
            $row++;
        }

        self::download($spreadsheet, $filename);
    }

    /**
     * 通用CSV导出（实例方法，兼容ExportController）
     */
    public function toCsv(string $filename, array $headers, iterable $data): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename) . '_' . date('Ymd_His') . '.csv"');
        header('Cache-Control: no-cache');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $headers);

        foreach ($data as $item) {
            fputcsv($output, array_values($item));
        }

        fclose($output);
        exit;
    }

    /**
     * V2.9.2 M23: 高级导出 — 支持筛选条件+字段选择+分块导出
     */
    public static function advancedExport(string $module, array $filters, array $fields, string $format = 'xlsx'): void
    {
        $exportConfig = self::getModuleExportConfig($module);
        $query = self::buildQuery($module, $filters, $exportConfig);

        if ($format === 'csv') {
            self::csvExport($query, $fields, $module);
        } else {
            self::xlsxChunkExport($query, $fields, $module);
        }
    }

    /**
     * CSV流式导出（大数据量，内存可控）
     */
    protected static function csvExport($query, array $fields, string $module): void
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $module) . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // 表头
        $headers = [];
        foreach ($fields as $field) {
            $headers[] = self::getFieldLabel($field);
        }
        fputcsv($output, $headers);

        // 分块写入
        $query->chunk(1000, function ($items) use ($output, $fields) {
            foreach ($items as $item) {
                $row = [];
                foreach ($fields as $field) {
                    $row[] = self::formatFieldValue($item, $field);
                }
                fputcsv($output, $row);
            }
        });

        fclose($output);
        exit;
    }

    /**
     * XLSX分块导出（中等数据量）
     */
    protected static function xlsxChunkExport($query, array $fields, string $module): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 表头
        $col = 1;
        foreach ($fields as $field) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1', self::getFieldLabel($field));
            $col++;
        }

        $row = 2;
        $query->chunk(1000, function ($items) use ($sheet, $fields, &$row) {
            foreach ($items as $item) {
                $col = 1;
                foreach ($fields as $field) {
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row, self::formatFieldValue($item, $field));
                    $col++;
                }
                $row++;
            }
        });

        self::download($spreadsheet, $module);
    }

    /**
     * 获取模块导出配置
     */
    protected static function getModuleExportConfig(string $module): array
    {
        $configs = [
            'content' => [
                'model'  => \app\common\model\Content::class,
                'fields' => ['id', 'title', 'cate_id', 'type', 'status', 'views', 'like_count', 'comment_count', 'create_time', 'update_time'],
                'relations' => ['cate' => 'name'],
            ],
            'member' => [
                'model'  => \app\common\model\Member::class,
                'fields' => ['id', 'username', 'nickname', 'email', 'points', 'total_points', 'level_id', 'create_time'],
                'relations' => ['level' => 'name'],
            ],
            'order' => [
                'model'  => \app\common\model\PaidOrder::class,
                'fields' => ['order_sn', 'member_id', 'content_id', 'type', 'price', 'pay_type', 'status', 'paid_at', 'create_time'],
            ],
        ];
        return $configs[$module] ?? [];
    }

    /**
     * 构建查询
     */
    protected static function buildQuery(string $module, array $filters, array $config)
    {
        $modelClass = $config['model'] ?? \app\common\model\Content::class;
        $query = $modelClass::query();

        if (!empty($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('create_time', '>=', strtotime($filters['start_date']));
        }
        if (!empty($filters['end_date'])) {
            $query->where('create_time', '<=', strtotime($filters['end_date'] . ' 23:59:59'));
        }
        if (!empty($filters['keyword'])) {
            $query->where('title', 'like', '%' . $filters['keyword'] . '%');
        }

        return $query;
    }

    /**
     * 获取字段中文标签
     */
    protected static function getFieldLabel(string $field): string
    {
        $labels = [
            'id' => 'ID', 'title' => '标题', 'cate_id' => '分类ID', 'cate_name' => '分类',
            'type' => '类型', 'status' => '状态', 'views' => '浏览量',
            'like_count' => '点赞数', 'comment_count' => '评论数',
            'create_time' => '创建时间', 'update_time' => '更新时间',
            'username' => '用户名', 'nickname' => '昵称', 'email' => '邮箱',
            'email' => '邮箱', 'points' => '积分', 'total_points' => '总积分',
            'level_id' => '等级ID', 'level_name' => '等级',
            'order_sn' => '订单号', 'member_id' => '会员ID',
            'content_id' => '内容ID', 'price' => '金额',
            'pay_type' => '支付方式', 'paid_at' => '支付时间',
        ];
        return $labels[$field] ?? $field;
    }

    /**
     * 格式化字段值
     */
    protected static function formatFieldValue($item, string $field): string
    {
        $value = $item[$field] ?? '';

        if (in_array($field, ['create_time', 'update_time', 'paid_at']) && is_numeric($value)) {
            return date('Y-m-d H:i:s', (int) $value);
        }
        if ($field === 'status') {
            $map = [0 => '草稿', 1 => '待审', 2 => '已发布', -1 => '已删除'];
            return $map[$value] ?? $value;
        }
        if ($field === 'type') {
            $map = [1 => '产品', 2 => '案例', 3 => '新闻', 4 => '下载', 5 => '招聘', 6 => '单页'];
            return $map[$value] ?? $value;
        }
        if ($field === 'cate_name' && is_array($item) && isset($item['cate']['name'])) {
            return $item['cate']['name'];
        }
        if ($field === 'level_name' && is_array($item) && isset($item['level']['name'])) {
            return $item['level']['name'];
        }

        return (string) $value;
    }

    /**
     * 通用下载响应
     */
    protected static function download(Spreadsheet $spreadsheet, string $filename): void
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/u', '_', $filename) . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

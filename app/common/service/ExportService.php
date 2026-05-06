<?php
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
     * 通用下载响应
     */
    protected static function download(Spreadsheet $spreadsheet, string $filename): void
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename) . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

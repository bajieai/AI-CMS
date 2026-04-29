<?php
declare(strict_types=1);

namespace app\common\service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 导出服务
 */
class ExportService
{
    public function __construct()
    {
        if (!class_exists(Spreadsheet::class)) {
            throw new \RuntimeException('请先安装依赖：composer require phpoffice/phpspreadsheet');
        }
    }
    /**
     * 导出Excel
     */
    public function toExcel(string $filename, array $headers, \Generator $dataGenerator): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // 表头
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        $row = 2;
        foreach ($dataGenerator as $data) {
            $col = 1;
            foreach ($data as $value) {
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;

            if ($row % 1000 === 0) {
                ob_flush();
                flush();
            }
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 导出CSV
     */
    public function toCsv(string $filename, array $headers, \Generator $dataGenerator): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $headers);

        $count = 0;
        foreach ($dataGenerator as $data) {
            fputcsv($output, $data);
            $count++;
            if ($count % 1000 === 0) {
                ob_flush();
                flush();
            }
        }

        fclose($output);
        exit;
    }
}
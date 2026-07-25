<?php

declare(strict_types=1);

namespace app\common\service;

/**
 * V2.9.25 N-4: 报表导出服务
 *
 * 支持 CSV / Excel / PDF 多格式导出
 * Excel 需 phpoffice/phpspreadsheet，PDF 需 dompdf/dompdf
 * 未安装依赖时自动降级为 CSV
 */
class ReportService
{
    /**
     * 导出安装趋势 CSV
     */
    public function exportInstallTrendCsv(array $stats): string
    {
        $csv = "\xEF\xBB\xBF日期,安装量,卸载量,净增量\n";
        foreach ($stats['install_trend'] ?? [] as $item) {
            $installs = $item['count'] ?? 0;
            $uninstalls = $item['uninstall_count'] ?? 0;
            $net = $installs - $uninstalls;
            $csv .= "{$item['date']},{$installs},{$uninstalls},{$net}\n";
        }
        return $csv;
    }

    /**
     * 导出使用统计 CSV
     */
    public function exportUsageStatsCsv(array $overview, array $trend): string
    {
        $csv = "\xEF\xBB\xBF模板使用统计报表\n\n";
        $csv .= "统计周期,{$overview['date_range']['start']} 至 {$overview['date_range']['end']}\n";
        $csv .= "总浏览量,{$overview['total_views']}\n";
        $csv .= "独立访客,{$overview['unique_visitors']}\n";
        $csv .= "安装次数,{$overview['installs']}\n";
        $csv .= "卸载次数,{$overview['uninstalls']}\n";
        $csv .= "7日DAU均值,{$overview['dau_7day_avg']}\n";
        $csv .= "MAU,{$overview['mau']}\n\n";
        $csv .= "日期,浏览量,访客数,安装量,卸载量\n";
        foreach ($trend as $item) {
            $csv .= "{$item['stats_date']},{$item['views']},{$item['visitors']},{$item['installs']},{$item['uninstalls']}\n";
        }
        return $csv;
    }

    /**
     * 导出营收统计 CSV
     */
    public function exportRevenueCsv(array $overview): string
    {
        $csv = "\xEF\xBB\xBF模板商店营收统计报表\n\n";
        $csv .= "统计周期,{$overview['date_range']['start']} 至 {$overview['date_range']['end']}\n";
        $csv .= "总收入,{$overview['total_revenue']}\n";
        $csv .= "订单数,{$overview['order_count']}\n";
        $csv .= "客单价,{$overview['avg_order_value']}\n";
        $csv .= "退款金额,{$overview['refund_amount']}\n";
        $csv .= "退款订单,{$overview['refund_count']}\n";
        $csv .= "待支付订单,{$overview['pending_count']}\n\n";
        $csv .= "日期,收入,订单数\n";
        foreach ($overview['revenue_trend'] ?? [] as $item) {
            $csv .= "{$item['date']},{$item['revenue']},{$item['orders']}\n";
        }
        $csv .= "\n模板收入排行\n";
        $csv .= "模板ID,模板名称,总收入,订单数\n";
        foreach ($overview['top_templates'] ?? [] as $item) {
            $csv .= "{$item['template_id']},{$item['template_name']},{$item['total_revenue']},{$item['order_count']}\n";
        }
        return $csv;
    }

    /**
     * 导出 Excel（如果 phpspreadsheet 可用）
     */
    public function exportExcel(string $type, array $data): ?string
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            return null; // 降级到 CSV
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        switch ($type) {
            case 'install_trend':
                $sheet->setTitle('安装趋势');
                $sheet->setCellValue('A1', '日期')->setCellValue('B1', '安装量')->setCellValue('C1', '卸载量')->setCellValue('D1', '净增量');
                $row = 2;
                foreach ($data['install_trend'] ?? [] as $item) {
                    $sheet->setCellValue("A{$row}", $item['date'])
                          ->setCellValue("B{$row}", $item['count'] ?? 0)
                          ->setCellValue("C{$row}", $item['uninstall_count'] ?? 0)
                          ->setCellValue("D{$row}", ($item['count'] ?? 0) - ($item['uninstall_count'] ?? 0));
                    $row++;
                }
                break;

            case 'revenue':
                $sheet->setTitle('营收统计');
                $sheet->setCellValue('A1', '日期')->setCellValue('B1', '收入')->setCellValue('C1', '订单数');
                $row = 2;
                foreach ($data['revenue_trend'] ?? [] as $item) {
                    $sheet->setCellValue("A{$row}", $item['date'])
                          ->setCellValue("B{$row}", $item['revenue'])
                          ->setCellValue("C{$row}", $item['orders']);
                    $row++;
                }
                break;

            default:
                $sheet->setCellValue('A1', '无数据');
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    /**
     * 导出 HTML 报表（用于 PDF 转换或直接打印）
     */
    public function exportHtmlReport(string $title, array $overview, array $trend = []): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>';
        $html .= '<style>body{font-family:Microsoft YaHei,sans-serif;padding:20px}table{border-collapse:collapse;width:100%;margin:10px 0}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f5f5f5}h1{color:#333}.summary{background:#f0f8ff;padding:15px;border-radius:5px;margin:10px 0}</style>';
        $html .= '</head><body>';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<div class="summary"><p>统计周期：' . ($overview['date_range']['start'] ?? '') . ' 至 ' . ($overview['date_range']['end'] ?? '') . '</p></div>';

        $html .= '<table><tr><th>指标</th><th>数值</th></tr>';
        foreach ($overview as $key => $value) {
            if (is_scalar($value)) {
                $html .= '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars((string)$value) . '</td></tr>';
            }
        }
        $html .= '</table>';

        if (!empty($trend)) {
            $html .= '<h2>趋势数据</h2><table><tr>';
            $headers = array_keys($trend[0] ?? []);
            foreach ($headers as $h) {
                $html .= '<th>' . htmlspecialchars($h) . '</th>';
            }
            $html .= '</tr>';
            foreach ($trend as $row) {
                $html .= '<tr>';
                foreach ($headers as $h) {
                    $html .= '<td>' . htmlspecialchars((string)($row[$h] ?? '')) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        $html .= '<p style="text-align:center;color:#999;margin-top:30px">生成时间：' . date('Y-m-d H:i:s') . '</p>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * 导出 PDF（如果 dompdf 可用）
     */
    public function exportPdf(string $title, array $overview, array $trend = []): ?string
    {
        if (!class_exists('\Dompdf\Dompdf')) {
            return null; // 降级
        }

        $html = $this->exportHtmlReport($title, $overview, $trend);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * 智能导出：根据可用依赖选择最佳格式
     */
    public function smartExport(string $type, array $data, string $format = 'csv'): array
    {
        switch ($format) {
            case 'excel':
                $content = $this->exportExcel($type, $data);
                if ($content !== null) {
                    return ['content' => $content, 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'ext' => 'xlsx'];
                }
                // 降级到 CSV
                $format = 'csv';
                // no break

            case 'pdf':
                $content = $this->exportPdf($type, $data, $data['revenue_trend'] ?? []);
                if ($content !== null) {
                    return ['content' => $content, 'mime' => 'application/pdf', 'ext' => 'pdf'];
                }
                // 降级到 CSV
                $format = 'csv';
                // no break

            case 'csv':
            default:
                $csv = '';
                switch ($type) {
                    case 'install_trend':
                        $csv = $this->exportInstallTrendCsv($data);
                        break;
                    case 'usage_stats':
                        $csv = $this->exportUsageStatsCsv($data, $data['usage_trend'] ?? []);
                        break;
                    case 'revenue':
                        $csv = $this->exportRevenueCsv($data);
                        break;
                }
                return ['content' => $csv, 'mime' => 'text/csv; charset=utf-8', 'ext' => 'csv'];
        }
    }
}

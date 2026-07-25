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

namespace app\common\service\data;

use app\admin\model\DataReport;
use think\facade\Db;
use think\facade\Log;

/**
 * 报表导出服务 - V2.9.40 DATA-DEEP2
 *
 * 支持 PDF / Excel / CSV / HTML / Image 多格式导出
 */
class ReportExportService
{
    /** @var string 导出文件存放目录（相对于 public） */
    protected string $exportDir = 'uploads/exports';

    /**
     * 导出报表
     *
     * @param int    $reportId 报表ID
     * @param string $format   导出格式：pdf|excel|csv|html|image
     * @param array  $config   额外配置（时间范围/筛选等）
     * @return string 导出文件相对路径
     */
    public function export(int $reportId, string $format, array $config = []): string
    {
        $report = DataReport::find($reportId);
        if (!$report) {
            throw new \RuntimeException('报表不存在');
        }

        // 生成报表数据
        $smartReportService = new SmartReportService();
        $data = $this->buildExportData($report, $config);

        $format = strtolower($format);
        $fileName = 'report_' . $reportId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));

        return match ($format) {
            'pdf'   => $this->exportPdf($data, array_merge($config, ['filename' => $fileName])),
            'excel' => $this->exportExcel($data, array_merge($config, ['filename' => $fileName])),
            'csv'   => $this->exportCsv($data),
            'html'  => $this->exportHtml($data),
            default => throw new \RuntimeException('不支持的导出格式：' . $format),
        };
    }

    /**
     * 构建导出数据
     */
    protected function buildExportData(DataReport $report, array $config): array
    {
        $dataConfig = $report->data_config ?? [];
        $chartConfig = $report->chart_config ?? [];

        // 获取数据源数据
        $dataSource = $dataConfig['data_source'] ?? 'content';
        $startTime = $config['start_time'] ?? ($dataConfig['start_time'] ?? date('Y-m-d 00:00:00', strtotime('-30 days')));
        $endTime = $config['end_time'] ?? ($dataConfig['end_time'] ?? date('Y-m-d 23:59:59'));

        $rows = $this->fetchData($dataSource, $startTime, $endTime, $dataConfig);

        return [
            'report'      => $report->toArray(),
            'data'        => $rows,
            'summary'     => $this->buildSummary($rows),
            'chart_config'=> $chartConfig,
            'export_time' => date('Y-m-d H:i:s'),
            'time_range'  => [$startTime, $endTime],
        ];
    }

    /**
     * 获取数据
     */
    protected function fetchData(string $source, string $startTime, string $endTime, array $config): array
    {
        $tableMap = [
            'content'        => 'content',
            'member'         => 'member',
            'visit'          => 'visit_log',
            'order'          => 'paid_content_record',
            'ai_log'         => 'ai_log',
            'template_store' => 'template_order',
            'comment'        => 'comment',
            'share'          => 'share_log',
        ];

        $table = $tableMap[$source] ?? 'content';
        $timeField = $config['time_field'] ?? 'create_time';

        $query = Db::name($table)
            ->where($timeField, '>=', $startTime)
            ->where($timeField, '<=', $endTime);

        // 应用筛选
        if (!empty($config['filters'])) {
            foreach ($config['filters'] as $field => $value) {
                $query->where($field, $value);
            }
        }

        // 分组
        if (!empty($config['group_by'])) {
            $query->group($config['group_by']);
        }

        // 限制条数
        $limit = $config['limit'] ?? 1000;
        return $query->limit($limit)->select()->toArray();
    }

    /**
     * 构建汇总
     */
    protected function buildSummary(array $rows): array
    {
        $total = count($rows);
        $summary = ['total' => $total];

        if ($total > 0) {
            $numericFields = array_filter(array_keys($rows[0]), function ($key) use ($rows) {
                return is_numeric($rows[0][$key] ?? null);
            });

            foreach ($numericFields as $field) {
                $values = array_column($rows, $field);
                $summary[$field] = [
                    'sum'   => array_sum($values),
                    'avg'   => round(array_sum($values) / $total, 2),
                    'max'   => max($values),
                    'min'   => min($values),
                ];
            }
        }

        return $summary;
    }

    /**
     * 导出 PDF — 基于 HTML→TCPDF（若 TCPDF 不可用则降级为 HTML）
     *
     * @param array $data   报表数据
     * @param array $config 配置
     * @return string 文件相对路径
     */
    public function exportPdf(array $data, array $config): string
    {
        $html = $this->renderHtmlTemplate($data, true);
        $fileName = ($config['filename'] ?? 'report_' . time()) . '.pdf';
        $filePath = $this->getExportPath($fileName);

        // 尝试使用 TCPDF
        if (class_exists(\TCPDF::class)) {
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator('AI-CMS');
            $pdf->SetAuthor('八界AI-CMS');
            $pdf->SetTitle($data['report']['name'] ?? '报表导出');
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($filePath, 'F');
        } else {
            // 降级：生成 HTML 并改扩展名提示
            Log::warning('[ReportExportService] TCPDF not available, falling back to HTML');
            $fileName = str_replace('.pdf', '.html', $fileName);
            $filePath = $this->getExportPath($fileName);
            file_put_contents($filePath, $html);
        }

        return $this->exportDir . '/' . $fileName;
    }

    /**
     * 导出 Excel — 基于 PhpSpreadsheet（若不可用则降级为 CSV）
     *
     * @param array $data   报表数据
     * @param array $config 配置
     * @return string 文件相对路径
     */
    public function exportExcel(array $data, array $config): string
    {
        $fileName = ($config['filename'] ?? 'report_' . time()) . '.xlsx';
        $filePath = $this->getExportPath($fileName);

        // 尝试使用 PhpSpreadsheet
        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // 标题行
            $rowNum = 1;
            $sheet->setCellValue('A' . $rowNum, $data['report']['name'] ?? '报表');
            $rowNum++;
            $sheet->setCellValue('A' . $rowNum, '导出时间：' . ($data['export_time'] ?? date('Y-m-d H:i:s')));
            $rowNum++;
            $sheet->setCellValue('A' . $rowNum, '时间范围：' . ($data['time_range'][0] ?? '') . ' ~ ' . ($data['time_range'][1] ?? ''));
            $rowNum += 2;

            // 表头
            $rows = $data['data'] ?? [];
            if (!empty($rows)) {
                $headers = array_keys($rows[0]);
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . $rowNum, $header);
                    $col++;
                }
                $rowNum++;

                // 数据行
                foreach ($rows as $row) {
                    $col = 'A';
                    foreach ($headers as $header) {
                        $sheet->setCellValue($col . $rowNum, $row[$header] ?? '');
                        $col++;
                    }
                    $rowNum++;
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);
        } else {
            // 降级为 CSV
            Log::warning('[ReportExportService] PhpSpreadsheet not available, falling back to CSV');
            $fileName = str_replace('.xlsx', '.csv', $fileName);
            $filePath = $this->getExportPath($fileName);
            file_put_contents($filePath, $this->exportCsv($data));
        }

        return $this->exportDir . '/' . $fileName;
    }

    /**
     * 导出 CSV
     *
     * @param array $data 报表数据
     * @return string CSV 文件相对路径
     */
    public function exportCsv(array $data): string
    {
        $fileName = 'report_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.csv';
        $filePath = $this->getExportPath($fileName);

        $fp = fopen($filePath, 'w');
        // 写入 BOM 头确保 Excel 正确识别 UTF-8
        fwrite($fp, "\xEF\xBB\xBF");

        $rows = $data['data'] ?? [];

        if (!empty($rows)) {
            // 表头
            fputcsv($fp, array_keys($rows[0]));

            // 数据
            foreach ($rows as $row) {
                fputcsv($fp, array_map(function ($v) {
                    return is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
                }, array_values($row)));
            }
        } else {
            fputcsv($fp, ['暂无数据']);
        }

        fclose($fp);
        return $this->exportDir . '/' . $fileName;
    }

    /**
     * 导出 HTML
     *
     * @param array $data 报表数据
     * @return string HTML 文件相对路径
     */
    public function exportHtml(array $data): string
    {
        $fileName = 'report_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.html';
        $filePath = $this->getExportPath($fileName);

        $html = $this->renderHtmlTemplate($data, false);
        file_put_contents($filePath, $html);

        return $this->exportDir . '/' . $fileName;
    }

    /**
     * 渲染 HTML 模板
     */
    protected function renderHtmlTemplate(array $data, bool $forPdf = false): string
    {
        $report = $data['report'] ?? [];
        $rows = $data['data'] ?? [];
        $summary = $data['summary'] ?? [];

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<title>' . ($report['name'] ?? '报表') . '</title>';
        $html .= '<style>';
        $html .= 'body{font-family:Arial,sans-serif;margin:20px;}';
        $html .= 'h1{color:#333;font-size:20px;}';
        $html .= 'table{width:100%;border-collapse:collapse;margin-top:10px;}';
        $html .= 'th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:12px;}';
        $html .= 'th{background:#f5f5f5;}';
        $html .= '.meta{color:#666;font-size:12px;margin:5px 0;}';
        $html .= '.summary{background:#f9f9f9;padding:10px;margin:10px 0;border-radius:4px;}';
        $html .= '</style></head><body>';

        $html .= '<h1>' . ($report['name'] ?? '报表导出') . '</h1>';
        $html .= '<div class="meta">报表类型：' . ($report['report_type'] ?? '') . '</div>';
        $html .= '<div class="meta">导出时间：' . ($data['export_time'] ?? '') . '</div>';
        $html .= '<div class="meta">时间范围：' . ($data['time_range'][0] ?? '') . ' ~ ' . ($data['time_range'][1] ?? '') . '</div>';

        // 汇总
        if (!empty($summary) && count($summary) > 1) {
            $html .= '<div class="summary"><strong>数据汇总</strong><br>';
            foreach ($summary as $field => $stats) {
                if (is_array($stats)) {
                    $html .= $field . ': 总和=' . ($stats['sum'] ?? 0) . ' 均值=' . ($stats['avg'] ?? 0) . ' 最大=' . ($stats['max'] ?? 0) . ' 最小=' . ($stats['min'] ?? 0) . '<br>';
                } else {
                    $html .= $field . ': ' . $stats . '<br>';
                }
            }
            $html .= '</div>';
        }

        // 数据表
        if (!empty($rows)) {
            $html .= '<table><thead><tr>';
            foreach (array_keys($rows[0]) as $header) {
                $html .= '<th>' . htmlspecialchars((string)$header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            $count = 0;
            foreach ($rows as $row) {
                if ($forPdf && $count >= 100) {
                    $html .= '<tr><td colspan="' . count($rows[0]) . '">... 更多数据请查看完整导出</td></tr>';
                    break;
                }
                $html .= '<tr>';
                foreach ($row as $val) {
                    $display = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val;
                    $html .= '<td>' . htmlspecialchars($display) . '</td>';
                }
                $html .= '</tr>';
                $count++;
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>暂无数据</p>';
        }

        $html .= '</body></html>';
        return $html;
    }

    /**
     * 获取导出文件完整路径
     */
    protected function getExportPath(string $fileName): string
    {
        $dir = public_path() . $this->exportDir;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . DIRECTORY_SEPARATOR . $fileName;
    }
}

<?php
declare(strict_types=1);

namespace app\common\service;

/**
 * 导入服务
 */
class ImportService
{
    /**
     * 解析CSV文件
     */
    public function parseCsv(string $filePath): array
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) === count($headers)) {
                    $rows[] = array_combine($headers, $data);
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * 批量创建内容
     */
    public function batchCreateContent(array $rows, int $userId): array
    {
        $success = 0;
        $fail = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $data = [
                    'title'   => $row['title'] ?? '',
                    'content' => $row['content'] ?? '',
                    'excerpt' => $row['excerpt'] ?? '',
                    'type'    => $row['type'] ?? 1,
                    'cate_id' => $row['cate_id'] ?? 0,
                    'user_id' => $userId,
                    'status'  => 2,
                ];

                if (empty($data['title'])) {
                    throw new \Exception('标题不能为空');
                }

                \app\common\model\Content::create($data);
                $success++;
            } catch (\Exception $e) {
                $fail++;
                $errors[] = "第" . ($index + 2) . "行: " . $e->getMessage();
            }
        }

        return [
            'success' => $success,
            'fail'    => $fail,
            'errors'  => $errors,
        ];
    }
}
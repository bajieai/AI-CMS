<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

/**
 * 导入服务
 */
class ImportService
{
    /**
     * 导入CSV文件（组合解析+批量创建）
     */
    public function importCsv(string $filePath, int $userId = 0): array
    {
        $rows = $this->parseCsv($filePath);
        if (empty($rows)) {
            throw new \Exception('CSV文件为空或格式不正确');
        }
        return $this->batchCreateContent($rows, $userId);
    }

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
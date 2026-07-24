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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Subscriber;

/**
 * 订阅者管理控制器 - V2.9.18 D-3
 * 后台管理：列表/添加/删除/导出
 */
class SubscriberController extends AdminBaseController
{
    /**
     * 订阅者列表页
     */
    public function index()
    {
        return $this->view('/subscriber');
    }

    /**
     * AJAX 列表
     */
    public function list()
    {
        $page     = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('limit', 20);
        $status   = $this->request->get('status', '');
        $tag      = $this->request->get('tag', '');
        $showInvalid = (int) $this->request->get('show_invalid', 0);

        $query = Subscriber::order('id', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }
        if ($tag !== '') {
            $query->where('tag', $tag);
        }
        // 默认过滤无效订阅者，除非显式开启「显示无效」
        if (!$showInvalid) {
            $query->where('status', '<>', Subscriber::STATUS_INVALID);
        }

        $total = $query->count();
        $data  = $query->page($page, $pageSize)->select();

        // 获取无效订阅者数量（用于灰色提示）
        $invalidCount = Subscriber::where('status', Subscriber::STATUS_INVALID)->count();

        return $this->success('ok', [
            'data' => $data,
            'total' => $total,
            'invalid_count' => $invalidCount,
        ]);
    }

    /**
     * 手动添加订阅者
     */
    public function add()
    {
        $email = $this->request->post('email', '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('邮箱格式不正确');
        }

        $exists = Subscriber::where('email', $email)->find();
        if ($exists) {
            return $this->error('该邮箱已存在');
        }

        Subscriber::create([
            'email'         => $email,
            'status'        => Subscriber::STATUS_CONFIRMED,
            'confirm_token' => Subscriber::generateToken(),
            'source'        => 'admin_add',
            'subscribed_at' => date('Y-m-d H:i:s'),
            'confirmed_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->success('添加成功');
    }

    /**
     * 删除订阅者
     */
    public function delete()
    {
        $id = $this->request->post('id', 0);
        $subscriber = Subscriber::find((int) $id);
        if (!$subscriber) {
            return $this->error('订阅者不存在');
        }
        $subscriber->delete();
        return $this->success('删除成功');
    }

    /**
     * 导出 CSV
     */
    public function export()
    {
        $list = Subscriber::where('status', Subscriber::STATUS_CONFIRMED)->column('email');
        $csv = "email\n" . implode("\n", $list);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=subscribers_' . date('Ymd') . '.csv',
        ]);
    }

    /**
     * V2.9.19 S-1c: CSV 批量导入
     */
    public function import()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return $this->error('请上传 CSV 文件');
        }

        $realPath = $file->getRealPath();
        if (!$realPath || !file_exists($realPath)) {
            return $this->error('文件读取失败');
        }

        $rows = array_map('str_getcsv', file($realPath));
        if (empty($rows)) {
            return $this->error('CSV 文件为空');
        }

        // 检测首行是否为表头
        $firstRow = $rows[0];
        $hasHeader = false;
        if (isset($firstRow[0]) && strtolower(trim($firstRow[0])) === 'email') {
            $hasHeader = true;
            array_shift($rows);
        }

        $success = 0;
        $skipped = 0;
        $failed  = 0;
        $failedRows = [];

        foreach ($rows as $idx => $row) {
            $email = trim($row[0] ?? '');
            $tag   = trim($row[1] ?? '');

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed++;
                $failedRows[] = $hasHeader ? ($idx + 2) : ($idx + 1);
                continue;
            }

            $exists = Subscriber::where('email', $email)->find();
            if ($exists) {
                $skipped++;
                continue;
            }

            Subscriber::create([
                'email'         => $email,
                'tag'           => $tag,
                'status'        => Subscriber::STATUS_CONFIRMED,
                'confirm_token' => Subscriber::generateToken(),
                'source'        => 'csv_import',
                'subscribed_at' => date('Y-m-d H:i:s'),
                'confirmed_at'  => date('Y-m-d H:i:s'),
            ]);
            $success++;
        }

        return $this->success("导入完成：成功 {$success} 条，跳过 {$skipped} 条重复，失败 {$failed} 行", [
            'success' => $success,
            'skipped' => $skipped,
            'failed'  => $failed,
            'failed_rows' => $failedRows,
        ]);
    }

    /**
     * V2.9.19 S-1c: 手动标记无效
     */
    public function markInvalid()
    {
        $id = (int) $this->request->post('id', 0);
        $subscriber = Subscriber::find($id);
        if (!$subscriber) {
            return $this->error('订阅者不存在');
        }
        $subscriber->markInvalid();
        return $this->success('已标记为无效');
    }

    /**
     * V2.9.19 S-1c: 恢复为有效
     */
    public function restoreValid()
    {
        $id = (int) $this->request->post('id', 0);
        $subscriber = Subscriber::find($id);
        if (!$subscriber) {
            return $this->error('订阅者不存在');
        }
        $subscriber->restoreValid();
        return $this->success('已恢复为有效');
    }

    /**
     * V2.9.19 S-1c: 获取标签列表
     */
    public function tagOptions()
    {
        $tags = Subscriber::getTagOptions();
        return $this->success('ok', ['tags' => $tags]);
    }
}

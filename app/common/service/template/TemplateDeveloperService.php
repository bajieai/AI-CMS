<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateDevUpload;
use app\common\model\TemplateStore;

/**
 * 模板开发者服务 - V2.9.12
 *
 * 提供模板上传、版本管理、审核流程支持
 */
class TemplateDeveloperService
{
    /**
     * 上传模板
     */
    public function upload(int $memberId, array $fileInfo, array $meta = []): array
    {
        // 校验上传权限
        $permCheck = $this->checkUploadPermission($memberId);
        if (!$permCheck['allowed']) {
            return ['success' => false, 'message' => $permCheck['reason']];
        }

        $manifest = $meta['manifest'] ?? [];
        $slug = $manifest['slug'] ?? basename($fileInfo['name'], '.zip');
        $version = $manifest['version'] ?? '1.0.0';

        $upload = TemplateDevUpload::create([
            'member_id'     => $memberId,
            'theme_slug'    => $slug,
            'theme_name'    => $manifest['name'] ?? $slug,
            'version'       => $version,
            'file_path'     => $fileInfo['path'] ?? '',
            'manifest_json' => json_encode($manifest, JSON_UNESCAPED_UNICODE),
            'status'        => TemplateDevUpload::STATUS_PENDING,
        ]);

        return [
            'success' => true,
            'message' => '上传成功，等待审核',
            'data'    => $upload->toArray(),
        ];
    }

    /**
     * 获取我的上传记录
     */
    public function getMyUploads(int $memberId, int $page = 1, int $limit = 20): array
    {
        return TemplateDevUpload::getByMember($memberId, $page, $limit);
    }

    /**
     * 获取上传详情
     */
    public function getUploadDetail(int $uploadId, int $memberId): ?array
    {
        $upload = TemplateDevUpload::where('id', $uploadId)
            ->where('member_id', $memberId)
            ->find();
        return $upload ? $upload->toArray() : null;
    }

    /**
     * 检查上传权限
     */
    public function checkUploadPermission(int $memberId): array
    {
        // 默认允许，可扩展为需要认证开发者身份
        return ['allowed' => true, 'reason' => ''];
    }

    /**
     * 发布新版本
     */
    public function publishVersion(int $memberId, string $slug, string $newVersion, string $filePath): array
    {
        $existing = TemplateDevUpload::where('member_id', $memberId)
            ->where('theme_slug', $slug)
            ->where('status', TemplateDevUpload::STATUS_APPROVED)
            ->order('create_time', 'desc')
            ->find();

        if (!$existing) {
            return ['success' => false, 'message' => '未找到已通过的模板，请先上传首版'];
        }

        // 创建新上传记录（审核通过后会更新store版本号）
        return $this->upload($memberId, ['path' => $filePath, 'name' => basename($filePath)], [
            'manifest' => array_merge($existing->getManifest(), ['version' => $newVersion]),
        ]);
    }

    /**
     * 审核通过处理
     */
    public function approve(int $uploadId, int $auditorId, string $remark = ''): array
    {
        $upload = TemplateDevUpload::find($uploadId);
        if (!$upload) {
            return ['success' => false, 'message' => '上传记录不存在'];
        }

        $upload->status = TemplateDevUpload::STATUS_APPROVED;
        $upload->auditor_id = $auditorId;
        $upload->audit_remark = $remark;
        $upload->audit_time = time();
        $upload->save();

        // 同步到模板商店
        $manifest = $upload->getManifest();
        $store = TemplateStore::where('slug', $upload->theme_slug)->find();
        $isUpdate = false;
        $oldVersion = '';
        if ($store) {
            $oldVersion = $store->version;
            $store->version = $upload->version;
            $store->status = TemplateStore::STATUS_ONLINE;
            $store->save();
            $isUpdate = true;
        } else {
            $store = TemplateStore::create([
                'slug'        => $upload->theme_slug,
                'name'        => $manifest['name'] ?? $upload->theme_name,
                'description' => $manifest['description'] ?? '',
                'author_name' => $manifest['author'] ?? '开发者',
                'version'     => $upload->version,
                'status'      => TemplateStore::STATUS_ONLINE,
                'price'       => 0,
            ]);
        }

        // V2.9.13 H-4: 模板更新通知（仅版本更新时触发）
        if ($isUpdate && $oldVersion !== $upload->version) {
            $installIds = \app\common\model\TemplateInstall::where('store_id', $store->id)
                ->where('status', 1)
                ->column('id');
            if (!empty($installIds)) {
                $notifyService = new \app\common\service\NotificationService();
                $notifyService->notifyTemplateUpdate($store->id, $store->name, $upload->version, $installIds);
            }
        }

        return ['success' => true, 'message' => '审核通过，已上架'];
    }

    /**
     * 审核拒绝处理
     */
    public function reject(int $uploadId, int $auditorId, string $remark): array
    {
        $upload = TemplateDevUpload::find($uploadId);
        if (!$upload) {
            return ['success' => false, 'message' => '上传记录不存在'];
        }

        $upload->status = TemplateDevUpload::STATUS_REJECTED;
        $upload->auditor_id = $auditorId;
        $upload->audit_remark = $remark;
        $upload->audit_time = time();
        $upload->save();

        return ['success' => true, 'message' => '已拒绝'];
    }

    /**
     * 获取待审核列表
     */
    public function getPendingList(int $page = 1, int $limit = 20): array
    {
        return TemplateDevUpload::getPending($page, $limit);
    }

    /**
     * V2.9.13 H-3: 版本差异对比（从0新建）
     *
     * 对比两个版本的模板目录，输出差异文件列表
     */
    public function diffVersions(string $slug, string $oldVersion, string $newVersion): array
    {
        $basePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
        $oldPath = $basePath . $slug . '_v' . $oldVersion;
        $newPath = $basePath . $slug . '_v' . $newVersion;

        // 如果目录不存在，尝试从上传记录查找
        if (!is_dir($oldPath)) {
            $oldUpload = TemplateDevUpload::where('theme_slug', $slug)
                ->where('version', $oldVersion)
                ->where('status', TemplateDevUpload::STATUS_APPROVED)
                ->order('audit_time', 'desc')
                ->find();
            if ($oldUpload && !empty($oldUpload->file_path)) {
                $oldPath = dirname($oldUpload->file_path) . DIRECTORY_SEPARATOR . 'extracted';
            }
        }
        if (!is_dir($newPath)) {
            $newUpload = TemplateDevUpload::where('theme_slug', $slug)
                ->where('version', $newVersion)
                ->where('status', TemplateDevUpload::STATUS_APPROVED)
                ->order('audit_time', 'desc')
                ->find();
            if ($newUpload && !empty($newUpload->file_path)) {
                $newPath = dirname($newUpload->file_path) . DIRECTORY_SEPARATOR . 'extracted';
            }
        }

        if (!is_dir($oldPath) || !is_dir($newPath)) {
            return [
                'success' => false,
                'message' => '版本目录不存在，无法对比',
            ];
        }

        $oldFiles = $this->scanFiles($oldPath);
        $newFiles = $this->scanFiles($newPath);

        $added = array_diff($newFiles, $oldFiles);
        $removed = array_diff($oldFiles, $newFiles);
        $common = array_intersect($oldFiles, $newFiles);

        $modified = [];
        foreach ($common as $file) {
            $oldHash = md5_file($oldPath . DIRECTORY_SEPARATOR . $file);
            $newHash = md5_file($newPath . DIRECTORY_SEPARATOR . $file);
            if ($oldHash !== $newHash) {
                $modified[] = $file;
            }
        }

        return [
            'success'   => true,
            'slug'      => $slug,
            'old'       => $oldVersion,
            'new'       => $newVersion,
            'added'     => array_values($added),
            'removed'   => array_values($removed),
            'modified'  => array_values($modified),
            'unchanged' => array_values(array_diff($common, $modified)),
            'summary'   => "新增 " . count($added) . " 个文件，删除 " . count($removed) . " 个文件，修改 " . count($modified) . " 个文件",
        ];
    }

    /**
     * 扫描目录中的所有文件（返回相对路径列表）
     */
    protected function scanFiles(string $dir, string $baseDir = ''): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getRealPath(), strlen($dir) + 1);
                $files[] = str_replace('\\', '/', $relativePath);
            }
        }
        sort($files);
        return $files;
    }
}

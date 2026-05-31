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
        if ($store) {
            $store->version = $upload->version;
            $store->status = TemplateStore::STATUS_ONLINE;
            $store->save();
        } else {
            TemplateStore::create([
                'slug'        => $upload->theme_slug,
                'name'        => $manifest['name'] ?? $upload->theme_name,
                'description' => $manifest['description'] ?? '',
                'author_name' => $manifest['author'] ?? '开发者',
                'version'     => $upload->version,
                'status'      => TemplateStore::STATUS_ONLINE,
                'price'       => 0,
            ]);
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
}

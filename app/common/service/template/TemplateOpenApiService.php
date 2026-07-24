<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateStore;
use app\common\model\TemplateDeveloperApp;
use think\facade\Cache;

/**
 * 模板API开放平台 — V2.9.33 DEV-3
 * 公开API(3) + 管理API(5) + 统计API(3) = 11个接口
 * HMAC-SHA256签名认证 + 1000次/小时频率限制
 */
class TemplateOpenApiService
{
    private const RATE_LIMIT = 1000; // 次/小时
    private const CACHE_TAG = 'openapi';

    /**
     * 验证签名
     */
    public function verifySignature(array $params, string $signature, string $appSecret): bool
    {
        ksort($params);
        $str = http_build_query($params);
        $expected = hash_hmac('sha256', $str, $appSecret);
        return hash_equals($expected, $signature);
    }

    /**
     * 查找App
     */
    public function findApp(string $appKey): ?array
    {
        $app = TemplateDeveloperApp::where('app_key', $appKey)->where('status', 1)->find();
        if (!$app) return null;

        $app->last_used_time = time();
        $app->save();

        return $app->toArray();
    }

    /**
     * 频率限制检查
     */
    public function checkRateLimit(string $appKey): bool
    {
        $key = "api_rate:{$appKey}:" . date('YmdH');
        $count = Cache::inc($key);
        if ($count === 1) Cache::expire($key, 3600);
        return $count <= self::RATE_LIMIT;
    }

    // ===== 公开API（无需认证）=====

    /**
     * 获取模板列表
     */
    public function listTemplates(int $page = 1, int $limit = 20, array $filter = []): array
    {
        $query = TemplateStore::where('status', 1);
        if (!empty($filter['category_id'])) $query->where('category_id', $filter['category_id']);
        if (!empty($filter['keyword'])) $query->where('name', 'like', "%{$filter['keyword']}%");

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->field('id,name,slug,price,install_count,avg_rating,screenshots,description')
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 获取模板详情
     */
    public function getTemplateDetail(int $id): ?array
    {
        $template = TemplateStore::where('id', $id)->where('status', 1)->find();
        return $template ? $template->toArray() : null;
    }

    /**
     * 获取模板分类
     */
    public function getCategories(): array
    {
        return \app\common\model\TemplateStoreCategory::order('sort_order', 'asc')->select()->toArray();
    }

    /**
     * 获取模板排行
     */
    public function getRanking(string $type = 'install', int $limit = 10): array
    {
        $order = $type === 'rating' ? 'avg_rating' : 'install_count';
        return TemplateStore::where('status', 1)
            ->order($order, 'desc')
            ->limit($limit)
            ->field('id,name,slug,price,install_count,avg_rating')
            ->select()
            ->toArray();
    }

    // ===== 管理API（需认证）=====

    /**
     * 上传模板
     */
    public function uploadTemplate(array $data, int $developerId): array
    {
        $data['developer_id'] = $developerId;
        $data['upload_status'] = 'pending_audit';
        $template = TemplateStore::create($data);
        return ['success' => true, 'id' => $template->id];
    }

    /**
     * 更新模板
     */
    public function updateTemplate(int $id, array $data, int $developerId): array
    {
        $template = TemplateStore::where('id', $id)->where('developer_id', $developerId)->find();
        if (!$template) return ['success' => false, 'message' => '模板不存在或无权操作'];
        $template->save($data);
        return ['success' => true];
    }

    /**
     * 删除模板
     */
    public function deleteTemplate(int $id, int $developerId): array
    {
        $template = TemplateStore::where('id', $id)->where('developer_id', $developerId)->find();
        if (!$template) return ['success' => false, 'message' => '模板不存在或无权操作'];
        $template->delete();
        return ['success' => true];
    }

    /**
     * 发布新版本
     */
    public function publishVersion(int $id, string $version, int $developerId): array
    {
        $template = TemplateStore::where('id', $id)->where('developer_id', $developerId)->find();
        if (!$template) return ['success' => false, 'message' => '模板不存在或无权操作'];
        $template->current_version = $version;
        $template->save();
        return ['success' => true];
    }

    // ===== 统计API（需认证）=====

    /**
     * 模板安装统计
     */
    public function getInstallStats(int $id, int $developerId): array
    {
        $template = TemplateStore::where('id', $id)->where('developer_id', $developerId)->find();
        if (!$template) return ['success' => false];
        return ['success' => true, 'install_count' => $template->install_count];
    }

    /**
     * 模板评分统计
     */
    public function getRatingStats(int $id, int $developerId): array
    {
        $template = TemplateStore::where('id', $id)->where('developer_id', $developerId)->find();
        if (!$template) return ['success' => false];
        return ['success' => true, 'avg_rating' => $template->avg_rating, 'rating_count' => $template->rating_count ?? 0];
    }

    /**
     * 模板收入统计
     */
    public function getRevenueStats(int $id, int $developerId): array
    {
        // 简化版：返回模板价格和安装量
        $template = TemplateStore::where('id', $id)->where('developer_id', $developerId)->find();
        if (!$template) return ['success' => false];
        return ['success' => true, 'price' => $template->price, 'install_count' => $template->install_count];
    }
}

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

namespace app\common\service\template;

use app\common\model\TemplateStore;
use app\common\model\TemplateInstall;
use app\common\service\theme\ThemeRepairPipeline;

/**
 * V2.9.20 B-3: 模板安装服务（一键安装/卸载/切换）
 */
class TemplateInstallService
{
    /**
     * 安装模板
     */
    public function install(int $storeId, int $memberId): array
    {
        $store = TemplateStore::find($storeId);
        if (empty($store)) {
            throw new \RuntimeException('模板不存在');
        }

        // V2.9.13: 安装前自动质量校验
        $pipeline = new ThemeRepairPipeline();
        $checkResult = $pipeline->validate($store);
        $qualityScore = (int) ($checkResult['quality_score'] ?? 0);
        if (!$checkResult['pass']) {
            throw new \RuntimeException('模板质量校验未通过（评分' . $qualityScore . '），无法安装');
        }

        // 检查是否已安装
        $exists = TemplateInstall::where('store_id', $storeId)
            ->where('member_id', $memberId)
            ->find();
        if ($exists) {
            throw new \RuntimeException('该模板已安装');
        }

        // 创建安装记录
        $install = new TemplateInstall();
        $install->store_id = $storeId;
        $install->member_id = $memberId;
        $install->slug = $store->slug;
        $install->theme_name = $store->name;
        $install->is_active = 0;
        $install->install_path = 'themes/' . $store->slug;
        $install->quality_on_install = $qualityScore;
        $install->save();

        // 更新安装次数
        $store->inc('install_count')->save();

        // 清除相关缓存
        $this->clearCache();

        return [
            'install_id' => $install->id,
            'message' => '安装成功',
            'quality_score' => $qualityScore,
        ];
    }

    /**
     * 卸载模板
     */
    public function uninstall(int $installId, int $memberId): array
    {
        $install = TemplateInstall::where('id', $installId)
            ->where('member_id', $memberId)
            ->find();

        if (empty($install)) {
            throw new \RuntimeException('安装记录不存在');
        }

        // 如果当前激活，先取消激活
        if ($install->is_active) {
            $install->is_active = 0;
            $install->save();
        }

        $install->delete();
        $this->clearCache();

        return ['message' => '卸载成功'];
    }

    /**
     * 激活/切换模板
     */
    public function activate(int $installId, int $memberId): array
    {
        $install = TemplateInstall::where('id', $installId)
            ->where('member_id', $memberId)
            ->find();

        if (empty($install)) {
            throw new \RuntimeException('安装记录不存在');
        }

        // 取消该用户其他激活模板
        TemplateInstall::where('member_id', $memberId)
            ->where('is_active', 1)
            ->update(['is_active' => 0]);

        // 激活当前模板
        $install->is_active = 1;
        $install->save();

        $this->clearCache();

        return ['message' => '切换成功'];
    }

    /**
     * 获取已安装模板列表
     */
    public function getInstalled(int $memberId): array
    {
        return TemplateInstall::with('store')
            ->where('member_id', $memberId)
            ->order('is_active', 'desc')
            ->order('create_time', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 清除缓存
     */
    private function clearCache(): void
    {
        $cache = new TemplateStoreService();
        $cache->clearCache();
    }
}

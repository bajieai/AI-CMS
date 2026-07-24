<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint UX2: 空状态组件服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 空状态组件服务 - V2.9.31 UX2-2
 * 统一管理各模块的空状态展示文案和图标
 */
class EmptyStateService
{
    /**
     * 空状态配置
     */
    private const STATES = [
        'content' => [
            'icon' => 'bi bi-inbox',
            'title' => '暂无内容',
            'description' => '还没有发布任何内容，点击上方按钮开始创建',
            'action_text' => '创建内容',
            'action_url' => '/admin/content/add',
        ],
        'template' => [
            'icon' => 'bi bi-grid',
            'title' => '暂无模板',
            'description' => '还没有安装任何模板，去模板商店看看吧',
            'action_text' => '浏览模板商店',
            'action_url' => '/admin/template_store/market',
        ],
        'order' => [
            'icon' => 'bi bi-receipt',
            'title' => '暂无订单',
            'description' => '还没有收到任何订单',
            'action_text' => '',
            'action_url' => '',
        ],
        'member' => [
            'icon' => 'bi bi-people',
            'title' => '暂无会员',
            'description' => '还没有注册会员',
            'action_text' => '',
            'action_url' => '',
        ],
        'message' => [
            'icon' => 'bi bi-envelope-open',
            'title' => '暂无消息',
            'description' => '收件箱空空如也',
            'action_text' => '',
            'action_url' => '',
        ],
        'notification' => [
            'icon' => 'bi bi-bell',
            'title' => '暂无通知',
            'description' => '没有新的系统通知',
            'action_text' => '',
            'action_url' => '',
        ],
        'search' => [
            'icon' => 'bi bi-search',
            'title' => '未找到结果',
            'description' => '换个关键词试试，或调整筛选条件',
            'action_text' => '清除筛选',
            'action_url' => '',
        ],
        'favorite' => [
            'icon' => 'bi bi-heart',
            'title' => '暂无收藏',
            'description' => '还没有收藏任何内容',
            'action_text' => '去浏览',
            'action_url' => '/',
        ],
        'data' => [
            'icon' => 'bi bi-bar-chart',
            'title' => '暂无数据',
            'description' => '数据正在收集中，请稍后再来查看',
            'action_text' => '',
            'action_url' => '',
        ],
        'error' => [
            'icon' => 'bi bi-exclamation-triangle',
            'title' => '加载失败',
            'description' => '数据加载出错，请刷新页面重试',
            'action_text' => '刷新页面',
            'action_url' => 'javascript:location.reload()',
        ],
    ];

    /**
     * 获取空状态配置
     */
    public function get(string $type): array
    {
        return self::STATES[$type] ?? self::STATES['data'];
    }

    /**
     * 渲染空状态HTML
     */
    public function render(string $type, array $override = []): string
    {
        $config = array_merge($this->get($type), $override);
        $html = '<div class="empty-state text-center py-5">';
        $html .= '<i class="bi ' . $config['icon'] . ' fs-1 text-muted"></i>';
        $html .= '<h5 class="mt-3 text-muted">' . $config['title'] . '</h5>';
        $html .= '<p class="text-muted small">' . $config['description'] . '</p>';
        if (!empty($config['action_text'])) {
            $html .= '<a href="' . $config['action_url'] . '" class="btn btn-primary btn-sm">' . $config['action_text'] . '</a>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * 获取所有空状态类型
     */
    public function getTypes(): array
    {
        return array_keys(self::STATES);
    }
}

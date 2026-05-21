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

namespace app\common\service;

use app\common\model\SeoKeyword;
use app\common\model\SeoKeywordGroup;

/**
 * SEO关键词服务
 */
class SeoKeywordService
{
    /**
     * 关键词列表
     */
    public static function getList(int $groupId = 0, string $keyword = '', int $page = 1, int $limit = 20): array
    {
        $query = SeoKeyword::order('id', 'desc');
        if ($groupId > 0) $query->where('group_id', $groupId);
        if ($keyword) $query->where('keyword', 'like', "%{$keyword}%");
        return $query->page($page, $limit)->select()->toArray();
    }

    /**
     * 分组列表
     */
    public static function getGroups(): array
    {
        return SeoKeywordGroup::order('sort', 'asc')->select()->toArray();
    }

    /**
     * 批量导入关键词
     */
    public static function import(array $keywords, int $groupId = 0): int
    {
        $count = 0;
        foreach ($keywords as $kw) {
            $kw = trim($kw);
            if (empty($kw)) continue;

            // 跳过重复
            if (SeoKeyword::where('keyword', $kw)->find()) continue;

            SeoKeyword::create([
                'keyword'  => $kw,
                'group_id' => $groupId,
                'status'   => 1,
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * 获取标签自动关联的关键词
     */
    public static function getRelatedKeywords(string $tagName): array
    {
        return SeoKeyword::where('keyword', 'like', "%{$tagName}%")
            ->where('status', 1)
            ->limit(10)
            ->column('keyword');
    }

    /**
     * 内容编辑时推荐关键词
     */
    public static function suggestForContent(string $title, string $content = ''): array
    {
        $text = $title . ' ' . mb_substr($content, 0, 500);

        // 简单匹配：从关键词库中找到在文本中出现的关键词
        $allKeywords = SeoKeyword::where('status', 1)->column('keyword');
        $matched = [];
        foreach ($allKeywords as $kw) {
            if (mb_stripos($text, $kw) !== false) {
                $matched[] = $kw;
            }
        }

        return array_slice($matched, 0, 10);
    }
}

<?php
declare(strict_types=1);

namespace app\common\service;

/**
 * AI分析报告Prompt构造器 - V2.9.1 M9
 *
 * 将原始业务数据格式化为AI可读的Prompt文本
 */
class AiReportPromptBuilder
{
    /**
     * 构建日报Prompt
     */
    public static function buildDaily(array $data): string
    {
        $date = date('Y-m-d', $data['period_end'] ?? time());
        $prompt = "你是一位资深的数据分析师，请基于以下 {$date} 的网站运营数据生成一份专业的日报分析。\n\n";
        $prompt .= self::formatDataSection($data);
        $prompt .= self::getOutputFormat('daily');
        return $prompt;
    }

    /**
     * 构建周报Prompt
     */
    public static function buildWeekly(array $data): string
    {
        $start = date('Y-m-d', $data['period_start'] ?? time() - 604800);
        $end   = date('Y-m-d', $data['period_end'] ?? time());
        $prompt = "你是一位资深的数据分析师，请基于以下 {$start} 至 {$end} 的网站运营数据生成一份专业的周报分析。\n\n";
        $prompt .= self::formatDataSection($data);
        $prompt .= self::getOutputFormat('weekly');
        return $prompt;
    }

    /**
     * 构建月报Prompt
     */
    public static function buildMonthly(array $data): string
    {
        $month = date('Y年m月', $data['period_end'] ?? time());
        $prompt = "你是一位资深的数据分析师，请基于以下 {$month} 的网站运营数据生成一份专业的月报分析。\n\n";
        $prompt .= self::formatDataSection($data);
        $prompt .= self::getOutputFormat('monthly');
        return $prompt;
    }

    /**
     * 格式化数据段落
     */
    protected static function formatDataSection(array $data): string
    {
        $lines = [];

        // 流量数据
        if (!empty($data['traffic'])) {
            $t = $data['traffic'];
            $lines[] = '## 流量数据';
            $lines[] = "- 总PV: " . ($t['pv'] ?? 0);
            $lines[] = "- 总UV: " . ($t['uv'] ?? 0);
            $lines[] = "- 独立IP数: " . ($t['ip_count'] ?? 0);
            $lines[] = "- 人均浏览页数: " . ($t['avg_pages'] ?? 0);
            $lines[] = '';
        }

        // 内容数据
        if (!empty($data['content'])) {
            $c = $data['content'];
            $lines[] = '## 内容数据';
            $lines[] = "- 新增内容: " . ($c['new_count'] ?? 0) . ' 篇';
            $lines[] = "- 总内容数: " . ($c['total_count'] ?? 0) . ' 篇';
            $lines[] = "- 总阅读量: " . ($c['total_views'] ?? 0);
            $lines[] = "- 热门内容TOP3:";
            foreach ($c['top_contents'] ?? [] as $i => $item) {
                $lines[] = "  " . ($i + 1) . ". {$item['title']} (阅读量: {$item['views']})";
            }
            $lines[] = '';
        }

        // 会员数据
        if (!empty($data['member'])) {
            $m = $data['member'];
            $lines[] = '## 会员数据';
            $lines[] = "- 新增会员: " . ($m['new_count'] ?? 0) . ' 人';
            $lines[] = "- 总会员数: " . ($m['total_count'] ?? 0) . ' 人';
            $lines[] = "- 活跃会员: " . ($m['active_count'] ?? 0) . ' 人';
            $lines[] = '';
        }

        // 互动数据
        if (!empty($data['interaction'])) {
            $i = $data['interaction'];
            $lines[] = '## 互动数据';
            $lines[] = "- 新增评论: " . ($i['new_comments'] ?? 0);
            $lines[] = "- 新增点赞: " . ($i['new_likes'] ?? 0);
            $lines[] = "- 新增收藏: " . ($i['new_favorites'] ?? 0);
            $lines[] = '';
        }

        // 订单数据
        if (!empty($data['order'])) {
            $o = $data['order'];
            $lines[] = '## 订单数据';
            $lines[] = "- 新增订单: " . ($o['new_count'] ?? 0);
            $lines[] = "- 订单总额: " . ($o['total_amount'] ?? 0) . ' 元';
            $lines[] = "- 付费会员新增: " . ($o['new_vip'] ?? 0) . ' 人';
            $lines[] = '';
        }

        return implode("\n", $lines) . "\n\n";
    }

    /**
     * 获取输出格式要求
     */
    protected static function getOutputFormat(string $type): string
    {
        $periodName = match($type) {
            'daily' => '日报',
            'weekly' => '周报',
            'monthly' => '月报',
            default => '报告',
        };

        return <<<PROMPT
## 输出格式要求（请严格按以下JSON格式返回）

```json
{
  "summary": "一句话总结本{$periodName}核心发现（50字以内）",
  "findings": [
    "发现1：具体数据和洞察",
    "发现2：具体数据和洞察",
    "发现3：具体数据和洞察"
  ],
  "anomalies": [
    {
      "metric": "指标名称（如PV/UV/新增会员）",
      "value": "当前值",
      "expected": "预期值或上期值",
      "deviation": "偏离百分比（如+25%）",
      "severity": "high/medium/low",
      "description": "异常描述及可能原因"
    }
  ],
  "recommendations": [
    "建议1：基于数据的具体可执行建议",
    "建议2：基于数据的具体可执行建议",
    "建议3：基于数据的具体可执行建议"
  ],
  "sections": [
    {
      "title": "流量分析",
      "content": "详细分析内容..."
    },
    {
      "title": "内容表现",
      "content": "详细分析内容..."
    },
    {
      "title": "用户增长",
      "content": "详细分析内容..."
    },
    {
      "title": "互动与转化",
      "content": "详细分析内容..."
    }
  ]
}
```

注意：
1. 必须返回合法的JSON，不要包含任何JSON之外的文本
2. anomalies数组可为空（如无异常）
3. 数据必须真实反映输入，不要虚构数字
4. 分析要专业、有洞察力，避免空泛描述

PROMPT;
    }
}

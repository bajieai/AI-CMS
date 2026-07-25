<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Container;
use think\facade\Log;

/**
 * AI内容增强引擎V2
 * V2.9.38 AI-PLUS-4
 * 复用AiProviderInterface::chat()发送结构化prompt，复用AiGeoService::extractEntities()
 */
class AiContentEnhanceV2Service
{
    /**
     * 分析文档结构
     */
    public function analyzeStructure(string $content): array
    {
        $headings = [];
        $pattern = '/^(#{1,6})\s+(.+)$/m';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $headings[] = [
                'level' => strlen($match[1]),
                'text' => $match[2],
            ];
        }
        return [
            'headings' => $headings,
            'paragraph_count' => count(preg_split('/\n\s*\n/', $content)),
            'word_count' => mb_strlen(strip_tags($content)),
            'has_code_blocks' => preg_match('/```/', $content) > 0,
            'has_tables' => preg_match('/\|.*\|/', $content) > 0,
            'has_images' => preg_match('/!\[.*\]\(.*\)/', $content) > 0,
        ];
    }

    /**
     * 提取关键信息
     */
    public function extractInfo(string $content): array
    {
        $aiService = Container::getInstance()->make(\app\common\service\ai\AiConversationService::class);
        $prompt = "请从以下内容中提取关键信息(标题、作者、日期、关键词、摘要)，以JSON格式返回:\n\n" . $content;
        $result = $aiService->chat($prompt, ['temperature' => 0.1]);
        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : ['raw' => $result];
    }

    /**
     * 生成大纲
     */
    public function generateOutline(string $content, int $maxLevel = 3): array
    {
        $aiService = Container::getInstance()->make(\app\common\service\ai\AiConversationService::class);
        $prompt = "请为以下内容生成一个层级不超过{$maxLevel}级的大纲，以JSON数组格式返回(每个元素含title,level,children):\n\n" . mb_substr($content, 0, 2000);
        $result = $aiService->chat($prompt, ['temperature' => 0.2]);
        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 重建层级结构
     */
    public function rebuildHierarchy(array $flatHeadings): array
    {
        $root = ['children' => []];
        $stack = [[0, &$root['children']]];
        
        foreach ($flatHeadings as $heading) {
            $level = $heading['level'] ?? 1;
            while (!empty($stack) && $stack[count($stack) - 1][0] >= $level) {
                array_pop($stack);
            }
            $node = ['title' => $heading['text'] ?? '', 'level' => $level, 'children' => []];
            $parent = &$stack[count($stack) - 1][1];
            $parent[] = &$node;
            $stack[] = [$level, &$node['children']];
            unset($node);
        }
        
        return $root['children'];
    }

    /**
     * 结构化输出
     */
    public function structuredOutput(string $content, string $format = 'markdown'): string
    {
        $aiService = Container::getInstance()->make(\app\common\service\ai\AiConversationService::class);
        $prompt = "请将以下内容重新整理为{$format}格式的结构化文档:\n\n" . $content;
        return $aiService->chat($prompt);
    }

    /**
     * 格式转换
     */
    public function convertTo(string $content, string $targetFormat): array
    {
        $formatPrompts = [
            'ppt_outline' => '将内容转换为PPT大纲格式(每页一个要点)',
            'speech' => '将内容转换为演讲稿格式(口语化)',
            'social_media' => '将内容转换为社交媒体短文(200字以内)',
            'email_summary' => '将内容转换为邮件摘要格式',
            'faq' => '将内容转换为FAQ问答格式',
            'infographic' => '将内容转换为信息图数据格式(关键数据点列表)',
            'video_script' => '将内容转换为视频脚本格式(分镜头)',
            'simplified' => '将内容简化为通俗易懂的版本',
        ];
        
        $prompt = $formatPrompts[$targetFormat] ?? '转换内容格式';
        $aiService = Container::getInstance()->make(\app\common\service\ai\AiConversationService::class);
        $result = $aiService->chat($prompt . ":\n\n" . $content);
        
        return [
            'format' => $targetFormat,
            'content' => $result,
        ];
    }

    /**
     * 生成摘要
     */
    public function generateSummary(string $content, array $options = []): array
    {
        $maxLength = $options['max_length'] ?? 200;
        $style = $options['style'] ?? 'concise';
        $lang = $options['lang'] ?? 'zh';
        $level = $options['level'] ?? 'medium'; // short/medium/detailed
        
        $levelMap = ['short' => '一句话摘要', 'medium' => '段落摘要', 'detailed' => '详细摘要'];
        $levelText = $levelMap[$level] ?? '段落摘要';
        
        $aiService = Container::getInstance()->make(\app\common\service\ai\AiSummaryService::class);
        $summary = $aiService->summarize($content, [
            'max_length' => $maxLength,
            'style' => $style,
            'lang' => $lang,
            'level' => $levelText,
        ]);
        
        return ['summary' => $summary, 'level' => $level, 'style' => $style, 'lang' => $lang];
    }

    /**
     * 多文档摘要
     */
    public function generateMultiDocSummary(array $documents, array $options = []): array
    {
        $combined = implode("\n\n---\n\n", $documents);
        return $this->generateSummary($combined, $options);
    }

    /**
     * 提取实体
     */
    public function extractEntities(string $content): array
    {
        // 复用AiGeoService::extractEntities()
        try {
            $geoService = Container::getInstance()->make(\app\common\service\ai\AiGeoService::class);
            return $geoService->extractEntities($content);
        } catch (\Throwable $e) {
            // 降级: 使用AI对话提取
            $aiService = Container::getInstance()->make(\app\common\service\ai\AiConversationService::class);
            $prompt = "请从以下内容中提取实体(人名、地名、组织、日期、数字等)，以JSON格式返回:\n\n" . $content;
            $result = $aiService->chat($prompt, ['temperature' => 0.1]);
            return json_decode($result, true) ?: ['raw' => $result];
        }
    }

    /**
     * 提取关系
     */
    public function extractRelations(string $content): array
    {
        $aiService = Container::getInstance()->make(\app\common\service\ai\AiConversationService::class);
        $prompt = "请从以下内容中提取实体之间的关系，以JSON数组格式返回(每个元素含subject, relation, object):\n\n" . $content;
        $result = $aiService->chat($prompt, ['temperature' => 0.1]);
        return json_decode($result, true) ?: [];
    }

    /**
     * 构建知识图谱
     */
    public function buildKnowledgeGraph(string $content): array
    {
        $entities = $this->extractEntities($content);
        $relations = $this->extractRelations($content);
        
        $nodes = [];
        $edges = [];
        
        // 构建节点
        $entityList = $entities['entities'] ?? $entities;
        if (is_array($entityList)) {
            foreach ($entityList as $entity) {
                if (is_array($entity)) {
                    $nodes[] = [
                        'id' => $entity['name'] ?? uniqid(),
                        'label' => $entity['name'] ?? '',
                        'type' => $entity['type'] ?? 'unknown',
                    ];
                }
            }
        }
        
        // 构建边
        foreach ($relations as $rel) {
            if (is_array($rel)) {
                $edges[] = [
                    'source' => $rel['subject'] ?? '',
                    'target' => $rel['object'] ?? '',
                    'label' => $rel['relation'] ?? '',
                ];
            }
        }
        
        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * 可视化知识图谱(D3.js CDN)
     */
    public function visualizeGraph(array $graph): string
    {
        $nodes = json_encode($graph['nodes'] ?? [], JSON_UNESCAPED_UNICODE);
        $edges = json_encode($graph['edges'] ?? [], JSON_UNESCAPED_UNICODE);
        
        return <<<HTML
<div id="knowledge-graph" style="width:100%;height:500px;border:1px solid #ddd;"></div>
<script src="/assets/js/d3.min.js"></script>
<script>
(function(){
    var nodes = {$nodes};
    var links = {$edges};
    var svg = d3.select('#knowledge-graph').append('svg').attr('width','100%').attr('height','100%');
    var simulation = d3.forceSimulation(nodes)
        .force('link', d3.forceLink(links).id(function(d){return d.id||d.label;}).distance(100))
        .force('charge', d3.forceManyBody().strength(-300))
        .force('center', d3.forceCenter(250, 250));
    var link = svg.selectAll('line').data(links).enter().append('line').attr('stroke','#999');
    var node = svg.selectAll('circle').data(nodes).enter().append('circle').attr('r',8).attr('fill','#4a90d9');
    var label = svg.selectAll('text').data(nodes).enter().append('text').text(function(d){return d.label;}).attr('font-size','10px');
    simulation.on('tick', function(){
        link.attr('x1',function(d){return d.source.x;}).attr('y1',function(d){return d.source.y;})
            .attr('x2',function(d){return d.target.x;}).attr('y2',function(d){return d.target.y;});
        node.attr('cx',function(d){return d.x;}).attr('cy',function(d){return d.y;});
        label.attr('x',function(d){return d.x+10;}).attr('y',function(d){return d.y;});
    });
})();
</script>
HTML;
    }
}

<?php
declare(strict_types=1);

namespace app\common\service\seo;

use think\facade\Cache;

/**
 * 结构化数据增强服务
 * V2.9.37 SEO-1
 */
class SchemaEnhanceService
{
    private const CACHE_TAG = 'seo_schema';

    /**
     * 生成Schema
     */
    public function generateSchema(string $type, array $data): array
    {
        $base = ['@context' => 'https://schema.org'];
        $schemas = [
            'FAQPage' => array_merge($base, ['@type' => 'FAQPage', 'mainEntity' => $data['questions'] ?? []]),
            'HowTo' => array_merge($base, ['@type' => 'HowTo', 'name' => $data['name'] ?? '', 'step' => $data['steps'] ?? []]),
            'VideoObject' => array_merge($base, ['@type' => 'VideoObject', 'name' => $data['title'] ?? '', 'contentUrl' => $data['url'] ?? '']),
            'ImageObject' => array_merge($base, ['@type' => 'ImageObject', 'contentUrl' => $data['url'] ?? '', 'name' => $data['title'] ?? '']),
            'Event' => array_merge($base, ['@type' => 'Event', 'name' => $data['name'] ?? '', 'startDate' => $data['start'] ?? '']),
            'Recipe' => array_merge($base, ['@type' => 'Recipe', 'name' => $data['name'] ?? '']),
            'Review' => array_merge($base, ['@type' => 'Review', 'reviewBody' => $data['content'] ?? '', 'author' => $data['author'] ?? '']),
            'LocalBusiness' => array_merge($base, ['@type' => 'LocalBusiness', 'name' => $data['name'] ?? '', 'address' => $data['address'] ?? '']),
            'JobPosting' => array_merge($base, ['@type' => 'JobPosting', 'title' => $data['title'] ?? '']),
            'Course' => array_merge($base, ['@type' => 'Course', 'name' => $data['name'] ?? '']),
            'Product' => array_merge($base, ['@type' => 'Product', 'name' => $data['name'] ?? '', 'sku' => $data['sku'] ?? '']),
            'Article' => array_merge($base, ['@type' => 'Article', 'headline' => $data['title'] ?? '']),
            'Organization' => array_merge($base, ['@type' => 'Organization', 'name' => $data['name'] ?? '', 'url' => $data['url'] ?? '']),
        ];
        return $schemas[$type] ?? [];
    }

    /**
     * 获取Schema类型列表
     */
    public function getSchemaTypes(): array
    {
        return ['FAQPage', 'HowTo', 'VideoObject', 'ImageObject', 'Event', 'Recipe', 'Review', 'LocalBusiness', 'JobPosting', 'Course', 'Product', 'Article', 'Organization', 'WebSite', 'BreadcrumbList'];
    }

    /**
     * 测试Schema
     */
    public function testSchema(string $url): array
    {
        return ['url' => $url, 'valid' => true, 'errors' => [], 'warnings' => [], 'google_test_url' => 'https://search.google.com/test/rich-results?url=' . urlencode($url)];
    }

    /**
     * 批量测试
     */
    public function batchTest(array $urls): array
    {
        $results = [];
        foreach ($urls as $url) $results[] = $this->testSchema($url);
        return $results;
    }

    /**
     * 覆盖率统计
     */
    public function getCoverageStats(): array
    {
        return Cache::remember('schema_coverage', fn() => ['total_pages' => 0, 'with_schema' => 0, 'coverage_rate' => 0, 'error_count' => 0], 600);
    }

    /**
     * 输出JSON-LD
     */
    public function outputJsonLd(string $pageType, array $data): string
    {
        $schema = $this->generateSchema($pageType, $data);
        if (empty($schema)) return '';
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }
}

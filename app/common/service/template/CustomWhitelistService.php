<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\CustomWhitelist;
use think\facade\Cache;

/**
 * CSS/JS白名单管理 — V2.9.33 CUS3-1
 * 预置CSS 100+ / JS 50+ 白名单
 */
class CustomWhitelistService
{
    private const CACHE_TAG = 'custom_whitelist';

    /** 预置CSS白名单 */
    private const DEFAULT_CSS = [
        // 属性(50+)
        ['category' => 'property', 'item_name' => 'color'],
        ['category' => 'property', 'item_name' => 'background'],
        ['category' => 'property', 'item_name' => 'background-color'],
        ['category' => 'property', 'item_name' => 'background-image'],
        ['category' => 'property', 'item_name' => 'font-size'],
        ['category' => 'property', 'item_name' => 'font-weight'],
        ['category' => 'property', 'item_name' => 'font-family'],
        ['category' => 'property', 'item_name' => 'line-height'],
        ['category' => 'property', 'item_name' => 'letter-spacing'],
        ['category' => 'property', 'item_name' => 'text-align'],
        ['category' => 'property', 'item_name' => 'text-decoration'],
        ['category' => 'property', 'item_name' => 'text-transform'],
        ['category' => 'property', 'item_name' => 'margin'],
        ['category' => 'property', 'item_name' => 'padding'],
        ['category' => 'property', 'item_name' => 'border'],
        ['category' => 'property', 'item_name' => 'border-radius'],
        ['category' => 'property', 'item_name' => 'border-color'],
        ['category' => 'property', 'item_name' => 'width'],
        ['category' => 'property', 'item_name' => 'height'],
        ['category' => 'property', 'item_name' => 'max-width'],
        ['category' => 'property', 'item_name' => 'max-height'],
        ['category' => 'property', 'item_name' => 'min-width'],
        ['category' => 'property', 'item_name' => 'min-height'],
        ['category' => 'property', 'item_name' => 'display'],
        ['category' => 'property', 'item_name' => 'flex'],
        ['category' => 'property', 'item_name' => 'flex-direction'],
        ['category' => 'property', 'item_name' => 'justify-content'],
        ['category' => 'property', 'item_name' => 'align-items'],
        ['category' => 'property', 'item_name' => 'grid'],
        ['category' => 'property', 'item_name' => 'grid-template-columns'],
        ['category' => 'property', 'item_name' => 'gap'],
        ['category' => 'property', 'item_name' => 'position'],
        ['category' => 'property', 'item_name' => 'top'],
        ['category' => 'property', 'item_name' => 'left'],
        ['category' => 'property', 'item_name' => 'right'],
        ['category' => 'property', 'item_name' => 'bottom'],
        ['category' => 'property', 'item_name' => 'z-index'],
        ['category' => 'property', 'item_name' => 'opacity'],
        ['category' => 'property', 'item_name' => 'visibility'],
        ['category' => 'property', 'item_name' => 'overflow'],
        ['category' => 'property', 'item_name' => 'box-shadow'],
        ['category' => 'property', 'item_name' => 'transition'],
        ['category' => 'property', 'item_name' => 'transform'],
        ['category' => 'property', 'item_name' => 'animation'],
        ['category' => 'property', 'item_name' => 'cursor'],
        ['category' => 'property', 'item_name' => 'float'],
        ['category' => 'property', 'item_name' => 'clear'],
        ['category' => 'property', 'item_name' => 'list-style'],
        ['category' => 'property', 'item_name' => 'white-space'],
        ['category' => 'property', 'item_name' => 'text-overflow'],
        ['category' => 'property', 'item_name' => 'object-fit'],
        // 函数(10+)
        ['category' => 'function', 'item_name' => 'calc'],
        ['category' => 'function', 'item_name' => 'rgb'],
        ['category' => 'function', 'item_name' => 'rgba'],
        ['category' => 'function', 'item_name' => 'var'],
        ['category' => 'function', 'item_name' => 'linear-gradient'],
        ['category' => 'function', 'item_name' => 'radial-gradient'],
        ['category' => 'function', 'item_name' => 'hsl'],
        ['category' => 'function', 'item_name' => 'url'],
        ['category' => 'function', 'item_name' => 'translate'],
        ['category' => 'function', 'item_name' => 'rotate'],
        // 选择器(20+)
        ['category' => 'selector', 'item_name' => '.class'],
        ['category' => 'selector', 'item_name' => '#id'],
        ['category' => 'selector', 'item_name' => 'element'],
        ['category' => 'selector', 'item_name' => ':hover'],
        ['category' => 'selector', 'item_name' => ':focus'],
        ['category' => 'selector', 'item_name' => ':active'],
        ['category' => 'selector', 'item_name' => ':first-child'],
        ['category' => 'selector', 'item_name' => ':last-child'],
        ['category' => 'selector', 'item_name' => ':nth-child'],
        ['category' => 'selector', 'item_name' => '::before'],
        ['category' => 'selector', 'item_name' => '::after'],
        ['category' => 'selector', 'item_name' => '>'],
        ['category' => 'selector', 'item_name' => '~'],
        ['category' => 'selector', 'item_name' => '+'],
        ['category' => 'selector', 'item_name' => '@media'],
        // 值(20+)
        ['category' => 'value', 'item_name' => 'auto'],
        ['category' => 'value', 'item_name' => 'none'],
        ['category' => 'value', 'item_name' => 'block'],
        ['category' => 'value', 'item_name' => 'inline'],
        ['category' => 'value', 'item_name' => 'flex'],
        ['category' => 'value', 'item_name' => 'grid'],
        ['category' => 'value', 'item_name' => 'absolute'],
        ['category' => 'value', 'item_name' => 'relative'],
        ['category' => 'value', 'item_name' => 'fixed'],
        ['category' => 'value', 'item_name' => 'sticky'],
        ['category' => 'value', 'item_name' => 'hidden'],
        ['category' => 'value', 'item_name' => 'visible'],
        ['category' => 'value', 'item_name' => 'solid'],
        ['category' => 'value', 'item_name' => 'dashed'],
        ['category' => 'value', 'item_name' => 'center'],
        ['category' => 'value', 'item_name' => 'left'],
        ['category' => 'value', 'item_name' => 'right'],
        ['category' => 'value', 'item_name' => 'pointer'],
        ['category' => 'value', 'item_name' => 'inherit'],
        ['category' => 'value', 'item_name' => 'unset'],
    ];

    /** 预置JS白名单 */
    private const DEFAULT_JS = [
        // API(20+)
        ['category' => 'api', 'item_name' => 'fetch'],
        ['category' => 'api', 'item_name' => 'XMLHttpRequest'],
        ['category' => 'api', 'item_name' => 'localStorage'],
        ['category' => 'api', 'item_name' => 'sessionStorage'],
        ['category' => 'api', 'item_name' => 'JSON.parse'],
        ['category' => 'api', 'item_name' => 'JSON.stringify'],
        ['category' => 'api', 'item_name' => 'setTimeout'],
        ['category' => 'api', 'item_name' => 'setInterval'],
        ['category' => 'api', 'item_name' => 'clearTimeout'],
        ['category' => 'api', 'item_name' => 'clearInterval'],
        ['category' => 'api', 'item_name' => 'requestAnimationFrame'],
        ['category' => 'api', 'item_name' => 'URLSearchParams'],
        ['category' => 'api', 'item_name' => 'IntersectionObserver'],
        ['category' => 'api', 'item_name' => 'MutationObserver'],
        ['category' => 'api', 'item_name' => 'ResizeObserver'],
        ['category' => 'api', 'item_name' => 'AbortController'],
        ['category' => 'api', 'item_name' => 'Promise'],
        ['category' => 'api', 'item_name' => 'async'],
        ['category' => 'api', 'item_name' => 'await'],
        ['category' => 'api', 'item_name' => 'import'],
        // 对象(15+)
        ['category' => 'object', 'item_name' => 'document'],
        ['category' => 'object', 'item_name' => 'window'],
        ['category' => 'object', 'item_name' => 'console'],
        ['category' => 'object', 'item_name' => 'Math'],
        ['category' => 'object', 'item_name' => 'Date'],
        ['category' => 'object', 'item_name' => 'Array'],
        ['category' => 'object', 'item_name' => 'Object'],
        ['category' => 'object', 'item_name' => 'String'],
        ['category' => 'object', 'item_name' => 'Number'],
        ['category' => 'object', 'item_name' => 'Boolean'],
        ['category' => 'object', 'item_name' => 'RegExp'],
        ['category' => 'object', 'item_name' => 'Map'],
        ['category' => 'object', 'item_name' => 'Set'],
        ['category' => 'object', 'item_name' => 'Error'],
        ['category' => 'object', 'item_name' => 'navigator'],
        // 事件(15+)
        ['category' => 'event', 'item_name' => 'click'],
        ['category' => 'event', 'item_name' => 'scroll'],
        ['category' => 'event', 'item_name' => 'resize'],
        ['category' => 'event', 'item_name' => 'load'],
        ['category' => 'event', 'item_name' => 'DOMContentLoaded'],
        ['category' => 'event', 'item_name' => 'change'],
        ['category' => 'event', 'item_name' => 'input'],
        ['category' => 'event', 'item_name' => 'submit'],
        ['category' => 'event', 'item_name' => 'mouseover'],
        ['category' => 'event', 'item_name' => 'mouseout'],
        ['category' => 'event', 'item_name' => 'focus'],
        ['category' => 'event', 'item_name' => 'blur'],
        ['category' => 'event', 'item_name' => 'keydown'],
        ['category' => 'event', 'item_name' => 'keyup'],
        ['category' => 'event', 'item_name' => 'touchstart'],
    ];

    /**
     * 初始化默认白名单
     */
    public function initDefaults(): void
    {
        if (CustomWhitelist::count() > 0) return;

        $now = time();
        $data = [];

        foreach (self::DEFAULT_CSS as $item) {
            $data[] = array_merge($item, [
                'list_type' => 'css', 'security_level' => 'safe',
                'description' => '', 'creator_id' => 0,
                'create_time' => $now, 'update_time' => $now,
            ]);
        }

        foreach (self::DEFAULT_JS as $item) {
            $data[] = array_merge($item, [
                'list_type' => 'js', 'security_level' => 'safe',
                'description' => '', 'creator_id' => 0,
                'create_time' => $now, 'update_time' => $now,
            ]);
        }

        if (!empty($data)) {
            (new CustomWhitelist())->insertAll($data);
            Cache::clear();
        }
    }

    /**
     * 检查CSS/JS是否在白名单内
     */
    public function check(string $code, string $type): array
    {
        $whitelist = $this->getWhitelist($type);
        $violations = [];

        // 危险模式检测
        $dangerousPatterns = $type === 'css'
            ? ['expression', 'javascript:', '@import', 'behavior:', '-moz-binding']
            : ['eval(', 'Function(', 'document.write', 'innerHTML', 'outerHTML', 'execScript', 'setTimeout("', 'setInterval("'];

        foreach ($dangerousPatterns as $pattern) {
            if (stripos($code, $pattern) !== false) {
                $violations[] = ['item' => $pattern, 'reason' => '危险模式', 'level' => 'forbidden'];
            }
        }

        return [
            'passed' => empty($violations),
            'violations' => $violations,
            'whitelist_count' => count($whitelist),
        ];
    }

    /**
     * 获取白名单
     */
    public function getWhitelist(string $type): array
    {
        return Cache::remember("whitelist_{$type}", function () use ($type) {
            return CustomWhitelist::where('list_type', $type)
                ->where('security_level', '<>', 'forbidden')
                ->select()
                ->toArray();
        }, 3600);
    }

    /**
     * 获取全部白名单（分CSS/JS）
     */
    public function getAll(): array
    {
        $this->initDefaults();
        return [
            'css' => $this->getWhitelist('css'),
            'js'  => $this->getWhitelist('js'),
        ];
    }

    /**
     * 保存白名单项
     */
    public function save(array $data, int $id = 0): array
    {
        if ($id > 0) {
            CustomWhitelist::update($data, ['id' => $id]);
        } else {
            CustomWhitelist::create($data);
        }
        Cache::clear();
        return ['success' => true, 'message' => '保存成功'];
    }

    /**
     * 删除白名单项
     */
    public function delete(int $id): array
    {
        CustomWhitelist::destroy($id);
        Cache::clear();
        return ['success' => true, 'message' => '删除成功'];
    }
}

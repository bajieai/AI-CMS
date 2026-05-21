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

namespace app\common\middleware;

use app\common\service\TemplateService;
use think\facade\Cache;

/**
 * 主题预览中间件 - V3.0 Phase 2
 *
 * 工作原理：
 * 1. 检测 ?preview={hash} 参数
 * 2. 验证hash有效且未过期（Cache存储，24h有效）
 * 3. 覆写 TemplateService 返回的主题名为预览主题
 * 4. 禁用整页缓存（避免预览页面污染正式缓存）
 * 5. 后续 FrontBaseController::initialize() 正常执行，自动加载预览主题
 *
 * 优势：
 * - 零路由修改，所有前台页面自动支持预览
 * - 零代码复制，完全复用现有控制器渲染
 * - 独立中间件，非预览请求零开销
 */
class ThemePreviewMiddleware
{
    /**
     * 预览主题缓存前缀
     */
    protected const CACHE_PREFIX = 'theme_preview_';

    /**
     * 预览hash有效期（秒）
     */
    protected const HASH_TTL = 86400; // 24小时

    public function handle($request, \Closure $next)
    {
        $previewHash = $request->param('preview', '');

        // 非预览请求：直接放行，零开销
        if (empty($previewHash)) {
            return $next($request);
        }

        // 验证hash
        $themeName = $this->verifyHash($previewHash);
        if ($themeName === null) {
            // hash无效或过期：静默忽略，正常渲染
            return $next($request);
        }

        // 检查主题目录是否存在
        $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName;
        if (!is_dir($themePath)) {
            return $next($request);
        }

        // 设置预览主题（影响TemplateService的后续调用）
        TemplateService::setPreviewTheme($themeName);

        // 标记当前请求为预览模式（供控制器判断是否禁用缓存）
        $request->withMiddleware(['is_preview' => true, 'preview_theme' => $themeName]);

        $response = $next($request);

        // 清除预览状态（不影响其他请求）
        TemplateService::clearPreviewTheme();

        // 添加响应头标记（调试用）
        if ($response instanceof \think\Response) {
            $response->header('X-Preview-Theme', $themeName);
        }

        return $response;
    }

    /**
     * 验证预览hash
     *
     * @return string|null 有效时返回主题名，无效返回null
     */
    protected function verifyHash(string $hash): ?string
    {
        if (strlen($hash) < 16 || !ctype_alnum($hash)) {
            return null;
        }

        $cacheKey = self::CACHE_PREFIX . $hash;
        $themeName = Cache::get($cacheKey);

        return is_string($themeName) && !empty($themeName) ? $themeName : null;
    }

    /**
     * 生成预览hash（供后台管理接口调用）
     *
     * @param string $themeName 主题名
     * @return string 32位hash
     */
    public static function generateHash(string $themeName): string
    {
        $hash = md5($themeName . uniqid('preview_', true) . random_bytes(8));
        $cacheKey = self::CACHE_PREFIX . $hash;

        Cache::set($cacheKey, $themeName, self::HASH_TTL);

        return $hash;
    }

    /**
     * 清除预览hash
     */
    public static function clearHash(string $hash): void
    {
        $cacheKey = self::CACHE_PREFIX . $hash;
        Cache::delete($cacheKey);
    }
}

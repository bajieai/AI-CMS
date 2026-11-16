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

namespace app\common\traits;

use app\common\service\agent\ScopeMatcher;

/**
 * API Scope校验Trait
 * 在API控制器中use此trait，调用requireScope()进行权限校验
 *
 * Scope定义（V2.9.63 R-4 起统一为三段式，旧两段仍兼容）：
 * - aicms:content:read   内容读取（列表/详情/搜索）
 * - aicms:content:write  内容写入（创建/编辑/删除）
 * - aicms:member:read    会员读取
 * - aicms:media:read     媒体读取
 * - aicms:theme:*        主题域全部（仅后缀通配合法）
 * - admin:all            兼容别名（等价全权限）
 *
 * 判定统一走 ScopeMatcher::allows()，禁止在本 trait 内自写匹配逻辑（R-4 硬要求）
 */
trait ApiScopeCheck
{
    /**
     * 校验当前请求是否具备指定Scope
     * @param string $required 需要的Scope，如 'aicms:content:read'（旧格式 'content:read' 亦可）
     * @throws \think\exception\HttpException
     */
    protected function requireScope(string $required): void
    {
        $userScopes = $this->request->apiScopes ?? [];

        if (!ScopeMatcher::allows($userScopes, $required)) {
            throw new \think\exception\HttpException(403, "Missing required scope: {$required}");
        }
    }
}

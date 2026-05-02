<?php
declare(strict_types=1);

namespace app\common\traits;

/**
 * API Scope校验Trait
 * 在API控制器中use此trait，调用requireScope()进行权限校验
 * 
 * Scope定义：模块级 resource:action 格式
 * - content:read   内容读取（列表/详情/搜索）
 * - content:write  内容写入（创建/编辑/删除）
 * - member:read    会员读取
 * - member:write   会员写入
 * - media:read     媒体读取
 * - media:write    媒体上传
 * - cate:read      分类读取
 * - admin:all      管理员全权限（通配）
 */
trait ApiScopeCheck
{
    /**
     * 校验当前请求是否具备指定Scope
     * @param string $required 需要的Scope，如 'content:read'
     * @throws \think\exception\HttpException
     */
    protected function requireScope(string $required): void
    {
        $userScopes = $this->request->apiScopes ?? [];

        // admin:all 通配权限
        if (in_array('admin:all', $userScopes)) {
            return;
        }

        if (!in_array($required, $userScopes)) {
            throw new \think\exception\HttpException(403, "Missing required scope: {$required}");
        }
    }
}

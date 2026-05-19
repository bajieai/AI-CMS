<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\oauth;

/**
 * OAuth Provider统一接口
 * 所有第三方OAuth适配器必须实现此接口
 */
interface OauthProviderInterface
{
    /**
     * 生成授权跳转URL
     * @param string $state CSRF状态参数
     * @return string 授权URL
     */
    public function getAuthUrl(string $state): string;

    /**
     * 通过授权码换取Access Token
     * @param string $code 授权码
     * @return array ['access_token'=>'', 'refresh_token'=>'', 'expires_in'=>7200, 'openid'=>'']
     */
    public function getAccessToken(string $code): array;

    /**
     * 通过Access Token获取用户信息
     * @param string $accessToken
     * @param string $openid
     * @return array 原始用户信息
     */
    public function getUserInfo(string $accessToken, string $openid): array;

    /**
     * 将原始用户数据映射为统一结构
     * @param array $raw 原始用户信息
     * @return array ['openid'=>'', 'unionid'=>'', 'nickname'=>'', 'avatar'=>'']
     */
    public function mapUserData(array $raw): array;

    /**
     * 获取Provider名称
     */
    public function getName(): string;
}

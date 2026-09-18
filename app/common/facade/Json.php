<?php
declare(strict_types=1);

namespace app\common\facade;

use think\Facade;

/**
 * Json 响应门面（兼容 think\facade\Json 缺失场景）
 *
 * V2.9.62 修复：当前 ThinkPHP 框架未提供 think\facade\Json，
 * 而大量后台控制器使用 `Json::success()` 返回 AJAX 结果，
 * 导致这些接口全部 500（Class "think\facade\Json" does not exist）。
 * 此处补全该门面，并通过 class_alias 映射到 think\facade\Json，
 * 使已有调用方无需改动即可正常工作。
 */
class Json extends Facade
{
    protected static function getFacadeClass(): string
    {
        return \think\response\Json::class;
    }

    /**
     * 成功响应
     * @param string $msg 提示信息
     * @param mixed  $data 业务数据
     * @param int    $code 状态码（默认 0）
     */
    public static function success(string $msg = '操作成功', mixed $data = [], int $code = 0)
    {
        return \json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ], 200, [], ['json_encode_param' => JSON_UNESCAPED_UNICODE]);
    }

    /**
     * 失败响应
     * @param string $msg 提示信息
     * @param mixed  $data 业务数据
     * @param int    $code 状态码（默认 1）
     */
    public static function error(string $msg = '操作失败', mixed $data = [], int $code = 1)
    {
        return \json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ], 200, [], ['json_encode_param' => JSON_UNESCAPED_UNICODE]);
    }
}

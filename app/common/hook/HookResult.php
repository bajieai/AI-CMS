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

namespace app\common\hook;

/**
 * Hook 事件触发结果 — V2.9.25 K-3/M-3
 *
 * 封装事件触发后所有监听器的执行结果。
 * 支持中断传播、阻塞检测、数据合并。
 */
class HookResult
{
    /** @var bool 是否停止事件传播 */
    public bool $stopped = false;

    /** @var int 结果码：0=成功, -1=阻塞 */
    public int $code = 0;

    /** @var string 提示信息 */
    public string $message = 'ok';

    /** @var mixed 修改后的数据（监听器可修改） */
    public mixed $data = null;

    /** @var array 所有监听器的返回值 */
    public array $responses = [];

    /** @var float 总处理耗时（毫秒） */
    public float $elapsed = 0.0;

    /**
     * 是否成功（无阻塞）
     */
    public function isSuccess(): bool
    {
        return $this->code === 0;
    }

    /**
     * 是否被阻塞
     */
    public function isBlocked(): bool
    {
        return $this->code === -1;
    }

    /**
     * 获取阻塞消息
     */
    public function getBlockMessage(): string
    {
        return $this->isBlocked() ? $this->message : '';
    }

    /**
     * 合并多个结果
     */
    public static function merge(array $results): self
    {
        $merged = new self();
        foreach ($results as $result) {
            if ($result instanceof self) {
                if ($result->isBlocked()) {
                    $merged->code = -1;
                    $merged->message = $result->message;
                    $merged->stopped = true;
                    break;
                }
                $merged->responses = array_merge($merged->responses, $result->responses);
                if ($result->data !== null) {
                    $merged->data = $result->data;
                }
            }
        }
        return $merged;
    }
}

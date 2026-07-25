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

namespace app\common\service\push;

use app\common\model\Notification;

/**
 * 站内广播推送通道 - V2.9.18 D-1
 * 
 * 将内容推送到站内所有用户的 notification 表，type='push'
 */
class ChannelBroadcast implements PushChannelInterface
{
    /**
     * 站内广播推送
     */
    public function push(array $payload, array $config): array
    {
        try {
            $title   = $payload['title'] ?? '';
            $content = $payload['content'] ?? '';
            $link    = $payload['link'] ?? '';

            if (empty($title)) {
                return $this->failResult('站内广播标题为空');
            }

            $pageSize = 500;
            $page = 1;
            $count = 0;

            while (true) {
                $members = \app\common\model\Member::where('status', 1)
                    ->page($page, $pageSize)
                    ->field(['id'])
                    ->select()
                    ->toArray();

                if (empty($members)) {
                    break;
                }

                $batch = array_map(function ($user) use ($title, $content, $link) {
                    return [
                        'type'          => 'push',
                        'receiver_type' => 'member',
                        'receiver_id'   => $user['id'],
                        'title'         => $title,
                        'content'       => $content,
                        'link'          => $link,
                        'is_read'       => 0,
                        'create_time'   => time(),
                    ];
                }, $members);

                Notification::insertAll($batch);
                $count += count($members);
                $page++;
            }

            if ($count === 0) {
                return $this->successResult([], 0, '无可用推送目标用户');
            }

            return $this->successResult([], 0, "已推送 {$count} 位用户");
        } catch (\Throwable $e) {
            return $this->failResult($e->getMessage());
        }
    }

    private function successResult(array $data, int $code, string $msg): array
    {
        return [
            'success'       => true,
            'response_code' => 200,
            'response_body' => json_encode(['msg' => $msg], JSON_UNESCAPED_UNICODE),
            'duration_ms'   => 0,
            'error_msg'     => '',
        ];
    }

    private function failResult(string $error): array
    {
        return [
            'success'       => false,
            'response_code' => 0,
            'response_body' => '',
            'duration_ms'   => 0,
            'error_msg'     => $error,
        ];
    }
}

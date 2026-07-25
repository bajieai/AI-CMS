<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class AiContentLog extends Model
{
    protected $name = 'ai_content_log';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    public static function log(
        int $userId, int $contentId, string $mode, string $style,
        string $inputText, string $outputText, string $provider,
        int $tokensUsed, int $elapsedMs
    ): void {
        self::create([
            'user_id'      => $userId,
            'content_id'   => $contentId,
            'mode'         => $mode,
            'style'        => $style,
            'input_text'   => $inputText,
            'output_text'  => $outputText,
            'provider'     => $provider,
            'tokens_used'  => $tokensUsed,
            'elapsed_ms'   => $elapsedMs,
        ]);
    }

    public static function getUserHistory(int $userId, int $limit = 20): array
    {
        return self::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}

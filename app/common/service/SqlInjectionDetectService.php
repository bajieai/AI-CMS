<?php

declare(strict_types=1);

namespace app\common\service;

/**
 * V2.9.35 SEC-2: SQL注入检测服务
 * 18+正则规则检测SQL注入攻击
 */
class SqlInjectionDetectService
{
    /**
     * SQL注入检测规则（按优先级排序，短路返回）
     */
    protected array $patterns = [
        // UNION注入
        '/union\s+select/i'                                 => 'UNION_SELECT',
        '/union\s+all\s+select/i'                           => 'UNION_ALL_SELECT',

        // 布尔盲注
        '/\b(and|or)\b\s+\d+\s*=\s*\d+/i'                  => 'BOOLEAN_BLIND',
        '/\b(and|or)\b\s+\w+\s*=\s*\w+/i'                   => 'BOOLEAN_BLIND_VAR',

        // 时间盲注
        '/\bsleep\s*\(/i'                                   => 'TIME_BLIND_SLEEP',
        '/\bbenchmark\s*\(/i'                               => 'TIME_BLIND_BENCHMARK',
        '/\bwaitfor\s+delay\b/i'                            => 'TIME_BLIND_WAITFOR',

        // 注释符
        '/--\s*$/m'                                         => 'SQL_COMMENT_DASH',
        '/\/\*.*?\*\//'                                     => 'SQL_COMMENT_BLOCK',
        '/#.*$/m'                                           => 'SQL_COMMENT_HASH',

        // 信息泄露
        '/information_schema/i'                             => 'INFO_SCHEMA',
        '/\bsysobjects\b/i'                                 => 'SYSOBJECTS',
        '/\bload_file\s*\(/i'                               => 'LOAD_FILE',
        '/\binto\s+outfile\b/i'                             => 'INTO_OUTFILE',

        // 堆叠注入
        '/;\s*(drop|delete|insert|update|alter|create)\s/i' => 'STACKED_INJECTION',

        // HEX编码注入
        '/0x[0-9a-f]{8,}/i'                                 => 'HEX_INJECTION',

        // 字符串拼接
        '/\bconcat\s*\(/i'                                  => 'CONCAT_FUNC',
        '/\bchar\s*\(\s*\d+/i'                              => 'CHAR_FUNC',
    ];

    /**
     * 检测参数中的SQL注入
     * @param array $params GET+POST参数
     * @return array|null 威胁信息数组或null
     */
    public function detect(array $params): ?array
    {
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $result = $this->detect($value);
                if ($result !== null) {
                    $result['param_key'] = $key . '.' . ($result['param_key'] ?? '');
                    return $result;
                }
                continue;
            }

            if (!is_string($value) || $value === '') {
                continue;
            }

            // URL解码后检测
            $decoded = urldecode($value);

            foreach ($this->patterns as $pattern => $ruleName) {
                if (preg_match($pattern, $value) || preg_match($pattern, $decoded)) {
                    return [
                        'rule'       => $ruleName,
                        'pattern'    => $pattern,
                        'param_key'  => $key,
                        'param_value'=> mb_substr($value, 0, 200),
                        'matched'    => (string) preg_match($pattern, $value, $m) ? ($m[0] ?? '') : '',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * 记录威胁日志
     */
    public function logThreat(array $threat, $request): void
    {
        try {
            $logService = app()->make(SecurityLogService::class);
            $logService->log([
                'event_type'  => 'sqli',
                'severity'    => 3,
                'description' => 'SQL注入检测: ' . $threat['rule'],
                'payload'     => json_encode([
                    'param_key'   => $threat['param_key'] ?? '',
                    'param_value' => $threat['param_value'] ?? '',
                    'matched'     => $threat['matched'] ?? '',
                    'pattern'     => $threat['pattern'] ?? '',
                ], JSON_UNESCAPED_UNICODE),
            ], $request);
        } catch (\Throwable) {
            // 日志记录失败不阻断流程
        }
    }
}

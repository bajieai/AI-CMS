<?php

declare(strict_types=1);

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use app\common\service\EncryptionService;
use app\common\model\Member;

/**
 * V2.9.35 SEC-3: 会员敏感字段加密命令
 * 增强V2.9.31版本，支持AES-256-CBC + GCM兼容
 *
 * 用法:
 *   php think member:encrypt --field=email          加密所有会员邮箱
 *   php think member:encrypt --field=phone --dry-run 预览加密效果
 *   php think member:encrypt --field=email --decrypt 解密
 */
class MemberEncryptCommand extends Command
{
    protected EncryptionService $encService;

    protected function configure(): void
    {
        $this->setName('member:encrypt')
            ->setDescription('会员敏感字段加密/解密')
            ->addArgument('action', Argument::OPTIONAL, 'encrypt|decrypt|rotate', 'encrypt')
            ->addOption('field', 'f', Option::VALUE_REQUIRED, '字段名: email/phone/real_name/id_card', 'email')
            ->addOption('dry-run', 'd', Option::VALUE_NONE, '预览模式，不实际修改')
            ->addOption('batch', 'b', Option::VALUE_REQUIRED, '每批处理数量', 100);
    }

    protected function execute(Input $input, Output $output): int
    {
        $this->encService = new EncryptionService();
        $action = $input->getArgument('action');
        $field = $input->getOption('field');
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run');
        $batchSize = (int) $input->getOption('batch');

        $allowedFields = ['email', 'phone', 'real_name', 'id_card'];
        if (!in_array($field, $allowedFields, true)) {
            $output->error("不支持的字段: {$field}，允许: " . implode(', ', $allowedFields));
            return 1;
        }

        $output->info("=== 会员字段加密工具 ===");
        $output->info("动作: {$action} | 字段: {$field} | 批次: {$batchSize}" . ($dryRun ? ' [预览模式]' : ''));

        $total = Member::where($field, '<>', '')->count();
        $output->info("待处理记录: {$total}");

        if ($total === 0) {
            $output->info('无数据需要处理');
            return 0;
        }

        $processed = 0;
        $failed = 0;
        $page = 1;

        while ($processed < $total) {
            $members = Member::where($field, '<>', '')
                ->field('id, ' . $field)
                ->page($page, $batchSize)
                ->select();

            foreach ($members as $member) {
                $value = $member[$field];

                // 跳过已加密的数据（检测GCM:前缀或ENC:前缀）
                if (str_starts_with($value, 'GCM:') || str_starts_with($value, 'ENC:')) {
                    if ($action === 'rotate') {
                        // 密钥轮换：解密后重新加密
                        $decrypted = $this->encService->decrypt($value);
                        if ($decrypted === null) {
                            $failed++;
                            continue;
                        }
                        $value = $decrypted;
                    } else {
                        $processed++;
                        continue;
                    }
                }

                if ($dryRun) {
                    $output->info("[预览] ID={$member['id']} {$field}={$value} -> 加密");
                    $processed++;
                    continue;
                }

                try {
                    if ($action === 'decrypt') {
                        $result = $this->encService->decrypt($value);
                        if ($result !== null) {
                            Member::where('id', $member['id'])->update([$field => $result]);
                            $processed++;
                        } else {
                            $failed++;
                        }
                    } else {
                        // encrypt 或 rotate
                        $encrypted = $this->encService->encrypt($value);
                        Member::where('id', $member['id'])->update([$field => $encrypted]);
                        $processed++;
                    }
                } catch (\Throwable $e) {
                    $output->error("ID={$member['id']} 失败: " . $e->getMessage());
                    $failed++;
                }
            }

            $page++;
            $output->info("进度: {$processed}/{$total}");
        }

        $output->info("=== 处理完成 ===");
        $output->info("成功: {$processed} | 失败: {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}

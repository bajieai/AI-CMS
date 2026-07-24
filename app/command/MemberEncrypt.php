<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 敏感数据加密CLI命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

/**
 * 敏感数据加密CLI命令 - V2.9.31 Q8
 * 用法：php think member:encrypt [--all] [--verify]
 */
class MemberEncrypt extends Command
{
    protected function configure()
    {
        $this->setName('member:encrypt')
            ->setDescription('敏感数据加密：加密/解密会员敏感字段（手机号、邮箱等）')
            ->addArgument('field', Argument::OPTIONAL, '加密字段（phone/email/real_name）', 'phone')
            ->addArgument('id', Argument::OPTIONAL, '指定用户ID（不指定则批量处理）')
            ->addOption('all', 'a', Option::VALUE_NONE, '批量处理所有用户')
            ->addOption('verify', 'v', Option::VALUE_NONE, '验证加密数据可解密');
    }

    protected function execute(Input $input, Output $output)
    {
        $field = $input->getArgument('field');
        $memberId = $input->getArgument('id');
        $isAll = $input->hasOption('all') && $input->getOption('all');
        $isVerify = $input->hasOption('verify') && $input->getOption('verify');

        $fields = ['phone', 'email', 'real_name', 'id_card'];
        if (!in_array($field, $fields)) {
            $output->writeln("<error>不支持的字段: {$field}</error>");
            $output->writeln("支持的字段: " . implode(', ', $fields));
            return;
        }

        if ($isVerify) {
            // 验证模式：测试加密/解密是否正常工作
            $this->verify($output);
            return;
        }

        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');

        if ($memberId) {
            // 单用户加密
            $user = Db::table($prefix . 'member')->where('id', (int) $memberId)->find();
            if (!$user) {
                $output->writeln("<error>用户不存在: {$memberId}</error>");
                return;
            }

            $original = $user[$field] ?? '';
            if ($original && $this->isEncrypted($original)) {
                $output->writeln("<comment>字段 {$field} 已加密，跳过</comment>");
                return;
            }

            $encrypted = $this->encrypt($original);
            Db::table($prefix . 'member')->where('id', (int) $memberId)->update([
                $field => $encrypted,
            ]);

            $output->writeln("<info>已加密: ID={$memberId} {$field}</info>");
            $output->writeln("  原文: {$original}");
            $output->writeln("  密文: " . mb_substr($encrypted, 0, 30) . '...');
            return;
        }

        // 批量处理
        if (!$isAll && !$memberId) {
            $output->writeln("<comment>提示：使用 --all 参数批量处理所有用户</comment>");
            $output->writeln("示例：php think member:encrypt phone --all");
            return;
        }

        $count = 0;
        $skipped = 0;

        $output->writeln("开始批量加密字段: {$field}...");

        $users = Db::table($prefix . 'member')
            ->where('id', '>', 0)
            ->limit(500)
            ->select();

        foreach ($users as $user) {
            $original = $user[$field] ?? '';
            if (empty($original)) {
                $skipped++;
                continue;
            }

            if ($this->isEncrypted($original)) {
                $skipped++;
                continue;
            }

            $encrypted = $this->encrypt($original);
            Db::table($prefix . 'member')->where('id', $user['id'])->update([
                $field => $encrypted,
            ]);

            $count++;
            if ($count % 50 === 0) {
                $output->writeln("  已处理 {$count} 条...");
            }
        }

        $output->writeln("<info>加密完成！</info>");
        $output->writeln("  加密: {$count} 条");
        $output->writeln("  跳过: {$skipped} 条（已加密或为空）");
    }

    /**
     * AES-256-GCM 加密
     */
    private function encrypt(string $data): string
    {
        if (empty($data)) return '';

        $key = config('security.encryption_key', 'i8j-cms-encryption-key-2026');
        $key = hash('sha256', $key, true);

        $iv = random_bytes(12);
        $ciphertext = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            return $data; // 加密失败返回原文
        }

        $tag = substr(openssl_cipher_vtag(), -16);
        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * AES-256-GCM 解密
     */
    private function decrypt(string $data): string
    {
        if (empty($data) || !$this->isEncrypted($data)) return $data;

        $key = config('security.encryption_key', 'i8j-cms-encryption-key-2026');
        $key = hash('sha256', $key, true);

        $decoded = base64_decode($data);
        if ($decoded === false) return $data;

        $iv = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $ciphertext = substr($decoded, 28);

        $result = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $result ?: $data;
    }

    /**
     * 检测是否已加密
     */
    private function isEncrypted(string $data): bool
    {
        if (empty($data)) return false;
        // 加密数据为base64格式，长度至少48字节
        return strlen($data) >= 48 && preg_match('/^[A-Za-z0-9+\/]+=*$/', $data);
    }

    /**
     * 验证加密/解密
     */
    private function verify(Output $output): void
    {
        $testData = 'test@example.com';
        $encrypted = $this->encrypt($testData);
        $decrypted = $this->decrypt($encrypted);

        if ($decrypted === $testData) {
            $output->writeln("<info>验证通过：加密/解密功能正常</info>");
        } else {
            $output->writeln("<error>验证失败：解密结果不匹配</error>");
        }
    }
}

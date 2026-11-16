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

namespace app\common\command;

use app\common\model\MenuItem;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 菜单同步命令 - V2.9.19 R-5
 *
 * 从 config/menu.php 同步菜单配置到 i8j_menu_item 数据库表
 * 用法: php think menu:sync [--dry-run]
 */
class MenuSyncCommand extends Command
{
    protected function configure()
    {
        $this->setName('menu:sync')
            ->setDescription('从 config/menu.php 同步菜单到数据库')
            ->addOption('dry-run', 'd', Option::VALUE_NONE, '仅预览，不写入数据库');
    }

    protected function execute(Input $input, Output $output)
    {
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run');
        $menuPath = root_path() . 'config' . DIRECTORY_SEPARATOR . 'menu.php';

        if (!is_file($menuPath)) {
            $output->writeln("<error>菜单配置文件不存在: {$menuPath}</error>");
            return 1;
        }

        $menus = include $menuPath;
        if (!is_array($menus)) {
            $output->writeln('<error>菜单配置文件返回格式错误</error>');
            return 1;
        }

        $total = 0;
        $created = 0;
        $updated = 0;
        $sortIndex = 0;

        foreach ($menus as $group) {
            $groupId = (int) ($group['id'] ?? 0);
            if ($groupId <= 0) {
                continue;
            }

            // 一级分组(group)不应作为菜单项(item)入库，否则L2列会重复显示分组名
            // V2.9.19+ 修复：仅同步children，跳过group自身

            if (!empty($group['children']) && is_array($group['children'])) {
                foreach ($group['children'] as $child) {
                    $result = $this->syncItem($child, $groupId, $groupId, $sortIndex++, $dryRun);
                    $total++;
                    $created += $result['created'];
                    $updated += $result['updated'];
                }
            }
        }

        $mode = $dryRun ? '[预览模式] ' : '';
        $output->writeln("<info>{$mode}菜单同步完成: 共{$total}项, 新增{$created}项, 更新{$updated}项</info>");

        return 0;
    }

    private function syncItem(array $item, int $parentId, int $groupId, int $sort, bool $dryRun): array
    {
        $id = (int) ($item['id'] ?? 0);
        if ($id <= 0) {
            return ['created' => 0, 'updated' => 0];
        }

        $data = [
            'id'         => $id,
            'group_id'   => $groupId,
            'parent_id'  => $parentId,
            'name'       => $item['name'] ?? '',
            'url'        => $item['url'] ?? '',
            'permission' => $item['permission'] ?? '',
            'active'     => $item['active'] ?? '',
            'icon'       => $item['icon'] ?? '',
            'sort'       => $sort,
            'status'     => 1,
        ];

        // V2.9.64：迁移脚本可能以自增 id 写入同一条菜单（同一 url 在不同站点 id 不同），
        // 若只按 id 判重，sync 会插入重复行；若该 id 恰好被别的菜单占用，还会覆盖掉别人。
        // 因此：id 命中直接用；id 未命中时再按 url 兜底认领，认领后沿用库中真实 id。
        $exists = MenuItem::find($id);

        if (!$exists && ($data['url'] ?? '') !== '') {
            $exists = MenuItem::where('url', $data['url'])->find();
        }

        if ($exists) {
            $data['id'] = (int) $exists->getKey();
            if (!$dryRun) {
                $exists->save($data);
            }
            return ['created' => 0, 'updated' => 1];
        }

        if (!$dryRun) {
            MenuItem::create($data);
        }
        return ['created' => 1, 'updated' => 0];
    }
}

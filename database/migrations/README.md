# 数据库迁移（database/migrations/）

面向**已安装的存量库**的增量变更脚本。新装不读取本目录。

## 两条升级路径

| 场景 | 使用脚本 | 说明 |
| --- | --- | --- |
| 全新安装 | `database/install.sql` | 建表语句已包含全部字段，无需再跑迁移 |
| 存量库升级 | `database/migrations/*.sql` | 按文件名时间顺序逐个执行，可重复执行 |

## 执行方式

1. 脚本内表名使用 `{prefix}` 占位符（与 `install.sql` 一致），执行前替换为实际表前缀（默认 `i8j_`）。安装器/升级器与 `install.sql` 采用相同的替换规则。
2. 逐条语句执行（脚本已按语句拆分，可整份执行）；每个文件独立执行并单独记录，互不影响。
3. 脚本幂等：字段已存在或目标表不存在时跳过，不报错、不修改已有数据，因此**重复执行安全**。

## 现有迁移

| 文件 | 内容 |
| --- | --- |
| `20261116_theme_info_origin.sql` | 为 `{prefix}theme_info` 补充 `source` / `task_id` / `installed_at` / `package_sha256` 4 列（AI-THEME 适配器所需） |
| `20261116_theme_menu_entry.sql` | 为 `{prefix}menu_item` 补充「AI主题安装」后台菜单入口（`/admin/theme_generator/index`，AI中心分组），按 `url` 判重，可重复执行 |

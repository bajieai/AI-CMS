<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\MenuGroup;
use app\common\model\MenuItem;
use app\common\service\MenuService;

/**
 * 菜单管理控制器
 * V2.9.10: 可视化后台菜单管理
 */
class MenuManagerController extends AdminBaseController
{
    protected array $noNeedPermission = [];

    /**
     * 菜单管理首页
     */
    public function index()
    {
        $groups = MenuGroup::order('sort', 'asc')->select()->toArray();
        $items  = MenuItem::order('sort', 'asc')->select()->toArray();

        // 按分组归类菜单项
        $groupedItems = [];
        foreach ($items as $item) {
            $groupedItems[$item['group_id']][] = $item;
        }

        $this->assign([
            'groups'       => $groups,
            'groupedItems' => $groupedItems,
        ]);
        return $this->view('/menu_manager/index');
    }

    /**
     * 保存分组（新增/编辑）
     */
    public function saveGroup()
    {
        $data = $this->request->post();
        $id   = (int) ($data['id'] ?? 0);

        $saveData = [
            'name'   => trim($data['name'] ?? ''),
            'code'   => trim($data['code'] ?? ''),
            'icon'   => trim($data['icon'] ?? ''),
            'sort'   => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ];

        if (empty($saveData['name']) || empty($saveData['code'])) {
            return json(['code' => 1, 'msg' => '名称和标识不能为空']);
        }

        try {
            if ($id > 0) {
                MenuGroup::where('id', $id)->update($saveData);
            } else {
                $group = new MenuGroup();
                $group->save($saveData);
                $id = $group->id;
            }
            MenuService::clearMenuCache();
            return json(['code' => 0, 'msg' => '保存成功', 'data' => ['id' => $id]]);
        } catch (\Exception $e) {
            return json(['code' => 2, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 保存菜单项（新增/编辑）
     */
    public function saveItem()
    {
        $data = $this->request->post();
        $id   = (int) ($data['id'] ?? 0);

        $saveData = [
            'group_id'   => (int) ($data['group_id'] ?? 0),
            'parent_id'  => (int) ($data['parent_id'] ?? 0),
            'name'       => trim($data['name'] ?? ''),
            'url'        => trim($data['url'] ?? ''),
            'permission' => trim($data['permission'] ?? ''),
            'active'     => trim($data['active'] ?? ''),
            'icon'       => trim($data['icon'] ?? ''),
            'sort'       => (int) ($data['sort'] ?? 0),
            'status'     => (int) ($data['status'] ?? 1),
        ];

        if ($saveData['group_id'] <= 0 || empty($saveData['name'])) {
            return json(['code' => 1, 'msg' => '分组和名称不能为空']);
        }

        try {
            if ($id > 0) {
                MenuItem::where('id', $id)->update($saveData);
            } else {
                $item = new MenuItem();
                $item->save($saveData);
                $id = $item->id;
            }
            MenuService::clearMenuCache();
            return json(['code' => 0, 'msg' => '保存成功', 'data' => ['id' => $id]]);
        } catch (\Exception $e) {
            return json(['code' => 2, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 删除分组
     */
    public function deleteGroup()
    {
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        // 检查分组下是否有菜单项
        $count = MenuItem::where('group_id', $id)->count();
        if ($count > 0) {
            return json(['code' => 2, 'msg' => '该分组下还有菜单项，请先删除或迁移']);
        }

        try {
            MenuGroup::where('id', $id)->delete();
            MenuService::clearMenuCache();
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception) {
            return json(['code' => 3, 'msg' => '删除失败']);
        }
    }

    /**
     * 删除菜单项
     */
    public function deleteItem()
    {
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            MenuItem::where('id', $id)->delete();
            MenuService::clearMenuCache();
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception) {
            return json(['code' => 2, 'msg' => '删除失败']);
        }
    }

    /**
     * 批量排序
     */
    public function sort()
    {
        $type   = $this->request->post('type', '');
        $orders = $this->request->post('orders', []);
        $groupId = (int) $this->request->post('group_id', 0);

        if (empty($orders) || !is_array($orders)) {
            return json(['code' => 1, 'msg' => '排序数据不能为空']);
        }

        // 规范化：前端传 [{id:x, sort:y}] → 提取为 [x, y, ...] id 数组
        $ids = [];
        foreach ($orders as $item) {
            $itemId = is_array($item) ? ((int) ($item['id'] ?? 0)) : (int) $item;
            if ($itemId > 0) {
                $ids[] = $itemId;
            }
        }

        if ($type === 'group') {
            $result = MenuService::saveGroupSort($ids);
        } elseif ($type === 'item') {
            $result = MenuService::saveItemSort($ids, $groupId);
        } else {
            return json(['code' => 2, 'msg' => '无效的排序类型']);
        }

        if ($result) {
            return json(['code' => 0, 'msg' => '排序已更新']);
        }
        return json(['code' => 3, 'msg' => '排序更新失败']);
    }

    /**
     * V2.9.10-fix: 从配置文件同步菜单数据到数据库
     */
    public function syncFromConfig()
    {
        if (!$this->request->isPost()) {
            return $this->error('非法请求');
        }

        try {
            $configMenus = \think\facade\Config::get('menu', []);
            if (empty($configMenus)) {
                return $this->error('配置文件中没有菜单数据');
            }

            // 事务保护：失败时回滚，保证不丢失数据
            \think\facade\Db::startTrans();
            try {
                // 清空现有数据
                MenuItem::where('id', '>', 0)->delete();
                MenuGroup::where('id', '>', 0)->delete();

                foreach ($configMenus as $group) {
                    $groupId = (int) $group['id'];
                    // 生成唯一 code
                    $code = $group['code'] ?? '';
                    $code = preg_replace('/[^a-zA-Z0-9_]+/', '_', $code);
                    if (strlen($code) === 0 || preg_match('/^_+$/', $code)) {
                        $code = 'group_' . $groupId;
                    }
                    MenuGroup::create([
                        'id'          => $groupId,
                        'name'        => $group['name'],
                        'code'        => $code,
                        'icon'        => $group['icon'] ?? '',
                        'sort'        => $groupId * 10,
                        'status'      => 1,
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);

                foreach ($group['children'] ?? [] as $item) {
                    MenuItem::create([
                        'id'          => (int) $item['id'],
                        'group_id'    => $groupId,
                        'parent_id'   => 0,
                        'name'        => $item['name'],
                        'url'         => $item['url'] ?? '',
                        'permission'  => $item['permission'] ?? '',
                        'active'      => $item['active'] ?? '',
                        'icon'        => $item['icon'] ?? '',
                        'sort'        => ((int) $item['id'] % 100) * 10,
                        'status'      => 1,
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);
                }
            }

                \think\facade\Db::commit();
            } catch (\Exception $innerEx) {
                \think\facade\Db::rollback();
                throw $innerEx;
            }

            MenuService::clearMenuCache();
            $this->recordLog('从配置文件同步菜单数据');
            return $this->success('同步成功：已用 config/menu.php 覆盖数据库菜单数据');
        } catch (\Exception $e) {
            return $this->error('同步失败: ' . $e->getMessage());
        }
    }

    /**
     * 切换状态
     */
    public function toggleStatus()
    {
        $type   = $this->request->post('type', '');
        $id     = (int) $this->request->post('id', 0);
        $status = (int) $this->request->post('status', 1);

        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        if ($type === 'group') {
            $result = MenuService::updateGroupStatus($id, $status);
        } elseif ($type === 'item') {
            $result = MenuService::updateItemStatus($id, $status);
        } else {
            return json(['code' => 2, 'msg' => '无效的类型']);
        }

        if ($result) {
            return json(['code' => 0, 'msg' => '状态已更新']);
        }
        return json(['code' => 3, 'msg' => '状态更新失败']);
    }
}

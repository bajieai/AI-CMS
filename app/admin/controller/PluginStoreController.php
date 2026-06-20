<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\PluginPackage;
use app\common\model\PluginCategory;
use app\common\model\PluginVersion;
use app\common\model\PluginDependency;
use app\common\model\PluginDownloadLog;
use app\common\service\PluginStoreOpsService;
use think\facade\Cache;

/**
 * V2.9.25 L-2: 插件包管理后台控制器
 * 插件包CRUD / 分类管理 / 版本管理 / 依赖管理 / 安装日志
 */
class PluginStoreController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    // ==================== 插件包管理 ====================

    /**
     * 插件包列表
     */
    public function index()
    {
        $params = [
            'keyword' => $this->request->get('keyword', ''),
            'category_id' => (int) $this->request->get('category_id', 0),
            'status' => $this->request->get('status', ''),
            'page' => (int) $this->request->get('page', 1),
            'limit' => (int) $this->request->get('limit', 20),
        ];

        $service = new PluginStoreOpsService();
        $result = $service->getPackageList($params);

        $this->assign([
            'list' => $result['list'],
            'total' => $result['total'],
            'categories' => PluginCategory::where('status', 1)->order('sort', 'asc')->select(),
            'params' => $params,
            'menuActive' => 'plugin_store',
        ]);

        return $this->view('/plugin_store/index');
    }

    /**
     * 新增插件包
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $service = new PluginStoreOpsService();
            $result = $service->createPackage($data);
            return json($result);
        }

        $this->assign([
            'categories' => PluginCategory::where('status', 1)->order('sort', 'asc')->select(),
            'menuActive' => 'plugin_store',
        ]);
        return $this->view('/plugin_store/add');
    }

    /**
     * 编辑插件包
     */
    public function edit()
    {
        $id = (int) $this->request->param('id', 0);
        $package = PluginPackage::find($id);
        if (!$package) {
            return $this->error('插件包不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $service = new PluginStoreOpsService();
            $result = $service->updatePackage($id, $data);
            return json($result);
        }

        $this->assign([
            'package' => $package,
            'categories' => PluginCategory::where('status', 1)->order('sort', 'asc')->select(),
            'menuActive' => 'plugin_store',
        ]);
        return $this->view('/plugin_store/edit');
    }

    /**
     * 删除插件包（软删除）
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        $package = PluginPackage::find($id);
        if (!$package) {
            return json(['code' => 1, 'msg' => '插件包不存在']);
        }

        $package->delete();
        Cache::tag('plugin_store')->clear();
        $this->recordLog('删除插件包', 'ID: ' . $id . ' Code: ' . $package->code);
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 切换上架/下架状态
     */
    public function toggleStatus()
    {
        $id = (int) $this->request->post('id', 0);
        $package = PluginPackage::find($id);
        if (!$package) {
            return json(['code' => 1, 'msg' => '插件包不存在']);
        }

        $package->status = $package->status === 1 ? 0 : 1;
        $package->save();
        Cache::tag('plugin_store')->clear();
        $this->recordLog('切换插件包状态', 'ID: ' . $id . ' Status: ' . $package->status);
        return json(['code' => 0, 'msg' => '状态更新成功']);
    }

    /**
     * 设置推荐/热门
     */
    public function setFeatured()
    {
        $id = (int) $this->request->post('id', 0);
        $field = $this->request->post('field', 'is_recommended'); // is_recommended / is_hot
        $value = (int) $this->request->post('value', 0);

        $package = PluginPackage::find($id);
        if (!$package) {
            return json(['code' => 1, 'msg' => '插件包不存在']);
        }

        if (in_array($field, ['is_recommended', 'is_hot'])) {
            $package->$field = $value;
            $package->save();
            Cache::tag('plugin_store')->clear();
        }
        return json(['code' => 0, 'msg' => '设置成功']);
    }

    // ==================== 分类管理 ====================

    /**
     * 分类列表
     */
    public function categories()
    {
        $list = PluginCategory::order('sort', 'asc')->select();
        $this->assign([
            'list' => $list,
            'menuActive' => 'plugin_store_category',
        ]);
        return $this->view('/plugin_store/categories');
    }

    /**
     * 新增/编辑分类
     */
    public function categoryEdit()
    {
        $id = (int) $this->request->param('id', 0);

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate = new \think\Validate([
                'name' => 'require|max:64',
                'sort' => 'integer',
                'status' => 'in:0,1',
            ]);
            if (!$validate->check($data)) {
                return json(['code' => 1, 'msg' => $validate->getError()]);
            }

            if ($id) {
                $category = PluginCategory::find($id);
                if (!$category) {
                    return json(['code' => 1, 'msg' => '分类不存在']);
                }
                $category->save($data);
            } else {
                PluginCategory::create($data);
            }
            Cache::tag('plugin_store')->clear();
            return json(['code' => 0, 'msg' => '保存成功']);
        }

        $category = $id ? PluginCategory::find($id) : null;
        $this->assign([
            'category' => $category,
            'menuActive' => 'plugin_store_category',
        ]);
        return $this->view('/plugin_store/category_edit');
    }

    /**
     * 删除分类
     */
    public function categoryDelete()
    {
        $id = (int) $this->request->post('id', 0);
        $count = PluginPackage::where('category_id', $id)->count();
        if ($count > 0) {
            return json(['code' => 1, 'msg' => '该分类下还有 ' . $count . ' 个插件，无法删除']);
        }
        PluginCategory::destroy($id);
        Cache::tag('plugin_store')->clear();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    // ==================== 版本管理 ====================

    /**
     * 版本列表
     */
    public function versions()
    {
        $pluginId = (int) $this->request->param('plugin_id', 0);
        $package = PluginPackage::find($pluginId);
        if (!$package) {
            return $this->error('插件包不存在');
        }

        $versions = PluginVersion::where('plugin_id', $pluginId)
            ->order('id', 'desc')
            ->select();

        $this->assign([
            'package' => $package,
            'versions' => $versions,
            'menuActive' => 'plugin_store',
        ]);
        return $this->view('/plugin_store/versions');
    }

    /**
     * 新增版本
     */
    public function versionAdd()
    {
        $pluginId = (int) $this->request->param('plugin_id', 0);
        $package = PluginPackage::find($pluginId);
        if (!$package) {
            return json(['code' => 1, 'msg' => '插件包不存在']);
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['plugin_id'] = $pluginId;

            $validate = new \think\Validate([
                'version' => 'require|max:32',
                'file_path' => 'require',
            ]);
            if (!$validate->check($data)) {
                return json(['code' => 1, 'msg' => $validate->getError()]);
            }

            // 如果设为当前版本，取消其他版本的 is_current
            if (!empty($data['is_current'])) {
                PluginVersion::where('plugin_id', $pluginId)->update(['is_current' => 0]);
                PluginPackage::where('id', $pluginId)->update(['version' => $data['version']]);
            }

            PluginVersion::create($data);
            Cache::tag('plugin_store')->clear();
            return json(['code' => 0, 'msg' => '版本添加成功']);
        }

        $this->assign([
            'package' => $package,
            'menuActive' => 'plugin_store',
        ]);
        return $this->view('/plugin_store/version_add');
    }

    /**
     * 删除版本
     */
    public function versionDelete()
    {
        $id = (int) $this->request->post('id', 0);
        $version = PluginVersion::find($id);
        if (!$version) {
            return json(['code' => 1, 'msg' => '版本不存在']);
        }
        if ($version->is_current) {
            return json(['code' => 1, 'msg' => '当前版本不能删除']);
        }
        $version->delete();
        Cache::tag('plugin_store')->clear();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    // ==================== 依赖管理 ====================

    /**
     * 依赖列表
     */
    public function dependencies()
    {
        $pluginId = (int) $this->request->param('plugin_id', 0);
        $package = PluginPackage::find($pluginId);
        if (!$package) {
            return $this->error('插件包不存在');
        }

        $dependencies = PluginDependency::with('dependsOn')
            ->where('plugin_id', $pluginId)
            ->select();

        $allPlugins = PluginPackage::where('status', 1)
            ->where('id', '<>', $pluginId)
            ->column('name', 'id');

        $this->assign([
            'package' => $package,
            'dependencies' => $dependencies,
            'allPlugins' => $allPlugins,
            'menuActive' => 'plugin_store',
        ]);
        return $this->view('/plugin_store/dependencies');
    }

    /**
     * 新增依赖
     */
    public function dependencyAdd()
    {
        $data = $this->request->post();
        $validate = new \think\Validate([
            'plugin_id' => 'require|integer',
            'depends_on_plugin_id' => 'require|integer',
            'min_version' => 'require',
        ]);
        if (!$validate->check($data)) {
            return json(['code' => 1, 'msg' => $validate->getError()]);
        }

        if ($data['plugin_id'] == $data['depends_on_plugin_id']) {
            return json(['code' => 1, 'msg' => '不能依赖自己']);
        }

        PluginDependency::create($data);
        Cache::tag('plugin_store')->clear();
        return json(['code' => 0, 'msg' => '依赖添加成功']);
    }

    /**
     * 删除依赖
     */
    public function dependencyDelete()
    {
        $id = (int) $this->request->post('id', 0);
        PluginDependency::destroy($id);
        Cache::tag('plugin_store')->clear();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    // ==================== 安装日志 ====================

    /**
     * 下载/安装日志
     */
    public function logs()
    {
        $pluginId = (int) $this->request->get('plugin_id', 0);
        $source = $this->request->get('source', '');
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);

        $query = PluginDownloadLog::with('plugin');
        if ($pluginId) {
            $query->where('plugin_id', $pluginId);
        }
        if ($source) {
            $query->where('source', $source);
        }

        $list = $query->order('id', 'desc')
            ->paginate(['list_rows' => $limit, 'page' => $page, 'query' => $this->request->get()]);

        $this->assign([
            'list' => $list,
            'pluginId' => $pluginId,
            'source' => $source,
            'menuActive' => 'plugin_store_log',
        ]);
        return $this->view('/plugin_store/logs');
    }

    // ==================== 上传插件包 ====================

    /**
     * 上传插件ZIP包到服务器
     */
    public function uploadPackage()
    {
        $file = $this->request->file('package_zip');
        if (!$file) {
            return json(['code' => 1, 'msg' => '请选择ZIP文件']);
        }

        $ext = strtolower($file->getOriginalExtension());
        if ($ext !== 'zip') {
            return json(['code' => 1, 'msg' => '仅支持 .zip 格式']);
        }

        $size = $file->getSize();
        if ($size > 50 * 1024 * 1024) {
            return json(['code' => 1, 'msg' => '文件大小超过50MB限制']);
        }

        // 保存到 upload/plugin/ 目录
        $savePath = root_path() . 'public/upload/plugin/' . date('Ymd');
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        $fileName = md5(uniqid()) . '.zip';
        $fullPath = $savePath . '/' . $fileName;

        $file->move($savePath, $fileName);

        // 计算 SHA256
        $hash = hash_file('sha256', $fullPath);

        return json([
            'code' => 0,
            'msg' => '上传成功',
            'data' => [
                'file_path' => str_replace(root_path(), '', $fullPath),
                'file_size' => $size,
                'file_hash' => $hash,
                'original_name' => $file->getOriginalName(),
            ],
        ]);
    }
}

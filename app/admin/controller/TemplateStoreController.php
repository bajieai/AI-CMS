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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\TemplateInstall;
use app\common\model\TemplateReview;
use app\common\model\TemplateStore;
use app\common\model\TemplateStoreCategory;
use app\common\service\template\TemplatePaymentService;
use app\common\service\template\TemplateStoreService;
use app\common\service\theme\ThemeBackupService;
use app\common\service\theme\ThemeColorVariantService;
use app\common\service\theme\ThemePackageService;
use app\common\service\theme\ThemeVersionManager;

/**
 * 模板商店控制器 - V2.9.12新增
 *
 * 管理员角色：模板上下架/审核/分类管理
 * 网站主角色：浏览/安装/切换/评分（Day 3追加）
 */
class TemplateStoreController extends AdminBaseController
{
    /**
     * 模板列表（管理员）
     */
    public function index()
    {
        $service = new TemplateStoreService();
        $params = $this->request->get();
        $data = $service->getList($params);
        $categories = $service->getCategories();

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'pages' => $data['pages'],
            'categories' => $categories,
            'params' => $params,
        ]);

        return $this->view('/template_store/index');
    }

    /**
     * 添加模板（管理员）
     */
    public function add()
    {
        if ($this->request->isGet()) {
            $service = new TemplateStoreService();
            $categories = $service->getCategories();
            $this->assign(['categories' => $categories, 'info' => null]);
            return $this->view('/template_store/detail');
        }

        $data = $this->request->post();
        $result = $this->validateAndSave($data);

        if ($result['success']) {
            $this->recordLog('添加模板', $data['name'] ?? '', $data);
            if (!$this->request->isAjax()) {
                return redirect('/admin/template_store/index')->with('success', '添加成功');
            }
            return $this->success('添加成功', ['redirect' => '/admin/template_store/index']);
        }
        return $this->error($result['message']);
    }

    /**
     * 编辑模板（管理员）
     */
    public function edit(int $id)
    {
        $info = TemplateStore::find($id);
        if (empty($info)) {
            return $this->error('模板不存在');
        }

        if ($this->request->isGet()) {
            $service = new TemplateStoreService();
            $categories = $service->getCategories();
            $this->assign(['categories' => $categories, 'info' => $info]);
            return $this->view('/template_store/detail');
        }

        $data = $this->request->post();
        $result = $this->validateAndSave($data, $id);

        if ($result['success']) {
            $this->recordLog('编辑模板', $data['name'] ?? '', $data);
            // 普通表单POST提交，服务端重定向
            if (!$this->request->isAjax()) {
                return redirect('/admin/template_store/index')->with('success', '保存成功');
            }
            return $this->success('保存成功', ['redirect' => '/admin/template_store/index']);
        }
        return $this->error($result['message']);
    }

    /**
     * 删除模板
     */
    public function delete(int $id): \think\Response
    {
        $info = TemplateStore::find($id);
        if (empty($info)) {
            return $this->error('模板不存在');
        }

        if ($info->delete()) {
            $service = new TemplateStoreService();
            $service->clearCache();
            $this->recordLog('删除模板', $info->name);
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }

    /**
     * 上架模板
     */
    public function publish(int $id): \think\Response
    {
        $info = TemplateStore::find($id);
        if (empty($info)) {
            return $this->error('模板不存在');
        }

        $info->status = TemplateStore::STATUS_ONLINE;
        $info->save();

        $service = new TemplateStoreService();
        $service->clearCache();
        $this->recordLog('上架模板', $info->name);

        return $this->success('上架成功');
    }

    /**
     * 下架模板
     */
    public function unpublish(int $id): \think\Response
    {
        $info = TemplateStore::find($id);
        if (empty($info)) {
            return $this->error('模板不存在');
        }

        $info->status = TemplateStore::STATUS_OFFLINE;
        $info->save();

        $service = new TemplateStoreService();
        $service->clearCache();
        $this->recordLog('下架模板', $info->name);

        return $this->success('下架成功');
    }

    /**
     * 切换推荐状态
     */
    public function toggleFeatured(int $id): \think\Response
    {
        $info = TemplateStore::find($id);
        if (empty($info)) {
            return $this->error('模板不存在');
        }

        $info->is_featured = $info->is_featured ? 0 : 1;
        $info->save();

        $service = new TemplateStoreService();
        $service->clearCache();

        return $this->success('操作成功', ['is_featured' => $info->is_featured]);
    }

    /**
     * 分类管理页
     */
    public function categories(): string
    {
        $service = new TemplateStoreService();
        $list = $service->getCategories();

        $this->assign(['list' => $list]);
        return $this->view('/template_store/categories');
    }

    /**
     * 保存分类
     */
    public function saveCategory()
    {
        $data = $this->request->post();

        $id = (int) ($data['id'] ?? 0);
        $category = $id ? TemplateStoreCategory::find($id) : new TemplateStoreCategory();
        if ($id && empty($category)) {
            return $this->error('分类不存在');
        }

        $category->name = $data['name'] ?? '';
        $category->slug = $data['slug'] ?? '';
        $category->description = $data['description'] ?? '';
        $category->icon = $data['icon'] ?? '';
        $category->sort = (int) ($data['sort'] ?? 0);
        $category->is_enabled = (int) ($data['is_enabled'] ?? 1);

        if ($category->save()) {
            $service = new TemplateStoreService();
            $service->clearCache();
            $this->recordLog('保存模板分类', $category->name);
            return $this->success('保存成功', ['redirect' => '/admin/template_store/categories']);
        }
        return $this->error('保存失败');
    }

    /**
     * 删除分类
     */
    public function deleteCategory(int $id): \think\Response
    {
        $category = TemplateStoreCategory::find($id);
        if (empty($category)) {
            return $this->error('分类不存在');
        }

        // 检查分类下是否有模板
        $count = TemplateStore::where('category_id', $id)->count();
        if ($count > 0) {
            return $this->error('该分类下存在模板，无法删除');
        }

        if ($category->delete()) {
            $service = new TemplateStoreService();
            $service->clearCache();
            $this->recordLog('删除模板分类', $category->name);
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }

    // ============================================================
    // 私有方法
    // ============================================================

    /**
     * 验证并保存模板数据
     */
    private function validateAndSave(array $data, int $id = 0): array
    {
        if (empty($data['name'])) {
            return ['success' => false, 'message' => '模板名称不能为空'];
        }
        if (empty($data['slug'])) {
            return ['success' => false, 'message' => '模板标识不能为空'];
        }

        // slug唯一性检查
        $exists = TemplateStore::where('slug', $data['slug']);
        if ($id > 0) {
            $exists->where('id', '<>', $id);
        }
        if ($exists->find()) {
            return ['success' => false, 'message' => '模板标识已存在'];
        }

        $store = $id > 0 ? TemplateStore::find($id) : new TemplateStore();
        if ($id > 0 && empty($store)) {
            return ['success' => false, 'message' => '模板不存在'];
        }

        $store->slug = $data['slug'];
        $store->name = $data['name'];
        $store->category_id = (int) ($data['category_id'] ?? 0);
        $store->description = $data['description'] ?? '';
        // 截图：textarea 每行一个URL → JSON数组
        $screenshots = $data['screenshots'] ?? '';
        if (is_string($screenshots) && !empty(trim($screenshots))) {
            $lines = array_filter(array_map('trim', explode("\n", $screenshots)));
            $store->screenshots = json_encode(array_values($lines), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        } else {
            $store->screenshots = '[]';
        }
        $store->price = (float) ($data['price'] ?? 0);
        $store->author_name = $data['author_name'] ?? '';
        $store->author_id = (int) ($data['author_id'] ?? 0);
        $store->version = $data['version'] ?? '1.0.0';
        // 环境要求：req_php + req_cms → JSON对象
        $requirements = [
            'php' => $data['req_php'] ?? '>=8.0',
            'cms' => $data['req_cms'] ?? '>=2.9.0',
        ];
        $store->requirements = json_encode($requirements, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $store->file_size = (int) ($data['file_size'] ?? 0);

        // 新增时默认待审核
        if ($id === 0) {
            $store->status = TemplateStore::STATUS_PENDING;
        }

        if ($store->save()) {
            $service = new TemplateStoreService();
            $service->clearCache();
            return ['success' => true];
        }
        return ['success' => false, 'message' => '保存失败'];
    }

    // ============================================================
    // 网站主角色方法（Day 3）
    // ============================================================

    /**
     * 模板市场（网站主-卡片列表）
     */
    public function market(): string
    {
        $service = new TemplateStoreService();
        $params = $this->request->get();
        $data = $service->getList($params);
        $categories = $service->getCategories();
        $featured = $service->getFeatured(6);

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'pages' => $data['pages'],
            'categories' => $categories,
            'featured' => $featured,
            'params' => $params,
        ]);

        return $this->view('/template_store/market');
    }

    /**
     * AJAX分页（网站主）
     */
    public function list(): \think\Response
    {
        $service = new TemplateStoreService();
        $params = $this->request->get();
        $data = $service->getList($params);
        return $this->success('ok', $data);
    }

    /**
     * 模板详情（网站主）
     */
    public function detail(int $id): string
    {
        $service = new TemplateStoreService();
        $info = $service->getDetail($id);
        if (empty($info)) {
            return $this->view('/template_store/market');
        }

        // 检查当前用户是否已安装
        $memberId = (int) session('user_id');
        $installed = TemplateInstall::where('store_id', $id)
            ->where('member_id', $memberId)
            ->find();

        // 获取同分类推荐
        $related = $service->getByCategory($info->category_id, 4);

        $this->assign([
            'info' => $info,
            'installed' => $installed,
            'related' => $related,
        ]);

        return $this->view('/template_store/store_detail');
    }

    /**
     * 预览模板
     */
    public function preview(string $slug): string
    {
        $service = new TemplateStoreService();
        $info = $service->getBySlug($slug);
        if (empty($info)) {
            return $this->view('/template_store/market');
        }

        $this->assign([
            'info' => $info,
            'preview_url' => '/template/preview/' . $slug,
        ]);

        return $this->view('/template_store/preview');
    }

    /**
     * 我的已安装模板
     */
    public function myTemplates(): string
    {
        $memberId = (int) session('user_id');
        $list = TemplateInstall::with('store')
            ->where('member_id', $memberId)
            ->order('is_active', 'desc')
            ->order('create_time', 'desc')
            ->select();

        $this->assign([
            'list' => $list,
        ]);

        return $this->view('/template_store/my_templates');
    }

    /**
     * 安装模板（AJAX）
     */
    public function doInstall(int $id): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $service = new TemplateStoreService();

        try {
            $result = $service->installTheme($id, $memberId);
            return $this->success($result['message'], $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 激活/切换模板（AJAX）
     */
    public function doActivate(int $id): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $service = new TemplateStoreService();

        try {
            $result = $service->activateTheme($id, $memberId);
            return $this->success($result['message']);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 购买模板（AJAX）
     */
    public function buy(int $id): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $payMethod = $this->request->post('pay_method', 'wechat');
        $service = new TemplatePaymentService();

        $result = $service->createOrder($id, $memberId, $payMethod);

        if ($result['success']) {
            return $this->success('订单创建成功', $result);
        }
        return $this->error($result['msg'] ?? '购买失败');
    }

    /**
     * 生成配色变体（AJAX）
     */
    public function generateVariants(int $id): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        try {
            $service = new ThemeColorVariantService();
            $result = $service->generateVariants($id);
            return $this->success('配色方案生成成功', $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    // ============================================================
    // Day 10: 评分评论
    // ============================================================

    /**
     * 提交评分/评论（网站主）
     */
    public function submitReview(int $id): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $rating = (int) $this->request->post('rating', 5);
        $comment = $this->request->post('comment', '');
        $images = $this->request->post('images', []);
        if (is_string($images)) {
            $images = json_decode($images, true) ?: [];
        }

        if ($rating < 1 || $rating > 5) {
            return $this->error('评分必须在1-5之间');
        }

        // 检查是否已安装该模板
        $installed = TemplateInstall::where('store_id', $id)
            ->where('member_id', $memberId)
            ->find();
        if (empty($installed)) {
            return $this->error('您未安装该模板，无法评分');
        }

        // V2.9.13 I-3: 评论图片支持
        $imageUrls = [];
        if (!empty($_FILES['review_images'])) {
            $files = $_FILES['review_images'];
            $uploadService = new \app\common\service\UploadService();
            foreach ($files['tmp_name'] as $index => $tmpName) {
                if ($files['error'][$index] === UPLOAD_ERR_OK) {
                    $result = $uploadService->uploadImage($tmpName, $files['name'][$index]);
                    if ($result['success']) {
                        $imageUrls[] = $result['url'];
                    }
                }
            }
        }
        if (!empty($images) && empty($imageUrls)) {
            $imageUrls = array_slice((array) $images, 0, 5);
        }

        // 检查是否已评价
        $exists = TemplateReview::where('store_id', $id)
            ->where('member_id', $memberId)
            ->find();
        if ($exists) {
            $exists->rating = $rating;
            $exists->comment = $comment;
            $exists->images = !empty($imageUrls) ? $imageUrls : null;
            $exists->is_audited = TemplateReview::AUDIT_PENDING;
            $exists->save();
        } else {
            TemplateReview::create([
                'store_id' => $id,
                'member_id' => $memberId,
                'rating' => $rating,
                'comment' => $comment,
                'images' => !empty($imageUrls) ? $imageUrls : null,
                'is_audited' => TemplateReview::AUDIT_PENDING,
            ]);
        }

        // 更新模板评分统计
        $service = new TemplateStoreService();
        $service->updateRatingStats($id);

        return $this->success('评价提交成功，等待审核');
    }

    /**
     * 评论列表（管理员审核）
     */
    public function reviews(): string
    {
        $storeId = (int) $this->request->get('store_id', 0);
        $status = $this->request->get('status', '');

        $query = TemplateReview::with('store')->with('member')
            ->order('create_time', 'desc');

        if ($storeId > 0) {
            $query->where('store_id', $storeId);
        }
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $list = $query->paginate(20);

        $this->assign([
            'list' => $list,
            'store_id' => $storeId,
            'status' => $status,
        ]);

        return $this->view('/template_store/reviews');
    }

    /**
     * 审核评论（AJAX）
     */
    public function auditReview(int $id): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $review = TemplateReview::find($id);
        if (empty($review)) {
            return $this->error('评论不存在');
        }

        $action = $this->request->post('action', 'approve');
        $review->is_audited = $action === 'approve' ? TemplateReview::AUDIT_PASS : TemplateReview::AUDIT_REJECT;
        $review->save();

        // 更新评分统计
        $service = new TemplateStoreService();
        $service->updateRatingStats($review->store_id);

        return $this->success('审核完成');
    }

    /**
     * 删除评论（AJAX）
     */
    public function deleteReview(int $id): \think\Response
    {
        $review = TemplateReview::find($id);
        if (empty($review)) {
            return $this->error('评论不存在');
        }

        $storeId = $review->store_id;
        $review->delete();

        // 更新评分统计
        $service = new TemplateStoreService();
        $service->updateRatingStats($storeId);

        return $this->success('删除成功');
    }

    // ============================================================
    // Day 12: 模板备份还原
    // ============================================================

    /**
     * 备份列表页
     */
    public function backups(): string
    {
        $theme = $this->request->get('theme', '');
        $service = new ThemeBackupService();
        $list = $theme ? $service->getBackups($theme) : [];

        $this->assign([
            'list' => $list,
            'theme' => $theme,
        ]);

        return $this->view('/template_store/backups');
    }

    /**
     * 创建备份（AJAX）
     */
    public function doBackup(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $theme = $this->request->post('theme', '');
        if (empty($theme)) {
            return $this->error('缺少主题参数');
        }

        $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme;
        $service = new ThemeBackupService();
        $result = $service->backup($theme, $themePath);

        if ($result['success']) {
            return $this->success('备份成功', $result);
        }
        return $this->error($result['message']);
    }

    /**
     * 回滚备份（AJAX）
     */
    public function doRollback(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $backupId = $this->request->post('backup_id', '');
        $theme = $this->request->post('theme', '');

        if (empty($backupId) || empty($theme)) {
            return $this->error('缺少参数');
        }

        $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme;
        $service = new ThemeBackupService();
        $result = $service->rollback($backupId, $themePath);

        if ($result['success']) {
            return $this->success('回滚成功');
        }
        return $this->error($result['message']);
    }

    // ============================================================
    // Day 16: 模板打包导出
    // ============================================================

    /**
     * 导出模板ZIP
     */
    public function exportTheme(int $id): \think\Response
    {
        $store = TemplateStore::find($id);
        if (empty($store)) {
            return $this->error('模板不存在');
        }

        $packageService = new ThemePackageService();
        $result = $packageService->exportTheme($store->slug, true);

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        $zipPath = $result['path'];
        if (!file_exists($zipPath)) {
            return $this->error('ZIP文件不存在');
        }

        return download($zipPath, $store->slug . '_v' . $store->version . '.zip');
    }

    // ============================================================
    // Day 17: 模板上传与审核
    // ============================================================

    /**
     * 上传模板ZIP（AJAX）
     */
    public function uploadTheme(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $file = $this->request->file('file');
        if (empty($file)) {
            return $this->error('未上传文件');
        }

        // 验证扩展名
        $ext = strtolower($file->getOriginalExtension());
        if ($ext !== 'zip') {
            return $this->error('仅支持ZIP格式');
        }

        // 保存到临时目录
        $tempDir = runtime_path() . 'temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . DIRECTORY_SEPARATOR . 'upload_' . uniqid() . '.zip';
        $file->move(dirname($zipPath), basename($zipPath));

        // 导入
        $packageService = new ThemePackageService();
        $result = $packageService->importTheme($zipPath);

        if ($result['success']) {
            // 自动创建商店记录（待审核状态）
            $exists = TemplateStore::where('slug', $result['theme_name'])->find();
            if (!$exists) {
                TemplateStore::create([
                    'slug' => $result['theme_name'],
                    'name' => $result['theme_name'],
                    'status' => TemplateStore::STATUS_PENDING,
                    'version' => '1.0.0',
                    'author_name' => '开发者上传',
                ]);
            }
            return $this->success('上传成功，等待管理员审核', $result);
        }

        return $this->error($result['message']);
    }

    // ============================================================
    // Day 18: 版本管理
    // ============================================================

    /**
     * 版本历史
     */
    public function versionHistory(): \think\Response
    {
        $theme = $this->request->get('theme', '');
        if (empty($theme)) {
            return $this->error('缺少主题参数');
        }

        $manager = new ThemeVersionManager();
        $history = $manager->getVersionHistory($theme);

        return $this->success('ok', ['history' => $history]);
    }
}

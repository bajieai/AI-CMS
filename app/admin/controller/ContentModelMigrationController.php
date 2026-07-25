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
use app\common\model\ContentModel;
use app\common\service\content\ContentModelMigrationService;

/**
 * V2.9.27 S-8: 内容模型迁移工具控制器
 */
class ContentModelMigrationController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 迁移工具首页
     */
    public function index()
    {
        $models = ContentModel::where('status', 1)->order('sort', 'asc')->select();
        $logs = ContentModelMigrationService::getMigrationLogs(20);

        $this->assign([
            'models' => $models,
            'logs' => $logs,
        ]);
        return $this->view('/content_model_migration_index');
    }

    /**
     * AJAX: 批量分配模型
     */
    public function batchAssign()
    {
        $modelId = (int) $this->request->post('model_id', 0);
        $type = (int) $this->request->post('type', 0);

        if ($modelId <= 0 || $type <= 0) {
            return $this->error('参数错误');
        }

        $result = ContentModelMigrationService::batchAssignModel($modelId, $type);
        $this->recordLog('批量分配模型', "模型ID:{$modelId}, 类型:{$type}");
        return $this->success('操作完成', $result);
    }

    /**
     * AJAX: 从类型导入模型
     */
    public function importFromType()
    {
        $result = ContentModelMigrationService::importFromType();
        $this->recordLog('从类型导入模型', '');
        return $this->success('操作完成', $result);
    }

    /**
     * AJAX: 初始化模型字段
     */
    public function initFields()
    {
        $modelId = (int) $this->request->post('model_id', 0);
        if ($modelId <= 0) {
            return $this->error('参数错误');
        }

        $result = ContentModelMigrationService::initFields($modelId);
        $this->recordLog('初始化模型字段', "模型ID:{$modelId}");
        return $this->success('操作完成', $result);
    }
}

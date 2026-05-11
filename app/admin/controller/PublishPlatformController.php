<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\PublishPlatform;
use app\common\service\PublishPlatformService;
use think\facade\Request;

/**
 * 发布平台管理后台控制器 - V2.5新增
 */
class PublishPlatformController extends AdminBaseController
{
    public function index()
    {
        $list = PublishPlatform::order('id', 'desc')
            ->paginate(['list_rows' => 20, 'path' => '/admin/publish_platform/index']);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list->toArray()]);
        }

        $this->assign('list', $list);
        return $this->view('/publish_platform_index');
    }

    public function add()
    {
        return $this->edit(0);
    }

    public function edit(int $id = 0)
    {
        $platform = $id ? PublishPlatform::find($id) : null;
        $this->assign('info', $platform);
        return $this->view('/publish_platform_edit');
    }

    public function save()
    {
        $data = [
            'id'           => (int) $this->request->post('id', 0),
            'name'         => $this->request->post('name', ''),
            'display_name' => $this->request->post('display_name', ''),
            'config_json'  => $this->request->post('config_json', []),
            'is_enabled'   => (int) $this->request->post('is_enabled', 1),
        ];

        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '平台名称不能为空']);
        }

        try {
            if ($data['id'] > 0) {
                $platform = PublishPlatform::find($data['id']);
                if ($platform) { $platform->save($data); }
            } else {
                unset($data['id']);
                PublishPlatform::create($data);
            }
            \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_PUBLISH);
            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            PublishPlatform::destroy($id);
            \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_PUBLISH);
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * V2.9.3 M28: 手动同步内容到平台
     */
    public function sync()
    {
        $contentId = (int) Request::post('content_id', 0);
        if ($contentId <= 0) {
            return json(['code' => 1, 'msg' => '内容ID不能为空']);
        }

        try {
            PublishPlatformService::autoPublishToPlatforms($contentId);
            $this->recordLog('publish', '手动同步内容到平台 content_id=' . $contentId);
            return json(['code' => 0, 'msg' => '同步任务已提交']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '同步失败: ' . $e->getMessage()]);
        }
    }

    /**
     * V2.9.3 M28: 刷新所有平台Token
     */
    public function refreshTokens()
    {
        try {
            $results = PublishPlatformService::refreshAllTokens();
            return json(['code' => 0, 'msg' => 'Token刷新完成', 'data' => $results]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '刷新失败: ' . $e->getMessage()]);
        }
    }

    /**
     * V2.7: 头条号OAuth授权页
     */
    public function toutiaoOauth()
    {
        $id = (int) $this->request->get('id', 0);
        $platform = PublishPlatform::find($id);
        if (!$platform || $platform->name !== 'toutiao') {
            return $this->error('平台配置不存在');
        }

        $config = $platform->config_json;
        $clientKey = $config['client_key'] ?? '';
        $redirectUri = (string) url('/oauth/toutiao/callback', [], true, true);
        $state = $id . '|' . bin2hex(random_bytes(8));

        $authUrl = \app\common\service\publish\ToutiaoPlatform::getAuthUrl($clientKey, $redirectUri, $state);

        $this->assign([
            'platform'   => $platform,
            'auth_url'   => $authUrl,
            'client_key' => $clientKey ? '已配置' : '未配置',
        ]);
        return $this->view('/toutiao_oauth');
    }
}

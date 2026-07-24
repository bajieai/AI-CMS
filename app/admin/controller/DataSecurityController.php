<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\EncryptionService;
use app\common\service\PasswordPolicyService;
use app\common\service\DataMaskService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 SEC-3: 数据安全控制器
 */
class DataSecurityController extends AdminBaseController
{
    protected EncryptionService $encryptionService;
    protected PasswordPolicyService $passwordService;
    protected DataMaskService $maskService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->encryptionService = new EncryptionService();
        $this->passwordService = new PasswordPolicyService();
        $this->maskService = new DataMaskService();
    }

    /**
     * 数据安全配置页
     */
    public function index()
    {
        $keyList = $this->encryptionService->getKeyList();

        View::assign([
            'key_list' => $keyList,
        ]);

        return $this->view('/data_security/index');
    }

    /**
     * 加密/解密测试
     */
    public function encrypt()
    {
        $action = $this->request->post('action', 'encrypt');
        $text = $this->request->post('text', '');

        if ($action === 'encrypt') {
            $result = $this->encryptionService->encrypt($text);
        } else {
            $result = $this->encryptionService->decrypt($text);
        }

        return json(['code' => 0, 'msg' => '操作成功', 'data' => ['result' => $result]]);
    }

    /**
     * 密钥轮换
     */
    public function rotateKey()
    {
        $keyId = $this->request->post('key_id', 'default');

        try {
            $this->encryptionService->rotateKey($keyId);
            return json(['code' => 0, 'msg' => '密钥轮换成功']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '密钥轮换失败: ' . $e->getMessage()]);
        }
    }
}

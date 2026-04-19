<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\User;
use app\service\JwtService;
use app\exception\BusinessException;

/**
 * 认证控制器
 */
class AuthController extends BaseController
{
    /**
     * JWT服务
     */
    protected JwtService $jwtService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct($this->app ?? app());
        $this->jwtService = new JwtService();
    }

    /**
     * 用户登录
     */
    public function login(): \think\Response
    {
        $input = $this->getInput();
        
        // 验证参数
        $this->validateRequired(['username', 'password'], $input);
        
        $username = trim($input['username']);
        $password = $input['password'];
        
        // 查找用户（含角色）
        $user = User::with(['roles'])
            ->where('username', '=', $username)
            ->whereOr('email', '=', $username)
            ->find();
        
        if (!$user) {
            throw new BusinessException('用户不存在', 401);
        }
        
        // 检查状态
        if ($user->status !== User::STATUS_ENABLED) {
            throw new BusinessException('账号已被禁用', 403);
        }
        
        // 验证密码
        if (!$user->checkPassword($password)) {
            throw new BusinessException('密码错误', 401);
        }

        // 生成Token
        $tokens = $this->jwtService->generateTokens([
            'id' => $user->id,
            'username' => $user->username,
            'roles' => $user->roles->column('slug'),
        ]);
        
        // 更新登录信息
        $user->updateLastLogin($this->request->ip());
        
        return $this->success([
            'user' => $user->getBasicInfo(),
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
        ], '登录成功');
    }

    /**
     * 获取当前用户信息
     */
    public function me(): \think\Response
    {
        $userId = $this->request->user_id;
        
        $user = User::with('roles.permissions')->find($userId);
        
        if (!$user) {
            throw new BusinessException('用户不存在', 404);
        }
        
        return $this->success([
            'user' => $user->getBasicInfo(),
            'permissions' => $user->getPermissions(),
            'is_super_admin' => $user->isSuperAdmin(),
        ]);
    }

    /**
     * 刷新Token
     */
    public function refresh(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['refresh_token'], $input);
        
        $refreshToken = $input['refresh_token'];
        
        try {
            $tokens = $this->jwtService->refreshTokens($refreshToken);
            
            return $this->success([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'token_type' => $tokens['token_type'],
                'expires_in' => $tokens['expires_in'],
            ], 'Token刷新成功');
        } catch (\Exception $e) {
            throw new BusinessException($e->getMessage(), 401);
        }
    }

    /**
     * 登出
     */
    public function logout(): \think\Response
    {
        $userId = $this->request->user_id;
        
        // 将用户所有Token作废
        $this->jwtService->invalidateUser($userId);
        
        return $this->success(null, '登出成功');
    }

    /**
     * 修改密码
     */
    public function changePassword(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['old_password', 'new_password', 'confirm_password'], $input);
        
        $oldPassword = $input['old_password'];
        $newPassword = $input['new_password'];
        $confirmPassword = $input['confirm_password'];
        
        // 验证新密码
        if (strlen($newPassword) < 6) {
            throw new BusinessException('新密码长度不能少于6位', 400, ['new_password' => '新密码长度不能少于6位']);
        }
        
        if ($newPassword !== $confirmPassword) {
            throw new BusinessException('两次输入的密码不一致', 400, ['confirm_password' => '两次输入的密码不一致']);
        }
        
        // 获取当前用户
        $userId = $this->request->user_id;
        $user = User::find($userId);
        
        if (!$user) {
            throw new BusinessException('用户不存在', 404);
        }
        
        // 验证旧密码
        if (!$user->checkPassword($oldPassword)) {
            throw new BusinessException('原密码错误', 400, ['old_password' => '原密码错误']);
        }
        
        // 更新密码
        $user->password = $newPassword;
        $user->save();
        
        // 作废所有Token
        $this->jwtService->invalidateUser($userId);
        
        return $this->success(null, '密码修改成功，请重新登录');
    }
}

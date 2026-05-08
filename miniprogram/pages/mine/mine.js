// V2.8 个人中心页面
Page({
  data: {
    memberInfo: null,
    isLogin: false,
    points: 0,
    signinDays: 0,
    hasSignedToday: false
  },

  onShow() {
    this.checkLogin();
  },

  checkLogin() {
    const token = wx.getStorageSync('api_token');
    if (token) {
      this.loadMemberInfo();
    } else {
      this.setData({ isLogin: false });
    }
  },

  loadMemberInfo() {
    const app = getApp();
    app.request({ url: '/member/profile' }).then(res => {
      if (res.code === 0) {
        this.setData({
          memberInfo: res.data,
          isLogin: true,
          points: res.data.points || 0,
          signinDays: res.data.signin_count || 0
        });
        this.checkSigninStatus();
      }
    });
  },

  checkSigninStatus() {
    const app = getApp();
    app.request({ url: '/member/hasSignedToday' }).then(res => {
      if (res.code === 0) {
        this.setData({ hasSignedToday: res.data.signed });
      }
    });
  },

  // 微信登录
  handleLogin() {
    const app = getApp();
    app.wxLogin().then(() => {
      this.loadMemberInfo();
      wx.showToast({ title: '登录成功', icon: 'success' });
    }).catch(err => {
      wx.showToast({ title: err || '登录失败', icon: 'none' });
    });
  },

  // 签到
  handleSignin() {
    if (this.data.hasSignedToday) {
      wx.showToast({ title: '今日已签到', icon: 'none' });
      return;
    }
    const app = getApp();
    app.request({
      url: '/member/signin',
      method: 'POST'
    }).then(res => {
      if (res.code === 0) {
        this.setData({
          hasSignedToday: true,
          points: this.data.points + (res.data.points || 0),
          signinDays: this.data.signinDays + 1
        });
        wx.showToast({ title: '签到成功', icon: 'success' });
      } else {
        wx.showToast({ title: res.msg, icon: 'none' });
      }
    });
  },

  // 退出登录
  handleLogout() {
    wx.removeStorageSync('api_token');
    wx.removeStorageSync('member_id');
    const app = getApp();
    app.globalData.token = '';
    app.globalData.memberId = 0;
    this.setData({ isLogin: false, memberInfo: null });
    wx.showToast({ title: '已退出', icon: 'success' });
  },

  // 分享
  onShareAppMessage() {
    return {
      title: '八界AI-CMS',
      path: '/pages/index/index'
    };
  }
});

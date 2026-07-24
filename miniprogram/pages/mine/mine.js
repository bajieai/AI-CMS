// V2.9 个人中心页面
const api = require('../../utils/api.js');

Page({
  data: {
    memberInfo: null,
    isLogin: false,
    points: 0,
    signinDays: 0,
    hasSignedToday: false,
    couponCount: 0,
    orderCount: 0,
    inviteCount: 0,
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
          signinDays: res.data.signin_count || 0,
          couponCount: res.data.coupon_count || 0,
          orderCount: res.data.order_count || 0,
          inviteCount: res.data.invite_count || 0,
        });
        this.checkSigninStatus();
        this.loadInviteInfo();
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

  loadInviteInfo() {
    api.getInviteInfo().then(res => {
      if (res.code === 0) {
        this.setData({ inviteCount: res.data.invite_count || 0 });
      }
    }).catch(() => {});
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

  // 前往签到页
  goSignin() {
    wx.navigateTo({ url: '/pages/signin/signin' });
  },

  // 前往优惠券页
  goCoupon() {
    if (!this.data.isLogin) {
      wx.navigateTo({ url: '/pages/login/login' });
      return;
    }
    wx.navigateTo({ url: '/pages/coupon/coupon' });
  },

  // 前往订单页
  goOrders() {
    if (!this.data.isLogin) {
      wx.navigateTo({ url: '/pages/login/login' });
      return;
    }
    wx.navigateTo({ url: '/pages/order/order' });
  },

  // 邀请好友 - V2.9: 跳转独立邀请页
  handleInvite() {
    if (!this.data.isLogin) {
      wx.navigateTo({ url: '/pages/login/login' });
      return;
    }
    wx.navigateTo({ url: '/pages/invite/invite' });
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
    const memberId = getApp().globalData.memberId || 0;
    return {
      title: '八界AI-CMS',
      path: `/pages/index/index?invite_by=${memberId}`,
      imageUrl: '/images/share.png'
    };
  }
});

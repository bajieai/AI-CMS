// pages/login/login.js
const api = require('../../utils/api.js');
const app = getApp();

Page({
  data: {
    username: '',
    password: '',
  },

  onInputUsername(e) {
    this.setData({ username: e.detail.value });
  },

  onInputPassword(e) {
    this.setData({ password: e.detail.value });
  },

  async onLogin() {
    const { username, password } = this.data;
    if (!username || !password) {
      wx.showToast({ title: '请填写账号密码', icon: 'none' });
      return;
    }

    wx.showLoading({ title: '登录中' });
    try {
      // 注意：小程序登录应使用微信OAuth或会员账号体系
      // 这里简化演示，实际生产环境建议接入微信登录+后端token绑定
      const res = await api.login({ username, password });
      wx.hideLoading();

      if (res.code === 0 && res.data?.token) {
        wx.setStorageSync('api_token', res.data.token);
        wx.setStorageSync('member_id', res.data.member_id || 0);
        app.globalData.token = res.data.token;
        app.globalData.memberId = res.data.member_id || 0;
        wx.showToast({ title: '登录成功', icon: 'success' });
        setTimeout(() => wx.navigateBack(), 1500);
      } else {
        wx.showToast({ title: res.msg || '登录失败', icon: 'none' });
      }
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: '网络错误', icon: 'none' });
    }
  },
});

// app.js - 八界AI-CMS 微信小程序 V2.6
App({
  globalData: {
    apiBaseUrl: 'https://your-domain.com/api/v1',
    token: wx.getStorageSync('api_token') || '',
    memberId: wx.getStorageSync('member_id') || 0,
  },

  onLaunch() {
    // 检查登录状态
    const token = wx.getStorageSync('api_token');
    if (token) {
      this.globalData.token = token;
    }
  },

  // 全局请求封装
  request(options) {
    const app = this;
    return new Promise((resolve, reject) => {
      wx.request({
        url: app.globalData.apiBaseUrl + options.url,
        method: options.method || 'GET',
        data: options.data || {},
        header: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + app.globalData.token,
          ...options.header,
        },
        success(res) {
          if (res.statusCode === 401) {
            wx.removeStorageSync('api_token');
            wx.removeStorageSync('member_id');
            app.globalData.token = '';
            app.globalData.memberId = 0;
          }
          resolve(res.data);
        },
        fail(err) {
          reject(err);
        },
      });
    });
  },
});

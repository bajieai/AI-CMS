// app.js - 八界AI-CMS 微信小程序 V2.9
App({
  globalData: {
    apiBaseUrl: 'https://your-domain.com/api/v1',
    token: wx.getStorageSync('api_token') || '',
    memberId: wx.getStorageSync('member_id') || 0,
    newbieCouponShown: wx.getStorageSync('newbie_coupon_shown') || false,
  },

  onLaunch() {
    // 检查登录状态
    const token = wx.getStorageSync('api_token');
    if (token) {
      this.globalData.token = token;
    }
    // V2.9: 静默自动登录（未登录时尝试wx.login）
    if (!token) {
      this.silentLogin().catch(() => {});
    }
  },

  // V2.9: 静默自动登录
  silentLogin() {
    const app = this;
    return new Promise((resolve, reject) => {
      wx.login({
        success(res) {
          if (res.code) {
            app.request({
              url: '/auth/wxLogin',
              method: 'POST',
              data: { code: res.code, silent: true }
            }).then(data => {
              if (data.code === 0) {
                wx.setStorageSync('api_token', data.data.token);
                wx.setStorageSync('member_id', data.data.member_id);
                app.globalData.token = data.data.token;
                app.globalData.memberId = data.data.member_id;
                // 新人券弹窗标记
                if (data.data.is_newbie && !app.globalData.newbieCouponShown) {
                  app.globalData.showNewbieCoupon = true;
                }
                resolve(data.data);
              } else {
                reject(data.msg);
              }
            }).catch(reject);
          } else {
            reject(res.errMsg);
          }
        },
        fail: reject
      });
    });
  },

  // V2.8: 微信登录
  wxLogin() {
    const app = this;
    return new Promise((resolve, reject) => {
      wx.login({
        success(res) {
          if (res.code) {
            app.request({
              url: '/auth/wxLogin',
              method: 'POST',
              data: { code: res.code }
            }).then(data => {
              if (data.code === 0) {
                wx.setStorageSync('api_token', data.data.token);
                wx.setStorageSync('member_id', data.data.member_id);
                app.globalData.token = data.data.token;
                app.globalData.memberId = data.data.member_id;
                resolve(data.data);
              } else {
                reject(data.msg);
              }
            }).catch(reject);
          } else {
            reject(res.errMsg);
          }
        },
        fail: reject
      });
    });
  },

  // V2.8: 微信支付
  wxPay(orderId) {
    const app = this;
    return new Promise((resolve, reject) => {
      app.request({
        url: '/payment/wxPay',
        method: 'POST',
        data: { order_id: orderId }
      }).then(data => {
        if (data.code === 0) {
          wx.requestPayment({
            ...data.data.payment,
            success: resolve,
            fail: reject
          });
        } else {
          reject(data.msg);
        }
      }).catch(reject);
    });
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

// pages/detail/detail.js
const api = require('../../utils/api.js');
const app = getApp();

Page({
  data: {
    id: 0,
    info: null,
    chapters: [],
    loading: true,
  },

  onLoad(options) {
    const id = parseInt(options.id || 0);
    this.setData({ id });
    this.loadDetail(id);
  },

  async loadDetail(id) {
    this.setData({ loading: true });
    try {
      const memberId = app.globalData.memberId || 0;
      const res = await api.getContentDetail(id, memberId);
      if (res.code === 0) {
        this.setData({
          info: res.data,
          chapters: res.data.chapters || [],
        });
      } else {
        wx.showToast({ title: res.msg || '加载失败', icon: 'none' });
      }
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  onShareAppMessage() {
    const info = this.data.info;
    return {
      title: info ? info.title : '八界AI-CMS',
      path: `/pages/detail/detail?id=${this.data.id}`,
    };
  },
});

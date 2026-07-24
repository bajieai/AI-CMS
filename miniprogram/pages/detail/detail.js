// pages/detail/detail.js - V2.9
const api = require('../../utils/api.js');
const app = getApp();

Page({
  data: {
    id: 0,
    info: null,
    chapters: [],
    loading: true,
    isLogin: false,
    hasPaid: false,
    ratings: [],
    ratingSummary: null,
    // V2.9: 评价提交
    showRatingModal: false,
    ratingValue: 5,
    ratingContent: '',
    isAnonymous: false,
  },

  onLoad(options) {
    const id = parseInt(options.id || 0);
    this.setData({ id, isLogin: !!(app.globalData.token) });
    this.loadDetail(id);
    this.loadRatings(id);
  },

  onShow() {
    const isLogin = !!(app.globalData.token);
    if (isLogin !== this.data.isLogin) {
      this.setData({ isLogin });
      this.loadDetail(this.data.id);
    }
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
          hasPaid: res.data.has_paid || false,
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

  async loadRatings(id) {
    try {
      const res = await api.getRatings(id, { page: 1, limit: 5 });
      if (res.code === 0) {
        this.setData({
          ratings: res.data.list || [],
          ratingSummary: res.data.summary || null,
        });
      }
    } catch (e) {
      console.log('评价加载失败', e);
    }
  },

  // 购买内容
  handleBuy() {
    if (!this.data.isLogin) {
      wx.navigateTo({ url: '/pages/login/login' });
      return;
    }
    const info = this.data.info;
    if (!info || !info.price || info.price <= 0) {
      wx.showToast({ title: '该内容免费', icon: 'none' });
      return;
    }
    wx.navigateTo({
      url: `/pages/payment/payment?content_id=${info.id}&title=${encodeURIComponent(info.title)}&price=${info.price}`,
    });
  },

  // V2.9: 打开评价弹窗
  openRatingModal() {
    if (!this.data.isLogin) {
      wx.navigateTo({ url: '/pages/login/login' });
      return;
    }
    this.setData({ showRatingModal: true, ratingValue: 5, ratingContent: '', isAnonymous: false });
  },

  closeRatingModal() {
    this.setData({ showRatingModal: false });
  },

  // 选择评分星级
  selectRating(e) {
    this.setData({ ratingValue: e.currentTarget.dataset.value });
  },

  // 评价内容输入
  onRatingInput(e) {
    this.setData({ ratingContent: e.detail.value });
  },

  // 切换匿名
  toggleAnonymous() {
    this.setData({ isAnonymous: !this.data.isAnonymous });
  },

  // V2.9: 提交评价
  async submitRating() {
    const { id, ratingValue, ratingContent, isAnonymous } = this.data;
    if (!ratingContent.trim()) {
      wx.showToast({ title: '请填写评价内容', icon: 'none' });
      return;
    }
    try {
      const res = await api.submitRating({
        content_id: id,
        rating: ratingValue,
        content: ratingContent,
        is_anonymous: isAnonymous ? 1 : 0,
      });
      if (res.code === 0) {
        wx.showToast({ title: '评价成功', icon: 'success' });
        this.setData({ showRatingModal: false });
        this.loadRatings(id); // 刷新评价列表
      } else {
        wx.showToast({ title: res.msg || '评价失败', icon: 'none' });
      }
    } catch (e) {
      wx.showToast({ title: '评价提交失败', icon: 'none' });
    }
  },

  onShareAppMessage() {
    const info = this.data.info;
    const memberId = app.globalData.memberId || 0;
    return {
      title: info ? info.title : '八界AI-CMS',
      path: `/pages/detail/detail?id=${this.data.id}&invite_by=${memberId}`,
    };
  },
});

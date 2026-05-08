// pages/order/order.js - V2.9 订单页面
const api = require('../../utils/api.js');
const app = getApp();

Page({
  data: {
    list: [],
    page: 1,
    loading: false,
    hasMore: true,
  },

  onShow() {
    this.setData({ page: 1, list: [], hasMore: true });
    this.loadData();
  },

  async loadData() {
    this.setData({ loading: true });
    try {
      const res = await api.getOrderList({ page: this.data.page, limit: 10 });
      if (res.code === 0) {
        const data = res.data.list || res.data || [];
        this.setData({
          list: this.data.page === 1 ? data : this.data.list.concat(data),
          hasMore: data.length >= 10,
          page: this.data.page + 1,
        });
      }
    } catch (e) {
      wx.showToast({ title: '加载失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadData();
    }
  },

  // 继续支付
  handlePay(e) {
    const id = e.currentTarget.dataset.id;
    app.wxPay(id).then(() => {
      wx.showToast({ title: '支付成功', icon: 'success' });
      this.setData({ page: 1, list: [], hasMore: true });
      this.loadData();
    }).catch(err => {
      wx.showToast({ title: err || '支付失败', icon: 'none' });
    });
  },

  goDetail(e) {
    const contentId = e.currentTarget.dataset.contentId;
    if (contentId) {
      wx.navigateTo({ url: `/pages/detail/detail?id=${contentId}` });
    }
  },
});

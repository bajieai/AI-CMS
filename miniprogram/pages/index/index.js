// pages/index/index.js
const api = require('../../utils/api.js');

Page({
  data: {
    list: [],
    page: 1,
    loading: false,
    hasMore: true,
    cateId: 0,
  },

  onLoad(options) {
    this.loadData();
  },

  onPullDownRefresh() {
    this.setData({ page: 1, list: [], hasMore: true });
    this.loadData().finally(() => {
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadData();
    }
  },

  async loadData() {
    this.setData({ loading: true });
    try {
      const res = await api.getContentList({
        page: this.data.page,
        limit: 10,
        cate_id: this.data.cateId,
      });
      if (res.code === 0) {
        const data = res.data || [];
        this.setData({
          list: this.data.page === 1 ? data : this.data.list.concat(data),
          hasMore: data.length >= 10,
          page: this.data.page + 1,
        });
      }
    } catch (e) {
      console.error('加载失败', e);
    } finally {
      this.setData({ loading: false });
    }
  },

  goDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/detail/detail?id=${id}` });
  },
});

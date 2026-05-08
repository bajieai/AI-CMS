// pages/category/category.js
const api = require('../../utils/api.js');

Page({
  data: {
    cates: [],
    currentCate: 0,
    list: [],
    page: 1,
    loading: false,
    hasMore: true,
  },

  onLoad() {
    this.loadCates();
  },

  async loadCates() {
    try {
      const res = await api.getCateList();
      if (res.code === 0) {
        const cates = [{ id: 0, name: '全部' }].concat(res.data || []);
        this.setData({ cates });
        this.loadData();
      }
    } catch (e) {
      wx.showToast({ title: '分类加载失败', icon: 'none' });
    }
  },

  switchCate(e) {
    const cateId = e.currentTarget.dataset.id;
    this.setData({
      currentCate: cateId,
      page: 1,
      list: [],
      hasMore: true,
    });
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
        cate_id: this.data.currentCate,
      });
      if (res.code === 0) {
        const data = res.data || [];
        const list = this.data.page === 1 ? data : this.data.list.concat(data);
        this.setData({
          list,
          page: this.data.page + 1,
          hasMore: data.length >= 10,
          loading: false,
        });
      } else {
        this.setData({ loading: false });
      }
    } catch (e) {
      this.setData({ loading: false });
      wx.showToast({ title: '加载失败', icon: 'none' });
    }
  },

  goDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: '/pages/detail/detail?id=' + id });
  },
});

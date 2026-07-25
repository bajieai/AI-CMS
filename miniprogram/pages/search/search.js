// pages/search/search.js
const api = require('../../utils/api.js');

Page({
  data: {
    keyword: '',
    list: [],
    page: 1,
    loading: false,
    hasMore: true,
  },

  onInput(e) {
    this.setData({ keyword: e.detail.value });
  },

  onSearch() {
    if (!this.data.keyword.trim()) return;
    this.setData({ page: 1, list: [], hasMore: true });
    this.loadData();
  },

  async loadData() {
    this.setData({ loading: true });
    try {
      const res = await api.search(this.data.keyword, { page: this.data.page, limit: 10 });
      if (res.code === 0) {
        const data = res.data || [];
        this.setData({
          list: this.data.page === 1 ? data : this.data.list.concat(data),
          hasMore: data.length >= 10,
          page: this.data.page + 1,
        });
      }
    } catch (e) {
      console.error('搜索失败', e);
    } finally {
      this.setData({ loading: false });
    }
  },

  goDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/detail/detail?id=${id}` });
  },
});

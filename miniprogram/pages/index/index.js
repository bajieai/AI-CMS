// pages/index/index.js - V2.9
const api = require('../../utils/api.js');
const app = getApp();

Page({
  data: {
    list: [],
    page: 1,
    loading: false,
    hasMore: true,
    cateId: 0,
    // V2.9: 新人券弹窗
    showNewbieCoupon: false,
    newbieCouponInfo: null,
  },

  onLoad(options) {
    this.loadData();
    // V2.9: 检查新人券弹窗
    this.checkNewbieCoupon();
  },

  onShow() {
    // 每次显示时再次检查（登录状态可能变化）
    this.checkNewbieCoupon();
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

  // V2.9: 新人券弹窗检查
  checkNewbieCoupon() {
    const globalData = app.globalData;
    if (globalData.showNewbieCoupon && !globalData.newbieCouponShown) {
      this.setData({ showNewbieCoupon: true });
      // 异步获取新人券详情
      api.getNewbieCoupon().then(res => {
        if (res.code === 0 && res.data) {
          this.setData({ newbieCouponInfo: res.data });
        }
      }).catch(() => {});
    }
  },

  // V2.9: 领取新人券
  async receiveNewbieCoupon() {
    if (!this.data.newbieCouponInfo) return;
    try {
      const res = await api.receiveCoupon(this.data.newbieCouponInfo.template_id || this.data.newbieCouponInfo.id);
      if (res.code === 0) {
        wx.showToast({ title: '领取成功', icon: 'success' });
      } else {
        wx.showToast({ title: res.msg || '领取失败', icon: 'none' });
      }
    } catch (e) {
      wx.showToast({ title: '领取失败', icon: 'none' });
    }
    this.closeNewbieCoupon();
  },

  // V2.9: 关闭新人券弹窗
  closeNewbieCoupon() {
    this.setData({ showNewbieCoupon: false });
    app.globalData.newbieCouponShown = true;
    app.globalData.showNewbieCoupon = false;
    wx.setStorageSync('newbie_coupon_shown', true);
  },

  goDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/detail/detail?id=${id}` });
  },
});

// pages/coupon/coupon.js - V2.9 优惠券页面
const api = require('../../utils/api.js');

Page({
  data: {
    tab: 'my', // my / list
    myCoupons: [],
    couponList: [],
    loading: false,
    page: 1,
    hasMore: true,
  },

  onShow() {
    this.setData({ page: 1, hasMore: true, myCoupons: [], couponList: [] });
    this.loadData();
  },

  switchTab(e) {
    const tab = e.currentTarget.dataset.tab;
    this.setData({ tab, page: 1, hasMore: true, myCoupons: [], couponList: [] });
    this.loadData();
  },

  async loadData() {
    if (this.data.tab === 'my') {
      await this.loadMyCoupons();
    } else {
      await this.loadCouponList();
    }
  },

  async loadMyCoupons() {
    this.setData({ loading: true });
    try {
      const res = await api.getMyCoupons({ page: this.data.page, limit: 20 });
      if (res.code === 0) {
        const data = res.data.list || [];
        this.setData({
          myCoupons: this.data.page === 1 ? data : this.data.myCoupons.concat(data),
          hasMore: data.length >= 20,
        });
      }
    } catch (e) {
      console.log('加载我的优惠券失败', e);
    } finally {
      this.setData({ loading: false });
    }
  },

  async loadCouponList() {
    this.setData({ loading: true });
    try {
      const res = await api.getCouponList({ page: this.data.page, limit: 20 });
      if (res.code === 0) {
        const data = res.data.list || [];
        this.setData({
          couponList: this.data.page === 1 ? data : this.data.couponList.concat(data),
          hasMore: data.length >= 20,
        });
      }
    } catch (e) {
      console.log('加载优惠券列表失败', e);
    } finally {
      this.setData({ loading: false });
    }
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.setData({ page: this.data.page + 1 });
      this.loadData();
    }
  },

  async receiveCoupon(e) {
    const id = e.currentTarget.dataset.id;
    try {
      const res = await api.receiveCoupon(id);
      if (res.code === 0) {
        wx.showToast({ title: '领取成功', icon: 'success' });
        this.setData({ tab: 'my', page: 1 });
        this.loadMyCoupons();
      } else {
        wx.showToast({ title: res.msg || '领取失败', icon: 'none' });
      }
    } catch (e) {
      wx.showToast({ title: '领取失败', icon: 'none' });
    }
  },
});

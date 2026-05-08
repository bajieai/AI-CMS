// pages/payment/payment.js - V2.9 支付页面
const api = require('../../utils/api.js');
const app = getApp();

Page({
  data: {
    contentId: 0,
    title: '',
    price: 0,
    loading: false,
    coupons: [],
    selectedCoupon: null,
    finalPrice: 0,
  },

  onLoad(options) {
    const contentId = parseInt(options.content_id || 0);
    const title = decodeURIComponent(options.title || '');
    const price = parseFloat(options.price || 0);
    this.setData({ contentId, title, price, finalPrice: price });
    this.loadCoupons();
  },

  async loadCoupons() {
    try {
      const res = await api.getMyCoupons({ status: 0, usable: 1 });
      if (res.code === 0) {
        this.setData({ coupons: res.data.list || [] });
      }
    } catch (e) {
      console.log('优惠券加载失败', e);
    }
  },

  // 选择优惠券
  selectCoupon(e) {
    const index = e.currentTarget.dataset.index;
    const coupon = this.data.coupons[index];
    if (!coupon) return;
    const selected = this.data.selectedCoupon;
    if (selected && selected.id === coupon.id) {
      // 取消选择
      this.setData({ selectedCoupon: null, finalPrice: this.data.price });
    } else {
      // 计算最终价格
      let final = this.data.price;
      if (coupon.coupon_type === 'reduce') {
        final = Math.max(0, final - parseFloat(coupon.reduce_amount));
      } else if (coupon.coupon_type === 'discount') {
        final = Math.max(0, final * parseFloat(coupon.reduce_amount));
      }
      this.setData({ selectedCoupon: coupon, finalPrice: final.toFixed(2) });
    }
  },

  // 创建订单并支付
  async handlePay() {
    if (this.data.loading) return;
    this.setData({ loading: true });
    try {
      const orderRes = await api.createOrder({
        content_id: this.data.contentId,
        coupon_id: this.data.selectedCoupon ? this.data.selectedCoupon.id : 0,
      });
      if (orderRes.code !== 0) {
        wx.showToast({ title: orderRes.msg || '创建订单失败', icon: 'none' });
        this.setData({ loading: false });
        return;
      }
      const orderId = orderRes.data.order_id;
      // 发起微信支付
      await app.wxPay(orderId);
      wx.showToast({ title: '支付成功', icon: 'success' });
      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
    } catch (e) {
      wx.showToast({ title: '支付失败: ' + (e || ''), icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },
});

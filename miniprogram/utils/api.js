// utils/api.js - API请求封装 V2.9 完整版
const app = () => getApp();

const request = (url, method = 'GET', data = {}, header = {}) => {
  return app().request({ url, method, data, header });
};

module.exports = {
  // ==================== 内容 ====================
  getContentList(params = {}) {
    return request('/content', 'GET', params);
  },
  getContentDetail(id, memberId = 0) {
    return request(`/content/${id}`, 'GET', { member_id: memberId });
  },
  getCateList() {
    return request('/cate', 'GET');
  },
  search(keyword, params = {}) {
    return request('/search', 'GET', { keyword, ...params });
  },

  // ==================== 会员 ====================
  login(data) {
    return request('/member/login', 'POST', data);
  },
  getMemberInfo() {
    return request('/member/info', 'GET');
  },
  wxLogin(code) {
    return request('/auth/wxLogin', 'POST', { code });
  },

  // ==================== 签到 ====================
  signin() {
    return request('/member/signin', 'POST');
  },
  hasSignedToday() {
    return request('/member/hasSignedToday', 'GET');
  },
  getSigninRecords(params = {}) {
    return request('/member/signinRecords', 'GET', params);
  },

  // ==================== 优惠券 ====================
  getCouponList(params = {}) {
    return request('/coupon', 'GET', params);
  },
  getMyCoupons(params = {}) {
    return request('/coupon/my', 'GET', params);
  },
  receiveCoupon(templateId) {
    return request('/coupon/receive', 'POST', { template_id: templateId });
  },
  getNewbieCoupon() {
    return request('/coupon/newbie', 'GET');
  },

  // ==================== 支付/订单 ====================
  createOrder(data) {
    return request('/order/create', 'POST', data);
  },
  getOrderList(params = {}) {
    return request('/order', 'GET', params);
  },
  getOrderDetail(id) {
    return request(`/order/${id}`, 'GET');
  },
  wxPay(orderId) {
    return request('/payment/wxPay', 'POST', { order_id: orderId });
  },

  // ==================== 邀请 ====================
  getInviteInfo() {
    return request('/invite/info', 'GET');
  },
  getInviteRecords(params = {}) {
    return request('/invite/records', 'GET', params);
  },

  // ==================== 评价 ====================
  getRatings(contentId, params = {}) {
    return request(`/rating/${contentId}`, 'GET', params);
  },
  submitRating(data) {
    return request('/rating', 'POST', data);
  },

  // ==================== 多语言 ====================
  getLanguages() {
    return request('/language', 'GET');
  },
  switchLanguage(lang) {
    return request('/language/switch', 'POST', { lang });
  },
};

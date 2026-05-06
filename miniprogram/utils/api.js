// utils/api.js - API请求封装
const app = getApp();

const request = (url, method = 'GET', data = {}, header = {}) => {
  return app.request({ url, method, data, header });
};

module.exports = {
  // 内容列表
  getContentList(params = {}) {
    return request('/content', 'GET', params);
  },

  // 内容详情
  getContentDetail(id, memberId = 0) {
    return request(`/content/${id}`, 'GET', { member_id: memberId });
  },

  // 搜索
  search(keyword, params = {}) {
    return request('/search', 'GET', { keyword, ...params });
  },

  // 会员登录（通过API Token模式，实际生产建议OAuth2）
  login(data) {
    return request('/member/login', 'POST', data);
  },

  // 获取会员信息
  getMemberInfo() {
    return request('/member/info', 'GET');
  },
};

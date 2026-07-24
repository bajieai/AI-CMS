import request from './request'

export default {
  // 站点配置
  site() {
    return request.get('/config/site')
  },
  // 主题配置
  theme() {
    return request.get('/config/theme')
  },
  // PWA配置
  pwa() {
    return request.get('/config/pwa')
  },
}

import request from './request'

export default {
  // 首页聚合接口
  getHome() {
    return request.get('/home/index')
  },
  // 内容列表
  getContentList(params: { page?: number; limit?: number; category_id?: number; tag_id?: number }) {
    return request.get('/content/list', { params })
  },
  // 内容详情
  getContentDetail(id: number) {
    return request.get('/content/detail', { params: { id } })
  },
  // 搜索
  search(params: { keyword: string; page?: number; limit?: number }) {
    return request.get('/search/search', { params })
  },
  // 热门搜索
  hotSearch() {
    return request.get('/search/hot')
  },
  // 搜索建议
  suggest(keyword: string) {
    return request.get('/search/suggest', { params: { keyword } })
  },
}

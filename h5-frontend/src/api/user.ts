import request from './request'

export interface UserProfile {
  id: number
  username: string
  nickname: string
  avatar: string
  email: string
  phone: string
  created_at: string
}

export interface OrderItem {
  id: number
  order_no: string
  amount: number
  status: string
  status_text: string
  created_at: string
  title: string
}

export interface FavoriteItem {
  id: number
  content_id: number
  title: string
  cover: string
  description: string
  created_at: string
}

export interface CommentItem {
  id: number
  content_id: number
  content_title: string
  content: string
  created_at: string
}

export interface NotificationItem {
  id: number
  type: string
  title: string
  message: string
  is_read: boolean
  created_at: string
}

export interface MembershipInfo {
  level: string
  level_name: string
  points: number
  expire_at: string
  benefits: string[]
}

export interface PageParams {
  page?: number
  limit?: number
}

export default {
  // 登录
  login(data: { username: string; password: string }) {
    return request.post('/user/login', data)
  },
  // 用户信息
  info() {
    return request.get('/user/info')
  },
  // 获取个人资料
  getProfile() {
    return request.get('/user/profile')
  },
  // 更新个人资料
  updateProfile(data: Partial<UserProfile>) {
    return request.put('/user/profile', data)
  },
  // 上传头像
  uploadAvatar(file: File) {
    const formData = new FormData()
    formData.append('avatar', file)
    return request.post('/user/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  // 获取订单列表
  getOrders(params: PageParams) {
    return request.get('/user/orders', { params })
  },
  // 获取收藏列表
  getFavorites(params: PageParams) {
    return request.get('/user/favorites', { params })
  },
  // 取消收藏
  removeFavorite(id: number) {
    return request.delete(`/user/favorites/${id}`)
  },
  // 获取我的评论
  getComments(params: PageParams) {
    return request.get('/user/comments', { params })
  },
  // 获取消息通知
  getNotifications(params: PageParams) {
    return request.get('/user/notifications', { params })
  },
  // 标记通知已读
  markRead(ids: number[]) {
    return request.post('/user/notifications/read', { ids })
  },
  // 标记全部已读
  markAllRead() {
    return request.post('/user/notifications/read-all')
  },
  // 获取会员信息
  getMembership() {
    return request.get('/user/membership')
  },
}

import request from './request'

export interface CommentParams {
  page?: number
  limit?: number
  sort?: 'newest' | 'hottest'
}

export interface CommentData {
  id: number
  user_id: number
  user_avatar: string
  user_nickname: string
  content: string
  like_count: number
  is_liked: boolean
  created_at: string
  replies?: CommentData[]
}

export interface PostCommentData {
  content_id: number
  content: string
  parent_id?: number
}

export default {
  // 获取评论列表
  getComments(contentId: number, params: CommentParams) {
    return request.get('/comment/list', { params: { content_id: contentId, ...params } })
  },
  // 发表评论
  postComment(data: PostCommentData) {
    return request.post('/comment/post', data)
  },
  // 回复评论
  replyComment(id: number, data: Omit<PostCommentData, 'parent_id'>) {
    return request.post(`/comment/${id}/reply`, data)
  },
  // 点赞评论
  likeComment(id: number) {
    return request.post(`/comment/${id}/like`)
  },
  // 删除评论
  deleteComment(id: number) {
    return request.delete(`/comment/${id}`)
  },
}

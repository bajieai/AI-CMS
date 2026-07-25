import request from './request'

export interface ShareLogData {
  content_id: number
  platform: string
  title?: string
}

export interface ShareStats {
  total_shares: number
  platform_stats: Record<string, number>
  trend: { date: string; count: number }[]
}

export default {
  // 记录分享行为
  logShare(data: ShareLogData) {
    return request.post('/share/log', data)
  },
  // 获取分享统计
  getShareStats(contentId: number) {
    return request.get('/share/stats', { params: { content_id: contentId } })
  },
}

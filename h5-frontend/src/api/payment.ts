import request from './request'

export default {
  // 创建支付订单
  create(data: { type: string; target_id?: number; amount: number; payment_method: string }) {
    return request.post('/payment/create', data)
  },
  // 支付回调
  callback(order_no: string) {
    return request.get('/payment/callback', { params: { order_no } })
  },
}

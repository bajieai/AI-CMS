<template>
  <div class="member-center">
    <van-nav-bar title="会员中心" left-arrow @click-left="$router.back()" />
    <div class="vip-card">
      <h2>{{ level }}</h2>
      <p>会员到期: {{ expireTime || '永久' }}</p>
    </div>
    <van-cell-group>
      <van-cell title="会员权益" is-link />
      <van-cell title="充值记录" is-link />
      <van-cell title="积分明细" is-link />
    </van-cell-group>
    <div class="actions">
      <van-button type="primary" block @click="payVip">立即续费</van-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { showToast } from 'vant'
import paymentApi from '@/api/payment'

const level = ref('普通会员')
const expireTime = ref('')

async function payVip() {
  try {
    const res = await paymentApi.create({ type: 'member', amount: 99, payment_method: 'wechat' })
    showToast('订单已创建: ' + res.data.order_no)
  } catch (e: any) { showToast(e.message || '创建订单失败') }
}
</script>

<style scoped lang="scss">
.member-center { min-height: 100vh; background: #f7f8fa; }
.vip-card { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 24px; margin-bottom: 8px;
  h2 { font-size: 24px; } p { font-size: 14px; opacity: 0.9; margin-top: 8px; }
}
.actions { padding: 20px; }
</style>

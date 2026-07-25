<template>
  <div class="orders-page">
    <van-nav-bar title="我的订单" left-arrow @click-left="$router.back()" />

    <van-list
      v-model:loading="loading"
      :finished="finished"
      finished-text="没有更多了"
      @load="onLoad"
    >
      <van-cell
        v-for="order in list"
        :key="order.id"
        class="order-cell"
      >
        <template #title>
          <div class="order-header">
            <span class="order-no">订单号: {{ order.order_no }}</span>
            <van-tag :type="getStatusType(order.status)">{{ order.status_text }}</van-tag>
          </div>
          <div class="order-body">
            <span class="order-title">{{ order.title }}</span>
            <span class="order-amount">¥{{ order.amount.toFixed(2) }}</span>
          </div>
          <div class="order-time">{{ order.created_at }}</div>
        </template>
      </van-cell>
    </van-list>

    <van-empty v-if="!loading && list.length === 0" description="暂无订单" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import userApi, { type OrderItem } from '@/api/user'

const list = ref<OrderItem[]>([])
const loading = ref(false)
const finished = ref(false)
const page = ref(1)
const pageSize = 20

async function onLoad() {
  loading.value = true
  try {
    const res: any = await userApi.getOrders({ page: page.value, limit: pageSize })
    const items = res.data?.list || res.data || []
    list.value.push(...items)
    if (items.length < pageSize) {
      finished.value = true
    } else {
      page.value++
    }
  } catch (e) {
    console.error('加载订单失败:', e)
    finished.value = true
  } finally {
    loading.value = false
  }
}

function getStatusType(status: string): 'primary' | 'success' | 'warning' | 'danger' {
  const map: Record<string, 'primary' | 'success' | 'warning' | 'danger'> = {
    pending: 'warning',
    paid: 'success',
    cancelled: 'danger',
    refunded: 'danger',
  }
  return map[status] || 'primary'
}
</script>

<style scoped lang="scss">
.orders-page {
  min-height: 100vh;
  background: $background-color;
}
.order-cell {
  margin-bottom: 8px;
}
.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  .order-no {
    font-size: $font-size-sm;
    color: $text-secondary;
  }
}
.order-body {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
  .order-title {
    font-size: $font-size-md;
    color: $text-color;
  }
  .order-amount {
    font-size: $font-size-lg;
    color: $danger-color;
    font-weight: 600;
  }
}
.order-time {
  font-size: $font-size-sm;
  color: $text-secondary;
}
</style>

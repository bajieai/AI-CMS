<template>
  <div class="notifications-page">
    <van-nav-bar title="消息通知" left-arrow @click-left="$router.back()">
      <template #right>
        <span class="read-all-btn" @click="onMarkAllRead" v-if="list.length > 0">全部已读</span>
      </template>
    </van-nav-bar>

    <van-list
      v-model:loading="loading"
      :finished="finished"
      finished-text="没有更多了"
      @load="onLoad"
    >
      <van-cell
        v-for="item in list"
        :key="item.id"
        class="notif-cell"
        :class="{ unread: !item.is_read }"
        @click="onTapItem(item)"
      >
        <template #icon>
          <van-badge :dot="!item.is_read" class="notif-badge">
            <van-icon :name="getIcon(item.type)" size="24" :color="getIconColor(item.type)" />
          </van-badge>
        </template>
        <template #title>
          <div class="notif-title">{{ item.title }}</div>
          <div class="notif-message">{{ item.message }}</div>
          <div class="notif-time">{{ item.created_at }}</div>
        </template>
      </van-cell>
    </van-list>

    <van-empty v-if="!loading && list.length === 0" description="暂无通知" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { showSuccessToast } from 'vant'
import userApi, { type NotificationItem } from '@/api/user'

const list = ref<NotificationItem[]>([])
const loading = ref(false)
const finished = ref(false)
const page = ref(1)
const pageSize = 20

async function onLoad() {
  loading.value = true
  try {
    const res: any = await userApi.getNotifications({ page: page.value, limit: pageSize })
    const items = res.data?.list || res.data || []
    list.value.push(...items)
    if (items.length < pageSize) {
      finished.value = true
    } else {
      page.value++
    }
  } catch (e) {
    console.error('加载通知失败:', e)
    finished.value = true
  } finally {
    loading.value = false
  }
}

async function onTapItem(item: NotificationItem) {
  if (!item.is_read) {
    try {
      await userApi.markRead([item.id])
      item.is_read = true
    } catch (e) {
      console.error('标记已读失败:', e)
    }
  }
}

async function onMarkAllRead() {
  try {
    await userApi.markAllRead()
    list.value.forEach((item) => (item.is_read = true))
    showSuccessToast('已全部标记为已读')
  } catch (e) {
    console.error('标记全部已读失败:', e)
  }
}

function getIcon(type: string): string {
  const map: Record<string, string> = {
    system: 'info-o',
    comment: 'chat-o',
    like: 'like-o',
    order: 'orders-o',
    default: 'bell',
  }
  return map[type] || map.default
}

function getIconColor(type: string): string {
  const map: Record<string, string> = {
    system: '#1989fa',
    comment: '#07c160',
    like: '#ee0a24',
    order: '#ff976a',
    default: '#1989fa',
  }
  return map[type] || map.default
}
</script>

<style scoped lang="scss">
.notifications-page {
  min-height: 100vh;
  background: $background-color;
}
.read-all-btn {
  font-size: $font-size-sm;
  color: $primary-color;
}
.notif-cell {
  margin-bottom: 1px;
  align-items: flex-start;
  &.unread {
    background: #f0f9ff;
  }
}
.notif-badge {
  margin-right: 12px;
  margin-top: 2px;
}
.notif-title {
  font-size: $font-size-md;
  color: $text-color;
  font-weight: 500;
  margin-bottom: 4px;
}
.notif-message {
  font-size: $font-size-sm;
  color: $text-secondary;
  line-height: 1.5;
  margin-bottom: 4px;
}
.notif-time {
  font-size: $font-size-sm;
  color: $text-secondary;
}
</style>

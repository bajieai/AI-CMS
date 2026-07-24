<template>
  <div class="favorites-page">
    <van-nav-bar title="我的收藏" left-arrow @click-left="$router.back()" />

    <van-list
      v-model:loading="loading"
      :finished="finished"
      finished-text="没有更多了"
      @load="onLoad"
    >
      <van-swipe-cell v-for="item in list" :key="item.id">
        <van-card
          :title="item.title"
          :desc="item.description"
          :thumb="item.cover"
          @click="goToDetail(item.content_id)"
        >
          <template #footer>
            <span class="fav-time">{{ item.created_at }}</span>
          </template>
        </van-card>
        <template #right>
          <van-button
            square
            type="danger"
            text="取消收藏"
            class="swipe-delete-btn"
            @click="onRemove(item.id)"
          />
        </template>
      </van-swipe-cell>
    </van-list>

    <van-empty v-if="!loading && list.length === 0" description="暂无收藏" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { showConfirmDialog, showSuccessToast } from 'vant'
import userApi, { type FavoriteItem } from '@/api/user'

const router = useRouter()
const list = ref<FavoriteItem[]>([])
const loading = ref(false)
const finished = ref(false)
const page = ref(1)
const pageSize = 20

async function onLoad() {
  loading.value = true
  try {
    const res: any = await userApi.getFavorites({ page: page.value, limit: pageSize })
    const items = res.data?.list || res.data || []
    list.value.push(...items)
    if (items.length < pageSize) {
      finished.value = true
    } else {
      page.value++
    }
  } catch (e) {
    console.error('加载收藏失败:', e)
    finished.value = true
  } finally {
    loading.value = false
  }
}

async function onRemove(id: number) {
  try {
    await showConfirmDialog({ title: '提示', message: '确定取消收藏？' })
    await userApi.removeFavorite(id)
    list.value = list.value.filter((item) => item.id !== id)
    showSuccessToast('已取消收藏')
  } catch (e) {
    // 用户取消
  }
}

function goToDetail(contentId: number) {
  router.push(`/content/detail/${contentId}`)
}
</script>

<style scoped lang="scss">
.favorites-page {
  min-height: 100vh;
  background: $background-color;
}
.swipe-delete-btn {
  height: 100%;
}
.fav-time {
  font-size: $font-size-sm;
  color: $text-secondary;
}
</style>

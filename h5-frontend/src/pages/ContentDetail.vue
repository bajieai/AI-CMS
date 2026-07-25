<template>
  <div class="content-detail" v-if="detail">
    <van-nav-bar title="详情" left-arrow @click-left="$router.back()" />
    <div class="detail-header">
      <h1 class="detail-title">{{ detail.title }}</h1>
      <div class="detail-meta">
        <span>{{ detail.author || '佚名' }}</span>
        <span>{{ detail.create_time }}</span>
        <span>{{ detail.view_count }} 阅读</span>
      </div>
    </div>
    <div class="detail-content" v-html="detail.content"></div>
    <div class="detail-footer">
      <van-button v-if="prev" type="default" size="small" @click="$router.push(`/content/detail/${prev.id}`)">上一篇</van-button>
      <van-button v-if="next" type="default" size="small" @click="$router.push(`/content/detail/${next.id}`)">下一篇</van-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import contentApi from '@/api/content'

const route = useRoute()
const detail = ref<any>(null)
const prev = ref<any>(null)
const next = ref<any>(null)

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id) return
  try {
    const res = await contentApi.getContentDetail(id)
    detail.value = res.data.detail
    prev.value = res.data.prev
    next.value = res.data.next
  } catch (e) {
    console.error('加载详情失败:', e)
  }
})
</script>

<style scoped lang="scss">
.content-detail { background: #fff; min-height: 100vh; }
.detail-header { padding: 16px; border-bottom: 1px solid #ebedf0; }
.detail-title { font-size: 20px; font-weight: 600; margin-bottom: 8px; }
.detail-meta { font-size: 12px; color: #969799; display: flex; gap: 12px; }
.detail-content { padding: 16px; font-size: 15px; line-height: 1.8; }
.detail-content :deep(img) { max-width: 100%; height: auto; border-radius: 6px; margin: 8px 0; }
.detail-footer { display: flex; gap: 8px; padding: 16px; border-top: 1px solid #ebedf0; }
</style>

<template>
  <div class="comments-page">
    <van-nav-bar title="我的评论" left-arrow @click-left="$router.back()" />

    <van-list
      v-model:loading="loading"
      :finished="finished"
      finished-text="没有更多了"
      @load="onLoad"
    >
      <van-cell
        v-for="comment in list"
        :key="comment.id"
        class="comment-cell"
        @click="goToDetail(comment.content_id)"
      >
        <template #title>
          <div class="comment-content">{{ comment.content }}</div>
          <div class="comment-meta">
            <span class="comment-article">{{ comment.content_title }}</span>
            <span class="comment-time">{{ comment.created_at }}</span>
          </div>
        </template>
      </van-cell>
    </van-list>

    <van-empty v-if="!loading && list.length === 0" description="暂无评论" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import userApi, { type CommentItem } from '@/api/user'

const router = useRouter()
const list = ref<CommentItem[]>([])
const loading = ref(false)
const finished = ref(false)
const page = ref(1)
const pageSize = 20

async function onLoad() {
  loading.value = true
  try {
    const res: any = await userApi.getComments({ page: page.value, limit: pageSize })
    const items = res.data?.list || res.data || []
    list.value.push(...items)
    if (items.length < pageSize) {
      finished.value = true
    } else {
      page.value++
    }
  } catch (e) {
    console.error('加载评论失败:', e)
    finished.value = true
  } finally {
    loading.value = false
  }
}

function goToDetail(contentId: number) {
  router.push(`/content/detail/${contentId}`)
}
</script>

<style scoped lang="scss">
.comments-page {
  min-height: 100vh;
  background: $background-color;
}
.comment-cell {
  margin-bottom: 8px;
}
.comment-content {
  font-size: $font-size-md;
  color: $text-color;
  line-height: 1.6;
  margin-bottom: 8px;
}
.comment-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  .comment-article {
    font-size: $font-size-sm;
    color: $primary-color;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 60%;
  }
  .comment-time {
    font-size: $font-size-sm;
    color: $text-secondary;
  }
}
</style>

<template>
  <div class="comment-list">
    <van-tabs v-model:active="activeTab" @change="onTabChange">
      <van-tab title="最新" name="newest" />
      <van-tab title="最热" name="hottest" />
    </van-tabs>

    <van-list
      v-model:loading="loading"
      :finished="finished"
      finished-text="没有更多评论了"
      @load="onLoad"
    >
      <comment-item
        v-for="comment in list"
        :key="comment.id"
        :comment="comment"
        @reply="onReply"
        @like="onLike"
      />
    </van-list>

    <van-empty v-if="!loading && list.length === 0" description="暂无评论，快来抢沙发吧" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import CommentItem from './CommentItem.vue'
import commentApi, { type CommentData, type CommentParams } from '@/api/comment'

const props = defineProps<{
  contentId: number
}>()

const emit = defineEmits<{
  reply: [comment: CommentData]
}>()

const activeTab = ref<'newest' | 'hottest'>('newest')
const list = ref<CommentData[]>([])
const loading = ref(false)
const finished = ref(false)
const page = ref(1)
const pageSize = 10

async function onLoad() {
  loading.value = true
  try {
    const params: CommentParams = {
      page: page.value,
      limit: pageSize,
      sort: activeTab.value,
    }
    const res: any = await commentApi.getComments(props.contentId, params)
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

function onTabChange() {
  list.value = []
  page.value = 1
  finished.value = false
  onLoad()
}

function onReply(comment: CommentData) {
  emit('reply', comment)
}

async function onLike(comment: CommentData) {
  try {
    await commentApi.likeComment(comment.id)
    comment.is_liked = !comment.is_liked
    comment.like_count += comment.is_liked ? 1 : -1
  } catch (e) {
    console.error('点赞失败:', e)
  }
}

defineExpose({
  refresh: () => {
    list.value = []
    page.value = 1
    finished.value = false
    onLoad()
  },
})
</script>

<style scoped lang="scss">
.comment-list {
  background: #fff;
  min-height: 200px;
}
</style>

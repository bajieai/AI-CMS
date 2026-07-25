<template>
  <div class="comment-item">
    <van-image round width="36" height="36" :src="comment.user_avatar" />
    <div class="comment-body">
      <div class="comment-header">
        <span class="user-name">{{ comment.user_nickname }}</span>
        <span class="comment-time">{{ comment.created_at }}</span>
      </div>
      <div class="comment-text">{{ comment.content }}</div>
      <div class="comment-actions">
        <span class="action-btn" @click="onLike">
          <van-icon :name="comment.is_liked ? 'good-job' : 'good-job-o'" size="16" />
          {{ comment.like_count }}
        </span>
        <span class="action-btn" @click="onReplyClick">回复</span>
      </div>

      <!-- 嵌套回复 -->
      <div class="replies" v-if="comment.replies && comment.replies.length > 0">
        <comment-item
          v-for="reply in comment.replies"
          :key="reply.id"
          :comment="reply"
          @reply="onReply"
          @like="onLike"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CommentData } from '@/api/comment'

const props = defineProps<{
  comment: CommentData
}>()

const emit = defineEmits<{
  reply: [comment: CommentData]
  like: [comment: CommentData]
}>()

function onReplyClick() {
  emit('reply', props.comment)
}

function onLike() {
  emit('like', props.comment)
}
</script>

<style scoped lang="scss">
.comment-item {
  display: flex;
  gap: 10px;
  padding: 12px 0;
  border-bottom: 1px solid $border-color;

  &:last-child {
    border-bottom: none;
  }
}
.comment-body {
  flex: 1;
  min-width: 0;
}
.comment-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
  .user-name {
    font-size: $font-size-sm;
    font-weight: 500;
    color: $text-color;
  }
  .comment-time {
    font-size: 11px;
    color: $text-secondary;
  }
}
.comment-text {
  font-size: $font-size-md;
  line-height: 1.6;
  color: $text-color;
  word-break: break-word;
}
.comment-actions {
  display: flex;
  gap: 16px;
  margin-top: 6px;
  .action-btn {
    font-size: $font-size-sm;
    color: $text-secondary;
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
  }
}
.replies {
  margin-top: 8px;
  padding-left: 8px;
  border-left: 2px solid $border-color;
}
</style>

<template>
  <div class="comment-form">
    <van-field
      v-model="content"
      type="textarea"
      :placeholder="placeholder"
      rows="3"
      autosize
      maxlength="500"
      show-word-limit
      class="comment-textarea"
    />
    <div class="form-actions">
      <van-icon name="smile-o" size="24" @click="showEmoji = !showEmoji" />
      <van-button
        type="primary"
        size="small"
        :loading="submitting"
        :disabled="!content.trim()"
        @click="onSubmit"
      >
        发表
      </van-button>
    </div>

    <emoji-picker
      v-model:show="showEmoji"
      @select="onEmojiSelect"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { showToast, showSuccessToast } from 'vant'
import EmojiPicker from './EmojiPicker.vue'
import commentApi from '@/api/comment'

const props = defineProps<{
  contentId: number
  parentId?: number
}>()

const emit = defineEmits<{
  submitted: []
}>()

const content = ref('')
const submitting = ref(false)
const showEmoji = ref(false)

const placeholder = computed(() => {
  return props.parentId ? '回复评论...' : '写下你的评论...'
})

async function onSubmit() {
  if (!content.value.trim()) {
    showToast('请输入评论内容')
    return
  }
  submitting.value = true
  try {
    if (props.parentId) {
      await commentApi.replyComment(props.parentId, {
        content_id: props.contentId,
        content: content.value,
      })
    } else {
      await commentApi.postComment({
        content_id: props.contentId,
        content: content.value,
      })
    }
    content.value = ''
    showSuccessToast('评论成功')
    emit('submitted')
  } catch (e) {
    console.error('评论失败:', e)
    showToast('评论失败')
  } finally {
    submitting.value = false
  }
}

function onEmojiSelect(emoji: string) {
  content.value += emoji
}
</script>

<style scoped lang="scss">
.comment-form {
  background: #fff;
  padding: 12px;
  border-top: 1px solid $border-color;
}
.comment-textarea {
  background: $background-color;
  border-radius: $radius-md;
  padding: 8px;
}
.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 8px;
}
</style>

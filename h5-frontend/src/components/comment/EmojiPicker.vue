<template>
  <van-popup v-model:show="showPopup" position="bottom" round @close="onClose">
    <div class="emoji-picker">
      <div class="picker-header">
        <span>选择表情</span>
        <van-icon name="cross" @click="onClose" />
      </div>
      <van-grid :column-num="7" :gutter="4" :border="false">
        <van-grid-item
          v-for="(emoji, index) in emojis"
          :key="index"
          @click="onSelect(emoji)"
        >
          <span class="emoji-text">{{ emoji }}</span>
        </van-grid-item>
      </van-grid>
    </div>
  </van-popup>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  'update:show': [val: boolean]
  select: [emoji: string]
}>()

const showPopup = computed({
  get: () => props.show,
  set: (val) => emit('update:show', val),
})

const emojis = [
  '😀', '😁', '😂', '🤣', '😊', '😍', '🥰',
  '😎', '🤔', '😴', '😭', '😡', '🤯', '😱',
  '👍', '👎', '👏', '🙏', '💪', '🤝', '✌️',
  '❤️', '💔', '🔥', '💯', '🎉', '🎁', '🌟',
  '☕', '🍺', '🍕', '🍔', '🍟', '🍜', '🍰',
]

function onSelect(emoji: string) {
  emit('select', emoji)
}

function onClose() {
  emit('update:show', false)
}
</script>

<style scoped lang="scss">
.emoji-picker {
  padding: 16px;
  max-height: 50vh;
  overflow-y: auto;
}
.picker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-size: $font-size-lg;
  font-weight: 600;
}
.emoji-text {
  font-size: 24px;
}
</style>

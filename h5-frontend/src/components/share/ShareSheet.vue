<template>
  <van-action-sheet
    v-model:show="showSheet"
    :actions="actions"
    cancel-text="取消"
    close-on-click-action
    @select="onSelect"
  >
    <template #description>
      <div class="sheet-title">{{ title }}</div>
    </template>
  </van-action-sheet>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { showToast } from 'vant'
import { useShare, type ShareContent } from '@/composables/useShare'

const props = defineProps<{
  visible: boolean
  contentId: number
  title: string
  image?: string
  url?: string
}>()

const emit = defineEmits<{
  'update:visible': [val: boolean]
  'showPoster': [content: ShareContent]
}>()

const { shareTo, copyLink } = useShare()

const showSheet = computed({
  get: () => props.visible,
  set: (val) => emit('update:visible', val),
})

const actions = [
  { name: '微信', icon: 'wechat', color: '#07c160' },
  { name: 'QQ', icon: 'qq', color: '#12b7f5' },
  { name: '微博', icon: 'weibo', color: '#e6162d' },
  { name: '复制链接', icon: 'link-o' },
  { name: '二维码', icon: 'qr' },
  { name: '生成海报', icon: 'poster' },
]

async function onSelect(action: { name: string }) {
  const content: ShareContent = {
    id: props.contentId,
    title: props.title,
    image: props.image,
    url: props.url || window.location.href,
  }

  const platformMap: Record<string, string> = {
    '微信': 'wechat',
    'QQ': 'qq',
    '微博': 'weibo',
    '复制链接': 'copy',
  }

  if (action.name === '二维码') {
    // 简单弹窗显示二维码URL
    showToast('请使用浏览器扫码功能')
  } else if (action.name === '生成海报') {
    emit('showPoster', content)
  } else {
    const platform = platformMap[action.name]
    if (platform) {
      await shareTo(platform, content)
    }
  }
}
</script>

<style scoped lang="scss">
.sheet-title {
  text-align: center;
  padding: 12px;
  font-size: $font-size-md;
  color: $text-secondary;
}
</style>

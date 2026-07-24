<template>
  <div class="read-mode" :class="themeClass">
    <!-- 阅读进度条 -->
    <div class="progress-bar-wrapper">
      <div class="progress-bar" :style="{ width: progress + '%' }"></div>
    </div>

    <!-- 阅读内容 -->
    <div class="read-content" :style="{ fontSize: fontSizeValue + 'px' }" v-html="content"></div>

    <!-- 设置面板 -->
    <van-popup v-model:show="showSettings" position="bottom" round :style="{ height: '40%' }">
      <div class="settings-panel">
        <div class="settings-header">阅读设置</div>

        <div class="setting-group">
          <div class="setting-label">字体大小</div>
          <div class="font-size-buttons">
            <van-button
              v-for="opt in fontSizeOptions"
              :key="opt.value"
              :type="fontSize === opt.value ? 'primary' : 'default'"
              size="small"
              @click="setFontSize(opt.value)"
            >
              {{ opt.label }}
            </van-button>
          </div>
        </div>

        <div class="setting-group">
          <div class="setting-label">阅读主题</div>
          <div class="theme-buttons">
            <van-button
              v-for="opt in themeOptions"
              :key="opt.value"
              :type="theme === opt.value ? 'primary' : 'default'"
              size="small"
              @click="setTheme(opt.value)"
            >
              {{ opt.label }}
            </van-button>
          </div>
        </div>
      </div>
    </van-popup>

    <!-- 悬浮按钮 -->
    <div class="float-btn" @click="showSettings = true">
      <van-icon name="setting-o" size="20" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  content: string
}>()

type FontSize = 'small' | 'medium' | 'large'
type Theme = 'light' | 'dark' | 'sepia'

const showSettings = ref(false)
const fontSize = ref<FontSize>('medium')
const theme = ref<Theme>('light')
const progress = ref(0)

const fontSizeOptions = [
  { label: '小', value: 'small' as FontSize },
  { label: '中', value: 'medium' as FontSize },
  { label: '大', value: 'large' as FontSize },
]

const themeOptions = [
  { label: '日间', value: 'light' as Theme },
  { label: '夜间', value: 'dark' as Theme },
  { label: '护眼', value: 'sepia' as Theme },
]

const fontSizeValue = computed(() => {
  const map: Record<FontSize, number> = { small: 14, medium: 16, large: 18 }
  return map[fontSize.value]
})

const themeClass = computed(() => `theme-${theme.value}`)

function setFontSize(size: FontSize) {
  fontSize.value = size
  localStorage.setItem('read_font_size', size)
}

function setTheme(t: Theme) {
  theme.value = t
  localStorage.setItem('read_theme', t)
}

let scrollTimer: ReturnType<typeof setTimeout> | null = null

function handleScroll() {
  if (scrollTimer) clearTimeout(scrollTimer)
  scrollTimer = setTimeout(() => {
    const scrollTop = window.scrollY
    const scrollHeight = document.documentElement.scrollHeight - window.innerHeight
    if (scrollHeight > 0) {
      progress.value = Math.min(100, Math.round((scrollTop / scrollHeight) * 100))
    }
  }, 200)
}

onMounted(() => {
  // 恢复设置
  const savedFont = localStorage.getItem('read_font_size') as FontSize | null
  const savedTheme = localStorage.getItem('read_theme') as Theme | null
  if (savedFont) fontSize.value = savedFont
  if (savedTheme) theme.value = savedTheme

  window.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  if (scrollTimer) clearTimeout(scrollTimer)
})
</script>

<style scoped lang="scss">
.read-mode {
  position: relative;
  min-height: 100vh;
  transition: background 0.3s, color 0.3s;

  &.theme-light {
    background: #fff;
    color: $text-color;
  }
  &.theme-dark {
    background: #1a1a1a;
    color: #ccc;
    :deep(a) { color: #4a9eff; }
  }
  &.theme-sepia {
    background: #f4ecd8;
    color: #5f4b32;
    :deep(a) { color: #8b6914; }
  }
}
.progress-bar-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: rgba(0, 0, 0, 0.1);
  z-index: 100;
  .progress-bar {
    height: 100%;
    background: $primary-color;
    transition: width 0.2s;
  }
}
.read-content {
  padding: 16px;
  line-height: 1.8;
  word-break: break-word;
  :deep(img) { max-width: 100%; height: auto; border-radius: $radius-sm; }
  :deep(p) { margin: 8px 0; }
  :deep(h1), :deep(h2), :deep(h3) { margin: 16px 0 8px; }
}
.settings-panel {
  padding: 16px;
  .settings-header {
    font-size: $font-size-lg;
    font-weight: 600;
    margin-bottom: 16px;
    text-align: center;
  }
  .setting-group {
    margin-bottom: 20px;
    .setting-label {
      font-size: $font-size-md;
      margin-bottom: 8px;
      color: $text-secondary;
    }
    .font-size-buttons, .theme-buttons {
      display: flex;
      gap: 8px;
    }
  }
}
.float-btn {
  position: fixed;
  right: 16px;
  bottom: 80px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: $primary-color;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  z-index: 99;
}
</style>

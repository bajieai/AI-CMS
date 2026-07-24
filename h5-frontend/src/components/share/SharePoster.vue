<template>
  <van-popup v-model:show="showPopup" round closeable position="center" @close="onClose">
    <div class="poster-container">
      <canvas ref="canvasRef" class="poster-canvas"></canvas>
      <div class="poster-actions">
        <van-button type="primary" block @click="onSave">保存海报</van-button>
      </div>
    </div>
  </van-popup>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { showToast, showSuccessToast } from 'vant'

interface PosterContent {
  title: string
  image?: string
  url: string
  id?: number
}

const props = defineProps<{
  show: boolean
  content: PosterContent
}>()

const emit = defineEmits<{
  'update:show': [val: boolean]
}>()

const showPopup = ref(false)
const canvasRef = ref<HTMLCanvasElement | null>(null)

watch(
  () => props.show,
  async (val) => {
    showPopup.value = val
    if (val) {
      await nextTick()
      drawPoster()
    }
  }
)

watch(showPopup, (val) => {
  emit('update:show', val)
})

function drawPoster() {
  const canvas = canvasRef.value
  if (!canvas) return

  const width = 320
  const height = 480
  canvas.width = width
  canvas.height = height
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  // 背景
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, width, height)

  // 标题区域
  ctx.fillStyle = '#1989fa'
  ctx.fillRect(0, 0, width, 60)
  ctx.fillStyle = '#ffffff'
  ctx.font = 'bold 18px sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText('AI-CMS', width / 2, 38)

  // 内容图片
  let drawY = 80
  if (props.content.image) {
    const img = new Image()
    img.crossOrigin = 'anonymous'
    img.onload = () => {
      ctx.drawImage(img, 20, drawY, width - 40, 180)
      drawText()
    }
    img.onerror = () => {
      drawText()
    }
    img.src = props.content.image
  } else {
    drawText()
  }

  function drawText() {
    // 标题
    const title = props.content.title || ''
    ctx.fillStyle = '#323233'
    ctx.font = 'bold 16px sans-serif'
    ctx.textAlign = 'left'
    const titleLines = wrapText(ctx, title, width - 40)
    let textY = 290
    titleLines.slice(0, 3).forEach((line) => {
      ctx.fillText(line, 20, textY)
      textY += 22
    })

    // URL
    ctx.fillStyle = '#969799'
    ctx.font = '12px sans-serif'
    ctx.fillText(props.content.url, 20, 400)

    // 二维码占位（简单绘制）
    drawQrPlaceholder(ctx, width - 100, 360, 80)
  }

  function drawQrPlaceholder(ctx: CanvasRenderingContext2D, x: number, y: number, size: number) {
    // 简单二维码占位图
    ctx.fillStyle = '#f0f0f0'
    ctx.fillRect(x, y, size, size)
    ctx.strokeStyle = '#323233'
    ctx.strokeRect(x, y, size, size)

    // 绘制模拟二维码点阵
    const cellSize = size / 12
    ctx.fillStyle = '#323233'
    for (let r = 0; r < 12; r++) {
      for (let c = 0; c < 12; c++) {
        if (Math.random() > 0.5) {
          ctx.fillRect(x + c * cellSize, y + r * cellSize, cellSize, cellSize)
        }
      }
    }

    // 三个定位角
    const corners = [[0, 0], [9, 0], [0, 9]]
    corners.forEach(([cx, cy]) => {
      ctx.fillStyle = '#323233'
      ctx.fillRect(x + cx * cellSize, y + cy * cellSize, 3 * cellSize, 3 * cellSize)
      ctx.fillStyle = '#ffffff'
      ctx.fillRect(x + (cx + 1) * cellSize, y + (cy + 1) * cellSize, cellSize, cellSize)
    })
  }

  function wrapText(ctx: CanvasRenderingContext2D, text: string, maxWidth: number): string[] {
    const lines: string[] = []
    let current = ''
    for (const char of text) {
      const test = current + char
      if (ctx.measureText(test).width > maxWidth) {
        lines.push(current)
        current = char
      } else {
        current = test
      }
    }
    if (current) lines.push(current)
    return lines
  }
}

function onSave() {
  const canvas = canvasRef.value
  if (!canvas) return
  try {
    const dataUrl = canvas.toDataURL('image/png')
    const link = document.createElement('a')
    link.href = dataUrl
    link.download = `poster_${props.content.id || Date.now()}.png`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    showSuccessToast('海报已保存')
  } catch (e) {
    console.error('保存海报失败:', e)
    showToast('保存失败')
  }
}

function onClose() {
  emit('update:show', false)
}
</script>

<style scoped lang="scss">
.poster-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px;
  background: #fff;
  border-radius: $radius-lg;
}
.poster-canvas {
  width: 320px;
  height: 480px;
  border-radius: $radius-sm;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}
.poster-actions {
  width: 100%;
  margin-top: 16px;
}
</style>

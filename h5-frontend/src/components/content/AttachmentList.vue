<template>
  <div class="attachment-list">
    <van-cell-group inset>
      <van-cell
        v-for="(file, index) in attachments"
        :key="index"
        class="attachment-cell"
      >
        <template #icon>
          <van-icon :name="getFileIcon(file.type)" size="28" :color="getFileColor(file.type)" class="file-icon" />
        </template>
        <template #title>
          <div class="file-info">
            <span class="file-name">{{ file.name }}</span>
            <span class="file-size">{{ formatSize(file.size) }}</span>
          </div>
        </template>
        <template #value>
          <van-button
            size="small"
            type="primary"
            plain
            icon="down"
            @click="onDownload(file)"
          >
            下载
          </van-button>
        </template>
      </van-cell>
    </van-cell-group>
  </div>
</template>

<script setup lang="ts">
import { showToast } from 'vant'

interface Attachment {
  name: string
  size: number
  url: string
  type: string
}

const props = defineProps<{
  attachments: Attachment[]
}>()

function getFileIcon(type: string): string {
  const map: Record<string, string> = {
    pdf: 'description',
    doc: 'description',
    docx: 'description',
    xls: 'description',
    xlsx: 'description',
    ppt: 'description',
    pptx: 'description',
    zip: 'description',
    rar: 'description',
    '7z': 'description',
    image: 'photo-o',
    jpg: 'photo-o',
    png: 'photo-o',
    gif: 'photo-o',
    video: 'video-o',
    mp4: 'video-o',
    audio: 'music-o',
    mp3: 'music-o',
    txt: 'notes-o',
    default: 'description',
  }
  return map[type.toLowerCase()] || map.default
}

function getFileColor(type: string): string {
  const map: Record<string, string> = {
    pdf: '#ee0a24',
    doc: '#1989fa',
    docx: '#1989fa',
    xls: '#07c160',
    xlsx: '#07c160',
    ppt: '#ff976a',
    pptx: '#ff976a',
    zip: '#969799',
    rar: '#969799',
  }
  return map[type.toLowerCase()] || '#1989fa'
}

function formatSize(bytes: number): string {
  if (!bytes) return '0 B'
  const kb = bytes / 1024
  if (kb < 1024) return `${kb.toFixed(1)} KB`
  const mb = kb / 1024
  if (mb < 1024) return `${mb.toFixed(1)} MB`
  return `${(mb / 1024).toFixed(1)} GB`
}

function onDownload(file: Attachment) {
  try {
    const link = document.createElement('a')
    link.href = file.url
    link.download = file.name
    link.target = '_blank'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (e) {
    console.error('下载失败:', e)
    showToast('下载失败')
  }
}
</script>

<style scoped lang="scss">
.attachment-list {
  width: 100%;
}
.attachment-cell {
  align-items: center;
  .file-icon {
    margin-right: 12px;
  }
  .file-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    .file-name {
      font-size: $font-size-md;
      color: $text-color;
    }
    .file-size {
      font-size: $font-size-sm;
      color: $text-secondary;
    }
  }
}
</style>

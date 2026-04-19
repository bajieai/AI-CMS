<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">媒体库</h1>
        <p class="text-gray-500 mt-1">管理图片、视频、文档等媒体文件</p>
      </div>
      <el-button type="primary" @click="triggerUpload">
        <Upload class="w-4 h-4 mr-1" />
        上传文件
      </el-button>
      <input
        ref="fileInputRef"
        type="file"
        multiple
        class="hidden"
        @change="handleFileChange"
      />
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
      <div class="flex items-center gap-4">
        <el-input
          v-model="filters.keyword"
          placeholder="搜索文件名..."
          class="w-64"
          clearable
        >
          <template #prefix>
            <Search class="w-4 h-4 text-gray-400" />
          </template>
        </el-input>
        <el-select v-model="filters.type" placeholder="文件类型" clearable>
          <el-option label="图片" value="image" />
          <el-option label="视频" value="video" />
          <el-option label="文档" value="document" />
          <el-option label="其他" value="other" />
        </el-select>
      </div>
    </div>

    <!-- Media Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
      <div
        v-for="media in mediaList"
        :key="media.id"
        class="group relative bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100 hover:border-primary/30 transition-all cursor-pointer"
        @click="handlePreview(media)"
      >
        <!-- Thumbnail -->
        <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
          <el-image
            v-if="isImage(media.mime_type)"
            :src="media.thumbnail_url || media.url"
            :preview-src-list="[media.url]"
            fit="cover"
            class="w-full h-full"
          />
          <Video v-else-if="isVideo(media.mime_type)" class="w-12 h-12 text-gray-400" />
          <FileText v-else-if="isDocument(media.mime_type)" class="w-12 h-12 text-gray-400" />
          <File v-else class="w-12 h-12 text-gray-400" />
        </div>

        <!-- Info -->
        <div class="p-3">
          <div class="text-sm font-medium text-gray-900 truncate">{{ media.filename }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ formatFileSize(media.size) }}</div>
        </div>

        <!-- Hover Actions -->
        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
          <el-dropdown trigger="click" @command="(cmd: string) => handleCommand(cmd, media)">
            <el-button size="small" circle>
              <MoreVertical class="w-4 h-4" />
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="copy">
                  <Copy class="w-4 h-4 mr-2" />
                  复制链接
                </el-dropdown-item>
                <el-dropdown-item command="rename">
                  <FileEdit class="w-4 h-4 mr-2" />
                  重命名
                </el-dropdown-item>
                <el-dropdown-item command="delete" divided>
                  <Trash2 class="w-4 h-4 mr-2" />
                  删除
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <el-icon class="is-loading text-2xl text-gray-400">
        <Loader2 />
      </el-icon>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && mediaList.length === 0" class="text-center py-12 text-gray-400">
      暂无媒体文件
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > 0" class="flex justify-center">
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.page_size"
        :total="pagination.total"
        layout="prev, pager, next"
        @current-change="fetchMedia"
      />
    </div>

    <!-- Upload Progress Dialog -->
    <el-dialog v-model="uploadDialogVisible" title="上传文件" width="400px">
      <div class="space-y-3">
        <div v-for="file in uploadingFiles" :key="file.name" class="flex items-center gap-3">
          <FileText class="w-5 h-5 text-gray-400" />
          <div class="flex-1">
            <div class="text-sm text-gray-700">{{ file.name }}</div>
            <el-progress :percentage="file.progress" :show-text="false" />
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { get, del } from '@/api/request'
import { Upload, Search, Video, FileText, File, MoreVertical, Copy, FileEdit, Trash2, Loader2 } from 'lucide-vue-next'
import type { Media, Pagination } from '@/types'

const loading = ref(false)
const mediaList = ref<Media[]>([])
const fileInputRef = ref<HTMLInputElement>()
const uploadDialogVisible = ref(false)

const uploadingFiles = ref<Array<{ name: string; progress: number }>>([])

const filters = reactive({
  keyword: '',
  type: ''
})

const pagination = reactive<Pagination>({
  page: 1,
  page_size: 20,
  total: 0,
  total_pages: 0
})

const isImage = (mimeType: string) => mimeType.startsWith('image/')
const isVideo = (mimeType: string) => mimeType.startsWith('video/')
const isDocument = (mimeType: string) => mimeType.includes('pdf') || mimeType.includes('document') || mimeType.includes('text')

const formatFileSize = (bytes: number) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
  return (bytes / (1024 * 1024 * 1024)).toFixed(1) + ' GB'
}

const fetchMedia = async () => {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page: pagination.page,
      page_size: pagination.page_size
    }
    if (filters.keyword) params.keyword = filters.keyword
    if (filters.type) params.type = filters.type

    const response = await get<{ items: Media[], pagination: Pagination }>('/media', params)
    mediaList.value = response.data.data.items
    Object.assign(pagination, response.data.data.pagination)
  } catch (error) {
    // Error handled silently
  } finally {
    loading.value = false
  }
}

const triggerUpload = () => {
  fileInputRef.value?.click()
}

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  const files = target.files
  if (!files?.length) return

  uploadDialogVisible.value = true
  uploadingFiles.value = Array.from(files).map(f => ({ name: f.name, progress: 0 }))

  // Simulate upload progress
  files.forEach((file, index) => {
    const interval = setInterval(() => {
      if (uploadingFiles.value[index]) {
        uploadingFiles.value[index].progress += 10
        if (uploadingFiles.value[index].progress >= 100) {
          clearInterval(interval)
          setTimeout(() => {
            uploadingFiles.value = uploadingFiles.value.filter((_, i) => i !== index)
            if (uploadingFiles.value.length === 0) {
              uploadDialogVisible.value = false
              fetchMedia()
            }
          }, 500)
        }
      }
    }, 200)
  })

  target.value = ''
}

const handlePreview = (media: Media) => {
  // Preview logic
}

const handleCommand = async (command: string, media: Media) => {
  switch (command) {
    case 'copy':
      await navigator.clipboard.writeText(media.url)
      ElMessage.success('链接已复制')
      break
    case 'rename':
      ElMessage.info('重命名功能开发中')
      break
    case 'delete':
      try {
        await del(`/media/${media.id}`)
        ElMessage.success('文件已删除')
        fetchMedia()
      } catch (error) {
        ElMessage.error('删除失败')
      }
      break
  }
}

onMounted(() => {
  fetchMedia()
})
</script>

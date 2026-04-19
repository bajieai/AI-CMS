<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">标签管理</h1>
        <p class="text-gray-500 mt-1">管理内容标签</p>
      </div>
      <el-button type="primary" @click="showDialog">
        <Plus class="w-4 h-4 mr-1" />
        新建标签
      </el-button>
    </div>

    <!-- Tags Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <div
        v-for="tag in tags"
        :key="tag.id"
        class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:border-primary/30 transition-colors"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
              <Tag class="w-5 h-5 text-primary" />
            </div>
            <div>
              <div class="font-medium text-gray-900">{{ tag.name }}</div>
              <div class="text-sm text-gray-500 font-mono mt-0.5">{{ tag.slug }}</div>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <el-button type="primary" link size="small" circle @click="handleEdit(tag)">
              <FileEdit class="w-4 h-4" />
            </el-button>
            <el-button type="danger" link size="small" circle @click="handleDelete(tag)">
              <Trash2 class="w-4 h-4" />
            </el-button>
          </div>
        </div>
        <div class="mt-3 pt-3 border-t border-gray-100">
          <span class="text-sm text-gray-500">
            {{ tag.content_count }} 条信息
          </span>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-if="tags.length === 0 && !loading"
        class="col-span-full text-center py-12 text-gray-400"
      >
        暂无标签，点击上方按钮创建
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <el-icon class="is-loading text-2xl text-gray-400">
        <Loader2 />
      </el-icon>
    </div>

    <!-- Dialog -->
    <el-dialog v-model="dialogVisible" :title="editingTag ? '编辑标签' : '新建标签'" width="500px">
      <el-form :model="form" label-width="80px" label-position="left">
        <el-form-item label="名称" required>
          <el-input v-model="form.name" placeholder="请输入标签名称" />
        </el-form-item>
        <el-form-item label="别名">
          <el-input v-model="form.slug" placeholder="请输入URL别名" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { get, post, put, del } from '@/api/request'
import { Plus, Tag, FileEdit, Trash2, Loader2 } from 'lucide-vue-next'
import type { Tag as TagType } from '@/types'

const loading = ref(false)
const tags = ref<TagType[]>([])
const dialogVisible = ref(false)
const editingTag = ref<TagType | null>(null)

const form = reactive({
  name: '',
  slug: ''
})

const fetchTags = async () => {
  loading.value = true
  try {
    const response = await get<TagType[]>('/tags')
    tags.value = response.data.data
  } catch (error) {
    // Error handled silently
  } finally {
    loading.value = false
  }
}

const showDialog = () => {
  editingTag.value = null
  Object.assign(form, { name: '', slug: '' })
  dialogVisible.value = true
}

const handleEdit = (tag: TagType) => {
  editingTag.value = tag
  Object.assign(form, { name: tag.name, slug: tag.slug })
  dialogVisible.value = true
}

const handleSave = async () => {
  if (!form.name) {
    ElMessage.warning('请输入标签名称')
    return
  }

  try {
    if (editingTag.value) {
      await put(`/tags/${editingTag.value.id}`, form)
      ElMessage.success('标签已更新')
    } else {
      await post('/tags', form)
      ElMessage.success('标签已创建')
    }
    dialogVisible.value = false
    fetchTags()
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const handleDelete = async (tag: TagType) => {
  try {
    await del(`/tags/${tag.id}`)
    ElMessage.success('标签已删除')
    fetchTags()
  } catch (error) {
    ElMessage.error('删除失败')
  }
}

onMounted(() => {
  fetchTags()
})
</script>

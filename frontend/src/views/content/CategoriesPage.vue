<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">分类管理</h1>
        <p class="text-gray-500 mt-1">管理内容分类</p>
      </div>
      <el-button type="primary" @click="showDialog">
        <Plus class="w-4 h-4 mr-1" />
        新建分类
      </el-button>
    </div>

    <!-- Categories Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <el-table v-loading="loading" :data="categories" style="width: 100%">
        <el-table-column prop="name" label="名称" min-width="150">
          <template #default="{ row }">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
                <Folder class="w-4 h-4 text-primary" />
              </div>
              <span class="font-medium text-gray-900">{{ row.name }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="slug" label="别名" width="150">
          <template #default="{ row }">
            <span class="text-gray-500 font-mono text-sm">{{ row.slug }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="200">
          <template #default="{ row }">
            <span class="text-gray-600">{{ row.description || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="content_count" label="信息数" width="100" align="center">
          <template #default="{ row }">
            <el-tag type="info" size="small">{{ row.content_count }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Dialog -->
    <el-dialog v-model="dialogVisible" :title="editingCategory ? '编辑分类' : '新建分类'" width="500px">
      <el-form :model="form" label-width="80px" label-position="left">
        <el-form-item label="名称" required>
          <el-input v-model="form.name" placeholder="请输入分类名称" />
        </el-form-item>
        <el-form-item label="别名">
          <el-input v-model="form.slug" placeholder="请输入URL别名" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" :rows="3" placeholder="请输入分类描述" />
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
import { Plus, Folder } from 'lucide-vue-next'
import type { Category } from '@/types'

const loading = ref(false)
const categories = ref<Category[]>([])
const dialogVisible = ref(false)
const editingCategory = ref<Category | null>(null)

const form = reactive({
  name: '',
  slug: '',
  description: ''
})

const fetchCategories = async () => {
  loading.value = true
  try {
    const response = await get<Category[]>('/categories')
    categories.value = response.data.data
  } catch (error) {
    // Error handled silently
  } finally {
    loading.value = false
  }
}

const showDialog = () => {
  editingCategory.value = null
  Object.assign(form, { name: '', slug: '', description: '' })
  dialogVisible.value = true
}

const handleEdit = (category: Category) => {
  editingCategory.value = category
  Object.assign(form, {
    name: category.name,
    slug: category.slug,
    description: category.description || ''
  })
  dialogVisible.value = true
}

const handleSave = async () => {
  if (!form.name) {
    ElMessage.warning('请输入分类名称')
    return
  }

  try {
    if (editingCategory.value) {
      await put(`/categories/${editingCategory.value.id}`, form)
      ElMessage.success('分类已更新')
    } else {
      await post('/categories', form)
      ElMessage.success('分类已创建')
    }
    dialogVisible.value = false
    fetchCategories()
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const handleDelete = async (category: Category) => {
  try {
    await del(`/categories/${category.id}`)
    ElMessage.success('分类已删除')
    fetchCategories()
  } catch (error) {
    ElMessage.error('删除失败')
  }
}

onMounted(() => {
  fetchCategories()
})
</script>

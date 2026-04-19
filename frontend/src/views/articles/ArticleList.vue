<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">信息管理</h1>
        <p class="text-gray-500 mt-1">管理所有信息内容</p>
      </div>
      <el-button type="primary" @click="handleCreate">
        <Plus class="w-4 h-4 mr-1" />
        新建信息
      </el-button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
      <div class="flex flex-wrap gap-4">
        <!-- Search -->
        <el-input
          v-model="filters.keyword"
          placeholder="搜索信息标题..."
          class="w-64"
          clearable
          @clear="handleSearch"
        >
          <template #prefix>
            <Search class="w-4 h-4 text-gray-400" />
          </template>
        </el-input>

        <!-- Status Filter -->
        <el-select v-model="filters.status" placeholder="状态筛选" clearable @change="handleSearch">
          <el-option label="全部" value="" />
          <el-option label="草稿" value="draft" />
          <el-option label="已发布" value="published" />
          <el-option label="已归档" value="archived" />
        </el-select>

        <!-- Category Filter -->
        <el-select v-model="filters.category_id" placeholder="分类筛选" clearable @change="handleSearch">
          <el-option
            v-for="cat in categories"
            :key="cat.id"
            :label="cat.name"
            :value="cat.id"
          />
        </el-select>

        <el-button @click="handleSearch">搜索</el-button>
      </div>
    </div>

    <!-- Articles Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <el-table
        v-loading="loading"
        :data="articles"
        style="width: 100%"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" />
        
        <el-table-column label="信息" min-width="300">
          <template #default="{ row }">
            <div class="flex items-center gap-3">
              <el-image
                v-if="row.thumbnail"
                :src="row.thumbnail"
                class="w-12 h-12 rounded-lg object-cover flex-shrink-0"
                fit="cover"
              />
              <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0" v-else>
                <FileText class="w-6 h-6 text-gray-400" />
              </div>
              <div class="min-w-0">
                <div class="font-medium text-gray-900 truncate">{{ row.title }}</div>
                <div class="text-sm text-gray-500 truncate mt-0.5">
                  {{ row.excerpt || '暂无摘要' }}
                </div>
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="category_name" label="分类" width="120" />

        <el-table-column prop="author_name" label="作者" width="100" />

        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">
              {{ getStatusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column prop="view_count" label="浏览量" width="100" align="center">
          <template #default="{ row }">
            <span class="text-gray-600">{{ row.view_count.toLocaleString() }}</span>
          </template>
        </el-table-column>

        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button 
              type="success" 
              link 
              size="small" 
              v-if="row.status === 'draft'"
              @click="handlePublish(row)"
            >
              发布
            </el-button>
            <el-button 
              type="warning" 
              link 
              size="small" 
              v-if="row.status === 'published'"
              @click="handleArchive(row)"
            >
              归档
            </el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- Pagination -->
      <div class="flex justify-center py-4 border-t border-gray-100">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.page_size"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </div>

    <!-- Delete Confirmation -->
    <el-dialog v-model="deleteDialogVisible" title="确认删除" width="400px">
      <p>确定要删除信息《{{ currentArticle?.title }}》吗？此操作不可撤销。</p>
      <template #footer>
        <el-button @click="deleteDialogVisible = false">取消</el-button>
        <el-button type="danger" :loading="deleteLoading" @click="confirmDelete">删除</el-button>
      </template>
    </el-dialog>

    <!-- Article Edit/Create Drawer -->
    <el-drawer
      v-model="editDrawerVisible"
      :title="editingArticle?.id ? '编辑信息' : '新建信息'"
      size="700px"
      direction="rtl"
      :before-close="handleEditClose"
    >
      <div class="content-form">
        <el-form
          ref="formRef"
          :model="articleForm"
          :rules="formRules"
          label-width="90px"
          label-position="top"
          class="px-6"
        >
          <el-form-item label="文章标题" prop="title">
            <el-input v-model="articleForm.title" placeholder="请输入文章标题" maxlength="200" show-word-limit />
          </el-form-item>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="分类" prop="category_id">
                <el-select v-model="articleForm.category_id" placeholder="选择分类" class="w-full">
                  <el-option
                    v-for="cat in categories"
                    :key="cat.id"
                    :label="cat.name"
                    :value="cat.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="状态">
                <el-select v-model="articleForm.status" class="w-full">
                  <el-option label="草稿" :value="0" />
                  <el-option label="已发布" :value="2" />
                  <el-option label="待审核" :value="1" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="摘要">
            <el-input
              v-model="articleForm.excerpt"
              type="textarea"
              :rows="3"
              placeholder="请输入信息摘要（可选）"
              maxlength="500"
              show-word-limit
            />
          </el-form-item>

          <el-form-item label="内容" prop="content">
            <el-input
              v-model="articleForm.content"
              type="textarea"
              :rows="15"
              placeholder="请输入信息内容（支持HTML或Markdown格式）"
            />
          </el-form-item>

          <el-divider content-position="left">SEO设置</el-divider>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="SEO标题">
                <el-input v-model="articleForm.seo_title" placeholder="留空则使用文章标题" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="SEO关键词">
                <el-input v-model="articleForm.seo_keywords" placeholder="多个关键词用逗号分隔" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="SEO描述">
            <el-input
              v-model="articleForm.seo_description"
              type="textarea"
              :rows="2"
              placeholder="留空则自动截取内容前160字"
            />
          </el-form-item>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
            <el-button @click="handleEditClose">取消</el-button>
            <el-button @click="saveAsDraft" :loading="saving">保存草稿</el-button>
            <el-button type="primary" @click="saveAndPublish" :loading="saving">发布</el-button>
          </div>
        </el-form>
      </div>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { get, post, put, del } from '@/api/request'
import { Plus, Search, FileText } from 'lucide-vue-next'
import type { Article, Category, Pagination } from '@/types'

// State
const loading = ref(false)
const articles = ref<any[]>([])
const categories = ref<any[]>([])
const selectedArticles = ref<any[]>([])

// Filters
const filters = reactive({
  keyword: '',
  status: '',
  category_id: '' as number | ''
})

// Pagination
const pagination = reactive<Pagination>({
  page: 1,
  page_size: 20,
  total: 0,
  total_pages: 0
})

// Delete dialog
const deleteDialogVisible = ref(false)
const deleteLoading = ref(false)
const currentArticle = ref<any>(null)

// Edit drawer state
const editDrawerVisible = ref(false)
const saving = ref(false)
const formRef = ref<FormInstance>()
const editingArticle = ref<any>(null)

// Article form data - matching backend Article model fields
const articleForm = reactive({
  title: '',
  content: '',
  excerpt: '',
  category_id: null as number | null,
  status: 0 as number,
  seo_title: '',
  seo_keywords: '',
  seo_description: '',
  cover_image: '',
  is_top: 0 as number,
  is_featured: 0 as number,
})

// Form validation rules
const formRules: FormRules = {
  title: [
    { required: true, message: '请输入文章标题', trigger: 'blur' },
    { min: 2, max: 200, message: '标题长度在2到200个字符', trigger: 'blur' },
  ],
  content: [
    { required: true, message: '请输入信息内容', trigger: 'blur' },
  ],
  category_id: [
    { required: true, message: '请选择分类', trigger: 'change' },
  ],
}

// Get status type for tag (backend returns numeric status)
const getStatusType = (status: number | string) => {
  const s = typeof status === 'string' ? parseInt(status) : status
  const types: Record<number, string> = {
    0: 'info',     // 草稿
    1: 'warning',  // 待审核
    2: 'success',  // 已发布
    3: 'info',     // 已归档/下架
    4: 'danger',   // 回收站
  }
  return types[s] || 'info'
}

const getStatusLabel = (status: number | string) => {
  const s = typeof status === 'string' ? parseInt(status) : status
  const labels: Record<number, string> = {
    0: '草稿',
    1: '待审核',
    2: '已发布',
    3: '已下架',
    4: '回收站',
  }
  return labels[s] || '未知'
}

// Fetch articles
const fetchContents = async () => {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page: pagination.page,
      page_size: pagination.page_size
    }

    if (filters.keyword) params.keyword = filters.keyword
    if (filters.status) params.status = filters.status
    if (filters.category_id) params.category_id = filters.category_id

    const response = await get<{ items: any[], pagination: any }>('/articles', params)
    articles.value = response.data.data?.items ?? []
    if (response.data.data?.pagination) {
      Object.assign(pagination, response.data.data.pagination)
    }
  } catch (error) {
    console.error('Failed to fetch contents:', error)
  } finally {
    loading.value = false
  }
}

// Fetch categories
const fetchCategories = async () => {
  try {
    const response = await get<any[]>('/categories')
    categories.value = Array.isArray(response.data.data) ? response.data.data : []
  } catch (error) {
    // Error handled silently
  }
}

// Reset form
const resetForm = () => {
  Object.assign(articleForm, {
    title: '',
    content: '',
    excerpt: '',
    category_id: null,
    status: 0,
    seo_title: '',
    seo_keywords: '',
    seo_description: '',
    cover_image: '',
    is_top: 0,
    is_featured: 0,
  })
  editingArticle.value = null
  formRef.value?.clearValidate()
}

// Open create dialog
const handleCreate = () => {
  resetForm()
  articleForm.status = 0 // default to draft
  editDrawerVisible.value = true
}

// Open edit dialog
const handleEdit = (article: any) => {
  resetForm()
  editingArticle.value = article
  Object.assign(articleForm, {
    title: article.title || '',
    content: article.content || '',
    excerpt: article.excerpt || '',
    category_id: article.category_id || null,
    status: typeof article.status === 'string' ? parseInt(article.status) : (article.status ?? 0),
    seo_title: article.seo_title || '',
    seo_keywords: article.seo_keywords || '',
    seo_description: article.seo_description || '',
    cover_image: article.cover_image || '',
    is_top: article.is_top || 0,
    is_featured: article.is_featured || 0,
  })
  editDrawerVisible.value = true
}

// Close edit drawer
const handleEditClose = () => {
  editDrawerVisible.value = false
  resetForm()
}

// Save as draft
const saveAsDraft = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return

    saving.value = true
    try {
      const payload = { ...articleForm, status: 0 }
      if (editingArticle.value?.id) {
        await put(`/articles/${editingArticle.value.id}`, payload)
        ElMessage.success('草稿保存成功')
      } else {
        await post('/articles', payload)
        ElMessage.success('草稿创建成功')
      }
      handleEditClose()
      fetchArticles()
    } catch (error) {
      ElMessage.error('保存失败，请重试')
    } finally {
      saving.value = false
    }
  })
}

// Save and publish
const saveAndPublish = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return

    saving.value = true
    try {
      const payload = { ...articleForm, status: 2 }
      if (editingArticle.value?.id) {
        await put(`/articles/${editingArticle.value.id}`, payload)
        ElMessage.success('信息发布成功')
      } else {
        await post('/articles', payload)
        ElMessage.success('信息发布成功')
      }
      handleEditClose()
      fetchArticles()
    } catch (error) {
      ElMessage.error('发布失败，请重试')
    } finally {
      saving.value = false
    }
  })
}

const handlePublish = async (article: any) => {
  try {
    await post(`/articles/${article.id}/publish`)
    ElMessage.success('信息已发布')
    fetchArticles()
  } catch (error) {
    ElMessage.error('发布失败')
  }
}

const handleArchive = async (article: any) => {
  try {
    await post(`/articles/${article.id}/archive`)
    ElMessage.success('信息已归档')
    fetchArticles()
  } catch (error) {
    ElMessage.error('归档失败')
  }
}

const handleDelete = (article: any) => {
  currentArticle.value = article
  deleteDialogVisible.value = true
}

const confirmDelete = async () => {
  if (!currentArticle.value) return

  deleteLoading.value = true
  try {
    await del(`/articles/${currentArticle.value.id}`)
    ElMessage.success('删除成功')
    deleteDialogVisible.value = false
    fetchArticles()
  } catch (error) {
    ElMessage.error('删除失败')
  } finally {
    deleteLoading.value = false
  }
}

const handleSelectionChange = (selection: any[]) => {
  selectedArticles.value = selection
}

const handleSearch = () => {
  pagination.page = 1
  fetchArticles()
}

const handlePageChange = (page: number) => {
  pagination.page = page
  fetchArticles()
}

const handleSizeChange = (size: number) => {
  pagination.page_size = size
  pagination.page = 1
  fetchArticles()
}

onMounted(() => {
  fetchArticles()
  fetchCategories()
})
</script>

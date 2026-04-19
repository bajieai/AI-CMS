<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">系统设置</h1>
      <p class="text-gray-500 mt-1">配置系统各项参数</p>
    </div>

    <!-- Settings Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <el-tabs v-model="activeTab" class="settings-tabs">
        <!-- Basic Settings -->
        <el-tab-pane label="基础设置" name="basic">
          <div class="p-6 max-w-2xl">
            <el-form :model="basicSettings" label-width="120px" label-position="left">
              <el-form-item label="站点名称">
                <el-input v-model="basicSettings.site_name" placeholder="请输入站点名称" />
              </el-form-item>
              <el-form-item label="站点URL">
                <el-input v-model="basicSettings.site_url" placeholder="https://example.com" />
              </el-form-item>
              <el-form-item label="站点描述">
                <el-input
                  v-model="basicSettings.site_description"
                  type="textarea"
                  :rows="3"
                  placeholder="请输入站点描述"
                />
              </el-form-item>
              <el-form-item label="时区">
                <el-select v-model="basicSettings.timezone" class="w-full">
                  <el-option label="UTC+8 (北京时间)" value="Asia/Shanghai" />
                  <el-option label="UTC (世界标准时间)" value="UTC" />
                  <el-option label="UTC+9 (东京时间)" value="Asia/Tokyo" />
                  <el-option label="UTC-5 (美国东部时间)" value="America/New_York" />
                </el-select>
              </el-form-item>
              <el-form-item label="语言">
                <el-select v-model="basicSettings.language" class="w-full">
                  <el-option label="简体中文" value="zh-CN" />
                  <el-option label="English" value="en-US" />
                </el-select>
              </el-form-item>
              <el-form-item>
                <el-button type="primary" @click="saveBasicSettings">保存设置</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>

        <!-- AI Model Settings -->
        <el-tab-pane label="AI模型配置" name="ai-models">
          <div class="p-6">
            <div class="flex justify-between items-center mb-4">
              <span class="text-sm text-gray-500">当前已配置的AI模型（来自AI模块管理）</span>
            </div>

            <el-table :data="aiModels" stripe>
              <el-table-column prop="model_name" label="模型名称" min-width="120" />
              <el-table-column prop="provider" label="服务商" width="100">
                <template #default="{ row }">
                  <el-tag size="small">{{ row.provider }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="model_code" label="模型代码" min-width="150" />
              <el-table-column label="状态" width="100" align="center">
                <template #default="{ row }">
                  <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                    {{ row.status === 1 ? '启用' : '禁用' }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column label="默认" width="80" align="center">
                <template #default="{ row }">
                  <el-tag v-if="row.is_default === 1" type="warning" size="small">默认</el-tag>
                </template>
              </el-table-column>
            </el-table>

            <div v-if="aiModels.length === 0" class="text-center text-gray-400 py-8">
              暂无AI模型配置，请通过数据库或后台添加
            </div>
          </div>
        </el-tab-pane>

        <!-- SEO Settings -->
        <el-tab-pane label="SEO设置" name="seo">
          <div class="p-6 max-w-2xl">
            <el-form :model="seoSettings" label-width="120px" label-position="left">
              <el-form-item label="标题格式">
                <el-input v-model="seoSettings.title_format" placeholder="{title} - {site_name}" />
                <div class="text-xs text-gray-400 mt-1">可用变量: {title}, {site_name}, {category}</div>
              </el-form-item>
              <el-form-item label="关键词分隔">
                <el-input v-model="seoSettings.keyword_separator" placeholder="," />
              </el-form-item>
              <el-form-item label="生成Sitemap">
                <el-switch v-model="seoSettings.sitemap_enabled" />
              </el-form-item>
              <el-form-item label="百度推送API">
                <el-input
                  v-model="seoSettings.baidu_push_api"
                  placeholder="请输入百度推送API地址"
                />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" @click="saveSeoSettings">保存设置</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>

        <!-- Email Settings -->
        <el-tab-pane label="邮件配置" name="email">
          <div class="p-6 max-w-2xl">
            <el-form :model="smtpSettings" label-width="120px" label-position="left">
              <el-form-item label="SMTP服务器">
                <el-input v-model="smtpSettings.host" placeholder="smtp.example.com" />
              </el-form-item>
              <el-form-item label="端口">
                <el-input-number v-model="smtpSettings.port" :min="1" :max="65535" />
              </el-form-item>
              <el-form-item label="用户名">
                <el-input v-model="smtpSettings.username" placeholder="your@email.com" />
              </el-form-item>
              <el-form-item label="密码">
                <el-input v-model="smtpSettings.password" type="password" show-password placeholder="请输入密码" />
              </el-form-item>
              <el-form-item label="发件人">
                <el-input v-model="smtpSettings.from_email" placeholder="noreply@example.com" />
              </el-form-item>
              <el-form-item label="发件人名称">
                <el-input v-model="smtpSettings.from_name" placeholder="AI-CMS" />
              </el-form-item>
              <el-form-item label="使用SSL">
                <el-switch v-model="smtpSettings.use_ssl" />
              </el-form-item>
              <el-form-item>
                <el-button @click="testSmtp">发送测试邮件</el-button>
                <el-button type="primary" @click="saveSmtpSettings" class="ml-4">保存设置</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>
    </el-tabs>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { get, post, put } from '@/api/request'
import { Plus } from 'lucide-vue-next'
import type { SystemSettings, SeoSettings, SmtpSettings } from '@/types'

// Active tab
const activeTab = ref('basic')

// Basic settings
const basicSettings = reactive<SystemSettings>({
  site_name: 'AI-CMS',
  site_url: 'https://example.com',
  site_description: '智能内容管理系统',
  timezone: 'Asia/Shanghai',
  language: 'zh-CN'
})

// SEO settings
const seoSettings = reactive<SeoSettings>({
  title_format: '{title} - {site_name}',
  keyword_separator: ',',
  sitemap_enabled: true,
  baidu_push_api: ''
})

// SMTP settings
const smtpSettings = reactive<SmtpSettings>({
  host: '',
  port: 465,
  username: '',
  password: '',
  from_email: '',
  from_name: 'AI-CMS',
  use_ssl: true
})

// AI Models (read-only from backend)
interface AiModelItem {
  id: number
  provider: string
  model_code: string
  model_name: string
  is_default: number
  status: number
}
const aiModels = ref<AiModelItem[]>([])

// Fetch settings by group (matching backend route: GET/PUT api/settings/:group)
const fetchSettings = async () => {
  try {
    // 并行获取所有设置分组
    const [basicRes, seoRes, smtpRes] = await Promise.all([
      get<Record<string, any>>('/settings/basic'),
      get<Record<string, any>>('/settings/seo'),
      get<Record<string, any>>('/settings/smtp')
    ])

    if (basicRes.data.data) {
      Object.assign(basicSettings, basicRes.data.data)
    }
    if (seoRes.data.data) {
      Object.assign(seoSettings, seoRes.data.data)
    }
    if (smtpRes.data.data) {
      Object.assign(smtpSettings, smtpRes.data.data)
    }

    // 获取AI模型列表 (from /api/ai/models)
    try {
      const modelsRes = await get<AiModelItem[]>('/ai/models')
      if (modelsRes.data.data) {
        aiModels.value = Array.isArray(modelsRes.data.data) ? modelsRes.data.data : []
      }
    } catch {
      // AI models fetch failed, ignore
    }
  } catch (error) {
    // Error handled silently
  }
}

// Save handlers - using PUT /settings/:group with body as JSON
const saveBasicSettings = async () => {
  try {
    await put('/settings/basic', basicSettings)
    ElMessage.success('基础设置已保存')
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const saveSeoSettings = async () => {
  try {
    await put('/settings/seo', seoSettings)
    ElMessage.success('SEO设置已保存')
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const saveSmtpSettings = async () => {
  try {
    await put('/settings/smtp', smtpSettings)
    ElMessage.success('邮件设置已保存')
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const testSmtp = async () => {
  try {
    await post('/settings?group=smtp', { action: 'test', ...smtpSettings })
    ElMessage.success('测试邮件已发送')
  } catch (error) {
    ElMessage.error('发送失败，请检查配置')
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
.settings-tabs :deep(.el-tabs__header) {
  padding: 0 24px;
  margin: 0;
}

.settings-tabs :deep(.el-tabs__nav-wrap::after) {
  height: 1px;
}
</style>

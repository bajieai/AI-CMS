<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 relative overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-1/2 -left-1/2 w-full h-full bg-gradient-to-r from-primary/20 to-transparent rounded-full blur-3xl transform rotate-12"></div>
      <div class="absolute -bottom-1/2 -right-1/2 w-full h-full bg-gradient-to-l from-purple-500/20 to-transparent rounded-full blur-3xl transform -rotate-12"></div>
    </div>

    <!-- Login Card -->
    <div class="relative z-10 w-full max-w-md px-6">
      <div class="glass-card p-8 shadow-2xl">
        <!-- Brand -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-2xl mb-4 shadow-lg shadow-primary/30">
            <Sparkles class="w-8 h-8 text-white" />
          </div>
          <h1 class="text-2xl font-bold text-gray-900">AI-CMS</h1>
          <p class="text-gray-500 mt-2">智能内容管理系统</p>
        </div>

        <!-- Login Form -->
        <el-form
          ref="formRef"
          :model="formData"
          :rules="rules"
          @submit.prevent="handleLogin"
        >
          <el-form-item prop="username">
            <div class="w-full">
              <label class="block text-sm font-medium text-gray-700 mb-2">用户名 / 邮箱</label>
              <el-input
                v-model="formData.username"
                placeholder="请输入用户名或邮箱"
                size="large"
                :prefix-icon="User"
              />
            </div>
          </el-form-item>

          <el-form-item prop="password">
            <div class="w-full">
              <label class="block text-sm font-medium text-gray-700 mb-2">密码</label>
              <el-input
                v-model="formData.password"
                type="password"
                placeholder="请输入密码"
                size="large"
                show-password
                :prefix-icon="Lock"
                @keyup.enter="handleLogin"
              />
            </div>
          </el-form-item>

          <div class="flex items-center justify-between mb-6">
            <el-checkbox v-model="formData.remember">记住登录</el-checkbox>
          </div>

          <el-button
            type="primary"
            size="large"
            :loading="loading"
            class="w-full"
            @click="handleLogin"
          >
            {{ loading ? '登录中...' : '登录' }}
          </el-button>
        </el-form>

        <!-- Error Message -->
        <el-alert
          v-if="errorMessage"
          :title="errorMessage"
          type="error"
          show-icon
          class="mt-4"
          @close="errorMessage = ''"
        />

        <!-- Default Account Hint -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-500 text-center">
            默认账户: <span class="font-mono text-gray-700">admin</span> / <span class="font-mono text-gray-700">123456</span>
          </p>
        </div>
      </div>

      <!-- Footer -->
      <p class="text-center text-gray-400 text-sm mt-6">
        © 2024 AI-CMS. All rights reserved.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { User, Lock, Sparkles } from 'lucide-vue-next'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'

const router = useRouter()
const userStore = useUserStore()

const formRef = ref<FormInstance>()
const loading = ref(false)
const errorMessage = ref('')

const formData = reactive({
  username: '',
  password: '',
  remember: false
})

const rules: FormRules = {
  username: [
    { required: true, message: '请输入用户名或邮箱', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码至少6位', trigger: 'blur' }
  ]
}

const handleLogin = async () => {
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
  } catch {
    return
  }
  
  loading.value = true
  errorMessage.value = ''
  
  try {
    await userStore.login({
      username: formData.username,
      password: formData.password,
      remember: formData.remember
    })
    ElMessage.success('登录成功')
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || '登录失败，请检查用户名和密码'
  } finally {
    loading.value = false
  }
}
</script>

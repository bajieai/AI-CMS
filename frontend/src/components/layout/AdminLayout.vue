<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <aside
      :class="[
        'fixed left-0 top-0 h-full bg-slate-900 transition-all duration-300 z-30',
        isCollapsed ? 'w-16' : 'w-64'
      ]"
    >
      <!-- Logo Area -->
      <div class="h-16 flex items-center px-4 border-b border-slate-800">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
            <Sparkles class="w-5 h-5 text-white" />
          </div>
          <span v-show="!isCollapsed" class="text-white font-semibold text-lg">
            AI-CMS
          </span>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="p-3 space-y-1">
        <router-link
          v-for="item in menuItems"
          :key="item.path"
          :to="item.path"
          class="nav-item"
          :class="{ 'active': isActive(item.path) }"
        >
          <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
          <span v-show="!isCollapsed">{{ item.title }}</span>
        </router-link>
      </nav>

      <!-- Collapse Toggle -->
      <button
        @click="isCollapsed = !isCollapsed"
        class="absolute -right-3 top-20 w-6 h-6 bg-slate-800 border border-slate-700 rounded-full flex items-center justify-center text-white hover:bg-slate-700 transition-colors"
      >
        <ChevronLeft v-if="!isCollapsed" class="w-4 h-4" />
        <ChevronRight v-else class="w-4 h-4" />
      </button>
    </aside>

    <!-- Main Content Area -->
    <div :class="['flex-1 transition-all duration-300', isCollapsed ? 'ml-16' : 'ml-64']">
      <!-- Top Header -->
      <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 sticky top-0 z-20">
        <!-- Left: Breadcrumb -->
        <div class="flex items-center gap-4">
          <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/' }">首页</el-breadcrumb-item>
            <el-breadcrumb-item v-if="currentRoute.meta?.title">
              {{ currentRoute.meta.title }}
            </el-breadcrumb-item>
          </el-breadcrumb>
        </div>

        <!-- Right: Search & User -->
        <div class="flex items-center gap-4">
          <!-- Search Box -->
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="text"
              placeholder="搜索..."
              class="w-64 pl-10 pr-4 py-2 bg-gray-100 border-0 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all"
            />
          </div>

          <!-- Clear Cache Button -->
          <el-tooltip content="清除缓存" placement="bottom">
            <button 
              @click="handleClearCache"
              :disabled="clearingCache"
              class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <RefreshCw 
                class="w-5 h-5 text-gray-600" 
                :class="{ 'animate-spin': clearingCache }" 
              />
            </button>
          </el-tooltip>

          <!-- Notifications -->
          <el-badge :value="3" :max="99" class="cursor-pointer">
            <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
              <Bell class="w-5 h-5 text-gray-600" />
            </button>
          </el-badge>

          <!-- User Dropdown -->
          <el-dropdown trigger="click" @command="handleUserCommand">
            <button class="flex items-center gap-3 hover:bg-gray-100 rounded-lg px-3 py-2 transition-colors">
              <el-avatar :size="32" :src="userStore.avatar">
                <User class="w-4 h-4" />
              </el-avatar>
              <span class="text-sm font-medium text-gray-700">{{ userStore.username }}</span>
              <ChevronDown class="w-4 h-4 text-gray-400" />
            </button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile">
                  <User class="w-4 h-4 mr-2" />
                  个人中心
                </el-dropdown-item>
                <el-dropdown-item command="settings">
                  <Settings class="w-4 h-4 mr-2" />
                  设置
                </el-dropdown-item>
                <el-dropdown-item divided command="logout">
                  <LogOut class="w-4 h-4 mr-2" />
                  退出登录
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </header>

      <!-- Page Content -->
      <main class="p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'
import {
  LayoutDashboard,
  FileText,
  Folder,
  Tag,
  Image,
  Sparkles,
  Settings,
  Search,
  Bell,
  User,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  LogOut,
  RefreshCw
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const isCollapsed = ref(false)
const clearingCache = ref(false)

const currentRoute = computed(() => route)

const menuItems = [
  { path: '/', title: '仪表盘', icon: LayoutDashboard },
  { path: '/articles', title: '信息管理', icon: FileText },
  { path: '/categories', title: '分类管理', icon: Folder },
  { path: '/tags', title: '标签管理', icon: Tag },
  { path: '/media', title: '媒体库', icon: Image },
  { path: '/ai-studio', title: 'AI工作室', icon: Sparkles },
  { path: '/settings', title: '系统设置', icon: Settings }
]

const isActive = (path: string) => {
  if (path === '/') {
    return route.path === '/'
  }
  return route.path.startsWith(path)
}

const handleUserCommand = async (command: string) => {
  switch (command) {
    case 'profile':
      // Navigate to profile page
      break
    case 'settings':
      router.push('/settings')
      break
    case 'logout':
      await userStore.logout()
      break
  }
}

const handleClearCache = async () => {
  if (clearingCache.value) return
  
  clearingCache.value = true
  try {
    const response = await fetch('/api/cache/clear', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('access_token')}`
      },
      body: JSON.stringify({ type: 'all' })
    })
    
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('缓存清除成功！页面将重新加载')
      // 延迟后刷新页面，让用户看到成功提示
      setTimeout(() => {
        window.location.reload()
      }, 1000)
    } else {
      ElMessage.error(data.message || '缓存清除失败')
    }
  } catch (error) {
    ElMessage.error('清除缓存时发生错误')
  } finally {
    clearingCache.value = false
  }
}
</script>

import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useUserStore } from '@/stores/user'

// Layouts
import AdminLayout from '@/components/layout/AdminLayout.vue'

// Views
import LoginPage from '@/views/login/LoginPage.vue'
import DashboardPage from '@/views/dashboard/DashboardPage.vue'
import ArticleList from '@/views/articles/ArticleList.vue'
import CategoriesPage from '@/views/content/CategoriesPage.vue'
import TagsPage from '@/views/content/TagsPage.vue'
import MediaPage from '@/views/content/MediaPage.vue'
import AIStudioPage from '@/views/ai/AIStudioPage.vue'
import SettingsPage from '@/views/settings/SettingsPage.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: LoginPage,
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: DashboardPage,
        meta: { title: '仪表盘', icon: 'LayoutDashboard' }
      },
      {
        path: 'dashboard',
        redirect: '/'
      },
      {
        path: 'articles',
        name: 'Articles',
        component: ArticleList,
        meta: { title: '信息管理', icon: 'FileText' }
      },
      {
        path: 'categories',
        name: 'Categories',
        component: CategoriesPage,
        meta: { title: '分类管理', icon: 'Folder' }
      },
      {
        path: 'tags',
        name: 'Tags',
        component: TagsPage,
        meta: { title: '标签管理', icon: 'Tag' }
      },
      {
        path: 'media',
        name: 'Media',
        component: MediaPage,
        meta: { title: '媒体库', icon: 'Image' }
      },
      {
        path: 'ai-studio',
        name: 'AIStudio',
        component: AIStudioPage,
        meta: { title: 'AI工作室', icon: 'Sparkles' }
      },
      {
        path: 'settings',
        name: 'Settings',
        component: SettingsPage,
        meta: { title: '系统设置', icon: 'Settings' }
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const userStore = useUserStore()
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth !== false)
  
  if (requiresAuth && !userStore.isAuthenticated) {
    // Check for token in localStorage (might be loaded after store initialization)
    const token = localStorage.getItem('access_token')
    if (!token) {
      next('/login')
      return
    }
  }
  
  if (to.path === '/login' && userStore.isAuthenticated) {
    next('/')
    return
  }
  
  next()
})

export default router

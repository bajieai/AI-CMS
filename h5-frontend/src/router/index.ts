import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/pages/Home.vue'),
    meta: { title: '首页', keepAlive: true },
  },
  {
    path: '/content/list',
    name: 'ContentList',
    component: () => import('@/pages/ContentList.vue'),
    meta: { title: '内容列表', keepAlive: true },
  },
  {
    path: '/content/detail/:id',
    name: 'ContentDetail',
    component: () => import('@/pages/ContentDetail.vue'),
    meta: { title: '内容详情' },
  },
  {
    path: '/search',
    name: 'Search',
    component: () => import('@/pages/Search.vue'),
    meta: { title: '搜索' },
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/pages/Login.vue'),
    meta: { title: '登录' },
  },
  {
    path: '/user',
    name: 'UserCenter',
    component: () => import('@/pages/UserCenter.vue'),
    meta: { title: '个人中心', requiresAuth: true },
    children: [
      {
        path: 'profile',
        name: 'UserProfile',
        component: () => import('@/pages/user/Profile.vue'),
        meta: { title: '个人资料', requiresAuth: true },
      },
      {
        path: 'orders',
        name: 'UserOrders',
        component: () => import('@/pages/user/Orders.vue'),
        meta: { title: '我的订单', requiresAuth: true },
      },
      {
        path: 'favorites',
        name: 'UserFavorites',
        component: () => import('@/pages/user/Favorites.vue'),
        meta: { title: '我的收藏', requiresAuth: true },
      },
      {
        path: 'comments',
        name: 'UserComments',
        component: () => import('@/pages/user/Comments.vue'),
        meta: { title: '我的评论', requiresAuth: true },
      },
      {
        path: 'notifications',
        name: 'UserNotifications',
        component: () => import('@/pages/user/Notifications.vue'),
        meta: { title: '消息通知', requiresAuth: true },
      },
      {
        path: 'membership',
        name: 'UserMembership',
        component: () => import('@/pages/user/Membership.vue'),
        meta: { title: '会员中心', requiresAuth: true },
      },
    ],
  },
  {
    path: '/member',
    name: 'MemberCenter',
    component: () => import('@/pages/MemberCenter.vue'),
    meta: { title: '会员中心', requiresAuth: true },
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('@/pages/Settings.vue'),
    meta: { title: '设置', requiresAuth: true },
  },
  {
    path: '/about',
    name: 'About',
    component: () => import('@/pages/About.vue'),
    meta: { title: '关于' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to, from, next) => {
  document.title = (to.meta.title as string) || 'AI-CMS'
  if (to.meta.requiresAuth) {
    const token = localStorage.getItem('token')
    if (!token) {
      next({ name: 'Login', query: { redirect: to.fullPath } })
      return
    }
  }
  next()
})

export default router

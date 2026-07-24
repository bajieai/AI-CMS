<template>
  <div class="user-center">
    <!-- 子路由出口：当访问 /user/profile 等子路由时，只渲染子页面 -->
    <router-view v-if="$route.path !== '/user'" />

    <!-- 个人中心主页：仅当精确匹配 /user 时显示 -->
    <template v-else>
      <van-nav-bar title="我的" />
      <div class="user-header" v-if="userInfo">
        <van-image round width="60" height="60" :src="userInfo.avatar" />
        <div class="user-info">
          <h3>{{ userInfo.nickname || userInfo.username }}</h3>
          <span>{{ userInfo.email }}</span>
        </div>
      </div>
      <div v-else class="user-header">
        <van-button type="primary" size="small" @click="goLogin">点击登录</van-button>
      </div>
      <van-cell-group v-if="userInfo">
        <van-cell title="个人资料" is-link @click="router.push('/user/profile')" />
        <van-cell title="我的订单" is-link @click="router.push('/user/orders')" />
        <van-cell title="我的收藏" is-link @click="router.push('/user/favorites')" />
        <van-cell title="我的评论" is-link @click="router.push('/user/comments')" />
        <van-cell title="消息通知" is-link @click="router.push('/user/notifications')">
          <template #value>
            <van-badge :content="unreadCount" v-if="unreadCount > 0" />
          </template>
        </van-cell>
        <van-cell title="会员中心" is-link @click="router.push('/user/membership')" />
        <van-cell title="设置" is-link @click="router.push('/settings')" />
        <van-cell title="关于我们" is-link @click="router.push('/about')" />
      </van-cell-group>
      <van-tabbar v-model="activeTab">
        <van-tabbar-item icon="home-o" @click="router.push('/')">首页</van-tabbar-item>
        <van-tabbar-item icon="search" @click="router.push('/search')">搜索</van-tabbar-item>
        <van-tabbar-item icon="user-o">我的</van-tabbar-item>
      </van-tabbar>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import userApi from '@/api/user'
import { useUserStore } from '@/store/user'

const router = useRouter()
const userStore = useUserStore()
const activeTab = ref(2)
const userInfo = ref<any>(null)
const unreadCount = ref(0)

function goLogin() {
  router.push('/login')
}

onMounted(async () => {
  if (!userStore.isLogin()) return
  try {
    const res = await userApi.info()
    userInfo.value = res.data
    unreadCount.value = res.data.unread_count || 0
  } catch (e) {
    console.error(e)
  }
})
</script>

<style scoped lang="scss">
.user-center {
  min-height: 100vh;
  background: #f7f8fa;
  padding-bottom: 50px;
}
.user-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  background: #fff;
  margin-bottom: 8px;
  .user-info {
    h3 {
      font-size: 16px;
      margin-bottom: 4px;
    }
    span {
      font-size: 12px;
      color: #969799;
    }
  }
}
</style>

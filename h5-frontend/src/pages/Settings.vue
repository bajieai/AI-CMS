<template>
  <div class="settings">
    <van-nav-bar title="设置" left-arrow @click-left="$router.back()" />
    <van-cell-group>
      <van-cell title="清除缓存" is-link @click="clearCache" />
      <van-cell title="语言设置" is-link />
      <van-cell title="暗色模式" is-link />
      <van-cell title="字号设置" is-link />
    </van-cell-group>
    <van-cell-group>
      <van-cell title="退出登录" is-link @click="logout" />
    </van-cell-group>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { useUserStore } from '@/store/user'

const router = useRouter()
const userStore = useUserStore()

function clearCache() { localStorage.clear(); showToast('缓存已清除') }
function logout() { userStore.clearToken(); router.replace('/') }
</script>

<style scoped lang="scss">
.settings { min-height: 100vh; background: #f7f8fa; }
</style>

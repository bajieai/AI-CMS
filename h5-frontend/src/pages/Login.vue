<template>
  <div class="login">
    <van-nav-bar title="登录" />
    <div class="login-form">
      <van-field v-model="form.username" label="用户名" placeholder="用户名/邮箱/手机号" />
      <van-field v-model="form.password" type="password" label="密码" placeholder="请输入密码" />
      <van-button type="primary" block @click="handleLogin" :loading="loading">登录</van-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast } from 'vant'
import userApi from '@/api/user'
import { useUserStore } from '@/store/user'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const loading = ref(false)
const form = ref({ username: '', password: '' })

async function handleLogin() {
  if (!form.value.username || !form.value.password) {
    showToast('请填写用户名和密码')
    return
  }
  loading.value = true
  try {
    const res = await userApi.login(form.value)
    userStore.setToken(res.data.token)
    showToast('登录成功')
    const redirect = (route.query.redirect as string) || '/user'
    router.replace(redirect)
  } catch (e: any) {
    showToast(e.message || '登录失败')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
.login { min-height: 100vh; background: #f7f8fa; }
.login-form { padding: 20px; }
.van-field { margin-bottom: 12px; border-radius: 8px; overflow: hidden; }
.van-button { margin-top: 20px; border-radius: 8px; }
</style>

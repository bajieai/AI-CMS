import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import userApi from '@/api/user'
import type { UserProfile } from '@/api/user'

export const useUserStore = defineStore('user', () => {
  const token = ref(localStorage.getItem('token') || '')
  const userInfo = ref<UserProfile | null>(null)

  const isLoggedIn = computed(() => !!token.value)
  const currentUser = computed(() => userInfo.value)

  function setToken(t: string) {
    token.value = t
    localStorage.setItem('token', t)
  }

  function clearToken() {
    token.value = ''
    userInfo.value = null
    localStorage.removeItem('token')
  }

  function isLogin() {
    return !!token.value
  }

  async function login(username: string, password: string) {
    const res: any = await userApi.login({ username, password })
    if (res.data?.token) {
      setToken(res.data.token)
    }
    return res
  }

  function logout() {
    clearToken()
  }

  async function refreshUser() {
    if (!token.value) return
    try {
      const res: any = await userApi.info()
      userInfo.value = res.data
    } catch (e) {
      console.error('获取用户信息失败:', e)
    }
  }

  async function updateProfile(data: Partial<UserProfile>) {
    const res: any = await userApi.updateProfile(data)
    if (res.data) {
      userInfo.value = { ...userInfo.value, ...res.data }
    }
    return res
  }

  return {
    token,
    userInfo,
    isLoggedIn,
    currentUser,
    setToken,
    clearToken,
    isLogin,
    login,
    logout,
    refreshUser,
    updateProfile,
  }
})

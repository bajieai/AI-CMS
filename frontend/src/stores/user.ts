import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { get, post } from '@/api/request'
import type { User, LoginCredentials, AuthTokens } from '@/types'
import router from '@/router'

export const useUserStore = defineStore('user', () => {
  // State
  const userInfo = ref<User | null>(null)
  const accessToken = ref<string>(localStorage.getItem('access_token') || '')
  const refreshToken = ref<string>(localStorage.getItem('refresh_token') || '')

  // Getters
  const isAuthenticated = computed(() => !!accessToken.value)
  
  const username = computed(() => userInfo.value?.username || '')
  
  const avatar = computed(() => userInfo.value?.avatar || '')
  
  const roles = computed(() => userInfo.value?.roles || [])

  // Actions
  async function login(credentials: LoginCredentials) {
    try {
      const response = await post<AuthTokens>('/auth/login', credentials)
      const { access_token, refresh_token, token_type, expires_in } = response.data.data
      
      // Store tokens
      accessToken.value = access_token
      refreshToken.value = refresh_token
      localStorage.setItem('access_token', access_token)
      localStorage.setItem('refresh_token', refresh_token)
      
      // Fetch user info
      await fetchUserInfo()
      
      // Redirect to dashboard
      router.push('/')
      
      return true
    } catch (error) {
      throw error
    }
  }

  async function logout() {
    try {
      // Try to call logout API (optional, don't fail if API is not available)
      await post('/auth/logout').catch(() => {})
    } finally {
      // Clear all user data regardless of API result
      clearUserData()
      router.push('/login')
    }
  }

  async function fetchUserInfo() {
    if (!accessToken.value) return
    
    try {
      const response = await get<User>('/auth/me')
      userInfo.value = response.data.data
    } catch (error) {
      // If fetch fails, use stored user info if available
      const storedUserInfo = localStorage.getItem('user_info')
      if (storedUserInfo) {
        userInfo.value = JSON.parse(storedUserInfo)
      }
    }
  }

  async function refreshAccessToken() {
    if (!refreshToken.value) {
      throw new Error('No refresh token available')
    }
    
    try {
      const response = await post<AuthTokens>('/auth/refresh', {
        refresh_token: refreshToken.value
      })
      
      const { access_token, refresh_token: newRefreshToken } = response.data.data
      
      accessToken.value = access_token
      refreshToken.value = newRefreshToken
      localStorage.setItem('access_token', access_token)
      localStorage.setItem('refresh_token', newRefreshToken)
      
      return access_token
    } catch (error) {
      clearUserData()
      throw error
    }
  }

  function clearUserData() {
    userInfo.value = null
    accessToken.value = ''
    refreshToken.value = ''
    localStorage.removeItem('access_token')
    localStorage.removeItem('refresh_token')
    localStorage.removeItem('user_info')
  }

  // Initialize - fetch user info if token exists
  if (accessToken.value) {
    const storedUserInfo = localStorage.getItem('user_info')
    if (storedUserInfo) {
      userInfo.value = JSON.parse(storedUserInfo)
    }
    fetchUserInfo()
  }

  return {
    // State
    userInfo,
    accessToken,
    refreshToken,
    
    // Getters
    isAuthenticated,
    username,
    avatar,
    roles,
    
    // Actions
    login,
    logout,
    fetchUserInfo,
    refreshAccessToken
  }
})

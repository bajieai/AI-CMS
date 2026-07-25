import { useUserStore } from '@/store/user'
import { computed } from 'vue'

export function useUser() {
  const userStore = useUserStore()

  const isLoggedIn = computed(() => userStore.isLoggedIn)
  const currentUser = computed(() => userStore.currentUser)

  async function login(username: string, password: string) {
    return userStore.login(username, password)
  }

  function logout() {
    userStore.logout()
  }

  async function updateProfile(data: Parameters<typeof userStore.updateProfile>[0]) {
    return userStore.updateProfile(data)
  }

  async function refreshUser() {
    return userStore.refreshUser()
  }

  return {
    isLoggedIn,
    currentUser,
    login,
    logout,
    updateProfile,
    refreshUser,
  }
}

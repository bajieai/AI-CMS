import { ref, onMounted, onUnmounted } from 'vue'

export function useReadingProgress(contentId: number) {
  const progress = ref(0)
  const storageKey = `reading_progress_${contentId}`

  function updateProgress() {
    const scrollTop = window.scrollY
    const scrollHeight = document.documentElement.scrollHeight - window.innerHeight
    if (scrollHeight > 0) {
      progress.value = Math.min(100, Math.round((scrollTop / scrollHeight) * 100))
    }
  }

  function saveProgress() {
    try {
      localStorage.setItem(storageKey, String(progress.value))
    } catch (e) {
      console.error('保存阅读进度失败:', e)
    }
  }

  function restoreProgress() {
    try {
      const saved = localStorage.getItem(storageKey)
      if (saved) {
        const val = Number(saved)
        if (val > 0 && val < 100) {
          const scrollHeight = document.documentElement.scrollHeight - window.innerHeight
          window.scrollTo({ top: (scrollHeight * val) / 100, behavior: 'smooth' })
        }
      }
    } catch (e) {
      console.error('恢复阅读进度失败:', e)
    }
  }

  let scrollTimer: ReturnType<typeof setTimeout> | null = null

  function handleScroll() {
    if (scrollTimer) clearTimeout(scrollTimer)
    scrollTimer = setTimeout(() => {
      updateProgress()
      saveProgress()
    }, 200)
  }

  onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true })
    // 延迟恢复进度，等待内容渲染
    setTimeout(() => {
      restoreProgress()
      updateProgress()
    }, 500)
  })

  onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
    if (scrollTimer) clearTimeout(scrollTimer)
    saveProgress()
  })

  return {
    progress,
    updateProgress,
    saveProgress,
    restoreProgress,
  }
}

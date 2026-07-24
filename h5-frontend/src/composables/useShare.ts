import { ref } from 'vue'
import shareApi from '@/api/share'
import { showToast } from 'vant'

export interface ShareContent {
  id: number
  title: string
  image?: string
  url: string
}

export function useShare() {
  const sharing = ref(false)

  async function shareTo(platform: string, content: ShareContent) {
    sharing.value = true
    try {
      // 记录分享行为
      await shareApi.logShare({
        content_id: content.id,
        platform,
        title: content.title,
      })

      switch (platform) {
        case 'wechat':
          // 微信分享需要调用微信JSSDK，这里用复制链接替代
          await copyLink(content.url)
          showToast('链接已复制，请粘贴到微信中分享')
          break
        case 'qq':
          window.open(
            `https://connect.qq.com/widget/shareqq/index.html?url=${encodeURIComponent(content.url)}&title=${encodeURIComponent(content.title)}`,
            '_blank'
          )
          break
        case 'weibo':
          window.open(
            `https://service.weibo.com/share/share.php?url=${encodeURIComponent(content.url)}&title=${encodeURIComponent(content.title)}`,
            '_blank'
          )
          break
        case 'copy':
          await copyLink(content.url)
          showToast('链接已复制')
          break
        case 'poster':
          // 海报生成由组件处理
          break
      }
    } catch (e) {
      console.error('分享失败:', e)
      showToast('分享失败，请重试')
    } finally {
      sharing.value = false
    }
  }

  async function copyLink(url: string) {
    try {
      if (navigator.clipboard) {
        await navigator.clipboard.writeText(url)
      } else {
        const textarea = document.createElement('textarea')
        textarea.value = url
        textarea.style.position = 'fixed'
        textarea.style.opacity = '0'
        document.body.appendChild(textarea)
        textarea.select()
        document.execCommand('copy')
        document.body.removeChild(textarea)
      }
    } catch (e) {
      console.error('复制链接失败:', e)
    }
  }

  function generatePoster(content: ShareContent) {
    // 海报生成逻辑由 SharePoster 组件处理
    return content
  }

  return {
    sharing,
    shareTo,
    copyLink,
    generatePoster,
  }
}

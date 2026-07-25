import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Vant from 'vant'
import 'vant/lib/index.css'
import App from './App.vue'
import router from './router'
import './styles/global.scss'

// SSR 预渲染入口：检测服务端环境
const isServer = typeof window === 'undefined'

function createAppInstance() {
  const app = createApp(App)
  app.use(createPinia())
  app.use(router)
  app.use(Vant)
  return { app, router }
}

// 客户端入口：挂载到 DOM
if (!isServer) {
  const { app, router } = createAppInstance()
  router.isReady().then(() => {
    app.mount('#app')
  })
}

// SSR 入口：导出 createApp 供服务端使用
export { createAppInstance }

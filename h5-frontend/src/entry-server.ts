/**
 * SSR 服务端入口 - V2.9.39
 * 用于 vite-plugin-ssr 和 @vue/server-renderer
 */
import { renderToString } from '@vue/server-renderer'
import { createAppInstance } from './main'

export async function render(url: string) {
  const { app, router } = createAppInstance()

  // 推路由到目标 URL
  await router.push(url)
  await router.isReady()

  // 渲染为字符串
  const ctx: { [key: string]: unknown } = {}
  const html = await renderToString(app, ctx)

  return { html, state: ctx }
}

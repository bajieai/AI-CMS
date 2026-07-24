/**
 * SSR 预渲染脚本 - V2.9.39
 * 在 vite build 后执行，对预定义路由生成静态 HTML
 * 运行方式: node scripts/prerender.mjs
 */
import { createSSRApp } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createPinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import Vant from 'vant'
import { writeFile, mkdir } from 'fs/promises'
import { existsSync } from 'fs'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

const __dirname = dirname(fileURLToPath(import.meta.url))

// 预渲染路由列表
const routes = [
  '/',
  '/content',
  '/category',
  '/search',
  '/about',
  '/contact',
]

async function prerender() {
  const distDir = resolve(__dirname, '../dist')

  for (const route of routes) {
    try {
      // 动态导入构建后的组件
      const { default: App } = await import(resolve(distDir, 'assets/main.js'))
      const { default: routesConfig } = await import(resolve(distDir, 'assets/router.js'))

      const app = createSSRApp(App)
      const router = createRouter({
        history: createMemoryHistory(),
        routes: routesConfig.routes || [],
      })

      app.use(createPinia())
      app.use(router)
      app.use(Vant)

      await router.push(route)
      await router.isReady()

      const html = await renderToString(app)

      // 写入静态 HTML 文件
      const outputPath = resolve(distDir, route === '/' ? 'index.html' : `${route.slice(1)}.html`)
      const outputDir = dirname(outputPath)
      if (!existsSync(outputDir)) {
        await mkdir(outputDir, { recursive: true })
      }

      // 包装完整 HTML
      const fullHtml = `<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI-CMS</title>
  <link rel="stylesheet" href="/assets/index.css">
</head>
<body>
  <div id="app">${html}</div>
  <script type="module" src="/assets/main.js"></script>
</body>
</html>`

      await writeFile(outputPath, fullHtml, 'utf-8')
      console.log(`✓ Prerendered: ${route} -> ${outputPath}`)
    } catch (err) {
      console.warn(`⚠ Skipped: ${route} (${err.message})`)
    }
  }

  console.log('\nPrerendering complete.')
}

prerender().catch(console.error)

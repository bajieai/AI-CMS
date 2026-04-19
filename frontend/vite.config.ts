import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src')
    }
  },
  server: {
    port: 3000,
    host: '0.0.0.0',
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8080',
        changeOrigin: true,
        // 关键：确保所有header（包括Authorization）都透传给后端
        secure: false,
        configure: (proxy, options) => {
          // 不修改任何请求头，原样透传
          options.headers = { ...options.headers };
        }
      }
    }
  },
  build: {
    chunkSizeWarningLimit: 1000
  }
})

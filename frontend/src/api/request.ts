import axios from 'axios'
import type { AxiosInstance, AxiosRequestConfig, AxiosResponse, AxiosError } from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'
import type { ApiResponse } from '@/types'

const BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'

const instance: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Request interceptor - Add auth token
instance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('access_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor - Handle errors
instance.interceptors.response.use(
  (response: AxiosResponse<ApiResponse>) => {
    const { code, message } = response.data
    
    // Handle business error codes
    if (code !== 200 && code !== 0) {
      ElMessage.error(message || '请求失败')
      return Promise.reject(new Error(message || '请求失败'))
    }
    
    return response
  },
  async (error: AxiosError<ApiResponse>) => {
    if (error.response) {
      const { status, data } = error.response
      
      switch (status) {
        case 401:
          // Try to refresh token
          const refreshToken = localStorage.getItem('refresh_token')
          if (refreshToken) {
            try {
              const response = await axios.post(`${BASE_URL}/auth/refresh`, {
                refresh_token: refreshToken
              })
              const { access_token, refresh_token: newRefreshToken } = response.data.data
              localStorage.setItem('access_token', access_token)
              localStorage.setItem('refresh_token', newRefreshToken)
              
              // Retry original request
              if (error.config) {
                error.config.headers.Authorization = `Bearer ${access_token}`
                return instance(error.config)
              }
            } catch {
              // Refresh failed, redirect to login
              localStorage.removeItem('access_token')
              localStorage.removeItem('refresh_token')
              router.push('/login')
            }
          } else {
            router.push('/login')
          }
          break
        case 403:
          ElMessage.error('没有权限访问')
          break
        case 404:
          ElMessage.error('资源不存在')
          break
        case 500:
          ElMessage.error('服务器错误')
          break
        default:
          ElMessage.error(data?.message || '请求失败')
      }
    } else if (error.request) {
      ElMessage.error('网络错误，请检查网络连接')
    } else {
      ElMessage.error('请求配置错误')
    }
    
    return Promise.reject(error)
  }
)

// GET request
export function get<T = any>(
  url: string,
  params?: Record<string, any>,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> {
  return instance.get(url, { params, ...config })
}

// POST request
export function post<T = any>(
  url: string,
  data?: Record<string, any>,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> {
  return instance.post(url, data, config)
}

// PUT request
export function put<T = any>(
  url: string,
  data?: Record<string, any>,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> {
  return instance.put(url, data, config)
}

// DELETE request
export function del<T = any>(
  url: string,
  params?: Record<string, any>,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> {
  return instance.delete(url, { params, ...config })
}

// SSE streaming request for AI generation
export function createSSEStream(
  url: string,
  data: Record<string, any>,
  onMessage: (content: string) => void,
  onComplete?: () => void,
  onError?: (error: Error) => void
): { abort: () => void } {
  const token = localStorage.getItem('access_token')
  const controller = new AbortController()
  
  fetch(`${BASE_URL}${url}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {})
    },
    body: JSON.stringify(data),
    signal: controller.signal
  })
    .then(async (response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const reader = response.body?.getReader()
      if (!reader) {
        throw new Error('No response body')
      }
      
      const decoder = new TextDecoder()
      let buffer = ''
      
      while (true) {
        const { done, value } = await reader.read()
        
        if (done) break
        
        buffer += decoder.decode(value, { stream: true })
        
        // Process complete lines
        const lines = buffer.split('\n')
        buffer = lines.pop() || ''
        
        for (const line of lines) {
          if (line.startsWith('data: ')) {
            const data = line.slice(6)
            if (data === '[DONE]') {
              onComplete?.()
              return
            }
            try {
              const parsed = JSON.parse(data)
              if (parsed.content) {
                onMessage(parsed.content)
              }
            } catch {
              // Ignore parse errors for partial data
            }
          }
        }
      }
      
      onComplete?.()
    })
    .catch((error) => {
      if (error.name !== 'AbortError') {
        onError?.(error)
      }
    })
  
  return {
    abort: () => controller.abort()
  }
}

export default instance

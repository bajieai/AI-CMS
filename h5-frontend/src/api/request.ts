import axios, { AxiosInstance, AxiosRequestConfig } from 'axios'

const request: AxiosInstance = axios.create({
  baseURL: '/api/h5',
  timeout: 10000,
})

// 请求拦截器
request.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    const lang = localStorage.getItem('lang') || 'zh-cn'
    config.headers['Accept-Language'] = lang
    return config
  },
  (error) => Promise.reject(error)
)

// 响应拦截器
request.interceptors.response.use(
  (response) => {
    const { code, msg, data } = response.data
    if (code === 0) {
      return response.data
    } else if (code === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
      return Promise.reject(new Error('请先登录'))
    } else {
      return Promise.reject(new Error(msg || '请求失败'))
    }
  },
  (error) => {
    if (error.response?.status === 429) {
      return Promise.reject(new Error('请求过于频繁，请稍后再试'))
    }
    return Promise.reject(error)
  }
)

export default request
export type { AxiosRequestConfig }

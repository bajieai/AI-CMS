// User Types
export interface User {
  id: number
  username: string
  email: string
  avatar?: string
  roles: string[]
  created_at: string
}

// Content Types
export interface Article {
  id: number
  title: string
  content: string
  excerpt?: string
  thumbnail?: string
  category_id: number
  category_name?: string
  author_id: number
  author_name?: string
  status: 'draft' | 'published' | 'archived'
  view_count: number
  created_at: string
  updated_at: string
  tags?: Tag[]
}

// Category Types
export interface Category {
  id: number
  name: string
  slug: string
  description?: string
  parent_id?: number
  article_count: number
  created_at: string
}

// Tag Types
export interface Tag {
  id: number
  name: string
  slug: string
  article_count: number
  created_at: string
}

// AI Task Types
export interface AiTask {
  id: number
  type: 'generate' | 'rewrite' | 'summarize' | 'translate'
  prompt: string
  model?: string
  temperature?: number
  style?: string
  status: 'pending' | 'processing' | 'completed' | 'failed'
  result?: string
  error?: string
  created_at: string
  completed_at?: string
}

// Media Types
export interface Media {
  id: number
  filename: string
  url: string
  thumbnail_url?: string
  mime_type: string
  size: number
  width?: number
  height?: number
  created_at: string
}

// API Response Types
export interface ApiResponse<T = any> {
  code: number
  message: string
  data: T
}

// Pagination Types
export interface Pagination {
  page: number
  page_size: number
  total: number
  total_pages: number
}

// Paginated Response
export interface PaginatedResponse<T> {
  items: T[]
  pagination: Pagination
}

// Login Credentials
export interface LoginCredentials {
  username: string
  password: string
  remember?: boolean
}

// Auth Tokens
export interface AuthTokens {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
}

// AI Generate Request
export interface AiGenerateRequest {
  prompt: string
  type: 'generate' | 'rewrite' | 'summarize' | 'translate'
  model?: string
  temperature?: number
  style?: string
}

// AI Generate Response
export interface AiGenerateResponse {
  content: string
  model: string
  usage?: {
    prompt_tokens: number
    completion_tokens: number
    total_tokens: number
  }
}

// Dashboard Stats
export interface DashboardStats {
  total_contents: number
  today_new_contents: number
  ai_usage_count: number
  total_views: number
  contents_trend: number[]
  ai_trend: number[]
}

// System Settings
export interface SystemSettings {
  site_name: string
  site_url: string
  site_description?: string
  timezone: string
  language: string
}

// AI Model Config
export interface AiModelConfig {
  id: number
  name: string
  endpoint: string
  api_key?: string
  status: 'active' | 'inactive'
  is_default: boolean
  models: string[]
}

// SEO Settings
export interface SeoSettings {
  title_format: string
  keyword_separator: string
  sitemap_enabled: boolean
  baidu_push_api?: string
}

// SMTP Settings
export interface SmtpSettings {
  host: string
  port: number
  username: string
  password: string
  from_email: string
  from_name: string
  use_ssl: boolean
}

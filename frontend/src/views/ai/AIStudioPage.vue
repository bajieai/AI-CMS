<template>
  <div class="h-[calc(100vh-8rem)] flex gap-6">
    <!-- Left: Chat Area (75%) -->
    <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <!-- Chat Header -->
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">AI 工作室</h2>
          <p class="text-sm text-gray-500">智能内容创作助手</p>
        </div>
        <div class="flex items-center gap-2">
          <el-tag v-if="isStreaming" type="success" size="small" effect="plain">
            <span class="flex items-center gap-1">
              <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
              生成中...
            </span>
          </el-tag>
          <el-button size="small" @click="clearMessages">
            <Trash2 class="w-4 h-4 mr-1" />
            清空
          </el-button>
        </div>
      </div>

      <!-- Messages Area -->
      <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
        <!-- Welcome Screen -->
        <div v-if="messages.length === 0" class="h-full flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mb-4">
            <Sparkles class="w-8 h-8 text-primary" />
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">欢迎使用 AI 工作室</h3>
          <p class="text-gray-500 mb-6 max-w-md">我可以帮你生成文章、续写内容、总结摘要、翻译文本等</p>
          
          <!-- Quick Start Cards -->
          <div class="grid grid-cols-2 gap-4 max-w-xl">
            <button
              v-for="card in quickStartCards"
              :key="card.title"
              @click="applyPrompt(card.prompt)"
              class="p-4 bg-gray-50 hover:bg-primary/5 border border-gray-200 rounded-xl text-left transition-colors"
            >
              <component :is="card.icon" class="w-5 h-5 text-primary mb-2" />
              <div class="font-medium text-gray-900">{{ card.title }}</div>
              <div class="text-sm text-gray-500 mt-1">{{ card.description }}</div>
            </button>
          </div>
        </div>

        <!-- Chat Messages -->
        <div v-for="(msg, index) in messages" :key="index" class="flex gap-3" :class="msg.role === 'user' ? 'flex-row-reverse' : ''">
          <!-- Avatar -->
          <div
            :class="[
              'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
              msg.role === 'user' ? 'bg-primary' : 'bg-gray-100'
            ]"
          >
            <User v-if="msg.role === 'user'" class="w-4 h-4 text-white" />
            <Sparkles v-else class="w-4 h-4 text-gray-600" />
          </div>

          <!-- Message Content -->
          <div :class="['max-w-[75%]', msg.role === 'user' ? 'items-end' : 'items-start']" class="flex flex-col">
            <div
              class="chat-bubble"
              :class="msg.role === 'user' ? 'user' : 'assistant'"
            >
              <div v-if="msg.role === 'assistant' && msg.isStreaming" class="chat-content" v-html="renderMarkdown(msg.content + '▊')"></div>
              <div v-else class="chat-content" v-html="renderMarkdown(msg.content)"></div>
            </div>
            <span class="text-xs text-gray-400 mt-1 px-1">
              {{ msg.timestamp }}
            </span>
          </div>
        </div>
      </div>

      <!-- Input Area -->
      <div class="p-4 border-t border-gray-100">
        <!-- Parameters -->
        <div class="flex items-center gap-6 mb-3 text-sm">
          <div class="flex items-center gap-2">
            <span class="text-gray-500">温度:</span>
            <el-slider v-model="params.temperature" :min="0" :max="1" :step="0.1" class="w-24" />
            <span class="text-gray-700 w-8">{{ params.temperature }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-gray-500">风格:</span>
            <el-select v-model="params.style" class="w-28">
              <el-option label="标准" value="standard" />
              <el-option label="专业" value="professional" />
              <el-option label="简洁" value="concise" />
              <el-option label="创意" value="creative" />
            </el-select>
          </div>
          <div class="flex items-center gap-2">
            <el-switch v-model="params.stream" active-text="SSE" inactive-text="同步" />
          </div>
        </div>

        <!-- Text Input -->
        <div class="flex gap-3">
          <el-input
            v-model="inputText"
            type="textarea"
            :rows="2"
            placeholder="输入你的问题或指令... (Enter 发送，Shift+Enter 换行)"
            @keydown.enter.exact.prevent="handleSend"
            @keydown.enter.shift.exact="handleShiftEnter"
            resize="none"
          />
          <el-button type="primary" :loading="isStreaming" @click="handleSend" class="h-full px-6">
            <Send v-if="!isStreaming" class="w-4 h-4" />
            <span v-else>生成中</span>
          </el-button>
        </div>
      </div>
    </div>

    <!-- Right: Side Panel (25%) -->
    <div class="w-80 flex flex-col gap-6">
      <!-- Task Queue -->
      <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900">任务队列</h3>
        </div>
        <div class="p-3 space-y-2 max-h-48 overflow-y-auto">
          <div
            v-for="task in taskQueue"
            :key="task.id"
            class="p-3 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center justify-between mb-1">
              <span class="text-sm font-medium text-gray-700">{{ task.type }}</span>
              <el-tag :type="getTaskStatusType(task.status)" size="small">
                {{ getTaskStatusLabel(task.status) }}
              </el-tag>
            </div>
            <div class="text-xs text-gray-500 truncate">{{ task.prompt }}</div>
          </div>
          <div v-if="taskQueue.length === 0" class="text-center text-gray-400 py-4">
            暂无任务
          </div>
        </div>
      </div>

      <!-- Prompt Templates -->
      <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900">Prompt 模板</h3>
        </div>
        <div class="p-3 space-y-2 overflow-y-auto max-h-64">
          <button
            v-for="template in promptTemplates"
            :key="template.name"
            @click="applyPrompt(template.prompt)"
            class="w-full p-3 bg-gray-50 hover:bg-primary/5 border border-gray-200 rounded-lg text-left transition-colors"
          >
            <div class="text-sm font-medium text-gray-700">{{ template.name }}</div>
            <div class="text-xs text-gray-500 mt-1 truncate">{{ template.description }}</div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, nextTick, onUnmounted } from 'vue'
import { marked } from 'marked'
import { ElMessage } from 'element-plus'
import { createSSEStream } from '@/api/request'
import { post } from '@/api/request'
import {
  Sparkles,
  User,
  Send,
  Trash2,
  FileText,
  RefreshCw,
  FileEdit,
  Languages
} from 'lucide-vue-next'
import type { AiTask } from '@/types'

// State
const inputText = ref('')
const messages = ref<Array<{
  role: 'user' | 'assistant'
  content: string
  timestamp: string
  isStreaming?: boolean
}>>([])
const isStreaming = ref(false)
const messagesContainer = ref<HTMLElement>()

// Task queue
const taskQueue = ref<AiTask[]>([])

// Params
const params = reactive({
  temperature: 0.7,
  style: 'standard',
  type: 'generate' as 'generate' | 'rewrite' | 'summarize' | 'translate',
  stream: true
})

// Quick start cards
const quickStartCards = [
  { title: '生成文章', description: '根据主题生成完整文章', icon: FileText, prompt: '请帮我写一篇关于' },
  { title: '内容续写', description: '续写现有内容', icon: RefreshCw, prompt: '请帮我续写以下内容：' },
  { title: '信息摘要', description: '提取关键信息', icon: FileText, prompt: '请帮我总结以下内容的要点：' },
  { title: '文本翻译', description: '多语言翻译', icon: Languages, prompt: '请帮我翻译以下内容：' }
]

// Prompt templates
const promptTemplates = [
  { name: '技术博客', description: '生成技术教程类文章', prompt: '请帮我写一篇技术教程，主题是' },
  { name: '产品介绍', description: '生成产品描述文案', prompt: '请帮我写一段产品介绍，重点突出' },
  { name: 'SEO优化', description: '生成SEO友好的内容', prompt: '请帮我写一篇SEO优化的信息，关键词是' },
  { name: '社交媒体', description: '生成社交平台文案', prompt: '请帮我写一条适合社交媒体发布的推文，关于' }
]

// Render markdown
const renderMarkdown = (text: string) => {
  // Remove typing indicator
  text = text.replace('▊', '')
  try {
    return marked.parse(text) as string
  } catch {
    return text
  }
}

// Get task status type
const getTaskStatusType = (status: string) => {
  const types: Record<string, any> = {
    pending: 'info',
    processing: 'warning',
    completed: 'success',
    failed: 'danger'
  }
  return types[status] || 'info'
}

const getTaskStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: '等待中',
    processing: '进行中',
    completed: '已完成',
    failed: '失败'
  }
  return labels[status] || status
}

// Scroll to bottom
const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

// Apply prompt
const applyPrompt = (prompt: string) => {
  inputText.value = prompt
}

// Handle send message
const handleSend = async () => {
  const text = inputText.value.trim()
  if (!text || isStreaming.value) return

  // Add user message
  messages.value.push({
    role: 'user',
    content: text,
    timestamp: new Date().toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' })
  })

  inputText.value = ''
  scrollToBottom()

  // Add assistant message placeholder
  const assistantIndex = messages.value.length
  messages.value.push({
    role: 'assistant',
    content: '',
    timestamp: new Date().toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' }),
    isStreaming: true
  })

  isStreaming.value = true

  // Add to task queue
  const task: AiTask = {
    id: Date.now(),
    type: params.type,
    prompt: text,
    status: 'processing',
    created_at: new Date().toISOString()
  }
  taskQueue.value.unshift(task)

  if (params.stream) {
    // SSE streaming mode
    let fullContent = ''
    
    const stream = createSSEStream(
      '/ai/generate-stream',
      {
        prompt: text,
        type: params.type,
        temperature: params.temperature,
        style: params.style
      },
      (content) => {
        fullContent += content
        messages.value[assistantIndex].content = fullContent
        scrollToBottom()
      },
      () => {
        messages.value[assistantIndex].isStreaming = false
        isStreaming.value = false
        task.status = 'completed'
        task.result = fullContent
        task.completed_at = new Date().toISOString()
      },
      (error) => {
        messages.value[assistantIndex].content = '抱歉，发生了错误：' + error.message
        messages.value[assistantIndex].isStreaming = false
        isStreaming.value = false
        task.status = 'failed'
        task.error = error.message
      }
    )
  } else {
    // Synchronous mode
    try {
      const response = await post<{ content: string }>('/ai/generate', {
        prompt: text,
        type: params.type,
        temperature: params.temperature,
        style: params.style
      })
      
      messages.value[assistantIndex].content = response.data.data.content
      messages.value[assistantIndex].isStreaming = false
      task.status = 'completed'
      task.result = response.data.data.content
      task.completed_at = new Date().toISOString()
    } catch (error: any) {
      messages.value[assistantIndex].content = '抱歉，发生了错误：' + (error.message || '未知错误')
      messages.value[assistantIndex].isStreaming = false
      task.status = 'failed'
      task.error = error.message
    } finally {
      isStreaming.value = false
      scrollToBottom()
    }
  }
}

// Handle shift+enter
const handleShiftEnter = (e: KeyboardEvent) => {
  // Allow default behavior (newline)
}

// Clear messages
const clearMessages = () => {
  messages.value = []
}

onUnmounted(() => {
  // Cleanup if needed
})
</script>

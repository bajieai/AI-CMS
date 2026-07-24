<template>
  <div class="rich-text-renderer" v-html="sanitizedContent"></div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface RichTextOptions {
  lazyImage?: boolean
  codeHighlight?: boolean
  responsiveTable?: boolean
}

const props = withDefaults(defineProps<{
  content: string
  options?: RichTextOptions
}>(), {
  content: '',
  options: () => ({
    lazyImage: true,
    codeHighlight: true,
    responsiveTable: true,
  }),
})

// 简单 XSS 净化函数
function sanitizeHtml(html: string): string {
  // 移除 script 标签
  let result = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
  // 移除 on* 事件属性
  result = result.replace(/\son\w+\s*=\s*"[^"]*"/gi, '')
  result = result.replace(/\son\w+\s*=\s*'[^']*'/gi, '')
  // 移除 javascript: 协议
  result = result.replace(/href\s*=\s*["']javascript:[^"']*["']/gi, 'href="#"')
  result = result.replace(/src\s*=\s*["']javascript:[^"']*["']/gi, '')
  // 移除 iframe
  result = result.replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, '')
  // 移除 object/embed
  result = result.replace(/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/gi, '')
  result = result.replace(/<embed\b[^>]*>/gi, '')
  return result
}

// 处理图片懒加载
function addLazyLoading(html: string): string {
  return html.replace(/<img\b([^>]*?)\ssrc=/gi, '<img$1 loading="lazy" src=')
}

// 处理代码高亮（简单包装）
function wrapCodeBlocks(html: string): string {
  // 给 pre 标签添加类名
  return html.replace(/<pre\b([^>]*)>/gi, '<pre class="code-block"$1>')
}

// 处理表格响应式
function wrapTables(html: string): string {
  return html.replace(/<table\b/gi, '<div class="table-wrapper"><table').replace(/<\/table>/gi, '</table></div>')
}

const sanitizedContent = computed(() => {
  let result = sanitizeHtml(props.content)
  if (props.options.lazyImage) {
    result = addLazyLoading(result)
  }
  if (props.options.codeHighlight) {
    result = wrapCodeBlocks(result)
  }
  if (props.options.responsiveTable) {
    result = wrapTables(result)
  }
  return result
})
</script>

<style scoped lang="scss">
.rich-text-renderer {
  font-size: 15px;
  line-height: 1.8;
  color: $text-color;
  word-break: break-word;

  :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: $radius-sm;
    margin: 8px 0;
  }

  :deep(.code-block) {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 12px;
    border-radius: $radius-sm;
    overflow-x: auto;
    font-size: 13px;
    line-height: 1.5;
    margin: 8px 0;
    code {
      color: inherit;
      background: none;
      padding: 0;
    }
  }

  :deep(code) {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 13px;
    color: #c62828;
  }

  :deep(.table-wrapper) {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 8px 0;
    table {
      width: 100%;
      border-collapse: collapse;
      th, td {
        border: 1px solid $border-color;
        padding: 8px;
        font-size: 14px;
      }
      th {
        background: $background-color;
        font-weight: 600;
      }
    }
  }

  :deep(a) {
    color: $primary-color;
  }

  :deep(blockquote) {
    border-left: 3px solid $primary-color;
    padding-left: 12px;
    margin: 8px 0;
    color: $text-secondary;
  }

  :deep(p) {
    margin: 8px 0;
  }

  :deep(h1), :deep(h2), :deep(h3), :deep(h4) {
    margin: 16px 0 8px;
    font-weight: 600;
  }
}
</style>

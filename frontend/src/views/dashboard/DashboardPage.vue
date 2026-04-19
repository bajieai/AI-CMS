<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">仪表盘</h1>
      <p class="text-gray-500 mt-1">欢迎回来，这里是系统概览</p>
    </div>

    <!-- KPI Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="stat in stats" :key="stat.label" class="stat-card">
        <div class="stat-icon" :style="{ backgroundColor: stat.bgColor }">
          <component :is="stat.icon" class="w-6 h-6" :style="{ color: stat.color }" />
        </div>
        <div class="stat-value">{{ stat.value.toLocaleString() }}</div>
        <div class="stat-label">{{ stat.label }}</div>
        <div class="stat-trend" :class="stat.trend >= 0 ? 'up' : 'down'">
          <TrendingUp v-if="stat.trend >= 0" class="w-4 h-4" />
          <TrendingDown v-else class="w-4 h-4" />
          <span>{{ Math.abs(stat.trend) }}% 较上周</span>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- AI Usage Trend Chart -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">AI 使用趋势</h3>
        <div ref="aiTrendChartRef" class="w-full h-72"></div>
      </div>

      <!-- Category Distribution Chart -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">内容分类分布</h3>
        <div ref="categoryChartRef" class="w-full h-72"></div>
      </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="p-6 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">最近动态</h3>
      </div>
      <el-table :data="activities" stripe style="width: 100%">
        <el-table-column prop="action" label="操作" width="180">
          <template #default="{ row }">
            <el-tag :type="getActionType(row.action)" size="small">
              {{ row.action }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="target" label="目标" min-width="200">
          <template #default="{ row }">
            <span class="text-gray-900">{{ row.target }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="user" label="用户" width="120" />
        <el-table-column prop="time" label="时间" width="180" />
      </el-table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, nextTick } from 'vue'
import * as echarts from 'echarts'
import { get } from '@/api/request'
import {
  FileText,
  Clock,
  Sparkles,
  Eye,
  TrendingUp,
  TrendingDown
} from 'lucide-vue-next'

// Stats data - initialized with defaults, will be updated by API
interface StatItem {
  label: string
  value: number
  icon: any
  bgColor: string
  color: string
  trend: number
}
const stats = reactive<StatItem[]>([
  {
    label: '总信息数',
    value: 0,
    icon: FileText,
    bgColor: '#EEF2FF',
    color: '#4F46E5',
    trend: 0
  },
  {
    label: '今日新增',
    value: 0,
    icon: Clock,
    bgColor: '#FEF3C7',
    color: '#D97706',
    trend: 0
  },
  {
    label: 'AI 调用次数',
    value: 0,
    icon: Sparkles,
    bgColor: '#FCE7F3',
    color: '#DB2777',
    trend: 0
  },
  {
    label: '总浏览量',
    value: 0,
    icon: Eye,
    bgColor: '#DCFCE7',
    color: '#16A34A',
    trend: 0
  }
])

// Activities data
const activities = ref<any[]>([])

// Chart refs
const aiTrendChartRef = ref<HTMLElement>()
const categoryChartRef = ref<HTMLElement>()

// Charts instances
let aiTrendChart: echarts.ECharts | null = null
let categoryChart: echarts.ECharts | null = null

const getActionType = (action: string) => {
  const types: Record<string, string> = {
    'create': 'primary',
    'update': 'warning',
    'delete': 'danger',
    'publish': 'success',
    'upload': 'info',
    'login': 'info'
  }
  return types[action.toLowerCase()] || 'info'
}

// Format relative time
function formatTime(timeStr: string): string {
  if (!timeStr) return ''
  try {
    const now = Date.now()
    const target = new Date(timeStr).getTime()
    const diff = now - target
    const minutes = Math.floor(diff / 60000)
    const hours = Math.floor(diff / 3600000)
    const days = Math.floor(diff / 86400000)

    if (minutes < 1) return '刚刚'
    if (minutes < 60) return `${minutes}分钟前`
    if (hours < 24) return `${hours}小时前`
    return `${days}天前`
  } catch {
    return timeStr
  }
}

// Initialize AI Trend Chart with dynamic data
const initAiTrendChart = (trendData?: any[]) => {
  if (!aiTrendChartRef.value) return

  if (aiTrendChart) {
    aiTrendChart.dispose()
  }

  aiTrendChart = echarts.init(aiTrendChartRef.value)

  // Use API data or fallback defaults
  const days = trendData || []
  const dayLabels = days.map((d: any) => d.day || '')
  const dayValues = days.map((d: any) => d.count || 0)

  // Fallback to mock data if no real data
  const labels = dayLabels.length > 0 ? dayLabels : ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
  const values = dayValues.length > 0 ? dayValues : [820, 932, 901, 1234, 1290, 1330, 1520]

  const option: echarts.EChartsOption = {
    tooltip: {
      trigger: 'axis'
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      top: '10%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: labels
    },
    yAxis: {
      type: 'value'
    },
    series: [
      {
        name: '新增信息',
        type: 'line',
        smooth: true,
        symbol: 'circle',
        symbolSize: 8,
        itemStyle: {
          color: '#DB2777'
        },
        lineStyle: {
          width: 3
        },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(219, 39, 119, 0.3)' },
            { offset: 1, color: 'rgba(219, 39, 119, 0.05)' }
          ])
        },
        data: values
      }
    ]
  }

  aiTrendChart.setOption(option)
}

// Initialize Category Distribution Chart with dynamic data
const initCategoryChart = (categoryData?: any[]) => {
  if (!categoryChartRef.value) return

  if (categoryChart) {
    categoryChart.dispose()
  }

  categoryChart = echarts.init(categoryChartRef.value)

  // Use API data or fallback defaults
  const pieData = Array.isArray(categoryData) && categoryData.length > 0
    ? categoryData.map((item, index) => ({
        value: item.value || 0,
        name: item.name || `分类${index + 1}`,
        itemStyle: { color: ['#4F46E5', '#7C3AED', '#DB2777', '#059669', '#D97706'][index % 5] }
      }))
    : [
        { value: 335, name: '技术教程', itemStyle: { color: '#4F46E5' } },
        { value: 234, name: '产品介绍', itemStyle: { color: '#7C3AED' } },
        { value: 154, name: '新闻资讯', itemStyle: { color: '#DB2777' } },
        { value: 135, name: '用户指南', itemStyle: { color: '#059669' } },
        { value: 148, name: '行业报告', itemStyle: { color: '#D97706' } }
      ]

  const option: echarts.EChartsOption = {
    tooltip: {
      trigger: 'item',
      formatter: '{b}: {c} ({d}%)'
    },
    legend: {
      orient: 'vertical',
      left: 'left'
    },
    series: [
      {
        type: 'pie',
        radius: ['40%', '70%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 10,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false,
          position: 'center'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 16,
            fontWeight: 'bold'
          }
        },
        labelLine: {
          show: false
        },
        data: pieData
      }
    ]
  }

  categoryChart.setOption(option)
}

// Resize charts
const handleResize = () => {
  aiTrendChart?.resize()
  categoryChart?.resize()
}

// Fetch dashboard data from real API
const fetchDashboardData = async () => {
  try {
    // Fetch main stats and chart data
    const response = await get<any>('/dashboard/stats')
    const data = response.data?.data

    if (data) {
      // Update KPI stats
      stats[0].value = data.total_contents ?? 0
      stats[1].value = data.today_new_contents ?? 0
      stats[2].value = data.ai_usage_count ?? 0
      stats[3].value = data.total_views ?? 0

      // Update charts with real data
      nextTick(() => {
        if (data.weekly_trend && Array.isArray(data.weekly_trend)) {
          initAiTrendChart(data.weekly_trend)
        }
        if (data.category_distribution && Array.isArray(data.category_distribution)) {
          initCategoryChart(data.category_distribution)
        }
      })
    }
  } catch (error) {
    // Keep using mock/default values
  }

  // Fetch recent activities separately
  try {
    const activityRes = await get<any[]>('/dashboard/recent-activities?limit=10')
    activities.value = activityRes.data?.data ?? []
  } catch (error) {
    console.warn('Failed to fetch recent activities:', error)
    // Keep empty or use fallback
    activities.value = []
  }
}

onMounted(() => {
  // Initialize charts first with default/mock data
  nextTick(() => {
    initAiTrendChart()
    initCategoryChart()
  })

  // Then fetch real data from API
  fetchDashboardData()

  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  aiTrendChart?.dispose()
  categoryChart?.dispose()
  window.removeEventListener('resize', handleResize)
})
</script>

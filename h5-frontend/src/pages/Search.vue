<template>
  <div class="search-page">
    <van-search v-model="keyword" placeholder="搜索内容" show-action @search="onSearch">
      <template #action>
        <span @click="onSearch">搜索</span>
      </template>
    </van-search>
    <div v-if="hotList.length" class="hot-search">
      <van-cell title="热门搜索" />
      <van-tag v-for="item in hotList" :key="item.keyword" plain type="primary" @click="keyword = item.keyword; onSearch()">
        {{ item.keyword }}
      </van-tag>
    </div>
    <van-list v-if="results.length" v-model:loading="loading" :finished="finished" @load="onLoad">
      <div v-for="item in results" :key="item.id" class="result-item" @click="$router.push(`/content/detail/${item.id}`)">
        <h3>{{ item.title }}</h3>
        <p>{{ item.description }}</p>
      </div>
    </van-list>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import contentApi from '@/api/content'

const keyword = ref('')
const results = ref<any[]>([])
const hotList = ref<any[]>([])
const loading = ref(false)
const finished = ref(false)
const page = ref(1)

onMounted(async () => {
  try {
    const res = await contentApi.hotSearch()
    hotList.value = res.data || []
  } catch (e) { console.error(e) }
})

async function onSearch() {
  if (!keyword.value.trim()) return
  page.value = 1
  results.value = []
  finished.value = false
  await onLoad()
}

async function onLoad() {
  if (!keyword.value.trim()) { loading.value = false; return }
  try {
    const res = await contentApi.search({ keyword: keyword.value, page: page.value, limit: 10 })
    results.value.push(...(res.data.list || []))
    loading.value = false
    if (page.value * 10 >= res.data.total) finished.value = true
    else page.value++
  } catch (e) { loading.value = false; finished.value = true }
}
</script>

<style scoped lang="scss">
.search-page { min-height: 100vh; background: #f7f8fa; }
.hot-search { padding: 12px; display: flex; flex-wrap: wrap; gap: 8px; background: #fff; margin-bottom: 8px; }
.result-item { padding: 12px; background: #fff; margin-bottom: 8px;
  h3 { font-size: 14px; margin-bottom: 4px; }
  p { font-size: 12px; color: #969799; }
}
</style>

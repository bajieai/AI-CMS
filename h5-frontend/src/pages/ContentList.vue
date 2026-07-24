<template>
  <div class="content-list">
    <van-nav-bar title="内容列表" left-arrow @click-left="$router.back()" />
    <van-list v-model:loading="loading" :finished="finished" @load="onLoad">
      <div v-for="item in list" :key="item.id" class="content-card" @click="goToDetail(item.id)">
        <img v-if="item.cover" :src="item.cover" class="card-cover" />
        <div class="card-body">
          <h3 class="card-title">{{ item.title }}</h3>
          <p class="card-desc">{{ item.description }}</p>
        </div>
      </div>
    </van-list>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import contentApi from '@/api/content'

const route = useRoute()
const router = useRouter()
const list = ref<any[]>([])
const loading = ref(false)
const finished = ref(false)
const page = ref(1)

async function onLoad() {
  try {
    const res = await contentApi.getContentList({
      page: page.value,
      limit: 10,
      category_id: Number(route.query.category_id) || 0,
    })
    list.value.push(...(res.data.list || []))
    loading.value = false
    if (page.value * 10 >= res.data.total) {
      finished.value = true
    } else {
      page.value++
    }
  } catch (e) {
    loading.value = false
    finished.value = true
  }
}

function goToDetail(id: number) {
  router.push(`/content/detail/${id}`)
}
</script>

<style scoped lang="scss">
.content-list { min-height: 100vh; background: #f7f8fa; }
.content-card {
  display: flex; gap: 12px; padding: 12px; background: #fff; margin-bottom: 8px;
  .card-cover { width: 100px; height: 70px; border-radius: 6px; object-fit: cover; }
  .card-body { flex: 1; display: flex; flex-direction: column; justify-content: center; }
  .card-title { font-size: 14px; font-weight: 500; margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .card-desc { font-size: 12px; color: #969799; }
}
</style>

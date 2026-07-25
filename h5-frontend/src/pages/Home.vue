<template>
  <div class="home">
    <van-swipe :autoplay="3000" class="banner-swipe">
      <van-swipe-item v-for="banner in banners" :key="banner.id">
        <img :src="banner.image" :alt="banner.title" class="banner-img" />
      </van-swipe-item>
    </van-swipe>

    <div class="category-nav">
      <div v-for="cat in categories" :key="cat.id" class="category-item" @click="goToList(cat.id)">
        <van-icon :name="cat.icon || 'apps-o'" size="28" color="#1989fa" />
        <span>{{ cat.name }}</span>
      </div>
    </div>

    <van-cell title="推荐内容" is-link value="更多" @click="goToList(0, 'recommend')" />
    <div class="content-list">
      <div v-for="item in recommend" :key="item.id" class="content-card" @click="goToDetail(item.id)">
        <img v-if="item.cover" :src="item.cover" class="card-cover" />
        <div class="card-body">
          <h3 class="card-title">{{ item.title }}</h3>
          <p class="card-desc">{{ item.description }}</p>
        </div>
      </div>
    </div>

    <van-cell title="热门内容" is-link value="更多" @click="goToList(0, 'hot')" />
    <div class="content-list">
      <div v-for="item in hot" :key="item.id" class="content-card" @click="goToDetail(item.id)">
        <img v-if="item.cover" :src="item.cover" class="card-cover" />
        <div class="card-body">
          <h3 class="card-title">{{ item.title }}</h3>
          <p class="card-desc">{{ item.description }}</p>
          <span class="card-views">{{ item.view_count }} 阅读</span>
        </div>
      </div>
    </div>

    <van-tabbar v-model="activeTab">
      <van-tabbar-item icon="home-o" @click="$router.push('/')">首页</van-tabbar-item>
      <van-tabbar-item icon="search" @click="$router.push('/search')">搜索</van-tabbar-item>
      <van-tabbar-item icon="user-o" @click="$router.push('/user')">我的</van-tabbar-item>
    </van-tabbar>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import contentApi from '@/api/content'

const router = useRouter()
const activeTab = ref(0)
const banners = ref<any[]>([])
const recommend = ref<any[]>([])
const hot = ref<any[]>([])
const categories = ref<any[]>([])

onMounted(async () => {
  try {
    const res = await contentApi.getHome()
    banners.value = res.data.banners || []
    recommend.value = res.data.recommend || []
    hot.value = res.data.hot || []
    categories.value = res.data.categories || []
  } catch (e) {
    console.error('加载首页失败:', e)
  }
})

function goToList(categoryId: number, sort?: string) {
  router.push({ path: '/content/list', query: { category_id: categoryId, sort } })
}

function goToDetail(id: number) {
  router.push(`/content/detail/${id}`)
}
</script>

<style scoped lang="scss">
.home { padding-bottom: 50px; }
.banner-swipe { height: 180px; }
.banner-img { width: 100%; height: 180px; object-fit: cover; }
.category-nav {
  display: flex; flex-wrap: wrap; padding: 12px; background: #fff; margin-bottom: 8px;
  .category-item {
    width: 25%; display: flex; flex-direction: column; align-items: center; gap: 4px;
    margin-bottom: 12px; font-size: 12px; color: #323233;
  }
}
.content-list { background: #fff; padding: 0 12px; margin-bottom: 8px; }
.content-card {
  display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #ebedf0;
  &:last-child { border-bottom: none; }
  .card-cover { width: 100px; height: 70px; border-radius: 6px; object-fit: cover; }
  .card-body { flex: 1; display: flex; flex-direction: column; justify-content: center; }
  .card-title { font-size: 14px; font-weight: 500; margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .card-desc { font-size: 12px; color: #969799; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
  .card-views { font-size: 11px; color: #969799; margin-top: 4px; }
}
</style>

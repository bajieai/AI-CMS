<template>
  <div class="related-content">
    <div class="section-title" v-if="title">{{ title }}</div>
    <div class="scroll-container">
      <div
        v-for="item in items"
        :key="item.id"
        class="related-card"
        @click="goToDetail(item.id)"
      >
        <van-image
          v-if="item.image"
          :src="item.image"
          fit="cover"
          width="100%"
          height="80"
          radius="6"
        />
        <div class="card-title">{{ item.title }}</div>
        <div class="card-time" v-if="item.create_time">{{ item.create_time }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

interface RelatedItem {
  id: number
  title: string
  image?: string
  create_time?: string
}

const props = withDefaults(defineProps<{
  items: RelatedItem[]
  title?: string
}>(), {
  items: () => [],
  title: '相关推荐',
})

const router = useRouter()

function goToDetail(id: number) {
  router.push(`/content/detail/${id}`)
}
</script>

<style scoped lang="scss">
.related-content {
  margin: 16px 0;
}
.section-title {
  font-size: $font-size-lg;
  font-weight: 600;
  padding: 0 16px;
  margin-bottom: 12px;
  color: $text-color;
}
.scroll-container {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding: 0 16px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  &::-webkit-scrollbar { display: none; }
}
.related-card {
  flex-shrink: 0;
  width: 140px;
  background: #fff;
  border-radius: $radius-md;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);

  .card-title {
    font-size: $font-size-sm;
    line-height: 1.4;
    padding: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: $text-color;
  }
  .card-time {
    font-size: 11px;
    color: $text-secondary;
    padding: 0 8px 8px;
  }
}
</style>

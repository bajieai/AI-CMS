<template>
  <div class="skeleton-wrapper" :class="`skeleton-${type}`">
    <van-skeleton title :row="rowConfig" :loading="true">
      <template #template>
        <!-- 文本骨架 -->
        <template v-if="type === 'text'">
          <div class="skeleton-text-row" v-for="i in 3" :key="i" :style="{ width: getWidth(i) }"></div>
        </template>
        <!-- 头像骨架 -->
        <template v-else-if="type === 'avatar'">
          <div class="skeleton-avatar">
            <van-skeleton-avatar />
            <div class="avatar-info">
              <div class="skeleton-line" style="width: 60%; height: 16px;"></div>
              <div class="skeleton-line" style="width: 40%; height: 12px; margin-top: 8px;"></div>
            </div>
          </div>
        </template>
        <!-- 卡片骨架 -->
        <template v-else-if="type === 'card'">
          <div class="skeleton-card">
            <div class="skeleton-card-cover"></div>
            <div class="skeleton-card-body">
              <div class="skeleton-line" style="width: 80%; height: 16px;"></div>
              <div class="skeleton-line" style="width: 60%; height: 12px; margin-top: 8px;"></div>
            </div>
          </div>
        </template>
        <!-- 列表骨架 -->
        <template v-else-if="type === 'list'">
          <div class="skeleton-list-item" v-for="i in 5" :key="i">
            <div class="skeleton-thumb"></div>
            <div class="skeleton-content">
              <div class="skeleton-line" style="width: 70%; height: 14px;"></div>
              <div class="skeleton-line" style="width: 50%; height: 12px; margin-top: 6px;"></div>
              <div class="skeleton-line" style="width: 30%; height: 12px; margin-top: 6px;"></div>
            </div>
          </div>
        </template>
      </template>
    </van-skeleton>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type SkeletonType = 'text' | 'avatar' | 'card' | 'list'

const props = withDefaults(defineProps<{
  type?: SkeletonType
}>(), {
  type: 'text',
})

const rowConfig = computed(() => {
  switch (props.type) {
    case 'text': return 3
    case 'avatar': return 0
    case 'card': return 2
    case 'list': return 0
    default: return 3
  }
})

function getWidth(index: number): string {
  const widths = ['100%', '90%', '70%']
  return widths[(index - 1) % 3]
}
</script>

<style scoped lang="scss">
.skeleton-wrapper {
  width: 100%;
  padding: 16px;
}

.skeleton-text-row {
  height: 14px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e6e6e6 37%, #f0f0f0 63%);
  background-size: 400% 100%;
  border-radius: 4px;
  margin-bottom: 10px;
  animation: skeleton-loading 1.4s ease infinite;
}

.skeleton-line {
  background: linear-gradient(90deg, #f0f0f0 25%, #e6e6e6 37%, #f0f0f0 63%);
  background-size: 400% 100%;
  border-radius: 4px;
  animation: skeleton-loading 1.4s ease infinite;
}

.skeleton-avatar {
  display: flex;
  align-items: center;
  gap: 12px;
  .avatar-info {
    flex: 1;
  }
}

.skeleton-card {
  .skeleton-card-cover {
    width: 100%;
    height: 180px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e6e6e6 37%, #f0f0f0 63%);
    background-size: 400% 100%;
    border-radius: $radius-md;
    margin-bottom: 12px;
    animation: skeleton-loading 1.4s ease infinite;
  }
  .skeleton-card-body {
    .skeleton-line {
      margin-bottom: 6px;
    }
  }
}

.skeleton-list-item {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  .skeleton-thumb {
    width: 100px;
    height: 70px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e6e6e6 37%, #f0f0f0 63%);
    background-size: 400% 100%;
    border-radius: $radius-sm;
    flex-shrink: 0;
    animation: skeleton-loading 1.4s ease infinite;
  }
  .skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
}

@keyframes skeleton-loading {
  0% { background-position: 100% 50%; }
  100% { background-position: 0 50%; }
}

:deep(.van-skeleton) {
  padding: 0;
}
</style>

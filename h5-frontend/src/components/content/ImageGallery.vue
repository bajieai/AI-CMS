<template>
  <div class="image-gallery">
    <div class="gallery-grid" :class="{ single: images.length === 1 }">
      <div
        v-for="(img, index) in images"
        :key="index"
        class="gallery-item"
        @click="onPreview(index)"
      >
        <van-image
          :src="img.url"
          :alt="img.alt"
          fit="cover"
          width="100%"
          height="100%"
          loading-icon="photo-o"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { showImagePreview } from 'vant'

interface GalleryImage {
  url: string
  alt?: string
  width?: number
  height?: number
}

const props = defineProps<{
  images: GalleryImage[]
}>()

const previewVisible = ref(false)
const previewIndex = ref(0)

function onPreview(index: number) {
  previewIndex.value = index
  const urls = props.images.map((img) => img.url)
  showImagePreview({
    images: urls,
    startPosition: index,
    closeable: true,
  })
}
</script>

<style scoped lang="scss">
.image-gallery {
  width: 100%;
}
.gallery-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px;

  &.single {
    grid-template-columns: 1fr;
    .gallery-item {
      padding-bottom: 0;
      height: auto;
    }
  }
}
.gallery-item {
  position: relative;
  width: 100%;
  padding-bottom: 100%;
  overflow: hidden;
  border-radius: $radius-sm;
  cursor: pointer;

  :deep(.van-image) {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }
}
</style>

<template>
  <div class="video-player">
    <div class="video-container" ref="containerRef">
      <video
        ref="videoRef"
        :src="src"
        :poster="poster"
        :autoplay="autoplay"
        playsinline
        webkit-playsinline
        @timeupdate="onTimeUpdate"
        @loadedmetadata="onLoadedMeta"
        @ended="onEnded"
        @play="playing = true"
        @pause="playing = false"
        @click="togglePlay"
      ></video>

      <!-- 自定义控件 -->
      <div class="video-controls" v-show="!playing || showControls" @click.stop>
        <div class="control-bar">
          <van-icon
            :name="playing ? 'pause' : 'play'"
            size="20"
            color="#fff"
            @click="togglePlay"
          />
          <span class="time current">{{ formatTime(currentTime) }}</span>
          <div class="progress-bar" @click="onSeek">
            <div class="progress-buffered" :style="{ width: bufferedPercent + '%' }"></div>
            <div class="progress-played" :style="{ width: playedPercent + '%' }"></div>
          </div>
          <span class="time duration">{{ formatTime(duration) }}</span>
          <van-icon name="expand-o" size="20" color="#fff" @click="toggleFullscreen" />
        </div>
      </div>

      <!-- 播放按钮遮罩 -->
      <div class="play-overlay" v-if="!playing && !loading" @click="togglePlay">
        <van-icon name="play-circle" size="48" color="rgba(255,255,255,0.8)" />
      </div>

      <!-- 加载中 -->
      <div class="loading-overlay" v-if="loading">
        <van-loading color="#fff" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const props = withDefaults(defineProps<{
  src: string
  poster?: string
  autoplay?: boolean
}>(), {
  src: '',
  poster: '',
  autoplay: false,
})

const videoRef = ref<HTMLVideoElement | null>(null)
const containerRef = ref<HTMLDivElement | null>(null)
const playing = ref(false)
const loading = ref(false)
const showControls = ref(true)
const currentTime = ref(0)
const duration = ref(0)
const bufferedPercent = ref(0)
const playedPercent = ref(0)

let controlsTimer: ReturnType<typeof setTimeout> | null = null

function togglePlay() {
  if (!videoRef.value) return
  if (playing.value) {
    videoRef.value.pause()
  } else {
    loading.value = true
    videoRef.value.play().finally(() => {
      loading.value = false
    })
  }
}

function onTimeUpdate() {
  if (!videoRef.value) return
  currentTime.value = videoRef.value.currentTime
  playedPercent.value = duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0

  if (videoRef.value.buffered.length > 0) {
    bufferedPercent.value = (videoRef.value.buffered.end(0) / duration.value) * 100
  }

  // 自动隐藏控件
  if (playing.value) {
    if (controlsTimer) clearTimeout(controlsTimer)
    showControls.value = true
    controlsTimer = setTimeout(() => {
      showControls.value = false
    }, 3000)
  }
}

function onLoadedMeta() {
  if (!videoRef.value) return
  duration.value = videoRef.value.duration
}

function onEnded() {
  playing.value = false
  showControls.value = true
}

function onSeek(e: MouseEvent) {
  if (!videoRef.value) return
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  const percent = (e.clientX - rect.left) / rect.width
  videoRef.value.currentTime = percent * duration.value
}

function toggleFullscreen() {
  if (!containerRef.value) return
  if (document.fullscreenElement) {
    document.exitFullscreen()
  } else {
    containerRef.value.requestFullscreen()
  }
}

function formatTime(sec: number): string {
  const m = Math.floor(sec / 60)
  const s = Math.floor(sec % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
}

onMounted(() => {
  if (props.autoplay && videoRef.value) {
    videoRef.value.play().catch(() => {})
  }
})

onUnmounted(() => {
  if (controlsTimer) clearTimeout(controlsTimer)
})
</script>

<style scoped lang="scss">
.video-player {
  width: 100%;
}
.video-container {
  position: relative;
  width: 100%;
  background: #000;
  border-radius: $radius-sm;
  overflow: hidden;
  aspect-ratio: 16 / 9;

  video {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }
}
.video-controls {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
  padding: 8px 12px;
  transition: opacity 0.3s;
}
.control-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  .time {
    color: #fff;
    font-size: 12px;
    min-width: 32px;
  }
  .progress-bar {
    flex: 1;
    height: 4px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    position: relative;
    cursor: pointer;
    .progress-buffered {
      position: absolute;
      height: 100%;
      background: rgba(255, 255, 255, 0.5);
      border-radius: 2px;
    }
    .progress-played {
      position: absolute;
      height: 100%;
      background: $primary-color;
      border-radius: 2px;
    }
  }
}
.play-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.2);
}
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>

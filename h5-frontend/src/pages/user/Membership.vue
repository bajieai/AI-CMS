<template>
  <div class="membership-page">
    <van-nav-bar title="会员中心" left-arrow @click-left="$router.back()" />

    <div class="membership-card" v-if="info">
      <div class="card-header">
        <div class="level-info">
          <van-icon name="diamond" size="32" color="#ffd700" />
          <div>
            <div class="level-name">{{ info.level_name }}</div>
            <div class="level-points">{{ info.points }} 积分</div>
          </div>
        </div>
        <div class="expire-info" v-if="info.expire_at">
          <span>到期时间</span>
          <span class="expire-date">{{ info.expire_at }}</span>
        </div>
      </div>
    </div>

    <div class="benefits-section" v-if="info">
      <div class="section-title">会员权益</div>
      <van-grid :column-num="3" :gutter="8">
        <van-grid-item
          v-for="(benefit, idx) in info.benefits"
          :key="idx"
          :icon="getBenefitIcon(idx)"
          :text="benefit"
        />
      </van-grid>
    </div>

    <div class="action-section">
      <van-button type="primary" block @click="onUpgrade">
        {{ info && info.level ? '续费升级' : '开通会员' }}
      </van-button>
    </div>

    <van-skeleton v-if="!info" title :row="4" style="padding: 16px;" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import userApi, { type MembershipInfo } from '@/api/user'

const router = useRouter()
const info = ref<MembershipInfo | null>(null)

onMounted(async () => {
  try {
    const res: any = await userApi.getMembership()
    info.value = res.data
  } catch (e) {
    console.error('获取会员信息失败:', e)
  }
})

function getBenefitIcon(index: number): string {
  const icons = ['gold-coin-o', 'gift-o', 'medal-o', 'like-o', 'star-o', 'crown-o', 'fire-o', 'gem-o', 'shield-o']
  return icons[index % icons.length]
}

function onUpgrade() {
  router.push('/member')
}
</script>

<style scoped lang="scss">
.membership-page {
  min-height: 100vh;
  background: $background-color;
}
.membership-card {
  margin: 16px;
  border-radius: $radius-lg;
  background: linear-gradient(135deg, #2c2c3a, #3a3a4e);
  padding: 20px;
  color: #fff;
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .level-info {
    display: flex;
    align-items: center;
    gap: 12px;
    .level-name {
      font-size: $font-size-lg;
      font-weight: 600;
    }
    .level-points {
      font-size: $font-size-sm;
      color: #ffd700;
    }
  }
  .expire-info {
    text-align: right;
    font-size: $font-size-sm;
    span {
      display: block;
      color: rgba(255, 255, 255, 0.7);
    }
    .expire-date {
      color: #ffd700;
      margin-top: 4px;
    }
  }
}
.benefits-section {
  margin: 16px;
  background: #fff;
  border-radius: $radius-md;
  padding: 16px;
  .section-title {
    font-size: $font-size-lg;
    font-weight: 600;
    margin-bottom: 12px;
    color: $text-color;
  }
}
.action-section {
  padding: 16px;
}
</style>

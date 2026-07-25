<template>
  <div class="profile-page">
    <van-nav-bar title="个人资料" left-arrow @click-left="$router.back()" />

    <div class="avatar-section">
      <van-image round width="80" height="80" :src="form.avatar" />
      <van-uploader :after-read="onAvatarUpload" accept="image/*" :max-count="1">
        <van-button size="small" plain type="primary">更换头像</van-button>
      </van-uploader>
    </div>

    <van-cell-group inset title="基本信息">
      <van-field
        v-model="form.nickname"
        label="昵称"
        placeholder="请输入昵称"
        :rules="[{ required: true, message: '请填写昵称' }]"
      />
      <van-field
        v-model="form.email"
        label="邮箱"
        placeholder="请输入邮箱"
        type="email"
      />
      <van-field
        v-model="form.phone"
        label="手机号"
        placeholder="请输入手机号"
        type="tel"
      />
    </van-cell-group>

    <div class="submit-btn">
      <van-button type="primary" block :loading="saving" @click="onSave">
        保存修改
      </van-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { showToast, showSuccessToast } from 'vant'
import userApi, { type UserProfile } from '@/api/user'
import { useUserStore } from '@/store/user'

const userStore = useUserStore()
const saving = ref(false)

const form = ref<UserProfile>({
  id: 0,
  username: '',
  nickname: '',
  avatar: '',
  email: '',
  phone: '',
  created_at: '',
})

onMounted(async () => {
  try {
    const res: any = await userApi.getProfile()
    form.value = { ...form.value, ...res.data }
  } catch (e) {
    console.error('获取资料失败:', e)
  }
})

async function onAvatarUpload(file: { file?: File }) {
  if (!file.file) return
  try {
    const res: any = await userApi.uploadAvatar(file.file)
    if (res.data?.url) {
      form.value.avatar = res.data.url
      showToast('头像上传成功')
    }
  } catch (e) {
    console.error('头像上传失败:', e)
    showToast('头像上传失败')
  }
}

async function onSave() {
  if (!form.value.nickname) {
    showToast('请填写昵称')
    return
  }
  saving.value = true
  try {
    await userApi.updateProfile({
      nickname: form.value.nickname,
      email: form.value.email,
      phone: form.value.phone,
    })
    // 同步更新 store
    userStore.userInfo = { ...userStore.userInfo, ...form.value } as any
    showSuccessToast('保存成功')
  } catch (e) {
    console.error('保存失败:', e)
    showToast('保存失败')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.profile-page {
  min-height: 100vh;
  background: $background-color;
  padding-bottom: 24px;
}
.avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 24px;
  background: #fff;
  margin-bottom: 8px;
}
.submit-btn {
  padding: 16px;
}
</style>

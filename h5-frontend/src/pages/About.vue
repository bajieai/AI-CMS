<template>
  <div class="about">
    <van-nav-bar title="关于" left-arrow @click-left="$router.back()" />
    <div class="about-logo">
      <van-icon name="info-o" size="60" color="#1989fa" />
      <h2>AI-CMS</h2>
      <p>智能内容管理系统 V2.9.39</p>
    </div>
    <van-cell-group>
      <van-cell title="版本号" value="V2.9.39" />
      <van-cell title="技术支持" value="https://gitee.com/bajieai/ai-cms" />
      <van-cell title="开源协议" value="MIT" />
    </van-cell-group>
  </div>
</template>

<style scoped lang="scss">
.about { min-height: 100vh; background: #f7f8fa; }
.about-logo { display: flex; flex-direction: column; align-items: center; padding: 40px; background: #fff; margin-bottom: 8px;
  h2 { font-size: 24px; margin: 12px 0 4px; }
  p { font-size: 14px; color: #969799; }
}
</style>

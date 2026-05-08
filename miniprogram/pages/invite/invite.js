// pages/invite/invite.js - V2.9 邀请好友页面
const api = require('../../utils/api.js');
const app = getApp();

Page({
  data: {
    inviteCode: '',
    inviteLink: '',
    inviteCount: 0,
    invitePoints: 0,
    records: [],
    // V2.9: 三阶段奖励进度
    rewardStages: [
      { stage: 0, label: '注册奖励', icon: '🎉', desc: '好友注册成功', points: 0, count: 0 },
      { stage: 1, label: '签到奖励', icon: '✅', desc: '好友首次签到', points: 0, count: 0 },
      { stage: 2, label: '付费奖励', icon: '💰', desc: '好友首次付费', points: 0, count: 0 },
    ],
  },

  onShow() {
    this.loadData();
  },

  async loadData() {
    try {
      const res = await api.getInviteInfo();
      if (res.code === 0) {
        const data = res.data;
        this.setData({
          inviteCode: data.invite_code || '',
          inviteLink: data.invite_link || '',
          inviteCount: data.invite_count || 0,
          invitePoints: data.invite_points || 0,
        });
        this.updateRewardStages(data);
      }
    } catch (e) {
      console.log('加载邀请信息失败', e);
    }

    // 加载邀请记录
    try {
      const res = await api.getInviteRecords({ limit: 20 });
      if (res.code === 0) {
        this.setData({ records: res.data.list || [] });
      }
    } catch (e) {}
  },

  // V2.9: 更新三阶段奖励
  updateRewardStages(data) {
    const stages = this.data.rewardStages.map((s, i) => ({
      ...s,
      points: data.stage_points?.[i] || s.points,
      count: data.stage_counts?.[i] || 0,
      achieved: (data.stage_counts?.[i] || 0) > 0,
    }));
    this.setData({ rewardStages: stages });
  },

  // 复制邀请链接
  copyLink() {
    wx.setClipboardData({
      data: this.data.inviteLink,
      success: () => {
        wx.showToast({ title: '链接已复制', icon: 'success' });
      }
    });
  },

  // 分享邀请
  onShareAppMessage() {
    const memberId = app.globalData.memberId || 0;
    return {
      title: '八界AI-CMS - 邀请好友赢积分',
      path: `/pages/index/index?invite_by=${memberId}`,
      imageUrl: '/images/share.png',
    };
  },
});

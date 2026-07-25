// pages/signin/signin.js - V2.9 签到页面
const api = require('../../utils/api.js');
const app = getApp();

Page({
  data: {
    hasSignedToday: false,
    signinDays: 0,
    records: [],
    points: 0,
    calendarDays: [],
    // V2.9: 7日连续奖励里程碑
    weeklyMilestones: [
      { day: 1, label: '第1天', reward: 5, achieved: false },
      { day: 2, label: '第2天', reward: 5, achieved: false },
      { day: 3, label: '第3天', reward: 15, achieved: false, milestone: true, milestoneLabel: '3日奖' },
      { day: 4, label: '第4天', reward: 5, achieved: false },
      { day: 5, label: '第5天', reward: 5, achieved: false },
      { day: 6, label: '第6天', reward: 10, achieved: false },
      { day: 7, label: '第7天', reward: 30, achieved: false, milestone: true, milestoneLabel: '7日大奖' },
    ],
  },

  onShow() {
    this.loadData();
  },

  async loadData() {
    try {
      const [statusRes, recordsRes] = await Promise.all([
        api.hasSignedToday(),
        api.getSigninRecords({ limit: 30 }),
      ]);
      if (statusRes.code === 0) {
        this.setData({ hasSignedToday: statusRes.data.signed });
      }
      if (recordsRes.code === 0) {
        const records = recordsRes.data.list || [];
        const totalDays = recordsRes.data.total_days || 0;
        const points = recordsRes.data.total_points || 0;
        this.setData({
          records,
          signinDays: totalDays,
          points,
        });
        this.buildCalendar(records);
        this.updateMilestones(totalDays);
      }
    } catch (e) {
      console.log('加载签到数据失败', e);
    }
  },

  buildCalendar(records) {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const signedDates = new Set(records.map(r => {
      const d = new Date(r.create_time * 1000);
      return `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
    }));

    const days = [];
    for (let i = 1; i <= daysInMonth; i++) {
      days.push({
        day: i,
        signed: signedDates.has(`${year}-${month}-${i}`),
        isToday: i === today.getDate(),
      });
    }
    this.setData({ calendarDays: days });
  },

  // V2.9: 更新7日里程碑进度
  updateMilestones(totalDays) {
    const cycleDay = ((totalDays - 1) % 7) + 1; // 当前周期天数(1-7)
    const milestones = this.data.weeklyMilestones.map(m => ({
      ...m,
      achieved: cycleDay >= m.day,
      current: cycleDay === m.day && !this.data.hasSignedToday,
    }));
    this.setData({ weeklyMilestones: milestones });
  },

  async handleSignin() {
    if (this.data.hasSignedToday) {
      wx.showToast({ title: '今日已签到', icon: 'none' });
      return;
    }
    try {
      const res = await api.signin();
      if (res.code === 0) {
        this.setData({
          hasSignedToday: true,
          signinDays: this.data.signinDays + 1,
          points: this.data.points + (res.data.points || 0),
        });
        wx.showToast({ title: `签到成功 +${res.data.points || 0}积分`, icon: 'success' });
        this.loadData();
      } else {
        wx.showToast({ title: res.msg, icon: 'none' });
      }
    } catch (e) {
      wx.showToast({ title: '签到失败', icon: 'none' });
    }
  },
});

<template>
  <div class="ccgame-rankpop-backdrop" @click.self="$emit('close')">
    <div class="ccgame-rankpop">
      <div class="ccgame-rankpop-head">
        <span class="ccgame-rankpop-title">🏆 ĐUA TOP MÙA HỒI QUY</span>
        <button class="ccgame-rankpop-x" @click="$emit('close')">&times;</button>
      </div>

      <div class="ccgame-rankpop-tabs">
        <button :class="{ on: view==='power' }" @click="view='power'">⚔️ Lực Chiến</button>
        <button :class="{ on: view==='donate' }" @click="view='donate'">💎 Đại Gia</button>
      </div>

      <div v-if="view==='power'" class="ccgame-rankpop-list">
        <div v-for="(p,i) in powerTop.slice(0,10)" :key="'p'+i" class="ccgame-rankpop-row">
          <span class="ccgame-rankpop-rank" :class="medal(i)">{{ i+1 }}</span>
          <span class="ccgame-rankpop-name">{{ p.name }}</span>
          <span class="ccgame-rankpop-val">{{ fmt(p.power) }}</span>
        </div>
        <div v-if="!powerTop.length" class="ccgame-rankpop-empty">Chưa có dữ liệu.</div>
      </div>

      <div v-else class="ccgame-rankpop-list">
        <div class="ccgame-rankpop-period">
          <button :class="{ on: donate.period==='week' }" @click="$emit('load-period','week')">Tuần</button>
          <button :class="{ on: donate.period==='month' }" @click="$emit('load-period','month')">Tháng</button>
          <button :class="{ on: donate.period==='all' }" @click="$emit('load-period','all')">Tất cả</button>
        </div>
        <div v-if="donate.loading" class="ccgame-rankpop-empty">Đang tải...</div>
        <template v-else>
          <div v-for="(p,i) in donate.top" :key="'d'+i" class="ccgame-rankpop-row">
            <span class="ccgame-rankpop-rank" :class="medal(i)">{{ i+1 }}</span>
            <span class="ccgame-rankpop-name">{{ p.name }}</span>
            <span class="ccgame-rankpop-val">{{ fmt(p.tom) }} 🦐</span>
          </div>
          <div v-if="!donate.top.length" class="ccgame-rankpop-empty">Chưa có ai trong kỳ này. Dẫn đầu ngay!</div>
        </template>
      </div>

      <div class="ccgame-rankpop-hint">🎁 Lên gần TOP 10 để nhận quà hỗ trợ bám đỉnh!</div>

      <div class="ccgame-rankpop-foot">
        <button class="ccgame-rankpop-close" @click="$emit('close')">Đóng</button>
        <button v-if="hasDonated" class="ccgame-rankpop-off" @click="$emit('dismiss-day')">Tắt cả ngày</button>
        <span v-else class="ccgame-rankpop-locked">🔒 Nạp để mở "tắt cả ngày"</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  powerTop: { type: Array, default: () => [] },
  donate: { type: Object, default: () => ({ period: 'week', top: [], loading: false }) },
  hasDonated: { type: Boolean, default: false },
})
defineEmits(['close', 'dismiss-day', 'load-period'])

const view = ref('power')
function medal(i) { return i === 0 ? 'g' : i === 1 ? 's' : i === 2 ? 'b' : '' }
function fmt(n) { return Number(n || 0).toLocaleString('vi-VN') }
</script>

<style scoped>
.ccgame-rankpop-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:2147483600;display:flex;align-items:center;justify-content:center;padding:16px;}
.ccgame-rankpop{width:100%;max-width:360px;background:#1c1c22;border:1px solid #3a3a44;border-radius:12px;color:#eee;font-family:system-ui,sans-serif;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.5);}
.ccgame-rankpop-head{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:linear-gradient(135deg,#3a2a10,#1c1c22);}
.ccgame-rankpop-title{font-weight:700;font-size:15px;color:#ffcc66;}
.ccgame-rankpop-x{background:none;border:none;color:#aaa;font-size:22px;cursor:pointer;line-height:1;}
.ccgame-rankpop-tabs{display:flex;gap:6px;padding:10px 14px 0;}
.ccgame-rankpop-tabs button{flex:1;padding:7px;border:none;border-radius:8px 8px 0 0;background:#26262e;color:#aaa;cursor:pointer;font-size:13px;}
.ccgame-rankpop-tabs button.on{background:#33333d;color:#ffcc66;font-weight:600;}
.ccgame-rankpop-period{display:flex;gap:6px;margin-bottom:8px;}
.ccgame-rankpop-period button{flex:1;padding:5px;border:1px solid #3a3a44;border-radius:6px;background:#26262e;color:#aaa;cursor:pointer;font-size:12px;}
.ccgame-rankpop-period button.on{background:#ffcc66;color:#222;border-color:#ffcc66;font-weight:600;}
.ccgame-rankpop-list{padding:10px 14px;max-height:300px;overflow-y:auto;}
.ccgame-rankpop-row{display:flex;align-items:center;gap:10px;padding:7px 4px;border-bottom:1px solid #2a2a32;}
.ccgame-rankpop-rank{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;background:#33333d;color:#bbb;flex-shrink:0;}
.ccgame-rankpop-rank.g{background:#ffd700;color:#222;}
.ccgame-rankpop-rank.s{background:#c0c0c0;color:#222;}
.ccgame-rankpop-rank.b{background:#cd7f32;color:#222;}
.ccgame-rankpop-name{flex:1;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ccgame-rankpop-val{font-size:13px;font-weight:600;color:#ffcc66;}
.ccgame-rankpop-empty{text-align:center;color:#888;padding:20px;font-size:13px;}
.ccgame-rankpop-hint{text-align:center;font-size:12px;color:#88ffdd;padding:6px 14px;background:#15201a;}
.ccgame-rankpop-foot{display:flex;gap:8px;padding:12px 14px;}
.ccgame-rankpop-close{flex:1;padding:9px;border:1px solid #3a3a44;border-radius:8px;background:#26262e;color:#ddd;cursor:pointer;font-size:13px;}
.ccgame-rankpop-off{flex:1;padding:9px;border:none;border-radius:8px;background:#33333d;color:#ffcc66;cursor:pointer;font-size:13px;}
.ccgame-rankpop-locked{flex:1;text-align:center;align-self:center;font-size:11px;color:#777;}
</style>

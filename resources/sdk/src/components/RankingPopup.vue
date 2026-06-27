<template>
  <div class="rp-backdrop" @click.self="$emit('close')">
    <div class="rp-modal">
      <!-- header -->
      <div class="rp-head">
        <div class="rp-head-glow"></div>
        <div class="rp-head-top">
          <span class="rp-head-icon"><span class="mat-icon">emoji_events</span></span>
          <div class="rp-head-titles">
            <h1 class="rp-head-title">ĐUA TOP MÙA HỒI QUY</h1>
            <div v-if="server" class="rp-head-sub">{{ server }}</div>
          </div>
          <button class="rp-close" @click="$emit('close')"><span class="mat-icon">close</span></button>
        </div>

        <!-- tabs -->
        <div class="rp-tabs">
          <button
            class="rp-tab" :class="{ 'rp-tab--on': view==='power' }"
            @click="view='power'"
          ><span class="mat-icon">swords</span>Lực Chiến</button>
          <button
            class="rp-tab" :class="{ 'rp-tab--on': view==='donate' }"
            @click="view='donate'"
          ><span class="mat-icon">diamond</span>Đại Gia</button>
        </div>
      </div>

      <!-- body -->
      <div class="rp-body">
        <!-- ===== LỰC CHIẾN ===== -->
        <div v-if="view==='power'">
          <template v-if="powerTop.length">
            <!-- podium -->
            <div class="rp-podium">
              <div class="rp-podium-glow"></div>
              <div
                v-for="slot in podium" :key="slot.rank"
                class="rp-podium-slot"
              >
                <span v-if="slot.isFirst" class="mat-icon rp-crown">emoji_events</span>
                <div class="rp-avatar" :style="slot.avatarStyle">{{ slot.initials }}</div>
                <div class="rp-podium-name">{{ slot.name }}</div>
                <div class="rp-podium-val">{{ fmt(slot.power) }}</div>
                <div class="rp-pedestal" :style="slot.pedestalStyle">
                  <span :style="{ color: slot.medalColor }">{{ slot.rank }}</span>
                </div>
              </div>
            </div>

            <!-- list rank 4+ -->
            <div class="rp-list">
              <div
                v-for="r in powerRest" :key="r.rank"
                class="rp-row" :class="{ 'rp-row--me': r.me }"
              >
                <span class="rp-badge" :class="{ 'rp-badge--me': r.me }">{{ r.rank }}</span>
                <span class="rp-row-name">{{ r.name }}</span>
                <span v-if="r.me" class="rp-tag">BẠN</span>
                <span class="rp-row-val">{{ fmt(r.power) }}</span>
              </div>
            </div>
          </template>
          <div v-else class="rp-empty">Chưa có dữ liệu.</div>
        </div>

        <!-- ===== ĐẠI GIA ===== -->
        <div v-else>
          <div class="rp-periods">
            <button
              v-for="p in periods" :key="p.key"
              class="rp-period" :class="{ 'rp-period--on': donate.period===p.key }"
              @click="$emit('load-period', p.key)"
            >{{ p.label }}</button>
          </div>

          <div v-if="donate.loading" class="rp-empty">Đang tải...</div>
          <template v-else>
            <div v-if="donateRows.length" class="rp-list">
              <div
                v-for="r in donateRows" :key="r.rank"
                class="rp-row" :class="{ 'rp-row--me': r.me }"
              >
                <span class="rp-badge" :class="{ 'rp-badge--me': r.me }">{{ r.rank }}</span>
                <span class="rp-row-name">{{ r.name }}</span>
                <span v-if="r.me" class="rp-tag">BẠN</span>
                <span class="rp-row-tom"><span class="rp-tom-dot"></span>{{ fmt(r.tom) }}</span>
              </div>
            </div>
            <div v-else class="rp-empty">Chưa có ai trong kỳ này. Dẫn đầu ngay!</div>
          </template>
        </div>

        <!-- banner -->
        <div class="rp-banner">
          <span class="mat-icon rp-banner-icon">trending_up</span>
          <span class="rp-banner-text">Lên gần TOP 10 để nhận quà hỗ trợ bám đỉnh!</span>
        </div>
      </div>

      <!-- footer -->
      <div class="rp-foot">
        <button class="rp-btn-close" @click="$emit('close')">Đóng</button>
        <button v-if="hasDonated" class="rp-btn-hide" @click="$emit('dismiss-day')">Tắt cả ngày</button>
        <button v-else class="rp-btn-locked">
          <span class="mat-icon">lock</span>Nạp để mở tắt cả ngày
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  powerTop: { type: Array, default: () => [] },
  donate: { type: Object, default: () => ({ period: 'week', top: [], loading: false }) },
  hasDonated: { type: Boolean, default: false },
  server: { type: String, default: '' },
})
defineEmits(['close', 'dismiss-day', 'load-period'])

const view = ref('power')

const periods = [
  { key: 'week', label: 'Tuần' },
  { key: 'month', label: 'Tháng' },
  { key: 'all', label: 'Tất cả' },
]

// Player hiện tại (highlight "BẠN"). actorname đến từ window.ccgame.user.
const meName = (window.ccgame?.user || '').trim()
function isMe(name) {
  return !!meName && (name || '').trim() === meName
}

const MEDALS = {
  1: { color: '#f4d77a', rgb: '244,215,122' },
  2: { color: '#c7cdd6', rgb: '199,205,214' },
  3: { color: '#cf9b6a', rgb: '207,155,106' },
}

function rankOf(item, i) {
  return item.rank || i + 1
}

function initials(name) {
  return Array.from((name || '').trim()).slice(0, 2).join('').toUpperCase() || '?'
}

// Top-3 sắp lại #2 - #1 - #3 (giữa cao). filter Boolean -> server <3 player không vỡ.
const podium = computed(() => {
  const top = props.powerTop.slice(0, 3).map((p, i) => ({ ...p, rank: rankOf(p, i) }))
  return [top[1], top[0], top[2]].filter(Boolean).map((p) => {
    const m = MEDALS[p.rank] || MEDALS[3]
    const first = p.rank === 1
    const size = first ? 66 : 52
    const pedH = first ? 80 : (p.rank === 2 ? 56 : 44)
    return {
      ...p,
      isFirst: first,
      initials: initials(p.name),
      medalColor: m.color,
      avatarStyle: `width:${size}px;height:${size}px;font-size:${first ? 22 : 17}px;`
        + `background:radial-gradient(circle at 40% 35%, rgba(${m.rgb},.32), #0c0c14 72%);`
        + `border-color:${m.color};color:${m.color};box-shadow:0 0 ${first ? 24 : 13}px rgba(${m.rgb},.5);`,
      pedestalStyle: `height:${pedH}px;`
        + `background:linear-gradient(180deg, rgba(${m.rgb},.22), rgba(${m.rgb},.05));`
        + `border-color:rgba(${m.rgb},.4);`,
    }
  })
})

const powerRest = computed(() =>
  props.powerTop.slice(3).map((p, i) => ({ ...p, rank: rankOf(p, i + 3), me: isMe(p.name) }))
)

const donateRows = computed(() =>
  (props.donate.top || []).map((p, i) => ({ ...p, rank: rankOf(p, i), me: isMe(p.name) }))
)

function fmt(n) {
  return Number(n || 0).toLocaleString('vi-VN')
}
</script>

<style scoped>
.rp-backdrop {
  position: fixed; inset: 0; z-index: 2147483600;
  display: flex; align-items: center; justify-content: center; padding: 24px;
  background: rgba(4,4,7,.78);
  backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
}
.rp-modal {
  position: relative; width: 340px; max-width: 100%; max-height: 92vh;
  display: flex; flex-direction: column;
  border-radius: 20px; overflow: hidden;
  border: 1px solid rgba(201,169,78,.28); background: #12121d;
  box-shadow: 0 34px 84px rgba(0,0,0,.66);
  font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: #f4f1e9;
}

/* header */
.rp-head { position: relative; flex: none; padding: 18px 18px 0; overflow: hidden;
  background: linear-gradient(160deg,#1c1708,#100d05 60%,#12121d); }
.rp-head-glow { position: absolute; top: -80px; left: 50%; transform: translateX(-50%);
  width: 300px; height: 200px; pointer-events: none;
  background: radial-gradient(circle at 50% 40%, rgba(244,215,122,.2), transparent 64%); }
.rp-head-top { position: relative; display: flex; align-items: center; gap: 11px; margin-bottom: 16px; }
.rp-head-icon { width: 38px; height: 38px; flex: none; border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  background: radial-gradient(circle at 42% 35%, rgba(244,215,122,.34), #0c0c14 72%);
  border: 1px solid rgba(244,215,122,.45); }
.rp-head-icon .mat-icon { font-size: 22px; color: #f4d77a; }
.rp-head-titles { flex: 1; min-width: 0; }
.rp-head-title { margin: 0; font-size: 17px; font-weight: 800; line-height: 1.1; letter-spacing: .02em; color: #f4f1e9; }
.rp-head-sub { font-size: 11px; font-weight: 500; color: #8c877b; margin-top: 3px; }
.rp-close { flex: none; width: 30px; height: 30px; border-radius: 9px;
  border: 1px solid rgba(255,255,255,.1); background: transparent; color: #b8b2a4;
  cursor: pointer; display: flex; align-items: center; justify-content: center; transition: border-color .15s; }
.rp-close:hover { border-color: rgba(201,169,78,.4); }
.rp-close .mat-icon { font-size: 19px; }

/* tabs */
.rp-tabs { position: relative; display: flex; gap: 5px; }
.rp-tab { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
  padding: 9px; border-radius: 10px 10px 0 0; cursor: pointer;
  border: 1px solid transparent; border-bottom: 2px solid transparent; background: transparent;
  color: #8c877b; font-size: 13px; font-weight: 600; font-family: inherit; transition: color .15s; }
.rp-tab .mat-icon { font-size: 17px; }
.rp-tab--on { border-bottom-color: #c9a94e; background: rgba(201,169,78,.1); color: #e8d49a; font-weight: 700; }

/* body */
.rp-body { flex: 1; overflow-y: auto; padding: 18px; }

/* podium */
.rp-podium { position: relative; display: grid; grid-template-columns: 1fr 1.16fr 1fr;
  gap: 8px; align-items: end; margin-bottom: 16px; padding-top: 26px; }
.rp-podium-glow { position: absolute; top: -4px; left: 50%; transform: translateX(-50%);
  width: 240px; height: 240px; pointer-events: none;
  background: radial-gradient(circle at 50% 30%, rgba(244,215,122,.18), transparent 62%); }
.rp-podium-slot { position: relative; display: flex; flex-direction: column; align-items: center; }
.rp-crown { font-size: 24px; color: #f4d77a; margin-bottom: 1px;
  filter: drop-shadow(0 0 10px rgba(244,215,122,.7)); }
.rp-avatar { border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-weight: 800; border: 2px solid; }
.rp-podium-name { font-size: 12px; font-weight: 700; color: #f4f1e9; margin-top: 7px;
  text-align: center; line-height: 1.15; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rp-podium-val { font-size: 11px; font-weight: 700; color: #e8d49a; margin-top: 3px; }
.rp-pedestal { width: 100%; margin-top: 7px; border-radius: 11px 11px 0 0;
  border: 1px solid; border-bottom: none;
  display: flex; align-items: flex-start; justify-content: center; padding-top: 9px; }
.rp-pedestal span { font-size: 21px; font-weight: 800; }

/* list rows */
.rp-list { display: flex; flex-direction: column; gap: 7px; }
.rp-row { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 11px;
  background: #0c0c14; border: 1px solid rgba(255,255,255,.05); }
.rp-row--me { background: rgba(201,169,78,.12); border-color: rgba(201,169,78,.5);
  box-shadow: 0 0 18px rgba(201,169,78,.12); }
.rp-badge { width: 24px; height: 24px; flex: none; border-radius: 7px;
  display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800;
  background: #15151f; border: 1px solid rgba(255,255,255,.07); color: #8c877b; }
.rp-badge--me { background: #e0b84a; border: none; color: #07070a; }
.rp-row-name { flex: 1; min-width: 0; font-size: 13px; font-weight: 700; color: #f4f1e9;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rp-tag { flex: none; font-size: 8.5px; font-weight: 700; color: #07070a; background: #e0b84a;
  padding: 1px 7px; border-radius: 5px; letter-spacing: .05em; }
.rp-row-val { flex: none; font-size: 12.5px; font-weight: 700; color: #e8d49a; }
.rp-row-tom { display: flex; align-items: center; gap: 5px; flex: none;
  font-size: 12.5px; font-weight: 700; color: #e8d49a; }
.rp-tom-dot { width: 7px; height: 7px; border-radius: 2px; background: #e0b84a; }

/* period pills */
.rp-periods { display: flex; gap: 6px; padding: 4px; margin-bottom: 15px;
  background: #0c0c14; border: 1px solid rgba(255,255,255,.06); border-radius: 11px; }
.rp-period { flex: 1; padding: 8px; border-radius: 8px; cursor: pointer;
  border: 1px solid transparent; background: transparent; color: #8c877b;
  font-size: 12px; font-weight: 600; font-family: inherit; }
.rp-period--on { border-color: rgba(201,169,78,.45); background: rgba(201,169,78,.13); color: #e8d49a; font-weight: 700; }

/* banner */
.rp-banner { display: flex; align-items: center; gap: 10px; margin-top: 15px; padding: 12px 14px;
  background: rgba(201,169,78,.1); border: 1px solid rgba(201,169,78,.32); border-radius: 12px; }
.rp-banner-icon { font-size: 19px; color: #e0b84a; flex: none; }
.rp-banner-text { font-size: 11.5px; font-weight: 600; line-height: 1.4; color: #e7e3d9; }

/* footer */
.rp-foot { flex: none; display: flex; align-items: center; gap: 10px; padding: 14px 18px;
  border-top: 1px solid rgba(255,255,255,.06); background: #0e0e16; }
.rp-foot button { flex: 1; border-radius: 11px; padding: 11px; cursor: pointer; font-family: inherit; }
.rp-btn-close { font-size: 13.5px; font-weight: 700; color: #cfc9bb;
  background: transparent; border: 1px solid rgba(255,255,255,.12); transition: border-color .15s; }
.rp-btn-close:hover { border-color: rgba(201,169,78,.4); }
.rp-btn-hide { display: flex; align-items: center; justify-content: center; gap: 6px;
  font-size: 13.5px; font-weight: 700; color: #07070a; border: none;
  background: linear-gradient(135deg,#f0d98c,#b3923c); box-shadow: 0 6px 18px rgba(201,169,78,.34); }
.rp-btn-locked { display: flex; align-items: center; justify-content: center; gap: 6px;
  font-size: 12px; font-weight: 600; color: #8c877b;
  background: #0c0c14; border: 1px solid rgba(255,255,255,.08); }
.rp-btn-locked .mat-icon { font-size: 16px; color: #6f6b61; }

/* empty */
.rp-empty { text-align: center; color: #8c877b; padding: 24px 12px; font-size: 12.5px; }
</style>

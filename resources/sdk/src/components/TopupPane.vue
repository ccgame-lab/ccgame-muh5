<template>
  <div class="tp-pane">
    <!-- Spotlight: lần đầu nạp x2 -->
    <div class="tp-spotlight">
      <span class="tp-spot-icon"><span class="mat-icon">stars</span></span>
      <div class="tp-spot-body">
        <div class="tp-spot-title">
          <span>Lần Đầu Nạp x2 Tôm</span>
          <span class="tp-spot-tag">HOT</span>
        </div>
        <div class="tp-spot-note">
          Mọi gói nạp đầu tiên nhân đôi số Tôm nhận được. Áp dụng một lần cho mỗi tài khoản.
        </div>
      </div>
    </div>

    <!-- Gói tiếp tế (redirect GreenJade, SDK không xử lý ví) -->
    <div class="tp-card">
      <div class="tp-card-hdr">
        <span class="tp-lbl">GÓI NẠP TÔM</span>
        <span class="tp-rate">1.000đ = 1 Tôm</span>
      </div>

      <div v-if="tiers.length" class="tp-grid">
        <div
          v-for="t in tiers"
          :key="t.id"
          class="tp-pkg"
          :class="{ 'tp-pkg--pop': t.popular }"
        >
          <span v-if="t.tag" class="tp-pkg-badge">{{ t.tag }}</span>
          <div class="tp-pkg-emoji">{{ t.emoji }}</div>
          <div class="tp-pkg-name">{{ t.name }}</div>
          <div class="tp-pkg-reward"><span class="mat-icon">set_meal</span>{{ t.reward }}</div>
          <a :href="tierUrl(t)" target="_blank" rel="noopener" class="tp-pkg-btn">{{ t.vnd }}</a>
        </div>
      </div>
      <a v-else :href="suppliesUrl" target="_blank" rel="noopener" class="tp-cta">
        <span class="mat-icon">add_circle</span>Nạp Tôm tại GreenJade
      </a>

      <div class="tp-foot">
        <span class="mat-icon">verified_user</span>
        Xử lý thủ công qua VietQR tại GreenJade ID. SDK không giữ ví, không thu tiền - chỉ điều hướng.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useSdkState } from '../composables/useSdkState.js'

const { state } = useSdkState()

const tiers = computed(() => state.supportTiers || [])
const suppliesUrl = computed(() => state.suppliesUrl)

function tierUrl(t) {
  const base = state.suppliesUrl
  const sep = base.includes('?') ? '&' : '?'
  return `${base}${sep}amount=${t.amount}`
}
</script>

<style scoped>
.tp-pane {
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  max-width: 760px;
  margin: 0 auto;
}

/* Spotlight */
.tp-spotlight {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(201,169,78,.16), rgba(201,169,78,.04));
  border: 1px solid rgba(201,169,78,.35);
}

.tp-spot-icon {
  width: 46px;
  height: 46px;
  border-radius: 13px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at 35% 30%, #d8bd6a, #b3923c);
  color: #07070a;
}
.tp-spot-icon .mat-icon { font-size: 26px; }

.tp-spot-body { min-width: 0; }

.tp-spot-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: 'Outfit', sans-serif;
  font-size: 16px;
  font-weight: 700;
  color: #f4f1e9;
}

.tp-spot-tag {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: .06em;
  padding: 2px 7px;
  border-radius: 5px;
  background: #f4554d;
  color: #fff;
}

.tp-spot-note {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 12.5px;
  color: #b8b2a4;
  line-height: 1.4;
  margin-top: 4px;
}

/* Card */
.tp-card {
  background: #10101a;
  border: 1px solid rgba(201,169,78,.14);
  border-radius: 16px;
  padding: 18px;
}

.tp-card-hdr {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.tp-lbl {
  font-family: 'Outfit', sans-serif;
  font-size: 13px;
  font-weight: 700;
  color: #c9a94e;
  letter-spacing: .03em;
}

.tp-rate {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 11px;
  font-weight: 600;
  color: #8c877b;
}

/* Package grid */
.tp-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 12px;
}

.tp-pkg {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 18px 12px 14px;
  background: #0c0c14;
  border: 1px solid rgba(255,255,255,.05);
  border-radius: 14px;
  transition: border-color .16s, transform .16s;
}
.tp-pkg:hover { border-color: rgba(201,169,78,.4); transform: translateY(-2px); }
.tp-pkg--pop { border-color: rgba(201,169,78,.45); }

.tp-pkg-badge {
  position: absolute;
  top: 9px;
  right: 9px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: .04em;
  padding: 2px 7px;
  border-radius: 999px;
  background: rgba(201,169,78,.16);
  color: #e8d49a;
  border: 1px solid rgba(201,169,78,.3);
}

.tp-pkg-emoji { font-size: 28px; line-height: 1; }

.tp-pkg-name {
  font-family: 'Outfit', sans-serif;
  font-size: 14px;
  font-weight: 700;
  color: #f4f1e9;
}

.tp-pkg-reward {
  display: flex;
  align-items: center;
  gap: 4px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 11.5px;
  font-weight: 600;
  color: #e8d49a;
  text-align: center;
}
.tp-pkg-reward .mat-icon { font-size: 15px; color: #c9a94e; }

.tp-pkg-btn {
  margin-top: 4px;
  width: 100%;
  text-align: center;
  text-decoration: none;
  font-family: 'Outfit', sans-serif;
  font-size: 13.5px;
  font-weight: 700;
  color: #07070a;
  background: linear-gradient(135deg, #f0d98c, #b3923c);
  border-radius: 10px;
  padding: 9px;
  box-shadow: 0 4px 14px rgba(201,169,78,.3);
  transition: filter .15s;
}
.tp-pkg-btn:hover { filter: brightness(1.06); }

/* Fallback CTA */
.tp-cta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  font-family: 'Outfit', sans-serif;
  font-size: 15px;
  font-weight: 800;
  color: #07070a;
  background: linear-gradient(135deg, #f0d98c, #b3923c);
  border-radius: 12px;
  padding: 14px;
  box-shadow: 0 6px 20px rgba(201,169,78,.35);
}

.tp-foot {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  margin-top: 14px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 11px;
  color: #6f6b61;
  line-height: 1.45;
}
.tp-foot .mat-icon { font-size: 14px; flex-shrink: 0; margin-top: 1px; }
</style>

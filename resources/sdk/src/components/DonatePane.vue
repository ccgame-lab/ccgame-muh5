<template>
  <div class="ccgame-sdk-pane">

    <!-- Section: Tiếp tế GreenJade (lối có thêm Tôm, SDK chỉ điều hướng, không xử lý ví) -->
    <div class="ccgame-sdk-priv-section">
      <div class="ccgame-sdk-priv-section-title">Tiếp tế GreenJade</div>
      <div class="ccgame-sdk-priv-note">
        Góp chi phí duy trì máy chủ và công cụ vận hành. Tiếp tế quy đổi thành Tôm (1.000đ = 1 Tôm) trong ví GreenJade, dùng để đổi đặc quyền bên dưới.
      </div>
      <div class="ccgame-sdk-support-tiers">
        <div
          v-for="t in supportTiers"
          :key="t.id"
          class="ccgame-sdk-support-card"
          :class="{ 'ccgame-sdk-support-card--pop': t.popular }"
        >
          <span v-if="t.tag" class="ccgame-sdk-support-tag">{{ t.tag }}</span>
          <div class="ccgame-sdk-support-head">
            <span class="ccgame-sdk-support-emoji">{{ t.emoji }}</span>
            <span class="ccgame-sdk-support-name">{{ t.name }}</span>
          </div>
          <div class="ccgame-sdk-support-amount">{{ t.vnd }}</div>
          <div class="ccgame-sdk-support-reward">{{ t.reward }}</div>
          <a :href="suppliesUrl" target="_blank" rel="noopener" class="ccgame-sdk-support-btn">Tiếp tế</a>
        </div>
      </div>
      <div class="ccgame-sdk-support-foot">
        Xử lý thủ công qua VietQR tại GreenJade. SDK không giữ ví, không thu tiền.
      </div>
    </div>

    <!-- Section: Cửa hàng Tôm -->
    <div class="ccgame-sdk-priv-section">
      <div class="ccgame-sdk-priv-section-title">Cửa hàng Tôm</div>
      <div class="ccgame-sdk-priv-note">
        Dùng Tôm (ví GreenJade) đổi lấy đặc quyền hỗ trợ. Vật phẩm được gửi vào hộp thư trong game.
      </div>

      <div v-if="itemsLoading && items.length === 0" class="ccgame-sdk-priv-loading">
        <div class="ccgame-sdk-spinner"></div>
      </div>
      <div v-else-if="itemsError" class="ccgame-sdk-priv-empty">
        <span class="ccgame-sdk-priv-empty-text">{{ itemsError }}</span>
      </div>
      <div v-else-if="items.length === 0" class="ccgame-sdk-priv-empty">
        <span class="ccgame-sdk-priv-empty-icon">🦐</span>
        <span class="ccgame-sdk-priv-empty-text">Chưa có vật phẩm đổi Tôm</span>
      </div>

      <div v-else v-for="it in items" :key="it.id" class="ccgame-sdk-priv-feature-card">
        <div class="ccgame-sdk-priv-feature-card-body">
          <div class="ccgame-sdk-tom-head">
            <span class="ccgame-sdk-priv-feature-card-title">{{ it.name }}</span>
            <span v-if="it.badge" class="ccgame-sdk-tom-badge">{{ it.badge }}</span>
          </div>
          <div v-if="it.description" class="ccgame-sdk-priv-feature-card-desc">{{ it.description }}</div>
          <div v-if="it.tags && it.tags.length" class="ccgame-sdk-tom-tags">
            <span v-for="(t, i) in it.tags" :key="i" class="ccgame-sdk-tom-tag">{{ t }}</span>
          </div>
        </div>
        <div class="ccgame-sdk-tom-action">
          <div class="ccgame-sdk-tom-price">{{ it.price_tom }} Tôm</div>
          <button
            class="ccgame-sdk-priv-feature-card-btn"
            :disabled="it.sold_out || buyingId === it.id"
            @click="onBuy(it)"
          >{{ buttonLabel(it) }}</button>
        </div>
      </div>

      <div
        v-if="feedback"
        class="ccgame-sdk-tom-feedback"
        :class="'ccgame-sdk-tom-feedback--' + feedback.type"
      >{{ feedback.message }}</div>
    </div>

    <!-- Section: Đặc quyền (hidden in compact/inline mode) -->
    <div v-if="!compact" class="ccgame-sdk-priv-section">
      <div class="ccgame-sdk-priv-section-title">Đặc quyền hỗ trợ</div>
      <div class="ccgame-sdk-priv-note">
        Người tiếp tế góp chi phí duy trì máy chủ, công cụ và thời gian vận hành.<br>
        Đặc quyền chỉ giúp chơi tiện hơn, không bán sức mạnh trực tiếp.
      </div>

      <div class="ccgame-sdk-priv-fairness">
        <div class="ccgame-sdk-priv-fairness-label">Cơ chế công bằng</div>
        <ul class="ccgame-sdk-priv-fairness-list">
          <li>Không cộng damage / thủ / máu</li>
          <li>Không khóa nội dung với người chơi free</li>
          <li>Bonus KC có giới hạn ngày</li>
          <li>Người chơi free vẫn nhận đủ KC theo thời gian</li>
          <li>Người hỗ trợ giúp server sống lâu hơn cho tất cả</li>
        </ul>
      </div>

      <!-- Dynamic features -->
      <div v-if="!loaded && filtered.length === 0" class="ccgame-sdk-priv-loading">
        <div class="ccgame-sdk-spinner"></div>
      </div>
      <div v-else-if="filtered.length === 0" class="ccgame-sdk-priv-empty">
        <span class="ccgame-sdk-priv-empty-icon">🔒</span>
        <span class="ccgame-sdk-priv-empty-text">Chưa có đặc quyền nào</span>
      </div>
      <div v-else v-for="f in filtered" :key="f.key" class="ccgame-sdk-priv-feature-card">
        <div class="ccgame-sdk-priv-feature-card-body">
          <div class="ccgame-sdk-priv-feature-card-title">{{ f.label }}</div>
          <div v-if="f.note" class="ccgame-sdk-priv-feature-card-desc">{{ f.note }}</div>
        </div>
        <a :href="f.href" target="_blank" rel="noopener" class="ccgame-sdk-priv-feature-card-btn">Xem</a>
      </div>

      <div class="ccgame-sdk-priv-footnote">
        Người chơi free không bị khóa tiến trình. Người hỗ trợ giúp server vận hành lâu dài cho tất cả.
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  features: { type: Array, default: () => [] },
  loaded: { type: Boolean, default: false },
  items: { type: Array, default: () => [] },
  itemsLoading: { type: Boolean, default: false },
  itemsError: { type: String, default: null },
  buy: { type: Function, default: null },
  compact: { type: Boolean, default: false },
  suppliesUrl: { type: String, default: 'https://id.greenjade.net/supplies' },
})

// 3 mức tiếp tế (chỉ hiển thị + điều hướng sang GreenJade supplies; SDK không xử lý tiền/ví).
// Quy đổi theo hệ GreenJade: 1.000đ = 1 Tôm, +1 OXY mỗi 10.000đ.
const supportTiers = [
  { id: 'small',  emoji: '☕', name: 'Ủng hộ Nhỏ', vnd: '10.000đ',  reward: '10 Tôm',          tag: '',            popular: false },
  { id: 'medium', emoji: '🍜', name: 'Ủng hộ Vừa', vnd: '50.000đ',  reward: '50 Tôm · +5 OXY',  tag: 'Phổ biến',    popular: true },
  { id: 'large',  emoji: '🖥️', name: 'Ủng hộ Lớn', vnd: '100.000đ', reward: '100 Tôm · +10 OXY', tag: 'Ý nghĩa nhất', popular: false },
]

const filtered = computed(() => props.features.filter(f => f.href !== ''))

const buyingId = ref('')
const feedback = ref(null)

function buttonLabel(it) {
  if (it.sold_out) return 'Đã sở hữu'
  if (buyingId.value === it.id) return 'Đang xử lý...'
  return 'Đổi Tôm'
}

async function onBuy(it) {
  if (buyingId.value || it.sold_out || !props.buy) return
  if (!window.confirm(`Đổi ${it.price_tom} Tôm lấy "${it.name}"?`)) return

  buyingId.value = it.id
  feedback.value = null
  const res = await props.buy(it.id)
  feedback.value = { type: res.success ? 'success' : 'error', message: res.message }
  buyingId.value = ''
}
</script>

<style scoped>
.ccgame-sdk-tom-head {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.ccgame-sdk-tom-badge {
  font-size: 9px;
  font-weight: 700;
  color: #c9a94e;
  border: 1px solid rgba(201, 169, 78, 0.4);
  border-radius: 4px;
  padding: 1px 5px;
  white-space: nowrap;
}
.ccgame-sdk-tom-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 6px;
}
.ccgame-sdk-tom-tag {
  font-size: 9px;
  color: #9a9ab0;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 4px;
  padding: 1px 6px;
}
.ccgame-sdk-tom-action {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
}
.ccgame-sdk-tom-price {
  font-size: 12px;
  font-weight: 700;
  color: #c9a94e;
  white-space: nowrap;
}
.ccgame-sdk-priv-feature-card-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.ccgame-sdk-tom-feedback {
  margin-top: 8px;
  font-size: 11px;
  line-height: 1.5;
  border-radius: 6px;
  padding: 8px 10px;
}
.ccgame-sdk-tom-feedback--success {
  color: #4caf50;
  background: rgba(76, 175, 80, 0.1);
  border: 1px solid rgba(76, 175, 80, 0.3);
}
.ccgame-sdk-tom-feedback--error {
  color: #f4685e;
  background: rgba(244, 104, 94, 0.1);
  border: 1px solid rgba(244, 104, 94, 0.3);
}

/* ── Tiếp tế GreenJade (3 gói ủng hộ) ── */
.ccgame-sdk-support-tiers {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;
  margin-top: 10px;
}
.ccgame-sdk-support-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 11px 6px 8px;
  background: #12121d;
  border: 1px solid #1e1e32;
  border-radius: 8px;
  text-align: center;
}
.ccgame-sdk-support-card--pop {
  border-color: rgba(46, 196, 182, 0.55);
  box-shadow: 0 0 0 1px rgba(46, 196, 182, 0.22);
}
.ccgame-sdk-support-tag {
  position: absolute;
  top: -7px;
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
  font-size: 8px;
  font-weight: 700;
  color: #04201d;
  background: #2ec4b6;
  border-radius: 4px;
  padding: 1px 5px;
}
.ccgame-sdk-support-head {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  margin-top: 2px;
}
.ccgame-sdk-support-emoji { font-size: 18px; line-height: 1; }
.ccgame-sdk-support-name { font-size: 10px; font-weight: 600; color: #c8c8e0; }
.ccgame-sdk-support-amount {
  font-size: 13px;
  font-weight: 800;
  color: #ffd54f;
  font-variant-numeric: tabular-nums;
}
.ccgame-sdk-support-reward {
  font-size: 9px;
  color: #8a8aaa;
  line-height: 1.3;
  min-height: 24px;
}
.ccgame-sdk-support-btn {
  margin-top: 4px;
  width: 100%;
  padding: 6px 0;
  border-radius: 6px;
  background: linear-gradient(135deg, #2ec4b6, #21a99d);
  color: #04201d;
  font-size: 10px;
  font-weight: 800;
  text-align: center;
  text-decoration: none;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.ccgame-sdk-support-btn:hover { filter: brightness(1.08); }
.ccgame-sdk-support-foot {
  margin-top: 8px;
  font-size: 9px;
  color: #5a5a7a;
  line-height: 1.4;
  text-align: center;
}
</style>

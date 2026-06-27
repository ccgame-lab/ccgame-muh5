<template>
  <div class="ccgame-sdk-pane">

    <!-- Section: Tiếp tế GreenJade (lối có thêm Tôm, SDK chỉ điều hướng, không xử lý ví) -->
    <div class="ccgame-sdk-priv-section">
      <div class="ccgame-sdk-priv-section-title">Tiếp tế GreenJade</div>
      <div class="ccgame-sdk-priv-note">
        Góp chi phí duy trì máy chủ và công cụ vận hành. Tiếp tế quy đổi thành Tôm (1.000đ = 1 Tôm) trong ví GreenJade, dùng để đổi đặc quyền bên dưới.
      </div>
      <div v-if="supportTiers.length" class="ccgame-sdk-support-tiers">
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
          <a :href="tierUrl(t)" target="_blank" rel="noopener" class="ccgame-sdk-support-btn">Tiếp tế</a>
        </div>
      </div>
      <a v-else :href="suppliesUrl" target="_blank" rel="noopener" class="ccgame-sdk-support-btn">Tiếp tế GreenJade</a>
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

      <div v-else class="ccsdk-shop-grid">
        <div
          v-for="it in items"
          :key="it.id"
          class="ccsdk-shop-card"
          :class="{ 'ccsdk-shop-card--hot': it.badge, 'ccsdk-shop-card--sold': it.sold_out }"
        >
          <span v-if="it.badge" class="ccsdk-shop-badge">{{ it.badge }}</span>
          <div class="ccsdk-shop-name">{{ it.name }}</div>
          <div v-if="it.description" class="ccsdk-shop-desc">{{ it.description }}</div>
          <div v-if="it.tags && it.tags.length" class="ccsdk-shop-tags">
            <span v-for="(t, i) in it.tags" :key="i" class="ccsdk-shop-tag">{{ t }}</span>
          </div>
          <div class="ccsdk-shop-foot">
            <div class="ccsdk-shop-price">
              <span class="ccsdk-shop-price-num">{{ it.price_tom }}</span>
              <span class="ccsdk-shop-price-unit">🦐 Tôm</span>
            </div>
            <button
              class="ccsdk-shop-buy"
              :disabled="it.sold_out || buyingId === it.id"
              @click="onBuy(it)"
            >{{ buttonLabel(it) }}</button>
          </div>
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
  // Mức tiếp tế lấy từ bootstrap (config/economy.php support_tiers). Nguồn sự thật phía muh5,
  // SDK chỉ hiển thị + điều hướng sang ví GreenJade, không xử lý tiền.
  supportTiers: { type: Array, default: () => [] },
})

// Link tiếp tế của từng gói: gắn ?amount để trang GreenJade điền sẵn số tiền gói đã chọn.
function tierUrl(t) {
  const sep = props.suppliesUrl.includes('?') ? '&' : '?'
  return `${props.suppliesUrl}${sep}amount=${t.amount}`
}

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

/* ── Cửa hàng Tôm: card grid dopamine ── */
.ccsdk-shop-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 10px;
  margin-top: 12px;
}
.ccsdk-shop-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 13px 12px 11px;
  border-radius: 12px;
  background: linear-gradient(160deg, #16141f 0%, #100f18 100%);
  border: 1px solid rgba(201, 169, 78, 0.22);
  transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
  overflow: hidden;
}
.ccsdk-shop-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(201, 169, 78, 0.7), transparent);
  opacity: 0.5;
}
.ccsdk-shop-card:hover {
  transform: translateY(-3px);
  border-color: rgba(201, 169, 78, 0.5);
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.45), 0 0 18px rgba(201, 169, 78, 0.14);
}
.ccsdk-shop-card--hot {
  border-color: rgba(240, 168, 32, 0.55);
  animation: ccsdk-shop-glow 2.6s ease-in-out infinite;
}
@keyframes ccsdk-shop-glow {
  0%, 100% { box-shadow: 0 0 0 1px rgba(240, 168, 32, 0.22), 0 0 14px rgba(240, 168, 32, 0.10); }
  50%      { box-shadow: 0 0 0 1px rgba(240, 168, 32, 0.55), 0 0 22px rgba(240, 168, 32, 0.28); }
}
.ccsdk-shop-card--sold { opacity: 0.55; }
.ccsdk-shop-badge {
  position: absolute;
  top: 9px; right: 9px;
  font-size: 8px;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #2a1c04;
  background: linear-gradient(135deg, #ffd54f, #f0a820);
  border-radius: 5px;
  padding: 2px 6px;
  box-shadow: 0 2px 6px rgba(240, 168, 32, 0.4);
}
.ccsdk-shop-name {
  font-size: 13px;
  font-weight: 800;
  color: #f3e9d0;
  line-height: 1.2;
  padding-right: 42px;
}
.ccsdk-shop-desc {
  font-size: 10px;
  color: #8f8fa8;
  line-height: 1.45;
  flex: 1;
}
.ccsdk-shop-tags { display: flex; flex-wrap: wrap; gap: 4px; }
.ccsdk-shop-tag {
  font-size: 8.5px;
  font-weight: 600;
  color: #c9a94e;
  background: rgba(201, 169, 78, 0.1);
  border: 1px solid rgba(201, 169, 78, 0.2);
  border-radius: 4px;
  padding: 1px 6px;
}
.ccsdk-shop-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: 4px;
  padding-top: 9px;
  border-top: 1px solid rgba(201, 169, 78, 0.12);
}
.ccsdk-shop-price { display: flex; flex-direction: column; line-height: 1.05; }
.ccsdk-shop-price-num {
  font-size: 19px;
  font-weight: 900;
  color: #ffd54f;
  font-variant-numeric: tabular-nums;
  text-shadow: 0 0 12px rgba(255, 190, 40, 0.5);
}
.ccsdk-shop-price-unit {
  font-size: 8px;
  color: #c9892a;
  font-weight: 700;
  letter-spacing: 0.05em;
  margin-top: 1px;
}
.ccsdk-shop-buy {
  flex-shrink: 0;
  padding: 8px 14px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #ffd95e 0%, #e0a820 55%, #c98a1e 100%);
  color: #2a1c04;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  cursor: pointer;
  box-shadow: 0 3px 10px rgba(240, 168, 32, 0.3);
  transition: filter 0.15s, transform 0.12s;
}
.ccsdk-shop-buy:hover:not(:disabled) { filter: brightness(1.08); transform: scale(1.04); }
.ccsdk-shop-buy:disabled { background: #2a2a3a; color: #6a6a80; box-shadow: none; cursor: not-allowed; }

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

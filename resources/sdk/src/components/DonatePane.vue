<template>
  <div class="ccgame-sdk-pane">

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
})

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
</style>

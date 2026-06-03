<template>
  <div class="ccgame-sdk-pane">

    <!-- Section: Đặc quyền -->
    <div class="ccgame-sdk-priv-section">
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

    <!-- Section: Lịch sử tiếp tế -->
    <div class="ccgame-sdk-priv-section">
      <div class="ccgame-sdk-priv-section-title">Lịch sử tiếp tế</div>
      <div v-if="history.length === 0" class="ccgame-sdk-empty">Chưa có giao dịch nào.</div>
      <div v-for="(item, i) in history" :key="i" class="ccgame-sdk-priv-history-item">
        <div class="ccgame-sdk-priv-history-left">
          <div class="ccgame-sdk-priv-history-label">{{ item.label }}</div>
          <div class="ccgame-sdk-priv-history-date">{{ item.date }}</div>
        </div>
        <div class="ccgame-sdk-priv-history-right">
          <div class="ccgame-sdk-priv-history-value">+{{ item.tom }} Tôm</div>
          <div
            class="ccgame-sdk-priv-history-status"
            :class="'ccgame-sdk-priv-history-status--' + item.status"
          >{{ statusLabel(item.status) }}</div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  features: { type: Array, default: () => [] },
  loaded: { type: Boolean, default: false },
})

const filtered = computed(() => props.features.filter(f => f.href !== ''))

const history = [
  { label: 'Tiếp tế phổ thông', tom: 200, date: '01/06/2026', status: 'completed' },
  { label: 'Tiếp tế cơ bản', tom: 50, date: '28/05/2026', status: 'completed' },
  { label: 'Tiếp tế cao cấp', tom: 500, date: '25/05/2026', status: 'pending' },
]

function statusLabel(s) {
  return s === 'completed' ? 'Hoàn tất' : s === 'pending' ? 'Đang xử lý' : s
}
</script>

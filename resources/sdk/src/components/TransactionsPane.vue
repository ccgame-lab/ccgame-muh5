<template>
  <div class="txn-pane">
    <div class="txn-header">
      <span class="txn-title">Lịch sử giao dịch</span>
      <button class="txn-refresh" @click="reload" :disabled="loading">↻</button>
    </div>

    <div v-if="loading" class="txn-loading">Đang tải...</div>

    <div v-else-if="!transactions.length" class="txn-empty">Chưa có giao dịch nào.</div>

    <div v-else class="txn-list">
      <div v-for="(t, i) in transactions" :key="i" class="txn-item">
        <div class="txn-item-icon">{{ iconFor(t) }}</div>
        <div class="txn-item-body">
          <div class="txn-item-label">{{ t.label }}</div>
          <div class="txn-item-time">{{ relTime(t.created_at) }}</div>
        </div>
        <div class="txn-item-amount" :class="amountClass(t)">{{ amountStr(t) }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useSdkState } from '../composables/useSdkState.js'

const { state, loadTransactions, resetTransactions } = useSdkState()

const transactions = computed(() => state.transactions)
const loading = computed(() => state.transactionsLoading)

function iconFor(t) {
  if (t.source === 'spin') return '🎰'
  const icons = { checkin: '📅', giftcode: '🎁', missions: '✅', buy_tom: '🦐' }
  return icons[t.type] || '💳'
}

function amountStr(t) {
  if (t.source === 'spin') {
    if (t.type === 'lose_turn') return '—'
    if (t.type === 'extra_turn') return '+1 lượt'
    if (t.amount != null) return `+${t.amount} PT`
    return '🎁'
  }
  if (t.amount == null) return ''
  return (t.amount > 0 ? '+' : '') + t.amount + ' PT'
}

function amountClass(t) {
  if (t.source === 'spin' && t.type === 'lose_turn') return 'txn-amount--neutral'
  if (t.amount > 0 || (t.source === 'spin' && t.type !== 'lose_turn')) return 'txn-amount--pos'
  if (t.amount < 0) return 'txn-amount--neg'
  return 'txn-amount--neutral'
}

function relTime(ts) {
  if (!ts) return ''
  const diff = Date.now() - new Date(ts).getTime()
  const m = Math.floor(diff / 60000)
  if (m < 1) return 'Vừa xong'
  if (m < 60) return `${m} phút trước`
  const h = Math.floor(m / 60)
  if (h < 24) return `${h} giờ trước`
  return `${Math.floor(h / 24)} ngày trước`
}

function reload() {
  resetTransactions()
}

onMounted(() => loadTransactions())
</script>

<style scoped>
.txn-pane {
  padding: 14px;
}

.txn-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.txn-title {
  font-size: 11px;
  font-weight: 700;
  color: #c9a94e;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.txn-refresh {
  background: none;
  border: none;
  color: #5a5a7a;
  font-size: 14px;
  cursor: pointer;
  padding: 2px 4px;
  transition: color 0.15s;
}
.txn-refresh:hover { color: #c9a94e; }
.txn-refresh:disabled { opacity: 0.4; cursor: not-allowed; }

.txn-loading,
.txn-empty {
  font-size: 11px;
  color: #5a5a7a;
  text-align: center;
  padding: 20px 0;
}

.txn-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.txn-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  background: #12121d;
  border: 1px solid #1e1e32;
  border-radius: 6px;
}

.txn-item-icon {
  font-size: 16px;
  flex-shrink: 0;
  width: 22px;
  text-align: center;
}

.txn-item-body {
  flex: 1;
  min-width: 0;
}

.txn-item-label {
  font-size: 11px;
  color: #c8c8e0;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.txn-item-time {
  font-size: 9px;
  color: #4a4a6a;
  margin-top: 1px;
}

.txn-item-amount {
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
  font-variant-numeric: tabular-nums;
}

.txn-amount--pos { color: #4caf50; }
.txn-amount--neg { color: #e6543a; }
.txn-amount--neutral { color: #5a5a7a; }
</style>

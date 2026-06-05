<template>
  <div class="noti-pane">
    <div class="noti-header">
      <span class="noti-title">Thông báo máy chủ</span>
    </div>

    <div v-if="!entries.length" class="noti-empty">Chưa có thông báo.</div>

    <div v-else class="noti-list">
      <div
        v-for="e in entries"
        :key="e.id"
        class="noti-card"
        :class="{ 'noti-card--open': expanded.has(e.id) }"
        @click="toggle(e.id)"
      >
        <div class="noti-card-top">
          <span class="noti-icon">{{ iconFor(e) }}</span>
          <div class="noti-card-body">
            <div class="noti-card-title">{{ e.title }}</div>
            <div class="noti-card-meta">
              <span class="noti-badge" :class="badgeClass(e)">{{ badgeLabel(e) }}</span>
              <span class="noti-date">{{ relDate(e.date || e.created_at) }}</span>
            </div>
          </div>
          <span class="noti-chevron">{{ expanded.has(e.id) ? '▲' : '▼' }}</span>
        </div>

        <div v-if="expanded.has(e.id) && lines(e).length" class="noti-card-content">
          <p v-for="(l, i) in lines(e)" :key="i">{{ l }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useSdkState } from '../composables/useSdkState.js'

const { state } = useSdkState()
const entries = computed(() => state.changelog)
const expanded = ref(new Set())

function toggle(id) {
  const s = new Set(expanded.value)
  s.has(id) ? s.delete(id) : s.add(id)
  expanded.value = s
}

function lines(e) {
  const body = e.body || e.content || ''
  return body.split('\n').map(l => l.replace(/^[-*]\s*/, '').trim()).filter(Boolean)
}

function iconFor(e) {
  const t = (e.title || '').toLowerCase()
  if (/bảo trì|maintenance/.test(t)) return '🛠️'
  if (/sự kiện|event|mở|khai/.test(t)) return '🎉'
  if (/cập nhật|nâng cấp|update/.test(t)) return '⚡'
  if (/sửa|fix|lỗi/.test(t)) return '🔧'
  if (/giftcode|quà|gift/.test(t)) return '🎁'
  return '📢'
}

function badgeLabel(e) {
  const t = (e.title || '').toLowerCase()
  if (/bảo trì|maintenance/.test(t)) return 'Bảo trì'
  if (/sự kiện|event|mở|khai/.test(t)) return 'Sự kiện'
  if (/cập nhật|nâng cấp|update/.test(t)) return 'Cập nhật'
  if (/sửa|fix|lỗi/.test(t)) return 'Sửa lỗi'
  if (/giftcode|quà|gift/.test(t)) return 'Quà tặng'
  return 'Tin tức'
}

function badgeClass(e) {
  const t = (e.title || '').toLowerCase()
  if (/bảo trì|maintenance/.test(t)) return 'badge--warn'
  if (/sự kiện|event|mở|khai/.test(t)) return 'badge--event'
  if (/giftcode|quà|gift/.test(t)) return 'badge--gift'
  return 'badge--info'
}

function relDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  if (isNaN(d)) return dateStr
  const diff = Date.now() - d.getTime()
  const days = Math.floor(diff / 86400000)
  if (days === 0) return 'Hôm nay'
  if (days === 1) return 'Hôm qua'
  if (days < 7) return `${days} ngày trước`
  if (days < 30) return `${Math.floor(days / 7)} tuần trước`
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>

<style scoped>
.noti-pane {
  padding: 14px;
}

.noti-header {
  margin-bottom: 12px;
}

.noti-title {
  font-size: 11px;
  font-weight: 700;
  color: #c9a94e;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.noti-empty {
  font-size: 11px;
  color: #5a5a7a;
  text-align: center;
  padding: 20px 0;
}

.noti-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.noti-card {
  background: #12121d;
  border: 1px solid #1e1e32;
  border-radius: 8px;
  cursor: pointer;
  transition: border-color 0.15s;
  overflow: hidden;
}

.noti-card:hover,
.noti-card--open {
  border-color: #2e2e50;
}

.noti-card-top {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 10px;
}

.noti-icon {
  font-size: 18px;
  flex-shrink: 0;
  width: 24px;
  text-align: center;
}

.noti-card-body {
  flex: 1;
  min-width: 0;
}

.noti-card-title {
  font-size: 11px;
  font-weight: 600;
  color: #c8c8e0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.noti-card-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 3px;
}

.noti-badge {
  font-size: 9px;
  font-weight: 700;
  padding: 1px 5px;
  border-radius: 3px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  flex-shrink: 0;
}

.badge--info    { background: #1a2a3a; color: #5b9bd5; }
.badge--event   { background: #2a1e0a; color: #f0a020; }
.badge--warn    { background: #2a1a0a; color: #e07030; }
.badge--gift    { background: #1a2a1a; color: #4caf50; }

.noti-date {
  font-size: 9px;
  color: #4a4a6a;
}

.noti-chevron {
  font-size: 9px;
  color: #3a3a5a;
  flex-shrink: 0;
}

.noti-card-content {
  padding: 0 10px 10px 42px;
  border-top: 1px solid #1a1a2c;
}

.noti-card-content p {
  margin: 0;
  padding: 4px 0;
  font-size: 10px;
  color: #8a8aaa;
  line-height: 1.5;
  border-bottom: 1px solid #14141f;
}

.noti-card-content p:last-child {
  border-bottom: none;
}
</style>

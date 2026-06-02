<template>
  <div class="ccgame-sdk-pane">
    <div v-if="entries.length" class="ccgame-sdk-changelog">
      <div v-for="(e, i) in entries" :key="i" class="ccgame-sdk-changelog-entry">
        <div class="ccgame-sdk-changelog-date">{{ e.date }}</div>
        <div class="ccgame-sdk-changelog-title">{{ e.title }}</div>
        <ul v-if="eLines(e).length" class="ccgame-sdk-changelog-body">
          <li v-for="(line, j) in eLines(e)" :key="j">{{ line }}</li>
        </ul>
      </div>
    </div>
    <div v-else class="ccgame-sdk-empty">Chưa có cập nhật</div>
  </div>
</template>

<script setup>
defineProps({ entries: { type: Array, default: () => [] } })

function eLines(e) {
  const body = e.body || e.content || ''
  return body.split('\n').filter(l => l.trim()).map(l => l.replace(/^[-*]\s*/, ''))
}
</script>

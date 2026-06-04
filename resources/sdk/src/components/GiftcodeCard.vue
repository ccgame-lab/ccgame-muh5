<template>
  <div class="ccsdk-giftcode">
    <div class="ccsdk-giftcode-header">
      <span class="ccsdk-giftcode-title">Đổi giftcode</span>
    </div>

    <div class="ccsdk-giftcode-form">
      <input
        v-model="code"
        type="text"
        class="ccsdk-giftcode-input"
        :disabled="loading"
        placeholder="Nhập mã giftcode..."
        maxlength="50"
        @keyup.enter="onRedeem"
      />
      <button
        class="ccsdk-giftcode-btn"
        :disabled="loading || !code"
        @click="onRedeem"
      >
        {{ loading ? 'Đang xử lý...' : 'Đổi' }}
      </button>
    </div>

    <div v-if="message" class="ccsdk-giftcode-msg" :class="'ccsdk-giftcode-msg--' + messageType">{{ message }}</div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useSdkState } from '../composables/useSdkState.js'

const { state, applyPointsReward } = useSdkState()

const code = ref('')
const loading = ref(false)
const message = ref('')
const messageType = ref('')

function validateBeforeRedeem() {
  const trimmed = code.value.trim()
  if (!trimmed) {
    message.value = 'Vui lòng nhập mã giftcode.'
    messageType.value = 'error'
    return false
  }
  return true
}

async function onRedeem() {
  message.value = ''
  messageType.value = ''

  if (!validateBeforeRedeem()) return

  const u = window.ccgame?.user || state.player.name
  if (!u) {
    message.value = 'Chưa xác thực.'
    messageType.value = 'error'
    return
  }

  loading.value = true
  try {
    const res = await fetch('/api/sdk/giftcode/redeem', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
      },
      body: JSON.stringify({
        code: code.value.trim(),
        u: u,
      }),
    })
    const data = await res.json()
    if (data.success) {
      applyPointsReward(data.reward.amount)
      code.value = ''
      message.value = data.message
      messageType.value = 'success'
    } else {
      message.value = data.message || 'Đã xảy ra lỗi.'
      messageType.value = 'error'
    }
  } catch (e) {
    message.value = 'Lỗi kết nối. Vui lòng thử lại.'
    messageType.value = 'error'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.ccsdk-giftcode {
  background: #161626;
  border: 1px solid rgba(120,100,255,0.18);
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 12px;
}

.ccsdk-giftcode-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.ccsdk-giftcode-title {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #8888aa;
}

.ccsdk-giftcode-form {
  display: flex;
  gap: 6px;
}

.ccsdk-giftcode-input {
  flex: 1;
  padding: 7px 10px;
  border-radius: 6px;
  border: 1px solid rgba(120,100,255,0.18);
  background: #1e1e32;
  color: #e8e8f0;
  font-size: 11px;
  outline: none;
  transition: border-color 0.15s;
}

.ccsdk-giftcode-input:focus {
  border-color: #7c6ff7;
}

.ccsdk-giftcode-input:disabled {
  opacity: 0.5;
}

.ccsdk-giftcode-btn {
  padding: 7px 14px;
  border-radius: 6px;
  border: none;
  background: #7c6ff7;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  transition: background 0.15s, opacity 0.15s;
  white-space: nowrap;
}

.ccsdk-giftcode-btn:hover:not(:disabled) {
  background: #6a5ee0;
}

.ccsdk-giftcode-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.ccsdk-giftcode-msg {
  margin-top: 8px;
  font-size: 10px;
  font-weight: 500;
  padding: 6px 8px;
  border-radius: 4px;
}

.ccsdk-giftcode-msg--success {
  background: rgba(76, 175, 80, 0.12);
  color: #81c784;
}

.ccsdk-giftcode-msg--error {
  background: rgba(244, 67, 54, 0.12);
  color: #ef9a9a;
}
</style>

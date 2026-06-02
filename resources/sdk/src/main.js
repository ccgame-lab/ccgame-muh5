import { createApp } from 'vue'
import App from './App.vue'
import './styles/sdk.css'

const root = document.getElementById('ccgame-sdk-root')
if (root) {
  createApp(App).mount(root)
}

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  root: resolve(__dirname),
  base: '/assets/sdk/',
  build: {
    outDir: resolve(__dirname, '../../public/assets/sdk'),
    emptyOutDir: true,
    // Giu toan bo CSS trong 1 file ccgame-sdk.css (assetFileNames fixed, tranh va ten khi
    // co async chunk). JS van duoc tach chunk theo dynamic import (SpinCard/MiningCard).
    cssCodeSplit: false,
    rollupOptions: {
      input: resolve(__dirname, 'src/main.js'),
      output: {
        entryFileNames: 'ccgame-sdk.js',
        chunkFileNames: 'ccgame-sdk-[hash].js',
        assetFileNames: 'ccgame-sdk.[ext]',
      },
    },
  },
})

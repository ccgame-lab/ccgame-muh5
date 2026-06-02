import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  root: resolve(__dirname),
  build: {
    outDir: resolve(__dirname, '../../public/assets/sdk'),
    emptyOutDir: true,
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

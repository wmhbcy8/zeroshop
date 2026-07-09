import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

const base = process.env.HJ_ADMIN_BASE || '/admin-vue/'

export default defineConfig({
  root: __dirname,
  base,
  server: {
    port: 5173,
    host: true,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true
      }
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src')
    }
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        index: path.resolve(__dirname, 'index.html')
      }
    }
  },
  plugins: [vue()]
})

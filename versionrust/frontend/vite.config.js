import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  server: {
    port: 5173,
    // En dev, on relaie les appels /api vers le backend Rust.
    proxy: {
      '/api': 'http://127.0.0.1:8788',
    },
  },
})

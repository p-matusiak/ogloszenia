import { fileURLToPath, URL } from 'node:url'

import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
// vitest/config, not vite: the `test` block below is not part of Vite's schema.
import { defineConfig } from 'vitest/config'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      refresh: true,
    }),
    vue(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    // The container publishes 5173 on host port 5174, so the HMR client in the
    // browser must be told where to reconnect.
    hmr: { host: 'localhost', clientPort: 5174 },
    watch: { ignored: ['**/storage/framework/views/**'] },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    include: ['resources/js/**/*.spec.ts'],
  },
})

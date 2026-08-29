import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

// Rende i mixin di breakpoint (mobile-first) disponibili in ogni blocco
// <style lang="scss"> senza dover scrivere @use in ogni componente.
const mixinsPath = fileURLToPath(
  new URL('./src/styles/mixins.scss', import.meta.url),
).replace(/\\/g, '/')

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.svg', 'favicon.ico', 'apple-touch-icon-180x180.png'],
      // Precache solo l'app shell (JS/CSS/HTML/icone del build). Nessuna
      // regola runtimeCaching per /api/*: le chiamate API non passano dalla
      // cache, sempre rete (coerente con "nessun caching aggressivo dei
      // dati API" in AGENTS.md/docs/pwa.md).
      manifest: {
        name: 'Gym Bros',
        short_name: 'Gym Bros',
        description: 'Condividi i tuoi allenamenti con gli amici',
        theme_color: '#aa3bff',
        background_color: '#ffffff',
        display: 'standalone',
        start_url: '/',
        scope: '/',
        lang: 'it',
        icons: [
          { src: 'pwa-64x64.png', sizes: '64x64', type: 'image/png' },
          { src: 'pwa-192x192.png', sizes: '192x192', type: 'image/png' },
          { src: 'pwa-512x512.png', sizes: '512x512', type: 'image/png' },
          {
            src: 'maskable-icon-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable',
          },
        ],
      },
    }),
  ],
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `@use "${mixinsPath}" as *;\n`,
      },
    },
  },
})

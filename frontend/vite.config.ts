import { fileURLToPath, URL } from 'node:url'
import { defineConfig, type Plugin } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

// Rende i mixin di breakpoint (mobile-first) disponibili in ogni blocco
// <style lang="scss"> senza dover scrivere @use in ogni componente.
const mixinsPath = fileURLToPath(
  new URL('./src/styles/mixins.scss', import.meta.url),
).replace(/\\/g, '/')

/**
 * Vite inietta di default <script type="module"> PRIMA di <link
 * rel="stylesheet"> nell'HTML di produzione. Lo script e' deferred (esegue
 * dopo il parsing) quindi non dovrebbe bloccare il CSS in teoria, ma in
 * pratica su alcuni browser mobile questo ordine causa un flash visibile di
 * contenuto senza stile (FOUC): l'app monta ed inietta il DOM prima che il
 * CSSOM sia pronto. Fix: sposta il/i <link rel="stylesheet"> prima del
 * primo <script type="module"> nell'HTML finale, cosi' il browser scopre e
 * blocca sul CSS per primo.
 */
function cssBeforeModuleScript(): Plugin {
  return {
    name: 'css-before-module-script',
    transformIndexHtml: {
      order: 'post',
      handler(html) {
        const cssLinks: string[] = []
        const withoutCss = html.replace(/\s*<link rel="stylesheet"[^>]*>/g, (match) => {
          cssLinks.push(match.trim())
          return ''
        })
        if (!cssLinks.length) return html

        const withCssFirst = withoutCss.replace(
          /<script type="module"/,
          `${cssLinks.join('\n    ')}\n    $&`,
        )
        return withCssFirst === withoutCss
          ? withoutCss.replace('</head>', `${cssLinks.join('\n    ')}\n  </head>`)
          : withCssFirst
      },
    },
  }
}

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    cssBeforeModuleScript(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.svg', 'favicon.ico', 'apple-touch-icon-180x180.png'],
      // injectManifest (non generateSW): serve un service worker custom
      // (src/sw.ts) per gestire gli eventi push/notificationclick (Fase 7).
      // Precache dell'app shell delegato comunque a workbox
      // (precacheAndRoute in src/sw.ts). Nessuna regola runtimeCaching per
      // /api/*: le chiamate API non passano dalla cache, sempre rete
      // (coerente con "nessun caching aggressivo dei dati API" in
      // AGENTS.md/docs/pwa.md).
      strategies: 'injectManifest',
      srcDir: 'src',
      filename: 'sw.ts',
      injectManifest: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,webmanifest}'],
      },
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

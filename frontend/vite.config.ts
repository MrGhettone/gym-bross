import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Rende i mixin di breakpoint (mobile-first) disponibili in ogni blocco
// <style lang="scss"> senza dover scrivere @use in ogni componente.
const mixinsPath = fileURLToPath(
  new URL('./src/styles/mixins.scss', import.meta.url),
).replace(/\\/g, '/')

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `@use "${mixinsPath}" as *;\n`,
      },
    },
  },
})

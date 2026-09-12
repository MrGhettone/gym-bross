<script lang="ts">
import { defineComponent } from 'vue'
import AppHeader from './components/AppHeader.vue'
import BottomNav from './components/BottomNav.vue'

export default defineComponent({
  components: { AppHeader, BottomNav },
})
</script>

<template>
  <div class="app-root">
    <AppHeader />
    <div class="app-shell" :class="{ 'app-shell--with-nav': $route.meta.requiresAuth }">
      <router-view />
    </div>
    <BottomNav v-if="$route.meta.requiresAuth" />
  </div>
</template>

<style scoped lang="scss">
.app-root {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.app-shell {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.app-shell--with-nav {
  padding-bottom: calc(4.25rem + env(safe-area-inset-bottom));
}
</style>

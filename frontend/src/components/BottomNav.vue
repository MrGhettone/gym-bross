<script lang="ts">
export default {}
</script>

<template>
  <nav class="bottom-nav">
    <router-link
      :to="{ name: 'feed' }"
      class="bottom-nav__item"
      :class="{ 'bottom-nav__item--active': $route.name === 'feed' || $route.name === 'feed-day' }"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path
          d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1v-5H9v5a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5Z"
        />
      </svg>
      <span class="sr-only">Feed</span>
    </router-link>

    <router-link
      :to="{ name: 'workouts' }"
      class="bottom-nav__item"
      :class="{ 'bottom-nav__item--active': $route.name === 'workouts' || $route.name === 'workout-detail' }"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 9v6M2 10v4M20 9v6M22 10v4M9 12h6" />
        <rect x="6" y="7" width="3" height="10" rx="1" />
        <rect x="15" y="7" width="3" height="10" rx="1" />
      </svg>
      <span class="sr-only">Allenamenti</span>
    </router-link>

    <router-link
      :to="{ name: 'friends' }"
      class="bottom-nav__item"
      :class="{ 'bottom-nav__item--active': $route.name === 'friends' }"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
        <circle cx="12" cy="7" r="4" />
      </svg>
      <span class="sr-only">Amici</span>
    </router-link>

    <router-link
      :to="{ name: 'settings' }"
      class="bottom-nav__item"
      :class="{ 'bottom-nav__item--active': $route.name === 'settings' }"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3" />
        <path
          d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"
        />
      </svg>
      <span class="sr-only">Impostazioni</span>
    </router-link>
  </nav>
</template>

<style scoped lang="scss">
.bottom-nav {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 10;
  display: flex;
  justify-content: space-around;
  align-items: center;
  background: var(--color-bg);
  border-top: 1px solid var(--color-border);
  padding: 0.375rem 0 calc(0.375rem + env(safe-area-inset-bottom));
}

.bottom-nav__item {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1.25rem;
  border-radius: 0.75rem;
  color: var(--color-text);

  svg {
    width: 1.5rem;
    height: 1.5rem;
  }

  &--active {
    color: var(--color-accent);
  }
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>

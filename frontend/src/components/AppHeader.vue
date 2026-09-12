<script lang="ts">
export default {}
</script>

<template>
  <header class="app-header">
    <svg class="app-header__mark" viewBox="0 0 32 32" aria-hidden="true">
      <rect width="32" height="32" rx="8" fill="var(--color-accent)" />
      <g fill="#fff">
        <rect x="6.5" y="9" width="3" height="14" rx="1.5" />
        <rect x="3" y="12" width="2.2" height="8" rx="1.1" />
        <rect x="22.5" y="9" width="3" height="14" rx="1.5" />
        <rect x="26.8" y="12" width="2.2" height="8" rx="1.1" />
        <rect x="9.5" y="14.5" width="13" height="3" rx="1.5" />
      </g>
    </svg>
    <span class="app-header__name">Gym<strong>Bros</strong></span>
  </header>
</template>

<style scoped lang="scss">
// min-height totale ~120px (100px minimo per non finire sotto
// fotocamera/notch, vedi main.scss, +20px extra su richiesta dell'utente);
// env(safe-area-inset-top) copre i telefoni con un ritaglio reale piu'
// grande di quel minimo.
.app-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 120px;
  box-sizing: border-box;
  padding: calc(env(safe-area-inset-top) + 1.25rem + 20px) 1rem 0.75rem;
  border-bottom: 1px solid var(--color-border);
}

.app-header__mark {
  width: 1.75rem;
  height: 1.75rem;
  flex-shrink: 0;
}

.app-header__name {
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--color-text-strong);
  letter-spacing: -0.01em;

  strong {
    color: var(--color-accent);
    font-weight: 700;
  }
}
</style>

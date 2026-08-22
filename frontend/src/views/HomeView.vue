<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, ApiError } from '../services/api'
import { useAuthStore } from '../stores/auth'

interface PingResponse {
  data: {
    status: string
    service: string
  }
}

const router = useRouter()
const auth = useAuthStore()

const backendStatus = ref<'checking' | 'online' | 'offline'>('checking')
const backendDetail = ref('')
const loggingOut = ref(false)

onMounted(async () => {
  try {
    const result = await api.get<PingResponse>('/ping')
    backendStatus.value = 'online'
    backendDetail.value = result.data.service
  } catch (error) {
    backendStatus.value = 'offline'
    backendDetail.value = error instanceof ApiError ? error.message : 'Impossibile contattare il backend'
  }
})

async function onLogout() {
  loggingOut.value = true
  try {
    await auth.logout()
    await router.push({ name: 'login' })
  } finally {
    loggingOut.value = false
  }
}
</script>

<template>
  <main class="home">
    <h1>Gym Bros</h1>
    <p v-if="auth.user">Ciao, {{ auth.user.username }}</p>
    <p class="status" :class="backendStatus">
      Backend: {{ backendStatus }}
      <span v-if="backendDetail">({{ backendDetail }})</span>
    </p>

    <nav class="nav">
      <router-link :to="{ name: 'friends' }">Amici</router-link>
    </nav>

    <button type="button" :disabled="loggingOut" @click="onLogout">
      {{ loggingOut ? 'Uscita in corso…' : 'Esci' }}
    </button>
  </main>
</template>

<style scoped lang="scss">
.home {
  padding: 1.5rem;
  text-align: center;

  @include tablet-up {
    padding: 2.5rem;
  }
}

.status {
  &.online {
    color: #16a34a;
  }

  &.offline {
    color: #dc2626;
  }

  &.checking {
    color: #6b7280;
  }
}

.nav {
  margin-top: 1.5rem;

  a {
    color: var(--color-accent);
  }
}

button {
  font: inherit;
  margin-top: 1.5rem;
  padding: 0.625rem 1.25rem;
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  background: var(--color-bg);
  color: var(--color-text-strong);
  cursor: pointer;

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}
</style>

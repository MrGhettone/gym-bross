<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, ApiError } from '../services/api'

interface PingResponse {
  data: {
    status: string
    service: string
  }
}

const status = ref<'checking' | 'online' | 'offline'>('checking')
const detail = ref('')

onMounted(async () => {
  try {
    const result = await api.get<PingResponse>('/ping')
    status.value = 'online'
    detail.value = result.data.service
  } catch (error) {
    status.value = 'offline'
    detail.value = error instanceof ApiError ? error.message : 'Impossibile contattare il backend'
  }
})
</script>

<template>
  <main class="home">
    <h1>Gym Bros</h1>
    <p>Fase 1 — Project setup</p>
    <p class="status" :class="status">
      Backend: {{ status }}
      <span v-if="detail">({{ detail }})</span>
    </p>
  </main>
</template>

<style scoped>
.home {
  padding: 1.5rem;
  text-align: center;
}

.status.online {
  color: #16a34a;
}

.status.offline {
  color: #dc2626;
}

.status.checking {
  color: #6b7280;
}
</style>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ApiError } from '../services/api'
import { workoutsService, type Workout } from '../services/workouts.service'

const router = useRouter()

const workouts = ref<Workout[]>([])
const status = ref<'loading' | 'ready'>('loading')
const starting = ref(false)
const errorMessage = ref('')

const active = ref<Workout | null>(null)

onMounted(async () => {
  await load()
})

async function load() {
  status.value = 'loading'
  workouts.value = await workoutsService.list()
  active.value = workouts.value.find((w) => w.status === 'active') ?? null
  status.value = 'ready'
}

async function onStart() {
  starting.value = true
  errorMessage.value = ''
  try {
    const workout = await workoutsService.start()
    await router.push({ name: 'workout-detail', params: { id: workout.id } })
  } catch (error) {
    errorMessage.value = error instanceof ApiError ? error.message : 'Impossibile contattare il backend'
  } finally {
    starting.value = false
  }
}

function statusLabel(workout: Workout): string {
  return { active: 'In corso', completed: 'Completato', cancelled: 'Annullato' }[workout.status]
}
</script>

<template>
  <main class="workouts">
    <h1>Allenamenti</h1>

    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

    <router-link v-if="active" :to="{ name: 'workout-detail', params: { id: active.id } }" class="active-link">
      Allenamento in corso — continua
    </router-link>
    <button v-else type="button" :disabled="starting" @click="onStart">
      {{ starting ? 'Avvio…' : 'Inizia allenamento' }}
    </button>

    <section class="history">
      <h2>Storico</h2>
      <p v-if="status === 'ready' && !workouts.length" class="empty">Nessun allenamento ancora.</p>
      <ul v-else class="list">
        <li v-for="w in workouts" :key="w.id">
          <router-link :to="{ name: 'workout-detail', params: { id: w.id } }" class="item">
            <span>{{ new Date(w.started_at).toLocaleDateString('it-IT') }}</span>
            <span class="badge" :class="w.status">{{ statusLabel(w) }}</span>
          </router-link>
        </li>
      </ul>
    </section>
  </main>
</template>

<style scoped lang="scss">
.workouts {
  padding: 1.5rem;
  max-width: 32rem;
  margin: 0 auto;

  @include tablet-up {
    padding: 2.5rem 1.5rem;
  }
}

.error {
  color: #dc2626;
  font-size: 0.875rem;
}

.active-link,
button {
  display: block;
  text-align: center;
  font: inherit;
  padding: 0.875rem;
  border: none;
  border-radius: 0.5rem;
  background: var(--color-accent);
  color: #fff;
  text-decoration: none;
  cursor: pointer;
  width: 100%;
  box-sizing: border-box;

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.history {
  margin-top: 2rem;

  h2 {
    margin: 0 0 0.75rem;
  }
}

.empty {
  color: var(--color-text);
  font-size: 0.875rem;
}

.list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  color: var(--color-text-strong);
  text-decoration: none;
}

.badge {
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 999px;
  background: var(--color-border);

  &.active {
    background: var(--color-accent-bg);
    color: var(--color-accent);
  }

  &.completed {
    background: rgba(22, 163, 74, 0.15);
    color: #16a34a;
  }

  &.cancelled {
    background: rgba(220, 38, 38, 0.15);
    color: #dc2626;
  }
}
</style>

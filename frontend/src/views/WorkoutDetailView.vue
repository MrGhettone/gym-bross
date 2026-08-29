<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ApiError } from '../services/api'
import { exercisesService } from '../services/exercises.service'
import { workoutsService, type Workout } from '../services/workouts.service'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const workoutId = Number(route.params.id)
const workout = ref<Workout | null>(null)
const loading = ref(true)
const errorMessage = ref('')

const newExerciseName = ref('')
const addingExercise = ref(false)

const setDrafts = reactive<Record<number, { weight: string; repetitions: string; duration: string; distance: string }>>({})
const loggingSetFor = ref<number | null>(null)

const finishing = ref(false)
const cancelling = ref(false)

const isActive = computed(() => workout.value?.status === 'active')
const isOwner = computed(() => workout.value?.user?.id === auth.user?.id)
const canEdit = computed(() => isActive.value && isOwner.value)

onMounted(load)

async function load() {
  loading.value = true
  workout.value = await workoutsService.show(workoutId)
  for (const we of workout.value.exercises ?? []) {
    setDrafts[we.id] ??= { weight: '', repetitions: '', duration: '', distance: '' }
  }
  loading.value = false
}

async function onAddExercise() {
  const name = newExerciseName.value.trim()
  if (!name) return

  addingExercise.value = true
  errorMessage.value = ''
  try {
    let exercise
    try {
      exercise = await exercisesService.create(name)
    } catch (error) {
      if (error instanceof ApiError && error.status === 422) {
        const matches = await exercisesService.list(name)
        const exact = matches.find((e) => e.name.toLowerCase() === name.toLowerCase())
        if (!exact) throw error
        exercise = exact
      } else {
        throw error
      }
    }

    await workoutsService.addExercise(workoutId, exercise.id)
    newExerciseName.value = ''
    await load()
  } catch (error) {
    errorMessage.value = error instanceof ApiError ? error.message : 'Impossibile contattare il backend'
  } finally {
    addingExercise.value = false
  }
}

async function onRemoveExercise(workoutExerciseId: number) {
  await workoutsService.removeExercise(workoutId, workoutExerciseId)
  await load()
}

async function onLogSet(workoutExerciseId: number) {
  const draft = setDrafts[workoutExerciseId]
  loggingSetFor.value = workoutExerciseId
  errorMessage.value = ''
  try {
    await workoutsService.logSet(workoutId, workoutExerciseId, {
      weight: draft.weight ? Number(draft.weight) : undefined,
      repetitions: draft.repetitions ? Number(draft.repetitions) : undefined,
      duration: draft.duration ? Number(draft.duration) : undefined,
      distance: draft.distance ? Number(draft.distance) : undefined,
    })
    draft.weight = ''
    draft.repetitions = ''
    draft.duration = ''
    draft.distance = ''
    await load()
  } catch (error) {
    errorMessage.value = error instanceof ApiError ? error.message : 'Impossibile contattare il backend'
  } finally {
    loggingSetFor.value = null
  }
}

async function onDeleteSet(setId: number) {
  await workoutsService.deleteSet(setId)
  await load()
}

async function onFinish() {
  finishing.value = true
  try {
    await workoutsService.finish(workoutId)
    await router.push({ name: 'workouts' })
  } finally {
    finishing.value = false
  }
}

async function onCancel() {
  cancelling.value = true
  try {
    await workoutsService.cancel(workoutId)
    await router.push({ name: 'workouts' })
  } finally {
    cancelling.value = false
  }
}
</script>

<template>
  <main class="workout" v-if="!loading && workout">
    <h1>Allenamento<span v-if="!isOwner && workout.user"> di {{ workout.user.username }}</span></h1>
    <p class="meta">
      {{ new Date(workout.started_at).toLocaleString('it-IT') }} — {{ workout.status }}
    </p>

    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

    <section v-for="we in workout.exercises" :key="we.id" class="exercise">
      <div class="exercise-header">
        <h2>{{ we.exercise.name }}</h2>
        <button v-if="canEdit" type="button" class="link" @click="onRemoveExercise(we.id)">Rimuovi</button>
      </div>

      <ul class="sets">
        <li v-for="s in we.sets" :key="s.id" class="set">
          <span>#{{ s.set_number }}</span>
          <span v-if="s.weight">{{ s.weight }} kg</span>
          <span v-if="s.repetitions">{{ s.repetitions }} rip</span>
          <span v-if="s.duration">{{ s.duration }} s</span>
          <span v-if="s.distance">{{ s.distance }} m</span>
          <button v-if="canEdit" type="button" class="link" @click="onDeleteSet(s.id)">×</button>
        </li>
      </ul>

      <form v-if="canEdit" class="set-form" @submit.prevent="onLogSet(we.id)">
        <input v-model="setDrafts[we.id].weight" type="number" step="0.01" placeholder="kg" />
        <input v-model="setDrafts[we.id].repetitions" type="number" placeholder="rip" />
        <input v-model="setDrafts[we.id].duration" type="number" placeholder="sec" />
        <input v-model="setDrafts[we.id].distance" type="number" step="0.01" placeholder="m" />
        <button type="submit" :disabled="loggingSetFor === we.id">+</button>
      </form>
    </section>

    <form v-if="canEdit" class="add-exercise" @submit.prevent="onAddExercise">
      <input v-model="newExerciseName" type="text" placeholder="Nome esercizio" required />
      <button type="submit" :disabled="addingExercise">
        {{ addingExercise ? '…' : 'Aggiungi esercizio' }}
      </button>
    </form>

    <div v-if="canEdit" class="lifecycle">
      <button type="button" :disabled="finishing" @click="onFinish">
        {{ finishing ? 'Salvataggio…' : 'Termina allenamento' }}
      </button>
      <button type="button" class="secondary" :disabled="cancelling" @click="onCancel">Annulla allenamento</button>
    </div>
  </main>
</template>

<style scoped lang="scss">
.workout {
  padding: 1.5rem;
  max-width: 32rem;
  margin: 0 auto;

  @include tablet-up {
    padding: 2.5rem 1.5rem;
  }
}

.meta {
  color: var(--color-text);
  font-size: 0.875rem;
  margin-top: -0.5rem;
}

.error {
  color: #dc2626;
  font-size: 0.875rem;
}

.exercise {
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  padding: 1rem;
  margin-bottom: 1rem;
}

.exercise-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  h2 {
    margin: 0;
    font-size: 1.05rem;
  }
}

.link {
  background: none;
  border: none;
  color: var(--color-accent);
  font: inherit;
  cursor: pointer;
  padding: 0;
}

.sets {
  list-style: none;
  padding: 0;
  margin: 0.75rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.set {
  display: flex;
  gap: 0.75rem;
  font-size: 0.875rem;
  align-items: center;

  span:first-child {
    color: var(--color-text);
  }

  .link {
    margin-left: auto;
  }
}

.set-form {
  display: grid;
  grid-template-columns: repeat(4, 1fr) auto;
  gap: 0.375rem;

  input {
    font: inherit;
    padding: 0.5rem;
    border: 1px solid var(--color-border);
    border-radius: 0.375rem;
    background: var(--color-bg);
    color: var(--color-text-strong);
    width: 100%;
    box-sizing: border-box;
    min-width: 0;
  }

  button {
    font: inherit;
    padding: 0.5rem 0.75rem;
    border: none;
    border-radius: 0.375rem;
    background: var(--color-accent);
    color: #fff;
    cursor: pointer;

    &:disabled {
      opacity: 0.6;
    }
  }
}

.add-exercise {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;

  input {
    flex: 1;
    font: inherit;
    padding: 0.75rem;
    border: 1px solid var(--color-border);
    border-radius: 0.5rem;
    background: var(--color-bg);
    color: var(--color-text-strong);
  }

  button {
    font: inherit;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 0.5rem;
    background: var(--color-accent);
    color: #fff;
    cursor: pointer;
    white-space: nowrap;

    &:disabled {
      opacity: 0.6;
    }
  }
}

.lifecycle {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;

  button {
    font: inherit;
    padding: 0.875rem;
    border: 1px solid var(--color-border);
    border-radius: 0.5rem;
    background: var(--color-accent);
    color: #fff;
    cursor: pointer;

    &:disabled {
      opacity: 0.6;
    }

    &.secondary {
      background: transparent;
      color: var(--color-text-strong);
    }
  }
}
</style>

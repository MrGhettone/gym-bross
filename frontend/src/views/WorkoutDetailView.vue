<script lang="ts">
import { defineComponent } from 'vue'
import { ApiError } from '../services/api'
import { exercisesService } from '../services/exercises.service'
import { workoutsService, type Workout, type WorkoutSet } from '../services/workouts.service'
import { useAuthStore } from '../stores/auth'

type SetDraft = { weight: string; repetitions: string; duration: string; distance: string }

export default defineComponent({
  data() {
    return {
      auth: useAuthStore(),
      workoutId: 0,
      workout: null as Workout | null,
      loading: true,
      errorMessage: '',
      newExerciseName: '',
      addingExercise: false,
      setDrafts: {} as Record<number, SetDraft>,
      loggingSetFor: null as number | null,
      editingSetId: null as number | null,
      editDraft: { weight: '', repetitions: '', duration: '', distance: '' } as SetDraft,
      savingEdit: false,
      finishing: false,
      cancelling: false,
    }
  },
  computed: {
    isActive(): boolean {
      return this.workout?.status === 'active'
    },
    isOwner(): boolean {
      return this.workout?.user?.id === this.auth.user?.id
    },
    canEdit(): boolean {
      return this.isActive && this.isOwner
    },
  },
  created() {
    this.workoutId = Number(this.$route.params.id)
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      this.loading = true
      this.workout = await workoutsService.show(this.workoutId)
      for (const we of this.workout.exercises ?? []) {
        this.setDrafts[we.id] ??= { weight: '', repetitions: '', duration: '', distance: '' }
      }
      this.loading = false
    },
    async onAddExercise() {
      const name = this.newExerciseName.trim()
      if (!name) return

      this.addingExercise = true
      this.errorMessage = ''
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

        await workoutsService.addExercise(this.workoutId, exercise.id)
        this.newExerciseName = ''
        await this.load()
      } catch (error) {
        this.errorMessage = error instanceof ApiError ? error.message : 'Impossibile contattare il backend'
      } finally {
        this.addingExercise = false
      }
    },
    async onRemoveExercise(workoutExerciseId: number) {
      await workoutsService.removeExercise(this.workoutId, workoutExerciseId)
      await this.load()
    },
    async onLogSet(workoutExerciseId: number) {
      const draft = this.setDrafts[workoutExerciseId]
      this.loggingSetFor = workoutExerciseId
      this.errorMessage = ''
      try {
        await workoutsService.logSet(this.workoutId, workoutExerciseId, {
          weight: draft.weight ? Number(draft.weight) : undefined,
          repetitions: draft.repetitions ? Number(draft.repetitions) : undefined,
          duration: draft.duration ? Number(draft.duration) : undefined,
          distance: draft.distance ? Number(draft.distance) : undefined,
        })
        draft.weight = ''
        draft.repetitions = ''
        draft.duration = ''
        draft.distance = ''
        await this.load()
      } catch (error) {
        this.errorMessage = error instanceof ApiError ? error.message : 'Impossibile contattare il backend'
      } finally {
        this.loggingSetFor = null
      }
    },
    async onDeleteSet(setId: number) {
      await workoutsService.deleteSet(setId)
      await this.load()
    },
    onStartEdit(set: WorkoutSet) {
      this.editingSetId = set.id
      this.editDraft.weight = set.weight ?? ''
      this.editDraft.repetitions = set.repetitions?.toString() ?? ''
      this.editDraft.duration = set.duration?.toString() ?? ''
      this.editDraft.distance = set.distance?.toString() ?? ''
    },
    onCancelEdit() {
      this.editingSetId = null
    },
    async onSaveEdit(setId: number) {
      this.savingEdit = true
      this.errorMessage = ''
      try {
        await workoutsService.updateSet(setId, {
          weight: this.editDraft.weight ? Number(this.editDraft.weight) : undefined,
          repetitions: this.editDraft.repetitions ? Number(this.editDraft.repetitions) : undefined,
          duration: this.editDraft.duration ? Number(this.editDraft.duration) : undefined,
          distance: this.editDraft.distance ? Number(this.editDraft.distance) : undefined,
        })
        this.editingSetId = null
        await this.load()
      } catch (error) {
        this.errorMessage = error instanceof ApiError ? error.message : 'Impossibile contattare il backend'
      } finally {
        this.savingEdit = false
      }
    },
    async onFinish() {
      this.finishing = true
      try {
        await workoutsService.finish(this.workoutId)
        await this.$router.push({ name: 'workouts' })
      } finally {
        this.finishing = false
      }
    },
    async onCancel() {
      this.cancelling = true
      try {
        await workoutsService.cancel(this.workoutId)
        await this.$router.push({ name: 'workouts' })
      } finally {
        this.cancelling = false
      }
    },
  },
})
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
          <template v-if="editingSetId === s.id">
            <form class="set-form set-form--edit" @submit.prevent="onSaveEdit(s.id)">
              <input v-model="editDraft.weight" type="number" step="0.01" placeholder="kg" />
              <input v-model="editDraft.repetitions" type="number" placeholder="rip" />
              <input v-model="editDraft.duration" type="number" placeholder="sec" />
              <input v-model="editDraft.distance" type="number" step="0.01" placeholder="m" />
              <button type="submit" :disabled="savingEdit">✓</button>
              <button type="button" class="link" @click="onCancelEdit">✕</button>
            </form>
          </template>
          <template v-else>
            <span>#{{ s.set_number }}</span>
            <span v-if="s.weight">{{ s.weight }} kg</span>
            <span v-if="s.repetitions">{{ s.repetitions }} rip</span>
            <span v-if="s.duration">{{ s.duration }} s</span>
            <span v-if="s.distance">{{ s.distance }} m</span>
            <button v-if="canEdit" type="button" class="link" @click="onStartEdit(s)">Modifica</button>
            <button v-if="canEdit" type="button" class="link" @click="onDeleteSet(s.id)">×</button>
          </template>
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

  .set-form--edit {
    width: 100%;
  }
}

.set-form {
  display: grid;
  grid-template-columns: repeat(4, 1fr) auto;
  gap: 0.375rem;

  &--edit {
    grid-template-columns: repeat(4, 1fr) auto auto;
  }

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

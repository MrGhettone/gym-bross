<script lang="ts">
import { defineComponent } from 'vue'
import { feedService, type FeedWorkout } from '../services/feed.service'
import { useAuthStore } from '../stores/auth'

interface GanttBar {
  workout: FeedWorkout
  leftPct: number
  widthPct: number
  ongoing: boolean
  startLabel: string
  endLabel: string
}

interface GanttRow {
  userId: number
  label: string
  isSelf: boolean
  bars: GanttBar[]
}

const MIN_WIDTH_PCT = 3
const PAD_MS = 15 * 60 * 1000
const MIN_SPAN_MS = 30 * 60 * 1000

function timeLabel(date: Date): string {
  return date.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })
}

export default defineComponent({
  data() {
    return {
      auth: useAuthStore(),
      date: '',
      workouts: [] as FeedWorkout[],
      loading: true,
      ticks: [] as string[],
    }
  },
  computed: {
    dateLabel(): string {
      const [year, month, day] = this.date.split('-').map(Number)
      return new Date(year, month - 1, day).toLocaleDateString('it-IT', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      })
    },
    scale(): { start: number; end: number } {
      const starts = this.workouts.map((w) => new Date(w.started_at).getTime())
      const ends = this.workouts.map((w) => (w.finished_at ? new Date(w.finished_at).getTime() : Date.now()))

      const start = Math.min(...starts) - PAD_MS
      const end = Math.max(Math.max(...ends) + PAD_MS, start + MIN_SPAN_MS)
      return { start, end }
    },
    rows(): GanttRow[] {
      if (!this.workouts.length) return []

      const { start: scaleStart, end: scaleEnd } = this.scale
      const span = scaleEnd - scaleStart
      const rowsByUser = new Map<number, GanttRow>()

      for (const workout of this.workouts) {
        const start = new Date(workout.started_at).getTime()
        const end = workout.finished_at ? new Date(workout.finished_at).getTime() : Date.now()

        const bar: GanttBar = {
          workout,
          leftPct: ((start - scaleStart) / span) * 100,
          widthPct: Math.max(((end - start) / span) * 100, MIN_WIDTH_PCT),
          ongoing: !workout.finished_at,
          startLabel: timeLabel(new Date(start)),
          endLabel: workout.finished_at ? timeLabel(new Date(end)) : 'in corso',
        }

        let row = rowsByUser.get(workout.user.id)
        if (!row) {
          const isSelf = workout.user.id === this.auth.user?.id
          row = { userId: workout.user.id, label: isSelf ? 'Tu' : workout.user.username, isSelf, bars: [] }
          rowsByUser.set(workout.user.id, row)
        }
        row.bars.push(bar)
      }

      return Array.from(rowsByUser.values())
    },
    axisTicks(): string[] {
      if (!this.workouts.length) return []

      const { start: scaleStart, end: scaleEnd } = this.scale
      const steps = 4
      const ticks: string[] = []
      for (let i = 0; i <= steps; i++) {
        ticks.push(timeLabel(new Date(scaleStart + ((scaleEnd - scaleStart) * i) / steps)))
      }
      return ticks
    },
  },
  created() {
    this.date = this.$route.params.date as string
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      this.loading = true
      this.workouts = await feedService.day(this.date)
      this.loading = false
    },
  },
})
</script>

<template>
  <main class="feed-day">
    <router-link :to="{ name: 'feed' }" class="back">‹ Calendario</router-link>
    <h1>{{ dateLabel }}</h1>

    <p v-if="!loading && !workouts.length" class="empty">Nessuna attività in questo giorno.</p>

    <div v-else-if="!loading" class="gantt">
      <div class="gantt__axis">
        <span v-for="(tick, i) in axisTicks" :key="i">{{ tick }}</span>
      </div>

      <div class="gantt__rows">
        <div v-for="row in rows" :key="row.userId" class="gantt__row">
          <span class="gantt__label" :class="{ 'gantt__label--self': row.isSelf }">
            {{ row.label }}
          </span>
          <span class="gantt__track">
            <router-link
              v-for="bar in row.bars"
              :key="bar.workout.id"
              :to="{ name: 'workout-detail', params: { id: bar.workout.id } }"
              class="gantt__bar"
              :class="{ 'gantt__bar--self': row.isSelf, 'gantt__bar--ongoing': bar.ongoing }"
              :style="{ left: bar.leftPct + '%', width: bar.widthPct + '%' }"
            >
              {{ bar.startLabel }}–{{ bar.endLabel }}
            </router-link>
          </span>
        </div>
      </div>
    </div>
  </main>
</template>

<style scoped lang="scss">
.feed-day {
  padding: 1.5rem;
  max-width: 32rem;
  margin: 0 auto;

  @include tablet-up {
    padding: 2.5rem 1.5rem;
  }
}

.back {
  display: inline-block;
  margin-bottom: 0.75rem;
  color: var(--color-accent);
  font-size: 0.875rem;
  text-decoration: none;
}

h1 {
  text-transform: capitalize;
  font-size: 1.15rem;
}

.empty {
  color: var(--color-text);
  font-size: 0.875rem;
  margin-top: 1.5rem;
}

.gantt {
  margin-top: 1.5rem;
}

.gantt__axis {
  display: flex;
  justify-content: space-between;
  padding-left: 4.5rem;
  font-size: 0.7rem;
  color: var(--color-text);
  margin-bottom: 0.5rem;
}

.gantt__rows {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

.gantt__row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.gantt__label {
  flex: 0 0 4rem;
  font-size: 0.8125rem;
  color: var(--color-text-strong);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;

  &--self {
    color: var(--color-accent);
    font-weight: 600;
  }
}

.gantt__track {
  position: relative;
  flex: 1;
  height: 2rem;
  background: var(--color-accent-bg);
  border-radius: 0.375rem;
}

.gantt__bar {
  position: absolute;
  top: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  padding: 0 0.375rem;
  border-radius: 0.375rem;
  background: var(--color-text);
  color: var(--color-bg);
  font-size: 0.6875rem;
  white-space: nowrap;
  overflow: hidden;
  text-decoration: none;

  &--self {
    background: var(--color-accent);
  }

  &--ongoing {
    background-image: repeating-linear-gradient(
      45deg,
      rgb(255 255 255 / 25%),
      rgb(255 255 255 / 25%) 4px,
      transparent 4px,
      transparent 8px
    );
  }
}
</style>

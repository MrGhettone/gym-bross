<script lang="ts">
import { defineComponent } from 'vue'
import { feedService } from '../services/feed.service'

interface CalendarCell {
  date: string
  day: number
  inCurrentMonth: boolean
  isToday: boolean
  count: number
}

const WEEKDAY_LABELS = ['L', 'M', 'M', 'G', 'V', 'S', 'D']
const MONTH_LABELS = [
  'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
  'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre',
]

function pad(n: number): string {
  return n.toString().padStart(2, '0')
}

function formatDate(date: Date): string {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

export default defineComponent({
  data() {
    const today = new Date()
    return {
      year: today.getFullYear(),
      month: today.getMonth(), // 0-based
      todayKey: formatDate(today),
      countsByDate: {} as Record<string, number>,
      status: 'loading' as 'loading' | 'ready',
      weekdayLabels: WEEKDAY_LABELS,
    }
  },
  computed: {
    monthKey(): string {
      return `${this.year}-${pad(this.month + 1)}`
    },
    monthLabel(): string {
      return `${MONTH_LABELS[this.month]} ${this.year}`
    },
    calendarDays(): CalendarCell[] {
      const firstOfMonth = new Date(this.year, this.month, 1)
      const firstWeekday = (firstOfMonth.getDay() + 6) % 7 // 0 = lunedì
      const daysInMonth = new Date(this.year, this.month + 1, 0).getDate()
      const totalCells = Math.ceil((firstWeekday + daysInMonth) / 7) * 7

      const cells: CalendarCell[] = []
      for (let i = 0; i < totalCells; i++) {
        const date = new Date(this.year, this.month, i - firstWeekday + 1)
        const key = formatDate(date)
        cells.push({
          date: key,
          day: date.getDate(),
          inCurrentMonth: date.getMonth() === this.month,
          isToday: key === this.todayKey,
          count: this.countsByDate[key] ?? 0,
        })
      }
      return cells
    },
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      this.status = 'loading'
      const rows = await feedService.summary(this.monthKey)
      this.countsByDate = Object.fromEntries(rows.map((r) => [r.date, r.count]))
      this.status = 'ready'
    },
    onPrevMonth() {
      this.shiftMonth(-1)
    },
    onNextMonth() {
      this.shiftMonth(1)
    },
    shiftMonth(delta: number) {
      const date = new Date(this.year, this.month + delta, 1)
      this.year = date.getFullYear()
      this.month = date.getMonth()
      this.load()
    },
    onSelectDay(cell: CalendarCell) {
      this.$router.push({ name: 'feed-day', params: { date: cell.date } })
    },
  },
})
</script>

<template>
  <main class="feed">
    <h1>Feed</h1>

    <div class="calendar">
      <div class="calendar__header">
        <button type="button" class="nav-btn" @click="onPrevMonth" aria-label="Mese precedente">‹</button>
        <span class="calendar__month">{{ monthLabel }}</span>
        <button type="button" class="nav-btn" @click="onNextMonth" aria-label="Mese successivo">›</button>
      </div>

      <div class="calendar__weekdays">
        <span v-for="(label, i) in weekdayLabels" :key="i">{{ label }}</span>
      </div>

      <div class="calendar__grid">
        <button
          v-for="cell in calendarDays"
          :key="cell.date"
          type="button"
          class="day"
          :class="{ 'day--outside': !cell.inCurrentMonth, 'day--today': cell.isToday, 'day--active': cell.count > 0 }"
          @click="onSelectDay(cell)"
        >
          <span class="day__number">{{ cell.day }}</span>
          <span v-if="cell.count > 0" class="day__dot" :class="{ 'day__dot--multi': cell.count > 1 }"></span>
        </button>
      </div>
    </div>

    <p v-if="status === 'ready'" class="hint">Tocca un giorno per vedere gli allenamenti di quella data.</p>
  </main>
</template>

<style scoped lang="scss">
.feed {
  padding: 1.5rem;
  max-width: 32rem;
  margin: 0 auto;

  @include tablet-up {
    padding: 2.5rem 1.5rem;
  }
}

.calendar {
  margin-top: 1rem;
}

.calendar__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.calendar__month {
  font-weight: 600;
  color: var(--color-text-strong);
  text-transform: capitalize;
}

.nav-btn {
  font: inherit;
  font-size: 1.25rem;
  line-height: 1;
  width: 2.25rem;
  height: 2.25rem;
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  background: var(--color-bg);
  color: var(--color-text-strong);
  cursor: pointer;
}

.calendar__weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  text-align: center;
  font-size: 0.75rem;
  color: var(--color-text);
  margin-bottom: 0.375rem;
}

.calendar__grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0.25rem;
}

.day {
  position: relative;
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 0.5rem;
  background: transparent;
  color: var(--color-text-strong);
  font: inherit;
  cursor: pointer;

  &--outside {
    color: var(--color-text);
    opacity: 0.4;
  }

  &--today .day__number {
    font-weight: 700;
  }

  &--today {
    box-shadow: inset 0 0 0 1px var(--color-accent-border);
  }

  &--active {
    background: var(--color-accent-bg);
  }
}

.day__dot {
  position: absolute;
  bottom: 0.3rem;
  width: 0.3rem;
  height: 0.3rem;
  border-radius: 50%;
  background: var(--color-accent);

  &--multi {
    box-shadow: 0.3rem 0 0 var(--color-accent);
  }
}

.hint {
  margin-top: 1.25rem;
  font-size: 0.8125rem;
  color: var(--color-text);
  text-align: center;
}
</style>

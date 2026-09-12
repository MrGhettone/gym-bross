<script lang="ts">
import { defineComponent } from 'vue'
import { feedService, type FeedWorkout } from '../services/feed.service'

export default defineComponent({
  data() {
    return {
      items: [] as FeedWorkout[],
      status: 'loading' as 'loading' | 'ready',
    }
  },
  async mounted() {
    this.items = await feedService.list()
    this.status = 'ready'
  },
  methods: {
    statusLabel(item: FeedWorkout): string {
      return item.status === 'active' ? 'sta allenandosi' : 'ha completato un allenamento'
    },
  },
})
</script>

<template>
  <main class="feed">
    <h1>Feed</h1>

    <p v-if="status === 'ready' && !items.length" class="empty">
      Nessuna attività dai tuoi amici, per ora.
    </p>

    <ul v-else class="list">
      <li v-for="item in items" :key="item.id">
        <router-link :to="{ name: 'workout-detail', params: { id: item.id } }" class="card">
          <p class="line">
            <strong>{{ item.user.username }}</strong> {{ statusLabel(item) }}
          </p>
          <p class="meta">
            {{ item.exercises_count }} esercizi · {{ new Date(item.started_at).toLocaleString('it-IT') }}
          </p>
        </router-link>
      </li>
    </ul>
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

.card {
  display: block;
  padding: 0.875rem;
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  text-decoration: none;
  color: var(--color-text-strong);
}

.line {
  margin: 0 0 0.25rem;
}

.meta {
  margin: 0;
  color: var(--color-text);
  font-size: 0.8125rem;
}
</style>

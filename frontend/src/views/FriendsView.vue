<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ApiError } from '../services/api'
import { useFriendsStore } from '../stores/friends'

const friends = useFriendsStore()

const username = ref('')
const sending = ref(false)
const errorMessage = ref('')
const pendingActionId = ref<number | null>(null)

onMounted(() => {
  friends.fetchAll()
})

async function onSendRequest() {
  sending.value = true
  errorMessage.value = ''

  try {
    await friends.sendRequest(username.value)
    username.value = ''
  } catch (error) {
    errorMessage.value = error instanceof ApiError ? error.message : 'Impossibile contattare il backend'
  } finally {
    sending.value = false
  }
}

async function runAction(id: number, action: (id: number) => Promise<void>) {
  pendingActionId.value = id
  try {
    await action(id)
  } finally {
    pendingActionId.value = null
  }
}
</script>

<template>
  <main class="friends">
    <h1>Amici</h1>

    <form class="request-form" @submit.prevent="onSendRequest">
      <label class="field">
        <span>Aggiungi per username</span>
        <input v-model="username" type="text" required placeholder="es. luigi" />
      </label>
      <button type="submit" :disabled="sending || !username">
        {{ sending ? 'Invio…' : 'Invia richiesta' }}
      </button>
      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
    </form>

    <section v-if="friends.incomingPending.length" class="section">
      <h2>Richieste ricevute</h2>
      <ul class="list">
        <li v-for="f in friends.incomingPending" :key="f.id" class="item">
          <span>{{ f.requester.username }}</span>
          <div class="actions">
            <button
              type="button"
              :disabled="pendingActionId === f.id"
              @click="runAction(f.id, friends.accept)"
            >
              Accetta
            </button>
            <button
              type="button"
              class="secondary"
              :disabled="pendingActionId === f.id"
              @click="runAction(f.id, friends.reject)"
            >
              Rifiuta
            </button>
          </div>
        </li>
      </ul>
    </section>

    <section v-if="friends.outgoingPending.length" class="section">
      <h2>Richieste inviate</h2>
      <ul class="list">
        <li v-for="f in friends.outgoingPending" :key="f.id" class="item">
          <span>{{ f.addressee.username }}</span>
          <div class="actions">
            <button
              type="button"
              class="secondary"
              :disabled="pendingActionId === f.id"
              @click="runAction(f.id, friends.remove)"
            >
              Annulla
            </button>
          </div>
        </li>
      </ul>
    </section>

    <section class="section">
      <h2>I tuoi amici</h2>
      <p v-if="friends.status === 'ready' && !friends.accepted.length" class="empty">
        Nessun amico ancora — cerca qualcuno per username.
      </p>
      <ul v-else class="list">
        <li v-for="f in friends.accepted" :key="f.id" class="item">
          <span>{{ f.direction === 'outgoing' ? f.addressee.username : f.requester.username }}</span>
          <div class="actions">
            <button
              type="button"
              class="secondary"
              :disabled="pendingActionId === f.id"
              @click="runAction(f.id, friends.remove)"
            >
              Rimuovi
            </button>
          </div>
        </li>
      </ul>
    </section>
  </main>
</template>

<style scoped lang="scss">
.friends {
  padding: 1.5rem;
  max-width: 32rem;
  margin: 0 auto;

  @include tablet-up {
    padding: 2.5rem 1.5rem;
  }
}

.request-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin: 1.5rem 0 2rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  font-size: 0.875rem;
  color: var(--color-text-strong);

  input {
    font: inherit;
    padding: 0.75rem;
    border: 1px solid var(--color-border);
    border-radius: 0.5rem;
    background: var(--color-bg);
    color: var(--color-text-strong);

    &:focus-visible {
      outline: 2px solid var(--color-accent);
      outline-offset: 1px;
    }
  }
}

button {
  font: inherit;
  padding: 0.625rem 1.125rem;
  border: none;
  border-radius: 0.5rem;
  background: var(--color-accent);
  color: #fff;
  cursor: pointer;

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  &.secondary {
    background: transparent;
    color: var(--color-text-strong);
    border: 1px solid var(--color-border);
  }
}

.error {
  color: #dc2626;
  font-size: 0.875rem;
  margin: 0;
}

.section {
  margin-bottom: 2rem;

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
  gap: 0.75rem;

  .actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
  }
}
</style>

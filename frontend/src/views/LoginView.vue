<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ApiError } from '../services/api'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const submitting = ref(false)
const errorMessage = ref('')

async function onSubmit() {
  submitting.value = true
  errorMessage.value = ''

  try {
    await auth.login({ email: email.value, password: password.value })
    await router.push({ name: 'home' })
  } catch (error) {
    errorMessage.value =
      error instanceof ApiError ? error.body.message : 'Impossibile contattare il backend'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <main class="login">
    <h1>Accedi</h1>

    <form class="login__form" @submit.prevent="onSubmit">
      <label class="field">
        <span>Email</span>
        <input v-model="email" type="email" autocomplete="email" required />
      </label>

      <label class="field">
        <span>Password</span>
        <input v-model="password" type="password" autocomplete="current-password" required />
      </label>

      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

      <button type="submit" :disabled="submitting">
        {{ submitting ? 'Accesso in corso…' : 'Accedi' }}
      </button>
    </form>

    <p class="switch">
      Non hai un account?
      <router-link :to="{ name: 'register' }">Registrati</router-link>
    </p>
  </main>
</template>

<style scoped lang="scss">
.login {
  padding: 1.5rem;
  max-width: 24rem;
  margin: 0 auto;

  @include tablet-up {
    padding: 3rem 1.5rem;
  }
}

.login__form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-top: 1.5rem;
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

button[type='submit'] {
  font: inherit;
  padding: 0.75rem;
  border: none;
  border-radius: 0.5rem;
  background: var(--color-accent);
  color: #fff;
  cursor: pointer;

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.error {
  color: #dc2626;
  font-size: 0.875rem;
  margin: 0;
}

.switch {
  margin-top: 1.5rem;
  font-size: 0.875rem;
  text-align: center;

  a {
    color: var(--color-accent);
  }
}
</style>

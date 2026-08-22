<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ApiError } from '../services/api'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const username = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

async function onSubmit() {
  submitting.value = true
  errorMessage.value = ''
  fieldErrors.value = {}

  try {
    await auth.register({
      username: username.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    await router.push({ name: 'home' })
  } catch (error) {
    if (error instanceof ApiError) {
      errorMessage.value = error.body.message
      fieldErrors.value = error.body.errors ?? {}
    } else {
      errorMessage.value = 'Impossibile contattare il backend'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <main class="register">
    <h1>Crea account</h1>

    <form class="register__form" @submit.prevent="onSubmit">
      <label class="field">
        <span>Username</span>
        <input v-model="username" type="text" autocomplete="username" required />
        <span v-if="fieldErrors.username" class="field-error">{{ fieldErrors.username[0] }}</span>
      </label>

      <label class="field">
        <span>Email</span>
        <input v-model="email" type="email" autocomplete="email" required />
        <span v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email[0] }}</span>
      </label>

      <label class="field">
        <span>Password</span>
        <input v-model="password" type="password" autocomplete="new-password" required />
        <span v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password[0] }}</span>
      </label>

      <label class="field">
        <span>Conferma password</span>
        <input v-model="passwordConfirmation" type="password" autocomplete="new-password" required />
      </label>

      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

      <button type="submit" :disabled="submitting">
        {{ submitting ? 'Creazione in corso…' : 'Registrati' }}
      </button>
    </form>

    <p class="switch">
      Hai già un account?
      <router-link :to="{ name: 'login' }">Accedi</router-link>
    </p>
  </main>
</template>

<style scoped lang="scss">
.register {
  padding: 1.5rem;
  max-width: 24rem;
  margin: 0 auto;

  @include tablet-up {
    padding: 3rem 1.5rem;
  }
}

.register__form {
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

.field-error {
  color: #dc2626;
  font-size: 0.8125rem;
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

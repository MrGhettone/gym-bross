<script lang="ts">
import { defineComponent } from 'vue'
import { api, ApiError } from '../services/api'
import { notificationsService } from '../services/notifications.service'
import { useAuthStore } from '../stores/auth'

interface PingResponse {
  data: {
    status: string
    service: string
  }
}

export default defineComponent({
  data() {
    return {
      auth: useAuthStore(),
      backendStatus: 'checking' as 'checking' | 'online' | 'offline',
      backendDetail: '',
      loggingOut: false,
      notificationsSupported: notificationsService.isSupported(),
      notificationsEnabled: false,
      notificationsBusy: false,
      notificationsError: '',
    }
  },
  async mounted() {
    try {
      const result = await api.get<PingResponse>('/ping')
      this.backendStatus = 'online'
      this.backendDetail = result.data.service
    } catch (error) {
      this.backendStatus = 'offline'
      this.backendDetail = error instanceof ApiError ? error.message : 'Impossibile contattare il backend'
    }

    if (this.notificationsSupported) {
      this.notificationsEnabled = (await notificationsService.getSubscription()) !== null
    }
  },
  methods: {
    async onToggleNotifications() {
      this.notificationsBusy = true
      this.notificationsError = ''
      try {
        if (this.notificationsEnabled) {
          await notificationsService.disable()
          this.notificationsEnabled = false
        } else {
          await notificationsService.enable()
          this.notificationsEnabled = true
        }
      } catch {
        this.notificationsError = 'Impossibile attivare le notifiche (permesso negato o browser non supportato)'
      } finally {
        this.notificationsBusy = false
      }
    },
    async onLogout() {
      this.loggingOut = true
      try {
        await this.auth.logout()
        await this.$router.push({ name: 'login' })
      } finally {
        this.loggingOut = false
      }
    },
  },
})
</script>

<template>
  <main class="settings">
    <h1>Impostazioni</h1>
    <p v-if="auth.user">Ciao, {{ auth.user.username }}</p>
    <p class="status" :class="backendStatus">
      Backend: {{ backendStatus }}
      <span v-if="backendDetail">({{ backendDetail }})</span>
    </p>

    <div v-if="notificationsSupported" class="notifications">
      <button type="button" :disabled="notificationsBusy" @click="onToggleNotifications">
        {{ notificationsEnabled ? 'Disattiva notifiche' : 'Attiva notifiche' }}
      </button>
      <p v-if="notificationsError" class="error">{{ notificationsError }}</p>
    </div>

    <button type="button" class="logout" :disabled="loggingOut" @click="onLogout">
      {{ loggingOut ? 'Uscita in corso…' : 'Esci' }}
    </button>
  </main>
</template>

<style scoped lang="scss">
.settings {
  padding: 1.5rem;
  max-width: 32rem;
  margin: 0 auto;

  @include tablet-up {
    padding: 2.5rem 1.5rem;
  }
}

.status {
  &.online {
    color: #16a34a;
  }

  &.offline {
    color: #dc2626;
  }

  &.checking {
    color: #6b7280;
  }
}

.notifications {
  margin-top: 1.5rem;
}

.error {
  color: #dc2626;
  font-size: 0.8125rem;
  margin: 0.5rem 0 0;
}

button {
  font: inherit;
  margin-top: 1.5rem;
  padding: 0.625rem 1.25rem;
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  background: var(--color-bg);
  color: var(--color-text-strong);
  cursor: pointer;

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.logout {
  display: block;
}
</style>

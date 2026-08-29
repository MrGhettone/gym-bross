import { api } from './api'

const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY as string

/**
 * L'applicationServerKey richiesta da pushManager.subscribe() vuole un
 * Uint8Array, non la stringa base64url restituita da webpush:vapid.
 */
function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(base64)
  return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)))
}

export const notificationsService = {
  isSupported(): boolean {
    return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window
  },

  async getSubscription(): Promise<PushSubscription | null> {
    if (!this.isSupported()) return null
    const registration = await navigator.serviceWorker.ready
    return registration.pushManager.getSubscription()
  },

  async enable(): Promise<void> {
    const permission = await Notification.requestPermission()
    if (permission !== 'granted') {
      throw new Error('Permesso notifiche negato')
    }

    const registration = await navigator.serviceWorker.ready
    const subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      // Uint8Array.from() e' tipato ArrayBufferLike (include SharedArrayBuffer)
      // dalla lib DOM piu' recente, piu' stretto di quanto richiesto qui:
      // e' sempre un ArrayBuffer vero a runtime, il cast e' solo per il tipo.
      applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY) as BufferSource,
    })

    await api.post('/push-subscriptions', subscription.toJSON())
  },

  async disable(): Promise<void> {
    const subscription = await this.getSubscription()
    if (!subscription) return

    await subscription.unsubscribe()
    await api.delete(`/push-subscriptions?endpoint=${encodeURIComponent(subscription.endpoint)}`)
  },
}

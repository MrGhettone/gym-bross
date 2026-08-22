import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { friendsService, type Friendship } from '../services/friends.service'

export const useFriendsStore = defineStore('friends', () => {
  const friendships = ref<Friendship[]>([])
  const status = ref<'idle' | 'loading' | 'ready'>('idle')

  const accepted = computed(() => friendships.value.filter((f) => f.status === 'accepted'))
  const incomingPending = computed(
    () => friendships.value.filter((f) => f.status === 'pending' && f.direction === 'incoming'),
  )
  const outgoingPending = computed(
    () => friendships.value.filter((f) => f.status === 'pending' && f.direction === 'outgoing'),
  )

  async function fetchAll(): Promise<void> {
    status.value = 'loading'
    try {
      friendships.value = await friendsService.list()
    } finally {
      status.value = 'ready'
    }
  }

  async function sendRequest(username: string): Promise<void> {
    await friendsService.sendRequest(username)
    await fetchAll()
  }

  async function accept(id: number): Promise<void> {
    await friendsService.accept(id)
    await fetchAll()
  }

  async function reject(id: number): Promise<void> {
    await friendsService.reject(id)
    await fetchAll()
  }

  async function remove(id: number): Promise<void> {
    await friendsService.remove(id)
    await fetchAll()
  }

  return { friendships, status, accepted, incomingPending, outgoingPending, fetchAll, sendRequest, accept, reject, remove }
})

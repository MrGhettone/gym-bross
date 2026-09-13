import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import FeedDayView from '../views/FeedDayView.vue'
import FeedView from '../views/FeedView.vue'
import FriendsView from '../views/FriendsView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import SettingsView from '../views/SettingsView.vue'
import WorkoutDetailView from '../views/WorkoutDetailView.vue'
import WorkoutsView from '../views/WorkoutsView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: { name: 'feed' },
    },
    {
      path: '/friends',
      name: 'friends',
      component: FriendsView,
      meta: { requiresAuth: true },
    },
    {
      path: '/feed',
      name: 'feed',
      component: FeedView,
      meta: { requiresAuth: true },
    },
    {
      path: '/feed/:date',
      name: 'feed-day',
      component: FeedDayView,
      meta: { requiresAuth: true },
    },
    {
      path: '/workouts',
      name: 'workouts',
      component: WorkoutsView,
      meta: { requiresAuth: true },
    },
    {
      path: '/workouts/:id',
      name: 'workout-detail',
      component: WorkoutDetailView,
      meta: { requiresAuth: true },
    },
    {
      path: '/settings',
      name: 'settings',
      component: SettingsView,
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true },
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { guestOnly: true },
    },
    // Qualunque path non riconosciuto da nessuna rotta sopra (typo, link
    // vecchio, ecc.) riporta alla home invece di lasciare vuoto <router-view>
    // (Vue Router non ha un concetto nativo di "pagina 404", semplicemente
    // non renderizza nulla se nessuna rotta combacia). Il lato server
    // (Apache FallbackResource su Netsons/nell'emulazione locale) risolve il
    // caso "refresh/link diretto su un path valido dell'app" servendo comunque
    // index.html; questa rotta gestisce invece i path che non esistono
    // proprio nell'app.
    {
      path: '/:pathMatch(.*)*',
      redirect: { path: '/' },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.status === 'idle') {
    await auth.fetchMe()
  }

  if (to.meta.requiresAuth && !auth.user) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && auth.user) {
    return { name: 'feed' }
  }
})

export default router

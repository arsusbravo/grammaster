import type { Router } from 'vue-router'
import { setUnauthorizedHandler } from './client'
import { useAuthStore } from '@/stores/auth'

/**
 * Any 401 from the API (expired or revoked token) ends the local session and sends the student to
 * the login screen, which brings them back to where they were afterwards.
 */
export function installUnauthorizedHandler(router: Router): void {
  setUnauthorizedHandler(() => {
    useAuthStore().clear()

    const route = router.currentRoute.value
    if (route.name !== 'login' && route.name !== 'register') {
      void router.push({ name: 'login', query: { redirect: route.fullPath } })
    }
  })
}

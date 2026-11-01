import { createRouter, createWebHistory, type RouteRecordRaw, type RouterHistory } from 'vue-router'
import { beginActivity } from '@/lib/activity'
import { pageTransition, transitionFor } from '@/lib/pageTransition'
import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    /** `bare` renders the page without header and bottom navigation (login, register). */
    layout?: 'bare'
    /** Only for signed-out visitors (login, register). */
    guestOnly?: boolean
    /** Reachable without signing in. */
    public?: boolean
    /** Position in the bottom navigation: moving between tabs slides left or right. */
    tab?: number
    /** 0 = a main page, 1 = a page inside a section (practice, exam, result): going deeper slides forward. */
    depth?: number
  }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    meta: { tab: 0, depth: 0 },
    component: () => import('@/views/HomeView.vue'),
  },
  {
    path: '/oefenen',
    name: 'practice',
    meta: { depth: 1 },
    component: () => import('@/views/PracticeView.vue'),
  },
  {
    path: '/oefenen/nagekeken',
    name: 'offline-review',
    meta: { depth: 1 },
    component: () => import('@/views/OfflineReviewView.vue'),
  },
  {
    path: '/account',
    name: 'account',
    meta: { tab: 3, depth: 0 },
    component: () => import('@/views/AccountView.vue'),
  },
  {
    path: '/examens',
    name: 'exams',
    meta: { tab: 1, depth: 0 },
    component: () => import('@/views/ExamsView.vue'),
  },
  {
    path: '/examens/poging/:attemptId(\\d+)',
    name: 'exam-run',
    // The exam screen has its own header (timer) and no navigation to wander off with.
    meta: { layout: 'bare', depth: 1 },
    component: () => import('@/views/ExamRunView.vue'),
  },
  {
    path: '/examens/resultaat/:attemptId(\\d+)',
    name: 'exam-result',
    meta: { depth: 1 },
    component: () => import('@/views/ExamResultView.vue'),
  },
  {
    path: '/voortgang',
    name: 'progress',
    meta: { tab: 2, depth: 0 },
    component: () => import('@/views/ProgressView.vue'),
  },
  {
    path: '/login',
    name: 'login',
    meta: { layout: 'bare', guestOnly: true },
    component: () => import('@/views/LoginView.vue'),
  },
  {
    path: '/registreren',
    name: 'register',
    meta: { layout: 'bare', guestOnly: true },
    component: () => import('@/views/RegisterView.vue'),
  },
]

// Design reference page: only exists in the dev server, never in production builds.
if (import.meta.env.DEV) {
  routes.push({
    path: '/design',
    name: 'design',
    meta: { public: true },
    component: () => import('@/views/DesignView.vue'),
  })
}

export function createAppRouter(
  history: RouterHistory = createWebHistory(import.meta.env.BASE_URL),
) {
  const router = createRouter({ history, routes })

  // A navigation (including loading the page's code) counts as loading, and decides the page animation.
  let endNavigation: (() => void) | null = null
  const finishNavigation = () => {
    endNavigation?.()
    endNavigation = null
  }
  router.beforeEach(() => {
    finishNavigation()
    endNavigation = beginActivity()
  })
  router.afterEach((to, from) => {
    finishNavigation()
    pageTransition.value = transitionFor(from, to)
  })
  router.onError(finishNavigation)

  router.beforeEach(async (to) => {
    const auth = useAuthStore()

    if (to.meta.guestOnly) return auth.isAuthenticated ? { name: 'home' } : true
    if (to.meta.public) return true

    const toLogin = { name: 'login', query: { redirect: to.fullPath } }
    if (!auth.isAuthenticated) return toLogin

    if (!auth.user) {
      try {
        await auth.fetchUser()
      } catch {
        // A 401 has already cleared the session; other errors (offline) still let the app open.
      }
      if (!auth.isAuthenticated) return toLogin
    } else {
      // The remembered user is good enough to open the page; update it in the background.
      auth.refreshInBackground()
    }

    return true
  })

  return router
}

export const router = createAppRouter()

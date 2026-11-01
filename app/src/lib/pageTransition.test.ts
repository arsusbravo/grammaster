import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory } from 'vue-router'
import { activityCount, subscribeActivity } from './activity'
import { pageTransition, transitionFor } from './pageTransition'
import * as endpoints from '@/api/endpoints'
import { createAppRouter } from '@/router'

vi.mock('@/api/endpoints')
const mocked = vi.mocked(endpoints)

beforeEach(() => {
  vi.resetAllMocks()
  localStorage.clear()
  localStorage.setItem('grammatica.token', 'tok')
  setActivePinia(createPinia())
})

// Use the real routes so the rules stay in step with the route table.
const router = createAppRouter(createMemoryHistory())
const go = (from: string, to: string) => transitionFor(router.resolve(from), router.resolve(to))

describe('transitionFor', () => {
  it('slides along the tab bar: forward to the right, back to the left', () => {
    expect(go('/', '/examens')).toBe('page-forward')
    expect(go('/examens', '/voortgang')).toBe('page-forward')
    expect(go('/', '/account')).toBe('page-forward')
    expect(go('/account', '/')).toBe('page-back')
    expect(go('/voortgang', '/examens')).toBe('page-back')
  })

  it('slides forward when going into a section and back when leaving it', () => {
    expect(go('/', '/oefenen')).toBe('page-forward')
    expect(go('/examens', '/examens/poging/5')).toBe('page-forward')
    expect(go('/oefenen', '/')).toBe('page-back')
    expect(go('/examens/resultaat/5', '/examens')).toBe('page-back')
    expect(go('/voortgang', '/examens/resultaat/5')).toBe('page-forward')
  })

  it('fades when the level does not change', () => {
    expect(go('/examens/poging/5', '/examens/resultaat/5')).toBe('page-fade')
    expect(go('/login', '/registreren')).toBe('page-fade')
    expect(go('/login', '/')).toBe('page-fade')
    expect(go('/', '/')).toBe('page-fade')
  })
})

describe('the router uses it', () => {
  it('sets the animation for each navigation', async () => {
    mocked.me.mockResolvedValue({ id: 1, name: 'Sanne', email: 's@example.nl', level_id: null })
    const r = createAppRouter(createMemoryHistory())

    await r.push('/')
    await r.push('/examens')
    expect(pageTransition.value).toBe('page-forward')

    await r.push('/')
    expect(pageTransition.value).toBe('page-back')

    await r.push('/oefenen')
    expect(pageTransition.value).toBe('page-forward')
  })

  it('counts a navigation as loading until it is done', async () => {
    const r = createAppRouter(createMemoryHistory())
    const seen: number[] = []
    const stop = subscribeActivity((n) => seen.push(n))
    const base = activityCount()

    await r.push('/login')
    stop()

    expect(Math.max(...seen)).toBeGreaterThan(base)
    expect(activityCount()).toBe(base)
  })
})

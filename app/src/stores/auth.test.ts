import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory } from 'vue-router'
import * as client from '@/api/client'
import * as endpoints from '@/api/endpoints'
import { installUnauthorizedHandler } from '@/api/session'
import { createAppRouter } from '@/router'
import type { User } from '@/types/api'
import { useAuthStore } from './auth'

vi.mock('@/api/endpoints')

const mocked = vi.mocked(endpoints)
const user: User = { id: 1, name: 'Sanne', email: 'sanne@example.com', level_id: 3 }

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
  vi.resetAllMocks()
  client.setToken(null)
})

describe('auth store', () => {
  it('logs in, keeps the token for the API client and remembers it', async () => {
    mocked.login.mockResolvedValue({ user, token: 'abc' })
    const setToken = vi.spyOn(client, 'setToken')
    const auth = useAuthStore()

    await auth.login('sanne@example.com', 'geheim')

    expect(mocked.login).toHaveBeenCalledWith('sanne@example.com', 'geheim')
    expect(auth.isAuthenticated).toBe(true)
    expect(auth.user).toEqual(user)
    expect(setToken).toHaveBeenLastCalledWith('abc')
    expect(localStorage.getItem('grammatica.token')).toBe('abc')
  })

  it('stays signed out when the login fails', async () => {
    mocked.login.mockRejectedValue(new client.ApiError(422, { errors: { email: ['x'] } }))
    const auth = useAuthStore()

    await expect(auth.login('a@b.nl', 'fout')).rejects.toBeInstanceOf(client.ApiError)

    expect(auth.isAuthenticated).toBe(false)
    expect(localStorage.getItem('grammatica.token')).toBeNull()
  })

  it('registers and signs in', async () => {
    mocked.register.mockResolvedValue({ user, token: 'new' })
    const auth = useAuthStore()

    await auth.register({
      name: 'Sanne',
      email: 'sanne@example.com',
      password: 'geheim1234',
      password_confirmation: 'geheim1234',
      level_id: 3,
    })

    expect(auth.token).toBe('new')
    expect(auth.user?.name).toBe('Sanne')
  })

  it('restores a saved token on startup and loads the user', async () => {
    localStorage.setItem('grammatica.token', 'saved')
    mocked.me.mockResolvedValue(user)
    const setToken = vi.spyOn(client, 'setToken')
    const auth = useAuthStore()

    expect(auth.isAuthenticated).toBe(true)
    expect(setToken).toHaveBeenCalledWith('saved')

    await auth.fetchUser()
    expect(auth.user).toEqual(user)
  })

  it('does not call the API for a user when signed out', async () => {
    await useAuthStore().fetchUser()

    expect(mocked.me).not.toHaveBeenCalled()
  })

  it('logs out locally even when the server cannot be reached', async () => {
    localStorage.setItem('grammatica.token', 'saved')
    mocked.logout.mockRejectedValue(new TypeError('offline'))
    const auth = useAuthStore()
    auth.user = user

    await auth.logout()

    expect(auth.isAuthenticated).toBe(false)
    expect(auth.user).toBeNull()
    expect(localStorage.getItem('grammatica.token')).toBeNull()
  })

  it('updates the level', async () => {
    mocked.updateMe.mockResolvedValue({ ...user, level_id: 4 })
    const auth = useAuthStore()

    await auth.setLevel(4)

    expect(mocked.updateMe).toHaveBeenCalledWith({ level_id: 4 })
    expect(auth.user?.level_id).toBe(4)
  })
})

describe('remembering the user on this device', () => {
  it('saves the signed-in user so the app knows them without a connection', async () => {
    mocked.login.mockResolvedValue({ user, token: 'abc' })
    const auth = useAuthStore()

    await auth.login('sanne@example.com', 'geheim')

    expect(JSON.parse(localStorage.getItem('grammatica.user')!)).toEqual(user)
  })

  it('restores the user together with the token on startup', () => {
    localStorage.setItem('grammatica.token', 'saved')
    localStorage.setItem('grammatica.user', JSON.stringify(user))

    const auth = useAuthStore()

    expect(auth.user).toEqual(user)
  })

  it('does not restore a user when there is no token', () => {
    localStorage.setItem('grammatica.user', JSON.stringify(user))

    expect(useAuthStore().user).toBeNull()
  })

  it('forgets the user on logout and when the session ends', async () => {
    localStorage.setItem('grammatica.token', 'saved')
    localStorage.setItem('grammatica.user', JSON.stringify(user))
    const auth = useAuthStore()

    auth.clear()

    expect(localStorage.getItem('grammatica.user')).toBeNull()
  })

  it('keeps changes (name, level) in the remembered user', async () => {
    localStorage.setItem('grammatica.token', 'saved')
    localStorage.setItem('grammatica.user', JSON.stringify(user))
    mocked.updateMe.mockResolvedValue({ ...user, name: 'Anders' })
    const auth = useAuthStore()

    await auth.updateName('Anders')

    expect(JSON.parse(localStorage.getItem('grammatica.user')!).name).toBe('Anders')
  })

  it('refreshes the remembered user in the background, once, and survives being offline', async () => {
    localStorage.setItem('grammatica.token', 'saved')
    localStorage.setItem('grammatica.user', JSON.stringify(user))
    mocked.me
      .mockRejectedValueOnce(new TypeError('offline'))
      .mockResolvedValue({ ...user, name: 'Vers' })
    const auth = useAuthStore()

    auth.refreshInBackground()
    await vi.waitFor(() => expect(mocked.me).toHaveBeenCalledTimes(1))
    expect(auth.user?.name).toBe('Sanne')

    auth.refreshInBackground() // offline failure allows another try
    await vi.waitFor(() => expect(auth.user?.name).toBe('Vers'))
    auth.refreshInBackground()
    expect(mocked.me).toHaveBeenCalledTimes(2)
  })
})

describe('401 handling and the route guard', () => {
  async function setup(path: string) {
    const router = createAppRouter(createMemoryHistory())
    installUnauthorizedHandler(router)
    const auth = useAuthStore()
    await router.push(path)
    await router.isReady()
    return { router, auth }
  }

  it('sends signed-out visitors to the login page and remembers where they wanted to go', async () => {
    const { router } = await setup('/oefenen?level=vmbo')

    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/oefenen?level=vmbo')
  })

  it('lets signed-in students through and loads the user once', async () => {
    localStorage.setItem('grammatica.token', 'saved')
    mocked.me.mockResolvedValue(user)

    const { router, auth } = await setup('/account')

    expect(router.currentRoute.value.name).toBe('account')
    expect(auth.user).toEqual(user)
    expect(mocked.me).toHaveBeenCalledTimes(1)

    await router.push('/')
    expect(mocked.me).toHaveBeenCalledTimes(1)
  })

  it('sends signed-in students away from the login page', async () => {
    localStorage.setItem('grammatica.token', 'saved')
    mocked.me.mockResolvedValue(user)

    const { router } = await setup('/login')

    expect(router.currentRoute.value.name).toBe('home')
  })

  it('treats a rejected token as signed out', async () => {
    localStorage.setItem('grammatica.token', 'expired')
    mocked.me.mockImplementation(async () => {
      // What the API client does on a 401: report it centrally, then fail.
      useAuthStore().clear()
      throw new client.ApiError(401, null)
    })

    const { router, auth } = await setup('/')

    expect(auth.isAuthenticated).toBe(false)
    expect(router.currentRoute.value.name).toBe('login')
  })

  it('a 401 from the API ends the session and returns to the current page after login', async () => {
    localStorage.setItem('grammatica.token', 'saved')
    mocked.me.mockResolvedValue(user)
    const { router, auth } = await setup('/account')

    // The API client reports a 401 through the registered handler.
    const fakeFetch = vi.fn().mockResolvedValue(new Response('{}', { status: 401 }))
    vi.stubGlobal('fetch', fakeFetch)
    await client.api('/progress').catch(() => undefined)
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('login'))
    vi.unstubAllGlobals()

    expect(auth.isAuthenticated).toBe(false)
    expect(localStorage.getItem('grammatica.token')).toBeNull()
    expect(router.currentRoute.value.query.redirect).toBe('/account')
  })

  it('does not redirect again when a 401 happens on the login page itself', async () => {
    const { router } = await setup('/login')
    const push = vi.spyOn(router, 'push')

    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status: 401 })))
    await client.api('/auth/me').catch(() => undefined)
    vi.unstubAllGlobals()

    expect(push).not.toHaveBeenCalled()
  })
})

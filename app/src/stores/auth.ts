import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import * as endpoints from '@/api/endpoints'
import { setToken } from '@/api/client'
import { readJson, readStorage, writeJson, writeStorage } from '@/lib/storage'
import type { User } from '@/types/api'

const STORAGE_KEY = 'grammatica.token'
const USER_KEY = 'grammatica.user'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(readStorage(STORAGE_KEY))
  // Kept on the device so the app knows who is signed in when it opens without a connection.
  const user = ref<User | null>(token.value ? readJson<User | null>(USER_KEY, null) : null)
  setToken(token.value)
  watch(
    user,
    (value) => {
      if (value) writeJson(USER_KEY, value)
      else writeStorage(USER_KEY, null)
    },
    { flush: 'sync' },
  )
  let refreshed = false

  const isAuthenticated = computed(() => token.value !== null)

  function setSession(newToken: string, newUser: User) {
    token.value = newToken
    user.value = newUser
    setToken(newToken)
    writeStorage(STORAGE_KEY, newToken)
  }

  /** Forget the session locally (after logout or when the server says the token is no longer valid). */
  function clear() {
    token.value = null
    user.value = null
    setToken(null)
    writeStorage(STORAGE_KEY, null)
  }

  async function login(email: string, password: string) {
    const result = await endpoints.login(email, password)
    setSession(result.token, result.user)
  }

  async function register(input: Parameters<typeof endpoints.register>[0]) {
    const result = await endpoints.register(input)
    setSession(result.token, result.user)
  }

  /** Load the signed-in user. A 401 clears the session through the central handler. */
  async function fetchUser() {
    if (!token.value) return
    user.value = await endpoints.me()
    refreshed = true
  }

  /** Refresh the remembered user once per visit without making the app wait for it (it may be offline). */
  function refreshInBackground() {
    if (refreshed || !token.value) return
    refreshed = true

    void (async () => {
      try {
        const fresh = await endpoints.me()
        if (fresh) user.value = fresh
      } catch {
        // Offline or the server is busy: keep what we have and try again on the next navigation.
        refreshed = false
      }
    })()
  }

  async function logout() {
    try {
      await endpoints.logout()
    } catch {
      // The token is dropped locally either way.
    }
    clear()
  }

  async function setLevel(levelId: number | null) {
    user.value = await endpoints.updateMe({ level_id: levelId })
  }

  async function updateName(name: string) {
    user.value = await endpoints.updateMe({ name })
  }

  /** Other devices are signed out by the server; this one stays signed in. */
  async function changePassword(input: Parameters<typeof endpoints.changePassword>[0]) {
    await endpoints.changePassword(input)
  }

  return {
    token,
    user,
    isAuthenticated,
    login,
    register,
    fetchUser,
    logout,
    clear,
    setLevel,
    updateName,
    changePassword,
    refreshInBackground,
  }
})

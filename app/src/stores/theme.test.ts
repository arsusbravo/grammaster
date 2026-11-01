import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useThemeStore } from './theme'

beforeEach(() => {
  localStorage.clear()
  delete document.documentElement.dataset.theme
  setActivePinia(createPinia())
})

describe('theme store', () => {
  it('starts with the saved theme', () => {
    localStorage.setItem('grammatica.theme', 'dark')

    expect(useThemeStore().theme).toBe('dark')
  })

  it('starts automatic when nothing was saved', () => {
    expect(useThemeStore().theme).toBe('system')
  })

  it('applies the saved theme on init', () => {
    localStorage.setItem('grammatica.theme', 'light')

    useThemeStore().init()

    expect(document.documentElement.dataset.theme).toBe('light')
  })

  it('changes the page and remembers the choice', () => {
    const store = useThemeStore()

    store.set('dark')
    expect(store.theme).toBe('dark')
    expect(document.documentElement.dataset.theme).toBe('dark')
    expect(localStorage.getItem('grammatica.theme')).toBe('dark')

    store.set('system')
    expect(document.documentElement.dataset.theme).toBeUndefined()
    expect(localStorage.getItem('grammatica.theme')).toBeNull()
  })
})

import { beforeEach, describe, expect, it } from 'vitest'
import { applyTheme, readTheme, saveTheme, THEME_KEY } from './theme'

const root = document.documentElement
const chrome = () =>
  [...document.querySelectorAll<HTMLMetaElement>('meta[name=theme-color]')].map((m) => m.content)

beforeEach(() => {
  localStorage.clear()
  delete root.dataset.theme
  document.head.innerHTML = `
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#081426" media="(prefers-color-scheme: dark)" />`
})

describe('readTheme / saveTheme', () => {
  it('follows the device when nothing is saved or the value is unknown', () => {
    expect(readTheme()).toBe('system')

    localStorage.setItem(THEME_KEY, 'purple')
    expect(readTheme()).toBe('system')
  })

  it('remembers light and dark', () => {
    saveTheme('dark')
    expect(localStorage.getItem(THEME_KEY)).toBe('dark')
    expect(readTheme()).toBe('dark')

    saveTheme('light')
    expect(readTheme()).toBe('light')
  })

  it('forgets the choice when going back to automatic', () => {
    saveTheme('dark')

    saveTheme('system')

    expect(localStorage.getItem(THEME_KEY)).toBeNull()
    expect(readTheme()).toBe('system')
  })
})

describe('applyTheme', () => {
  it('forces light or dark on the page', () => {
    applyTheme('dark')
    expect(root.dataset.theme).toBe('dark')

    applyTheme('light')
    expect(root.dataset.theme).toBe('light')
  })

  it('lets the device decide again for automatic', () => {
    applyTheme('dark')

    applyTheme('system')

    expect(root.dataset.theme).toBeUndefined()
  })

  it('sets the browser chrome colour to match', () => {
    applyTheme('dark')
    expect(chrome()).toEqual(['#081426', '#081426'])

    applyTheme('light')
    expect(chrome()).toEqual(['#ffffff', '#ffffff'])

    applyTheme('system')
    expect(chrome()).toEqual(['#ffffff', '#081426'])
  })

  it('works on a page without theme-color tags', () => {
    document.head.innerHTML = ''

    expect(() => applyTheme('dark')).not.toThrow()
  })
})

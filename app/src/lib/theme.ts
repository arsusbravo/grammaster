import { readStorage, writeStorage } from './storage'

export type Theme = 'system' | 'light' | 'dark'

export const THEME_KEY = 'grammatica.theme'
export const THEMES: Theme[] = ['system', 'light', 'dark']

// Browser chrome colours (address bar on a phone): the light and dark surface colours of the app.
const CHROME_LIGHT = '#ffffff'
const CHROME_DARK = '#081426'

/** The saved choice; anything unknown means "follow the device". */
export function readTheme(): Theme {
  const value = readStorage(THEME_KEY)
  return value === 'light' || value === 'dark' ? value : 'system'
}

export function saveTheme(theme: Theme): void {
  writeStorage(THEME_KEY, theme === 'system' ? null : theme)
}

/**
 * Show a theme. "system" removes the override so the CSS follows the device setting
 * (`color-scheme: light dark` in theme.css); light and dark force one scheme.
 */
export function applyTheme(theme: Theme): void {
  const root = document.documentElement
  if (theme === 'system') delete root.dataset.theme
  else root.dataset.theme = theme

  document.querySelectorAll<HTMLMetaElement>('meta[name="theme-color"]').forEach((meta) => {
    const isDarkVariant = (meta.getAttribute('media') ?? '').includes('dark')
    const dark = theme === 'system' ? isDarkVariant : theme === 'dark'
    meta.content = dark ? CHROME_DARK : CHROME_LIGHT
  })
}

import { defineStore } from 'pinia'
import { ref } from 'vue'
import { applyTheme, readTheme, saveTheme, type Theme } from '@/lib/theme'

/** The chosen theme (light, dark or follow the device). Remembered on this device. */
export const useThemeStore = defineStore('theme', () => {
  const theme = ref<Theme>(readTheme())

  function set(value: Theme) {
    theme.value = value
    saveTheme(value)
    applyTheme(value)
  }

  /** Apply the saved theme (the page itself already did so before the first paint, see index.html). */
  function init() {
    applyTheme(theme.value)
  }

  return { theme, set, init }
})

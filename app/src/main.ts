import '@fontsource-variable/atkinson-hyperlegible-next'
import '@fontsource-variable/fraunces'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import { installUnauthorizedHandler } from './api/session'
import { listenForInstall } from './composables/usePwaInstall'
import App from './App.vue'
import { i18n } from './i18n'
import { router } from './router'
import { useThemeStore } from './stores/theme'
import './style.css'

listenForInstall()

const pinia = createPinia()
const app = createApp(App).use(pinia).use(router).use(i18n)
installUnauthorizedHandler(router)

// The saved theme (index.html already applied it before the first paint; this also sets the browser chrome colour).
useThemeStore(pinia).init()

// Dev only: ?theme=light|dark forces a colour scheme for review links and screenshots (not saved).
if (import.meta.env.DEV) {
  const theme = new URLSearchParams(location.search).get('theme')
  if (theme === 'light' || theme === 'dark') document.documentElement.dataset.theme = theme
}

app.mount('#app')

// Fade out the launch splash defined in index.html.
const splash = document.getElementById('splash')
if (splash) {
  splash.classList.add('splash-hide')
  splash.addEventListener('transitionend', () => splash.remove(), { once: true })
  setTimeout(() => splash.remove(), 600)
}

import { computed, ref } from 'vue'

// "Install the app": Chrome and Edge offer it through an event we keep until the student asks for it;
// iPhones have no such event, so there we only explain the manual way.

interface BeforeInstallPromptEvent extends Event {
  prompt: () => Promise<void>
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

const deferred = ref<BeforeInstallPromptEvent | null>(null)
const installed = ref(false)
let listening = false

const isStandalone = () =>
  window.matchMedia('(display-mode: standalone)').matches ||
  (navigator as Navigator & { standalone?: boolean }).standalone === true

const isIos = () =>
  /iphone|ipad|ipod/i.test(navigator.userAgent) ||
  // iPadOS pretends to be a Mac.
  (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)

/** Call once at startup: the install event can fire long before the account page is opened. */
export function listenForInstall(): void {
  if (listening) return
  listening = true

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    deferred.value = event as BeforeInstallPromptEvent
  })
  window.addEventListener('appinstalled', () => {
    installed.value = true
    deferred.value = null
  })
}

export function usePwaInstall() {
  const standalone = computed(() => installed.value || isStandalone())

  async function install(): Promise<void> {
    const event = deferred.value
    if (!event) return

    await event.prompt()
    const { outcome } = await event.userChoice
    // The event can only be used once.
    deferred.value = null
    if (outcome === 'accepted') installed.value = true
  }

  return {
    standalone,
    canInstall: computed(() => !standalone.value && deferred.value !== null),
    showIosHint: computed(() => !standalone.value && deferred.value === null && isIos()),
    install,
  }
}

/** For tests. */
export function resetInstallState(): void {
  deferred.value = null
  installed.value = false
}

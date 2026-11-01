<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { subscribeActivity } from '@/lib/activity'

// Shows a thin moving bar at the top while the app waits for the server.
// It only appears after a short delay (quick requests must not make it flash) and then stays a
// moment, so it never blinks.
const SHOW_AFTER_MS = 150
const MIN_VISIBLE_MS = 450

const { t } = useI18n()
const visible = ref(false)

let showTimer: ReturnType<typeof setTimeout> | undefined
let hideTimer: ReturnType<typeof setTimeout> | undefined
let shownAt = 0

const unsubscribe = subscribeActivity((count) => {
  if (count > 0) {
    clearTimeout(hideTimer)
    hideTimer = undefined
    if (!visible.value && showTimer === undefined) {
      showTimer = setTimeout(() => {
        showTimer = undefined
        visible.value = true
        shownAt = Date.now()
      }, SHOW_AFTER_MS)
    }
    return
  }

  clearTimeout(showTimer)
  showTimer = undefined
  if (visible.value && hideTimer === undefined) {
    hideTimer = setTimeout(
      () => {
        hideTimer = undefined
        visible.value = false
      },
      Math.max(0, MIN_VISIBLE_MS - (Date.now() - shownAt)),
    )
  }
})

onBeforeUnmount(() => {
  unsubscribe()
  clearTimeout(showTimer)
  clearTimeout(hideTimer)
})
</script>

<template>
  <div
    class="pointer-events-none fixed inset-x-0 top-0 z-[60]"
    style="top: env(safe-area-inset-top)"
  >
    <Transition name="loader-fade">
      <div v-if="visible" class="h-1 overflow-hidden bg-secondary/20" data-testid="top-loader">
        <div class="loader-bar h-full w-2/5 rounded-full bg-secondary" />
      </div>
    </Transition>
    <!-- For screen readers; the bar itself is only visual. -->
    <p class="sr-only" role="status">{{ visible ? t('app.loading') : '' }}</p>
  </div>
</template>

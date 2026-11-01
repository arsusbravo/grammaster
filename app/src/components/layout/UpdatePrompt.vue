<script setup lang="ts">
import { useRegisterSW } from 'virtual:pwa-register/vue'
import AppButton from '@/components/ui/AppButton.vue'

// A new version of the app waits until the student agrees: reloading by itself could interrupt an exam.
const CHECK_EVERY_MS = 60 * 60 * 1000

const { needRefresh, offlineReady, updateServiceWorker } = useRegisterSW({
  onRegisteredSW(_url, registration) {
    if (registration) setInterval(() => void registration.update(), CHECK_EVERY_MS)
  },
})

function later() {
  needRefresh.value = false
  offlineReady.value = false
}
</script>

<template>
  <Transition name="question">
    <div
      v-if="needRefresh || offlineReady"
      role="status"
      class="fixed inset-x-0 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 mx-auto flex w-[calc(100%-2rem)] max-w-md flex-wrap items-center gap-3 rounded-card border-2 border-secondary bg-surface p-4 shadow-card md:bottom-6"
    >
      <p class="min-w-0 flex-1 font-semibold">
        {{ needRefresh ? $t('offline.update') : $t('offline.ready') }}
      </p>
      <AppButton v-if="needRefresh" class="min-h-11" @click="updateServiceWorker(true)">
        {{ $t('offline.updateNow') }}
      </AppButton>
      <AppButton variant="ghost" class="min-h-11" @click="later">
        {{ needRefresh ? $t('offline.later') : $t('offline.dismiss') }}
      </AppButton>
    </div>
  </Transition>
</template>

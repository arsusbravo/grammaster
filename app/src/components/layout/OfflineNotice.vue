<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { useOfflineStore } from '@/stores/offline'

// Says what is going on with the connection and with the answers given without one.
const offline = useOfflineStore()
const route = useRoute()

const showReviewed = computed(() => offline.reviewed.length > 0 && route.name !== 'offline-review')
</script>

<template>
  <div
    v-if="!offline.online || offline.queuedCount > 0 || showReviewed"
    class="mx-auto flex w-full max-w-5xl flex-col gap-2 px-page pt-3"
  >
    <p
      v-if="!offline.online"
      role="status"
      class="flex items-start gap-3 rounded-control border-2 border-warning bg-warning-soft p-3 text-warning-ink"
    >
      <AppIcon name="alert" class="mt-0.5 size-5" />
      <span>{{ $t('offline.offline') }}</span>
    </p>

    <p
      v-if="offline.queuedCount > 0"
      role="status"
      class="flex items-center gap-3 rounded-control border-2 border-border-strong bg-surface p-3"
    >
      <AppSpinner v-if="offline.syncing && offline.online" />
      <AppIcon v-else name="clock" class="size-5" />
      <span>
        {{
          offline.syncing && offline.online
            ? $t('offline.syncing')
            : $t('offline.waiting', offline.queuedCount)
        }}
      </span>
    </p>

    <div
      v-if="showReviewed"
      role="status"
      class="flex flex-wrap items-center gap-x-3 gap-y-2 rounded-control border-2 border-success bg-success-soft p-3 text-success-ink"
    >
      <AppIcon name="check" class="size-5" />
      <span class="flex-1 font-semibold">
        {{
          $t('offline.reviewed', {
            correct: offline.reviewedCorrect,
            total: offline.reviewed.length,
          })
        }}
      </span>
      <RouterLink
        :to="{ name: 'offline-review' }"
        class="min-h-11 content-center rounded-control px-3 font-semibold underline underline-offset-4"
      >
        {{ $t('offline.view') }}
      </RouterLink>
      <button
        type="button"
        class="min-h-11 rounded-control px-3 font-semibold"
        @click="offline.dismiss()"
      >
        {{ $t('offline.dismiss') }}
      </button>
    </div>
  </div>
</template>

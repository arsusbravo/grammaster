import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import * as endpoints from '@/api/endpoints'
import {
  clearReviewed,
  onOutboxChange,
  queuedAnswers,
  reviewedAnswers,
  syncOutbox,
  type ReviewedAnswer,
  type SyncSummary,
} from '@/lib/offline/outbox'
import { useAuthStore } from '@/stores/auth'

const RETRY_AFTER_MS = 15_000

/**
 * Connection state and the answers given offline: waiting in the outbox, or already checked by the server
 * and waiting to be looked at. Sends the outbox when the connection returns.
 */
export const useOfflineStore = defineStore('offline', () => {
  const auth = useAuthStore()

  const online = ref(navigator.onLine)
  const queuedCount = ref(0)
  const reviewed = ref<ReviewedAnswer[]>([])
  const syncing = ref(false)

  let started = false
  let retryTimer: ReturnType<typeof setTimeout> | undefined

  const reviewedCorrect = computed(() => reviewed.value.filter((r) => r.result.is_correct).length)

  function refresh() {
    const id = auth.user?.id
    queuedCount.value = id === undefined ? 0 : queuedAnswers(id).length
    reviewed.value = id === undefined ? [] : reviewedAnswers(id)
  }

  /** Send the waiting answers to the server (when there is a connection). */
  async function sync(): Promise<SyncSummary | null> {
    const id = auth.user?.id
    if (id === undefined || !online.value || syncing.value || queuedAnswers(id).length === 0)
      return null

    clearTimeout(retryTimer)
    syncing.value = true
    let summary: SyncSummary
    try {
      summary = await syncOutbox(id, endpoints.checkAnswer)
    } finally {
      syncing.value = false
      refresh()
    }

    // Something is left (flaky connection, server hiccup): try again shortly, unless the login expired.
    if (summary.remaining > 0 && summary.stoppedBecause !== 'auth') {
      retryTimer = setTimeout(() => void sync(), RETRY_AFTER_MS)
    }
    return summary
  }

  function dismiss() {
    const id = auth.user?.id
    if (id !== undefined) clearReviewed(id)
  }

  /** Start watching the connection. Safe to call more than once. */
  function start() {
    if (started) return
    started = true

    window.addEventListener('online', () => {
      online.value = true
      void sync()
    })
    window.addEventListener('offline', () => {
      online.value = false
    })
    onOutboxChange(refresh)

    // A different student (or none) on this device: show only their answers.
    watch(
      () => auth.user?.id,
      () => {
        refresh()
        void sync()
      },
      { immediate: true },
    )
  }

  return { online, queuedCount, reviewed, reviewedCorrect, syncing, refresh, sync, dismiss, start }
})

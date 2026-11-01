<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ExamTimerBar from '@/components/exam/ExamTimerBar.vue'
import QuestionNavigator from '@/components/exam/QuestionNavigator.vue'
import QuestionRenderer from '@/components/question/QuestionRenderer.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import { formatClock } from '@/lib/format'
import { useExamStore } from '@/stores/exam'
import type { Answer } from '@/types/api'

const exam = useExamStore()
const route = useRoute()
const router = useRouter()

const attemptId = computed(() => Number(route.params.attemptId))
const questionEl = useTemplateRef<HTMLElement>('question')

const submitOpen = ref(false)
const leaveOpen = ref(false)
const announcement = ref('')

const total = computed(() => exam.questions.length)
const isLast = computed(() => exam.index === total.value - 1)
const submitting = computed(() => exam.status === 'submitting')
const timeIsUp = computed(
  () => exam.remaining === 0 && exam.status !== 'running' && exam.status !== 'loading',
)

// The answer of the shown question, wired to the store (which saves it as the student works).
const answer = computed<Answer | null>({
  get: () => (exam.current ? (exam.answers[exam.current.id] ?? null) : null),
  set: (value) => {
    if (exam.current && value) exam.setAnswer(exam.current.id, value)
  },
})

const unanswered = computed(() =>
  exam.progress.flatMap((p, i) => (p.state === 'none' ? [i + 1] : [])),
)
const partial = computed(() =>
  exam.progress.flatMap((p, i) => (p.state === 'partial' ? [i + 1] : [])),
)

const timerClasses = computed(
  () =>
    ({
      normal: 'border-border-strong bg-surface-muted text-ink',
      low: 'border-warning bg-warning-soft text-warning-ink',
      critical: 'border-error bg-error-soft text-error-ink',
    })[exam.urgency],
)

let interval: ReturnType<typeof setInterval> | undefined

const onVisible = () => {
  if (document.visibilityState === 'visible') void exam.resync()
}
const onOnline = () => void exam.resync()
const onPageHide = () => void exam.flush()

onMounted(async () => {
  await exam.load(attemptId.value)
  interval = setInterval(() => exam.tick(), 1000)
  document.addEventListener('visibilitychange', onVisible)
  window.addEventListener('online', onOnline)
  window.addEventListener('pagehide', onPageHide)
})

onBeforeUnmount(() => {
  clearInterval(interval)
  document.removeEventListener('visibilitychange', onVisible)
  window.removeEventListener('online', onOnline)
  window.removeEventListener('pagehide', onPageHide)
  // Save what is still waiting; whatever cannot be saved now stays in localStorage for next time.
  void exam.flush()
  exam.dispose()
})

// Done (handed in, or the time ran out): show the result.
watch(
  () => exam.status,
  (status) => {
    if (status === 'finished')
      void router.replace({ name: 'exam-result', params: { attemptId: attemptId.value } })
  },
)

// Say so (once) when the time gets short, for screen reader users.
watch(
  () => exam.urgency,
  (urgency, previous) => {
    if (previous === undefined || urgency === 'normal') return
    announcement.value = urgency === 'low' ? 'Nog 5 minuten' : 'Nog 1 minuut'
  },
)

// Move focus to the new question so keyboard and screen reader users start at the top of it.
watch(
  () => exam.current?.id,
  async (id, previous) => {
    if (id === undefined || previous === undefined) return
    await nextTick()
    questionEl.value?.focus({ preventScroll: true })
    window.scrollTo({ top: 0 })
  },
)

function next() {
  if (isLast.value) openSubmit()
  else exam.goTo(exam.index + 1)
}

function openSubmit() {
  exam.submitError = null
  submitOpen.value = true
}

async function confirmSubmit() {
  const ok = await exam.submit()
  if (!ok) submitOpen.value = true
}

function leave() {
  void router.push({ name: 'exams' })
}

const submitErrorKey = computed(() =>
  exam.submitError === 'unsaved'
    ? 'examRun.unsaved'
    : exam.submitError === 'network'
      ? 'examRun.submitNetwork'
      : 'examRun.submitServer',
)
</script>

<template>
  <div class="flex min-h-dvh flex-col">
    <!-- Loading / error -->
    <div
      v-if="exam.status === 'loading' || exam.status === 'idle'"
      class="mx-auto w-full max-w-2xl animate-pulse space-y-4 px-page pt-[calc(2rem+env(safe-area-inset-top))] pb-8"
      aria-busy="true"
    >
      <div class="h-8 w-1/2 rounded-control bg-surface-muted" />
      <div class="h-14 rounded-control bg-surface-muted" />
      <div class="h-40 rounded-control bg-surface-muted" />
    </div>

    <div
      v-else-if="exam.status === 'error'"
      class="mx-auto w-full max-w-2xl px-page pt-[calc(2rem+env(safe-area-inset-top))] pb-8"
    >
      <FeedbackBanner kind="warning">
        {{ exam.error === 'network' ? $t('errors.network') : $t('examRun.loadError') }}
        <AppButton variant="ghost" class="mt-3" @click="exam.load(attemptId)">{{
          $t('errors.retry')
        }}</AppButton>
      </FeedbackBanner>
    </div>

    <template v-else-if="exam.attempt && exam.current">
      <header class="sticky top-0 z-20 border-b border-border bg-surface/95 pt-safe backdrop-blur">
        <div class="mx-auto w-full max-w-2xl px-page py-3">
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="inline-flex min-h-11 items-center gap-1 rounded-control px-2 font-semibold text-ink-muted hover:bg-surface-muted"
              @click="leaveOpen = true"
            >
              <AppIcon name="x" />
              {{ $t('examRun.leave') }}
            </button>
            <p class="min-w-0 flex-1 truncate text-center text-sm font-semibold text-ink-muted">
              {{ exam.attempt.exam.title }}
            </p>
            <p
              role="timer"
              :aria-label="$t('examRun.timer')"
              class="inline-flex min-h-11 items-center gap-1.5 rounded-control border-2 px-3 font-semibold tabular-nums"
              :class="timerClasses"
            >
              <AppIcon :name="exam.urgency === 'normal' ? 'clock' : 'alert'" />
              {{ formatClock(exam.remaining) }}
            </p>
          </div>

          <div class="mt-3">
            <ExamTimerBar
              :remaining="exam.remaining"
              :total="exam.attempt.exam.time_limit_minutes * 60"
              :urgency="exam.urgency"
            />
          </div>

          <div class="mt-2 flex items-center gap-3 text-sm">
            <span class="font-semibold">{{
              $t('examRun.questionOf', { n: exam.index + 1, total })
            }}</span>
            <span
              class="inline-flex items-center gap-1"
              :class="exam.saving === 'error' ? 'font-semibold text-warning-ink' : 'text-ink-muted'"
              role="status"
            >
              <AppIcon v-if="exam.saving === 'error'" name="alert" class="size-4" />
              <AppIcon v-else-if="exam.saving === 'saved'" name="check" class="size-4" />
              {{
                exam.saving === 'error'
                  ? $t('examRun.saveError')
                  : exam.saving === 'saved'
                    ? $t('examRun.saved')
                    : $t('examRun.saving')
              }}
            </span>
            <button
              type="button"
              class="ml-auto min-h-11 rounded-control border-2 border-border-strong px-4 font-semibold hover:bg-surface-muted"
              @click="openSubmit"
            >
              {{ $t('examRun.submit') }}
            </button>
          </div>

          <div class="mt-1">
            <QuestionNavigator :items="exam.progress" :current="exam.index" @select="exam.goTo" />
          </div>
        </div>
      </header>

      <main class="mx-auto w-full max-w-2xl flex-1 px-page py-6 pb-32">
        <div ref="question" tabindex="-1" class="outline-none">
          <Transition name="question" mode="out-in">
            <div :key="exam.current.id">
              <QuestionRenderer v-model="answer" :question="exam.current" />
            </div>
          </Transition>
        </div>
      </main>

      <footer class="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-surface pb-safe">
        <div class="mx-auto flex w-full max-w-2xl gap-3 px-page py-3">
          <AppButton
            variant="ghost"
            class="flex-1"
            :disabled="exam.index === 0"
            @click="exam.goTo(exam.index - 1)"
          >
            {{ $t('examRun.previous') }}
          </AppButton>
          <AppButton class="flex-1" @click="next">
            {{ isLast ? $t('examRun.submit') : $t('examRun.next') }}
          </AppButton>
        </div>
      </footer>

      <p class="sr-only" aria-live="polite">{{ announcement }}</p>
    </template>

    <!-- Time is up: handing in -->
    <div
      v-if="timeIsUp"
      role="alertdialog"
      aria-modal="true"
      aria-labelledby="time-up"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-6"
    >
      <div
        class="w-full max-w-sm rounded-card border border-border bg-surface p-6 text-center shadow-card"
      >
        <h2 id="time-up" class="text-2xl">{{ $t('examRun.timeUp') }}</h2>
        <p class="mt-2 text-ink-muted">{{ $t('examRun.timeUpText') }}</p>
        <FeedbackBanner v-if="exam.submitError" kind="warning" class="mt-4 text-left">
          {{ $t(submitErrorKey) }}
        </FeedbackBanner>
      </div>
    </div>

    <ConfirmDialog
      v-model:open="submitOpen"
      :title="$t('examRun.submitTitle')"
      :confirm-label="$t('examRun.submitConfirm')"
      :cancel-label="$t('examRun.back')"
      :busy="submitting"
      @confirm="confirmSubmit"
    >
      <p class="font-semibold text-ink">
        {{ $t('examRun.submitSummary', { answered: exam.answeredCount, total }) }}
      </p>
      <p v-if="unanswered.length">
        {{ $t('examRun.unanswered', { list: unanswered.join(', ') }) }}
      </p>
      <p v-if="partial.length">{{ $t('examRun.partial', { list: partial.join(', ') }) }}</p>
      <p>{{ $t('examRun.submitHint') }}</p>
      <FeedbackBanner v-if="exam.submitError" kind="warning">{{
        $t(submitErrorKey)
      }}</FeedbackBanner>
    </ConfirmDialog>

    <ConfirmDialog
      v-model:open="leaveOpen"
      :title="$t('examRun.leaveTitle')"
      :confirm-label="$t('examRun.leaveConfirm')"
      :cancel-label="$t('examRun.stay')"
      @confirm="leave"
    >
      <p>{{ $t('examRun.leaveText') }}</p>
    </ConfirmDialog>
  </div>
</template>

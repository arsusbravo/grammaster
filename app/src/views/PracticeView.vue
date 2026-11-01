<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, useTemplateRef, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import QuestionRenderer from '@/components/question/QuestionRenderer.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import { usePracticeStore } from '@/stores/practice'
import type { QuestionFilters } from '@/types/api'

const practice = usePracticeStore()
const route = useRoute()
const router = useRouter()

const questionEl = useTemplateRef<HTMLElement>('question')
const feedbackEl = useTemplateRef<HTMLElement>('feedback')

const filters = computed<QuestionFilters>(() => ({
  level: typeof route.query.level === 'string' ? route.query.level : undefined,
  topic: typeof route.query.topic === 'string' ? route.query.topic : undefined,
}))

const busy = computed(() => practice.status === 'checking')
const checked = computed(() => practice.status === 'checked')
// Answers waiting for a check (given offline) count as answered for the numbering.
const position = computed(() => practice.answered + practice.queuedCount + (checked.value ? 0 : 1))
const anyAnswers = computed(() => practice.answered + practice.queuedCount > 0)
const percentage = computed(() =>
  practice.answered ? Math.round((practice.correct / practice.answered) * 100) : 0,
)
const explanation = computed(() =>
  (practice.result?.explanation ?? '').split(/\n{2,}/).filter((p) => p.trim() !== ''),
)
const loadFailed = computed(() => practice.status === 'loading' && practice.error !== null)

function begin() {
  void practice.start(filters.value)
}

onMounted(begin)
watch(() => route.fullPath, begin)
onBeforeUnmount(() => practice.reset())

// Keep keyboard and screen reader users oriented: the feedback after checking, the next question after "next".
const smooth = () =>
  window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : ('smooth' as const)

watch(checked, async (isChecked) => {
  if (!isChecked) return
  await nextTick()
  feedbackEl.value?.focus({ preventScroll: true })
  feedbackEl.value?.scrollIntoView({ block: 'nearest', behavior: smooth() })
})

watch(
  () => practice.current?.id,
  async (id) => {
    if (id === undefined) return
    await nextTick()
    questionEl.value?.focus({ preventScroll: true })
    window.scrollTo({ top: 0, behavior: smooth() })
  },
)

// One button: "Controleer" while answering, "Volgende vraag" afterwards (so Enter does the right thing).
async function submit() {
  if (checked.value) await practice.next()
  else await practice.check()
}

function stop() {
  void router.push({ name: 'home' })
}
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <div class="mb-6 flex items-center gap-3">
      <button
        type="button"
        class="inline-flex min-h-11 items-center gap-1 rounded-control px-3 font-semibold text-ink-muted hover:bg-surface-muted"
        @click="stop"
      >
        <AppIcon name="x" />
        {{ $t('practice.stop') }}
      </button>

      <p
        v-if="practice.current"
        class="ml-auto flex flex-wrap items-center justify-end gap-x-3 text-sm text-ink-muted"
        aria-live="polite"
      >
        <span class="font-semibold text-ink">{{
          $t('practice.questionNumber', { n: position })
        }}</span>
        <span v-if="practice.answered">
          {{ $t('practice.score', { correct: practice.correct, answered: practice.answered }) }}
        </span>
        <span v-if="practice.queuedCount">
          {{ $t('practice.waitingScore', { n: practice.queuedCount }) }}
        </span>
      </p>
    </div>

    <!-- Could not load -->
    <FeedbackBanner v-if="loadFailed" kind="warning">
      {{ $t(`errors.${practice.error === 'network' ? 'network' : 'server'}`) }}
      <AppButton variant="ghost" class="mt-3" @click="practice.retry()">
        {{ $t('errors.retry') }}
      </AppButton>
    </FeedbackBanner>

    <!-- Loading -->
    <div v-else-if="practice.status === 'loading'" class="animate-pulse space-y-4" aria-busy="true">
      <div class="h-8 w-3/4 rounded-control bg-surface-muted" />
      <div class="h-14 rounded-control bg-surface-muted" />
      <div class="h-14 rounded-control bg-surface-muted" />
      <div class="h-14 rounded-control bg-surface-muted" />
      <p class="sr-only">{{ $t('practice.loading') }}</p>
    </div>

    <!-- Nothing (left) to practice -->
    <section
      v-else-if="practice.status === 'finished'"
      class="rounded-card border border-border bg-surface p-6 text-center shadow-card"
    >
      <template v-if="!anyAnswers">
        <h1 class="text-2xl">{{ $t('practice.emptyTitle') }}</h1>
        <p class="mt-2 text-ink-muted">{{ $t('practice.emptyText') }}</p>
      </template>
      <template v-else>
        <h1 class="text-2xl">{{ $t('practice.finishedTitle') }}</h1>
        <p v-if="practice.answered" class="mt-2 text-ink-muted">
          {{
            $t('practice.finishedText', { answered: practice.answered, correct: practice.correct })
          }}
          ({{ percentage }}%)
        </p>
        <p v-if="practice.queuedCount" class="mt-2 text-ink-muted">
          {{ $t('practice.finishedQueued', { n: practice.queuedCount }) }}
        </p>
      </template>
      <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
        <AppButton v-if="anyAnswers" @click="begin">{{ $t('practice.again') }}</AppButton>
        <AppButton variant="ghost" @click="stop">{{ $t('practice.otherChoice') }}</AppButton>
      </div>
    </section>

    <!-- A question -->
    <form v-else-if="practice.current" novalidate @submit.prevent="submit">
      <p
        v-if="practice.offlineQuestions"
        role="status"
        class="mb-4 flex items-start gap-2 rounded-control border-2 border-warning bg-warning-soft p-3 text-warning-ink"
      >
        <AppIcon name="alert" class="mt-0.5 size-5" />
        {{ $t('practice.offlineQuestions') }}
      </p>

      <div ref="question" tabindex="-1" class="outline-none">
        <Transition name="question" mode="out-in">
          <div :key="practice.current.id">
            <p
              v-if="practice.current.topic || practice.current.level"
              class="mb-3 flex flex-wrap gap-2"
            >
              <span
                v-if="practice.current.topic"
                class="rounded-full bg-surface-muted px-3 py-1 text-sm font-semibold"
              >
                {{ practice.current.topic.name }}
              </span>
              <span
                v-if="practice.current.level"
                class="rounded-full bg-surface-muted px-3 py-1 text-sm font-semibold"
              >
                {{ practice.current.level.name }}
              </span>
            </p>

            <QuestionRenderer
              v-model="practice.answer"
              :question="practice.current"
              :disabled="practice.status !== 'answering'"
              :result="practice.result"
            />
          </div>
        </Transition>
      </div>

      <div v-if="practice.result" ref="feedback" tabindex="-1" class="mt-6 outline-none">
        <FeedbackBanner :kind="practice.result.is_correct ? 'correct' : 'incorrect'">
          <p v-for="(paragraph, i) in explanation" :key="i" :class="i > 0 && 'mt-3'">
            {{ paragraph }}
          </p>
        </FeedbackBanner>
      </div>

      <div v-else-if="practice.queued" ref="feedback" tabindex="-1" class="mt-6 outline-none">
        <FeedbackBanner kind="info" :title="$t('practice.queuedTitle')">
          <p>{{ $t('practice.queuedText') }}</p>
        </FeedbackBanner>
      </div>

      <FeedbackBanner
        v-if="practice.error && practice.status === 'answering'"
        kind="warning"
        class="mt-6"
      >
        {{ practice.error === 'network' ? $t('errors.network') : $t('practice.checkError') }}
      </FeedbackBanner>

      <!-- Sticks above the bottom navigation so the button is always within reach -->
      <div
        class="sticky bottom-[calc(4rem+env(safe-area-inset-bottom))] z-10 -mx-4 mt-8 border-t border-border bg-canvas/95 px-4 py-3 backdrop-blur md:bottom-0"
      >
        <AppButton
          type="submit"
          block
          :disabled="checked ? false : !practice.canCheck"
          :loading="busy"
        >
          <template v-if="checked">{{ $t('practice.next') }}</template>
          <template v-else-if="busy">{{ $t('practice.checking') }}</template>
          <template v-else>{{ $t('practice.check') }}</template>
        </AppButton>
      </div>
    </form>
  </div>
</template>

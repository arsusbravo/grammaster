<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as endpoints from '@/api/endpoints'
import { errorKind } from '@/api/errors'
import QuestionRenderer from '@/components/question/QuestionRenderer.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import { minutesOf } from '@/lib/format'
import type { AnswerResult, ExamResult, ReviewItem } from '@/types/api'

const route = useRoute()
const router = useRouter()

const result = ref<ExamResult | null>(null)
const loading = ref(true)
const failure = ref<'network' | 'server' | 'notfound' | null>(null)
const filter = ref<'all' | 'wrong'>('all')
const againBusy = ref(false)

async function load() {
  loading.value = true
  failure.value = null
  try {
    result.value = await endpoints.examResult(Number(route.params.attemptId))
  } catch (e) {
    const kind = errorKind(e)
    failure.value = kind === 'network' ? 'network' : kind === 'notfound' ? 'notfound' : 'server'
  } finally {
    loading.value = false
  }
}

onMounted(load)

const numbered = computed(() => (result.value?.review ?? []).map((item, i) => ({ item, n: i + 1 })))
const shown = computed(() =>
  filter.value === 'all' ? numbered.value : numbered.value.filter(({ item }) => !item.is_correct),
)
const wrongCount = computed(() => numbered.value.filter(({ item }) => !item.is_correct).length)

/** The review item in the shape the question components use for a checked question. */
function asResult(item: ReviewItem): AnswerResult {
  return {
    question_id: item.question.id,
    attempt_id: result.value?.id ?? 0,
    is_correct: item.is_correct,
    items: item.items,
    correct_answer: item.correct_answer,
    explanation: item.explanation,
  }
}

const paragraphs = (text: string) => text.split(/\n{2,}/).filter((p) => p.trim() !== '')

async function again() {
  if (!result.value || againBusy.value) return
  againBusy.value = true
  try {
    const attempt = await endpoints.startExam(result.value.exam.id)
    await router.push({ name: 'exam-run', params: { attemptId: attempt.id } })
  } catch {
    await router.push({ name: 'exams' })
  } finally {
    againBusy.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <FeedbackBanner v-if="failure" kind="warning">
      {{ failure === 'network' ? $t('errors.network') : $t('examResult.loadError') }}
      <AppButton v-if="failure !== 'notfound'" variant="ghost" class="mt-3" @click="load">{{
        $t('errors.retry')
      }}</AppButton>
    </FeedbackBanner>

    <div v-else-if="loading" class="animate-pulse space-y-4" aria-busy="true">
      <div class="h-10 w-2/3 rounded-control bg-surface-muted" />
      <div class="h-32 rounded-card bg-surface-muted" />
    </div>

    <template v-else-if="result">
      <h1 class="text-3xl">{{ result.exam.title }}</h1>

      <!-- Verdict: colour, icon and words -->
      <section
        class="mt-6 rounded-card border-2 p-6 text-center"
        :class="
          result.passed
            ? 'border-success bg-success-soft text-success-ink'
            : 'border-error bg-error-soft text-error-ink'
        "
        aria-labelledby="verdict"
      >
        <p id="verdict" class="flex items-center justify-center gap-2 text-xl font-bold">
          <AppIcon :name="result.passed ? 'check' : 'x'" class="size-7" />
          {{ result.passed ? $t('examResult.passed') : $t('examResult.failed') }}
        </p>
        <p class="mt-2 font-display text-6xl font-semibold">{{ Math.round(result.score) }}%</p>
        <p class="mt-2 font-semibold">
          {{
            $t('examResult.points', { earned: result.points_earned, total: result.points_total })
          }}
        </p>
        <p class="mt-1 text-sm">
          {{ $t('examResult.passMark', { pass: result.exam.pass_mark_percent }) }} ·
          {{ $t('examResult.duration', { minutes: minutesOf(result.duration_seconds) }) }}
        </p>
      </section>

      <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <AppButton :loading="againBusy" @click="again">{{ $t('examResult.again') }}</AppButton>
        <AppButton variant="ghost" @click="router.push({ name: 'exams' })">{{
          $t('examResult.back')
        }}</AppButton>
      </div>

      <!-- Review -->
      <h2 class="mt-10 text-2xl">{{ $t('examResult.review') }}</h2>
      <div role="group" :aria-label="$t('examResult.filter')" class="mt-3 flex flex-wrap gap-2">
        <button
          type="button"
          :aria-pressed="filter === 'all'"
          class="min-h-11 rounded-control border-2 px-4 font-semibold"
          :class="
            filter === 'all'
              ? 'border-primary bg-primary text-on-primary'
              : 'border-border-strong bg-surface'
          "
          @click="filter = 'all'"
        >
          {{ $t('examResult.filterAll') }} ({{ numbered.length }})
        </button>
        <button
          type="button"
          :aria-pressed="filter === 'wrong'"
          class="min-h-11 rounded-control border-2 px-4 font-semibold"
          :class="
            filter === 'wrong'
              ? 'border-primary bg-primary text-on-primary'
              : 'border-border-strong bg-surface'
          "
          @click="filter = 'wrong'"
        >
          {{ $t('examResult.filterWrong') }} ({{ wrongCount }})
        </button>
      </div>

      <p v-if="shown.length === 0" class="mt-6 font-semibold text-success-ink">
        {{ $t('examResult.noMistakes') }}
      </p>

      <ol class="mt-6 flex flex-col gap-8">
        <li
          v-for="{ item, n } in shown"
          :key="item.question.id"
          class="rounded-card border border-border bg-surface p-5 shadow-card"
        >
          <div class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
            <span class="font-semibold">{{ $t('examResult.questionN', { n }) }}</span>
            <span
              class="inline-flex items-center gap-1 rounded-full px-3 py-0.5 font-semibold"
              :class="
                item.is_correct
                  ? 'bg-success-soft text-success-ink'
                  : item.answer === null
                    ? 'bg-warning-soft text-warning-ink'
                    : 'bg-error-soft text-error-ink'
              "
            >
              <AppIcon
                :name="item.is_correct ? 'check' : item.answer === null ? 'alert' : 'x'"
                class="size-4"
              />
              {{
                item.is_correct
                  ? $t('question.correct')
                  : item.answer === null
                    ? $t('examResult.unanswered')
                    : $t('question.incorrect')
              }}
            </span>
            <span class="text-ink-muted">
              {{ $t('examResult.pointsOf', { earned: item.points_earned, points: item.points }) }}
            </span>
          </div>

          <QuestionRenderer
            :model-value="item.answer"
            :question="item.question"
            disabled
            :result="asResult(item)"
          />

          <FeedbackBanner
            class="mt-5"
            :kind="item.is_correct ? 'correct' : item.answer === null ? 'warning' : 'incorrect'"
            :title="
              item.answer === null && !item.is_correct ? $t('examResult.unanswered') : undefined
            "
          >
            <p
              v-for="(paragraph, i) in paragraphs(item.explanation)"
              :key="i"
              :class="i > 0 && 'mt-3'"
            >
              {{ paragraph }}
            </p>
          </FeedbackBanner>
        </li>
      </ol>
    </template>
  </div>
</template>

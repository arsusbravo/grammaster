<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as endpoints from '@/api/endpoints'
import { errorKind } from '@/api/errors'
import AppButton from '@/components/ui/AppButton.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import { useCatalogStore } from '@/stores/catalog'
import type { ExamSummary } from '@/types/api'

const catalog = useCatalogStore()
const router = useRouter()

const exams = ref<ExamSummary[]>([])
const levelFilter = ref<number | null>(null)
const loading = ref(true)
const failure = ref<'network' | 'server' | null>(null)

const starting = ref<ExamSummary | null>(null)
const confirmOpen = ref(false)
const busy = ref(false)
const startFailed = ref(false)

const visible = computed(() =>
  levelFilter.value === null
    ? exams.value
    : exams.value.filter((e) => e.level.id === levelFilter.value),
)

async function load() {
  loading.value = true
  failure.value = null
  try {
    const [list] = await Promise.all([
      endpoints.exams(),
      catalog.loadLevels().catch(() => undefined),
    ])
    exams.value = list
  } catch (e) {
    failure.value = errorKind(e) === 'network' ? 'network' : 'server'
  } finally {
    loading.value = false
  }
}

onMounted(load)

const goToAttempt = (attemptId: number) => router.push({ name: 'exam-run', params: { attemptId } })

function ask(exam: ExamSummary) {
  starting.value = exam
  startFailed.value = false
  confirmOpen.value = true
}

async function start() {
  if (!starting.value) return
  busy.value = true
  startFailed.value = false
  try {
    const attempt = await endpoints.startExam(starting.value.id)
    confirmOpen.value = false
    await goToAttempt(attempt.id)
  } catch {
    startFailed.value = true
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <h1 class="text-3xl">{{ $t('exams.title') }}</h1>
    <p class="mt-1 text-ink-muted">{{ $t('exams.subtitle') }}</p>

    <FeedbackBanner v-if="failure" kind="warning" class="mt-6">
      {{ $t(`errors.${failure}`) }}
      <AppButton variant="ghost" class="mt-3" @click="load">{{ $t('errors.retry') }}</AppButton>
    </FeedbackBanner>

    <template v-else>
      <div
        v-if="catalog.levels.length > 1"
        role="group"
        :aria-label="$t('exams.levelFilter')"
        class="mt-6 flex flex-wrap gap-2"
      >
        <button
          type="button"
          :aria-pressed="levelFilter === null"
          class="min-h-11 rounded-control border-2 px-4 font-semibold"
          :class="
            levelFilter === null
              ? 'border-primary bg-primary text-on-primary'
              : 'border-border-strong bg-surface'
          "
          @click="levelFilter = null"
        >
          {{ $t('exams.allLevels') }}
        </button>
        <button
          v-for="level in catalog.levels"
          :key="level.id"
          type="button"
          :aria-pressed="levelFilter === level.id"
          class="min-h-11 rounded-control border-2 px-4 font-semibold"
          :class="
            levelFilter === level.id
              ? 'border-primary bg-primary text-on-primary'
              : 'border-border-strong bg-surface'
          "
          @click="levelFilter = level.id"
        >
          {{ level.name }}
        </button>
      </div>

      <p v-if="loading" class="mt-6 text-ink-muted" aria-busy="true">{{ $t('exams.loading') }}</p>
      <p v-else-if="visible.length === 0" class="mt-6 text-ink-muted">{{ $t('exams.empty') }}</p>

      <ul v-else class="mt-6 flex flex-col gap-4">
        <li
          v-for="exam in visible"
          :key="exam.id"
          class="rounded-card border border-border bg-surface p-5 shadow-card"
        >
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-xl">{{ exam.title }}</h2>
            <span class="rounded-full bg-surface-muted px-3 py-1 text-sm font-semibold">{{
              exam.level.name
            }}</span>
            <span
              v-if="exam.in_progress_attempt_id"
              class="rounded-full border-2 border-accent-ink px-3 py-0.5 text-sm font-semibold text-accent-ink"
            >
              {{ $t('exams.inProgress') }}
            </span>
          </div>

          <p class="mt-2 text-sm text-ink-muted">
            {{
              $t('exams.meta', {
                questions: exam.question_count,
                points: exam.total_points,
                minutes: exam.time_limit_minutes,
                pass: exam.pass_mark_percent,
              })
            }}
          </p>

          <p v-if="exam.attempts_count > 0" class="mt-2 text-sm">
            <span v-if="exam.best_score !== null" class="font-semibold">
              {{ $t('exams.best', { score: Math.round(Number(exam.best_score)) }) }}
            </span>
            · {{ $t('exams.attempts', exam.attempts_count) }}
          </p>

          <div class="mt-4 flex flex-col gap-3 sm:flex-row">
            <AppButton
              v-if="exam.in_progress_attempt_id"
              @click="goToAttempt(exam.in_progress_attempt_id)"
            >
              {{ $t('exams.continue') }}
            </AppButton>
            <AppButton v-else @click="ask(exam)">{{ $t('exams.start') }}</AppButton>

            <RouterLink
              v-if="exam.last_attempt_id"
              :to="{ name: 'exam-result', params: { attemptId: exam.last_attempt_id } }"
              class="inline-flex min-h-12 items-center justify-center rounded-control border-2 border-border-strong px-6 font-semibold hover:bg-surface-muted"
            >
              {{ $t('exams.lastResult') }}
            </RouterLink>
          </div>
        </li>
      </ul>
    </template>

    <ConfirmDialog
      v-model:open="confirmOpen"
      :title="$t('exams.startTitle')"
      :confirm-label="$t('exams.startConfirm')"
      :cancel-label="$t('dialog.cancel')"
      :busy="busy"
      @confirm="start"
    >
      <p>{{ $t('exams.startText', { minutes: starting?.time_limit_minutes ?? 0 }) }}</p>
      <FeedbackBanner v-if="startFailed" kind="warning">{{
        $t('exams.startFailed')
      }}</FeedbackBanner>
    </ConfirmDialog>
  </div>
</template>

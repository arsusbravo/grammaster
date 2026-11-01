<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as endpoints from '@/api/endpoints'
import { errorKind } from '@/api/errors'
import AppButton from '@/components/ui/AppButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import { formatWhen } from '@/lib/format'
import { useAuthStore } from '@/stores/auth'
import { useCatalogStore } from '@/stores/catalog'
import type { Progress, TopicProgress } from '@/types/api'

const auth = useAuthStore()
const catalog = useCatalogStore()
const router = useRouter()

const data = ref<Progress | null>(null)
const loading = ref(true)
const failure = ref<'network' | 'server' | null>(null)

async function load() {
  loading.value = true
  failure.value = null
  try {
    const [progress] = await Promise.all([
      endpoints.progress(),
      catalog.loadLevels().catch(() => undefined),
    ])
    data.value = progress
  } catch (e) {
    failure.value = errorKind(e) === 'network' ? 'network' : 'server'
  } finally {
    loading.value = false
  }
}

onMounted(load)

const isEmpty = computed(() => data.value !== null && data.value.overall.answered === 0)
const weakIds = computed(() => new Set(data.value?.weak_topics.map((t) => t.topic.id)))
const levelSlug = computed(() => catalog.levels.find((l) => l.id === auth.user?.level_id)?.slug)

function practice(topic: TopicProgress) {
  void router.push({
    name: 'practice',
    query: { topic: topic.topic.slug, ...(levelSlug.value ? { level: levelSlug.value } : {}) },
  })
}

const round = (n: number | null) => Math.round(n ?? 0)
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <h1 class="text-3xl">{{ $t('progress.title') }}</h1>
    <p class="mt-1 text-ink-muted">{{ $t('progress.subtitle') }}</p>

    <FeedbackBanner v-if="failure" kind="warning" class="mt-6">
      {{ $t(`errors.${failure}`) }}
      <AppButton variant="ghost" class="mt-3" @click="load">{{ $t('errors.retry') }}</AppButton>
    </FeedbackBanner>

    <div v-else-if="loading" class="mt-6 animate-pulse space-y-4" aria-busy="true">
      <div class="h-32 rounded-card bg-surface-muted" />
      <div class="h-48 rounded-card bg-surface-muted" />
    </div>

    <section
      v-else-if="isEmpty"
      class="mt-8 rounded-card border border-border bg-surface p-6 text-center shadow-card"
    >
      <AppIcon name="chart" class="mx-auto size-10 text-ink-muted" />
      <h2 class="mt-3 text-2xl">{{ $t('progress.emptyTitle') }}</h2>
      <p class="mt-2 text-ink-muted">{{ $t('progress.emptyText') }}</p>
      <AppButton class="mt-5" @click="router.push({ name: 'home' })">{{
        $t('progress.emptyCta')
      }}</AppButton>
    </section>

    <template v-else-if="data">
      <!-- Overall -->
      <section
        class="mt-6 rounded-card border border-border bg-surface p-6 text-center shadow-card"
        aria-labelledby="overall"
      >
        <h2 id="overall" class="text-sm font-semibold tracking-wide text-ink-muted uppercase">
          {{ $t('progress.overall') }}
        </h2>
        <p class="mt-1 font-display text-6xl font-semibold">
          {{ $t('progress.percentGood', { n: round(data.overall.accuracy) }) }}
        </p>
        <p class="mt-1 text-ink-muted">
          {{
            $t('progress.overallDetail', {
              correct: data.overall.correct,
              answered: data.overall.answered,
            })
          }}
        </p>
      </section>

      <!-- Weak topics -->
      <section class="mt-8" aria-labelledby="weak">
        <h2 id="weak" class="text-xl">{{ $t('progress.weakTitle') }}</h2>
        <p class="mt-1 text-sm text-ink-muted">
          {{
            $t('progress.weakIntro', {
              below: data.weak_criteria.below_percent,
              min: data.weak_criteria.min_answers,
            })
          }}
        </p>

        <p v-if="data.weak_topics.length === 0" class="mt-3 font-semibold text-success-ink">
          {{ $t('progress.weakNone') }}
        </p>

        <ul v-else class="mt-3 flex flex-col gap-3">
          <li
            v-for="t in data.weak_topics"
            :key="t.topic.id"
            class="flex flex-wrap items-center gap-x-4 gap-y-3 rounded-card border-2 border-warning bg-warning-soft p-4 text-warning-ink"
          >
            <div class="min-w-0 flex-1">
              <p class="text-lg font-semibold">{{ t.topic.name }}</p>
              <p class="text-sm">
                {{ $t('progress.percentGood', { n: round(t.accuracy) }) }} ·
                {{ $t('progress.answered', t.answered) }}
              </p>
            </div>
            <AppButton variant="primary" @click="practice(t)">{{
              $t('progress.practiceThis')
            }}</AppButton>
          </li>
        </ul>
      </section>

      <!-- Per topic -->
      <section class="mt-8" aria-labelledby="topics">
        <h2 id="topics" class="text-xl">{{ $t('progress.topicsTitle') }}</h2>
        <ul class="mt-3 flex flex-col gap-3">
          <li
            v-for="t in data.topics"
            :key="t.topic.id"
            class="rounded-card border border-border bg-surface p-4"
          >
            <div class="flex flex-wrap items-baseline justify-between gap-x-3">
              <p class="font-semibold">{{ t.topic.name }}</p>
              <p v-if="t.accuracy !== null" class="font-semibold tabular-nums">
                {{ $t('progress.percentGood', { n: round(t.accuracy) }) }}
              </p>
              <p v-else class="text-sm text-ink-muted">{{ $t('progress.notPracticed') }}</p>
            </div>

            <div
              v-if="t.accuracy !== null"
              role="meter"
              :aria-valuenow="round(t.accuracy)"
              aria-valuemin="0"
              aria-valuemax="100"
              :aria-label="$t('progress.meter', { name: t.topic.name, n: round(t.accuracy) })"
              class="mt-2 h-3 overflow-hidden rounded-full bg-surface-muted"
            >
              <div
                class="h-full rounded-full"
                :class="weakIds.has(t.topic.id) ? 'bg-warning' : 'bg-secondary'"
                :style="{ width: `${Math.max(2, round(t.accuracy))}%` }"
              />
            </div>

            <p
              v-if="t.accuracy !== null"
              class="mt-2 flex flex-wrap items-center gap-x-3 text-sm text-ink-muted"
            >
              <span>{{ $t('progress.answered', t.answered) }}</span>
              <span
                v-if="weakIds.has(t.topic.id)"
                class="inline-flex items-center gap-1 font-semibold text-warning-ink"
              >
                <AppIcon name="alert" class="size-4" />
                {{ $t('progress.weakFlag') }}
              </span>
            </p>
          </li>
        </ul>
      </section>

      <!-- History -->
      <section class="mt-8" aria-labelledby="history">
        <h2 id="history" class="text-xl">{{ $t('progress.historyTitle') }}</h2>
        <ul class="mt-3 divide-y divide-border rounded-card border border-border bg-surface">
          <li
            v-for="item in data.history"
            :key="item.id"
            class="flex flex-wrap items-center gap-x-4 gap-y-1 p-4"
          >
            <div class="min-w-0 flex-1">
              <p class="font-semibold">
                {{ item.exam ? item.exam.title : $t('progress.sessionLabel') }}
              </p>
              <p class="text-sm text-ink-muted">
                {{ formatWhen(item.finished_at) }} ·
                {{
                  $t('progress.answeredCorrect', { correct: item.correct, answered: item.total })
                }}
              </p>
              <RouterLink
                v-if="item.exam"
                :to="{ name: 'exam-result', params: { attemptId: item.id } }"
                class="mt-1 inline-block text-sm font-semibold text-secondary underline underline-offset-4"
              >
                {{ $t('progress.viewResult') }}
              </RouterLink>
            </div>

            <div class="text-right">
              <p class="text-xl font-semibold tabular-nums">{{ round(item.score) }}%</p>
              <p
                v-if="item.passed !== null"
                class="inline-flex items-center gap-1 text-sm font-semibold"
                :class="item.passed ? 'text-success-ink' : 'text-error-ink'"
              >
                <AppIcon :name="item.passed ? 'check' : 'x'" class="size-4" />
                {{ item.passed ? $t('progress.passed') : $t('progress.failed') }}
              </p>
            </div>
          </li>
        </ul>
      </section>
    </template>
  </div>
</template>

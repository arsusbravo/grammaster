<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { errorKind } from '@/api/errors'
import AppButton from '@/components/ui/AppButton.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import LevelPicker from '@/components/ui/LevelPicker.vue'
import { useAuthStore } from '@/stores/auth'
import { useCatalogStore } from '@/stores/catalog'

const auth = useAuthStore()
const catalog = useCatalogStore()
const router = useRouter()

const levelId = ref<number | null>(null)
const loading = ref(true)
const failure = ref<'network' | 'server' | null>(null)

const level = computed(() => catalog.levels.find((l) => l.id === levelId.value))
const topics = computed(() => (level.value ? (catalog.topicsByLevel[level.value.slug] ?? []) : []))
const total = computed(() => topics.value.reduce((sum, t) => sum + (t.questions_count ?? 0), 0))

async function load() {
  loading.value = true
  failure.value = null
  try {
    await catalog.loadLevels()
    levelId.value ??= auth.user?.level_id ?? catalog.levels[0]?.id ?? null
    if (level.value) await catalog.loadTopics(level.value.slug)
  } catch (e) {
    failure.value = errorKind(e) === 'network' ? 'network' : 'server'
  } finally {
    loading.value = false
  }
}

onMounted(load)

watch(levelId, async (id, previous) => {
  if (id === null || previous === null || !level.value) return

  try {
    await catalog.loadTopics(level.value.slug)
  } catch (e) {
    failure.value = errorKind(e) === 'network' ? 'network' : 'server'
  }
  // Remember the choice for next time. Not critical, so failures are ignored.
  if (id !== auth.user?.level_id) auth.setLevel(id).catch(() => undefined)
})

function practice(topicSlug?: string) {
  if (!level.value) return
  void router.push({
    name: 'practice',
    query: { level: level.value.slug, ...(topicSlug ? { topic: topicSlug } : {}) },
  })
}
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <h1 class="text-3xl">{{ $t('home.greeting', { name: auth.user?.name ?? '' }) }}</h1>
    <p class="mt-1 text-ink-muted">{{ $t('home.subtitle') }}</p>

    <FeedbackBanner v-if="failure" kind="warning" class="mt-6">
      {{ $t(`errors.${failure}`) }}
      <AppButton variant="ghost" class="mt-3" @click="load">{{ $t('errors.retry') }}</AppButton>
    </FeedbackBanner>

    <template v-else>
      <section class="mt-8" aria-labelledby="level-heading">
        <h2 id="level-heading" class="mb-3 text-lg">{{ $t('home.level') }}</h2>
        <LevelPicker v-model="levelId" :levels="catalog.levels" />
      </section>

      <section class="mt-8" aria-labelledby="topic-heading" :aria-busy="loading">
        <h2 id="topic-heading" class="mb-3 text-lg">{{ $t('home.topic') }}</h2>

        <p v-if="loading" class="text-ink-muted">{{ $t('home.loading') }}</p>

        <ul v-else class="grid gap-3 sm:grid-cols-2">
          <li class="sm:col-span-2">
            <button
              type="button"
              class="flex min-h-16 w-full items-center gap-3 rounded-card border-2 border-accent bg-surface p-4 text-left shadow-card hover:bg-surface-muted disabled:opacity-60"
              :disabled="total === 0"
              @click="practice()"
            >
              <span class="flex-1">
                <span class="block text-lg font-semibold">{{ $t('home.allTopics') }}</span>
                <span class="block text-sm text-ink-muted">{{ $t('home.allTopicsHint') }}</span>
              </span>
              <span class="text-sm font-semibold text-ink-muted">{{
                $t('home.questions', total)
              }}</span>
            </button>
          </li>
          <li v-for="topic in topics" :key="topic.id">
            <button
              type="button"
              class="flex h-full min-h-16 w-full flex-col gap-1 rounded-card border border-border bg-surface p-4 text-left shadow-card hover:bg-surface-muted disabled:opacity-60"
              :disabled="(topic.questions_count ?? 0) === 0"
              @click="practice(topic.slug)"
            >
              <span class="text-lg font-semibold">{{ topic.name }}</span>
              <span v-if="topic.description" class="text-sm text-ink-muted">{{
                topic.description
              }}</span>
              <span class="mt-auto pt-2 text-sm font-semibold text-accent-ink">
                {{ $t('home.questions', topic.questions_count ?? 0) }}
              </span>
            </button>
          </li>
        </ul>
      </section>
    </template>
  </div>
</template>

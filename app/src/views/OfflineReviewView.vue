<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import QuestionRenderer from '@/components/question/QuestionRenderer.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import { useOfflineStore } from '@/stores/offline'

// The answers the student gave offline, now checked by the server.
const offline = useOfflineStore()
const router = useRouter()

onMounted(() => offline.refresh())

const items = computed(() => offline.reviewed)
const paragraphs = (text: string) => text.split(/\n{2,}/).filter((p) => p.trim() !== '')

function done() {
  offline.dismiss()
  void router.push({ name: 'home' })
}
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <h1 class="text-3xl">{{ $t('offline.reviewTitle') }}</h1>
    <p class="mt-1 text-ink-muted">{{ $t('offline.reviewIntro') }}</p>

    <p v-if="items.length === 0" class="mt-6 text-ink-muted">{{ $t('offline.reviewEmpty') }}</p>

    <template v-else>
      <p class="mt-4 font-semibold">
        {{ $t('offline.reviewed', { correct: offline.reviewedCorrect, total: items.length }) }}
      </p>

      <ol class="mt-6 flex flex-col gap-8">
        <li
          v-for="(item, index) in items"
          :key="item.id"
          class="rounded-card border border-border bg-surface p-5 shadow-card"
        >
          <p class="mb-4 flex items-center gap-2 text-sm font-semibold">
            <span>{{ $t('examResult.questionN', { n: index + 1 }) }}</span>
            <span
              class="inline-flex items-center gap-1 rounded-full px-3 py-0.5"
              :class="
                item.result.is_correct
                  ? 'bg-success-soft text-success-ink'
                  : 'bg-error-soft text-error-ink'
              "
            >
              <AppIcon :name="item.result.is_correct ? 'check' : 'x'" class="size-4" />
              {{ item.result.is_correct ? $t('question.correct') : $t('question.incorrect') }}
            </span>
          </p>

          <QuestionRenderer
            :model-value="item.answer"
            :question="item.question"
            disabled
            :result="item.result"
          />

          <FeedbackBanner class="mt-5" :kind="item.result.is_correct ? 'correct' : 'incorrect'">
            <p
              v-for="(paragraph, i) in paragraphs(item.result.explanation)"
              :key="i"
              :class="i > 0 && 'mt-3'"
            >
              {{ paragraph }}
            </p>
          </FeedbackBanner>
        </li>
      </ol>

      <AppButton class="mt-8" block @click="done">{{ $t('offline.reviewDone') }}</AppButton>
    </template>
  </div>
</template>

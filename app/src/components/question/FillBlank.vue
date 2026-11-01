<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppInput from '@/components/ui/AppInput.vue'
import { splitBlankText } from '@/lib/answers'
import type { AnswerResult, FillBlankAnswer, FillBlankQuestion } from '@/types/api'

const model = defineModel<FillBlankAnswer | null>({ default: null })

const props = defineProps<{
  question: FillBlankQuestion
  disabled?: boolean
  result?: AnswerResult | null
}>()

const { t } = useI18n()

const parts = computed(() => splitBlankText(props.question.data.text))
const blanks = computed(() => model.value?.blanks ?? props.question.data.blanks.map(() => ''))

function update(index: number, value: string) {
  const next = [...blanks.value]
  next[index] = value
  model.value = { blanks: next }
}

function stateOf(index: number) {
  if (!props.result?.items) return 'idle'
  return props.result.items[index] ? 'correct' : 'incorrect'
}

function labelOf(index: number): string {
  const hint = props.question.data.blanks[index]?.hint
  const base = t('question.blankLabel', { n: index + 1, total: props.question.data.blanks.length })
  return hint ? `${base} (${hint})` : base
}

/** Blanks that were wrong, with the answer that was expected. */
const corrections = computed(() =>
  props.result
    ? (props.result.items ?? [])
        .map((ok, index) => ({ ok, index, answer: props.result?.correct_answer.blanks?.[index] }))
        .filter((c) => !c.ok && c.answer)
    : [],
)
</script>

<template>
  <div>
    <p class="text-lg leading-[3.25]">
      <template v-for="(part, index) in parts" :key="index">
        <span>{{ part }}</span>
        <span v-if="index < parts.length - 1" class="mx-1 inline-block align-baseline">
          <AppInput
            :model-value="blanks[index] ?? ''"
            inline
            answer
            :aria-label="labelOf(index)"
            :state="stateOf(index)"
            :disabled="disabled"
            @update:model-value="update(index, $event)"
          />
          <span v-if="question.data.blanks[index]?.hint" class="ml-1 text-sm text-ink-muted">
            ({{ question.data.blanks[index].hint }})
          </span>
        </span>
      </template>
    </p>

    <ul v-if="corrections.length" class="mt-2 space-y-1">
      <li v-for="c in corrections" :key="c.index" class="text-success-ink">
        <span class="font-semibold">{{ $t('question.correctBlank', { n: c.index + 1 }) }}</span>
        {{ c.answer }}
      </li>
    </ul>
  </div>
</template>

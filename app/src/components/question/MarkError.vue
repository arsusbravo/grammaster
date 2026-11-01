<script setup lang="ts">
import { computed } from 'vue'
import TokenChip from '@/components/ui/TokenChip.vue'
import type { OptionState } from '@/components/ui/OptionButton.vue'
import type { AnswerResult, MarkErrorAnswer, MarkErrorQuestion } from '@/types/api'

const model = defineModel<MarkErrorAnswer | null>({ default: null })

const props = defineProps<{
  question: MarkErrorQuestion
  disabled?: boolean
  result?: AnswerResult | null
}>()

const selected = computed(() => model.value?.index ?? -1)

function choose(index: number) {
  if (!props.disabled) model.value = { index }
}

function stateOf(index: number): OptionState {
  if (props.result) {
    const wrongIndex = props.result.correct_answer.index
    if (index === selected.value) return index === wrongIndex ? 'correct' : 'incorrect'
    return index === wrongIndex ? 'missed' : 'idle'
  }
  return index === selected.value ? 'selected' : 'idle'
}
</script>

<template>
  <div>
    <p v-if="!result" class="mb-3 text-ink-muted">{{ $t('question.markHint') }}</p>
    <div class="flex flex-wrap gap-2" role="group">
      <TokenChip
        v-for="(token, index) in question.data.tokens"
        :key="index"
        :label="token"
        :state="stateOf(index)"
        :disabled="disabled"
        @click="choose(index)"
      />
    </div>
    <p v-if="result?.correct_answer.correction" class="mt-3 text-success-ink">
      <span class="font-semibold">{{ $t('question.correctWord') }}</span>
      {{ result.correct_answer.correction }}
    </p>
  </div>
</template>

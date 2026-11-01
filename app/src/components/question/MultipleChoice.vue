<script setup lang="ts">
import { computed } from 'vue'
import OptionButton, { type OptionState } from '@/components/ui/OptionButton.vue'
import type { AnswerResult, MultipleChoiceAnswer, MultipleChoiceQuestion } from '@/types/api'

const model = defineModel<MultipleChoiceAnswer | null>({ default: null })

const props = defineProps<{
  question: MultipleChoiceQuestion
  disabled?: boolean
  result?: AnswerResult | null
}>()

const multiple = computed(() => props.question.data.multiple)
const selected = computed(() => model.value?.selected ?? [])

function toggle(index: number) {
  if (props.disabled) return

  if (!multiple.value) {
    model.value = { selected: [index] }
    return
  }

  const next = new Set(selected.value)
  if (!next.delete(index)) next.add(index)
  model.value = { selected: [...next].sort((a, b) => a - b) }
}

function stateOf(index: number): OptionState {
  const chosen = selected.value.includes(index)

  if (props.result) {
    const correct = props.result.correct_answer.selected ?? []
    if (chosen) return correct.includes(index) ? 'correct' : 'incorrect'
    return correct.includes(index) ? 'missed' : 'idle'
  }

  return chosen ? 'selected' : 'idle'
}
</script>

<template>
  <div>
    <p v-if="multiple && !result" class="mb-3 text-ink-muted">
      {{ $t('question.chooseMultiple') }}
    </p>
    <div :role="multiple ? 'group' : 'radiogroup'" class="flex flex-col gap-3">
      <OptionButton
        v-for="(option, index) in question.data.options"
        :key="index"
        :label="option"
        :marker="String.fromCharCode(65 + index)"
        :state="stateOf(index)"
        :control="multiple ? 'checkbox' : 'radio'"
        :disabled="disabled"
        @click="toggle(index)"
      />
    </div>
  </div>
</template>

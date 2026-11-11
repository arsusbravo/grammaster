<script setup lang="ts">
import { computed, useId, type Component } from 'vue'
import FillBlank from './FillBlank.vue'
import MarkError from './MarkError.vue'
import MultipleChoice from './MultipleChoice.vue'
import OrderWords from './OrderWords.vue'
import ParseSentence from './ParseSentence.vue'
import PassageCard from './PassageCard.vue'
import type { Answer, AnswerResult, Question, QuestionType } from '@/types/api'

// Every question component has the same contract: v-model (the answer), disabled, result.
const model = defineModel<Answer | null>({ default: null })

const props = defineProps<{
  question: Question
  disabled?: boolean
  result?: AnswerResult | null
}>()

const components: Record<QuestionType, Component> = {
  multiple_choice: MultipleChoice,
  fill_blank: FillBlank,
  order_words: OrderWords,
  mark_error: MarkError,
  parse_sentence: ParseSentence,
}

const component = computed(() => components[props.question.type])
const headingId = useId()
</script>

<template>
  <div role="group" :aria-labelledby="headingId">
    <PassageCard v-if="question.passage" :passage="question.passage" />
    <h2 :id="headingId" class="mb-4 text-xl leading-snug sm:text-2xl">{{ question.prompt }}</h2>
    <component
      :is="component"
      :key="question.id"
      v-model="model"
      :question="question"
      :disabled="disabled"
      :result="result"
    />
  </div>
</template>

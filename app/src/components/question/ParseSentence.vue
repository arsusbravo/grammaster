<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppSelect from '@/components/ui/AppSelect.vue'
import type { AnswerResult, ParseSentenceAnswer, ParseSentenceQuestion } from '@/types/api'

const model = defineModel<ParseSentenceAnswer | null>({ default: null })

const props = defineProps<{
  question: ParseSentenceQuestion
  disabled?: boolean
  result?: AnswerResult | null
}>()

const { t } = useI18n()

const roles = computed(() => model.value?.roles ?? props.question.data.tokens.map(() => null))

function update(index: number, role: string | null) {
  const next = [...roles.value]
  next[index] = role
  model.value = { roles: next }
}

function stateOf(index: number) {
  if (!props.result?.items) return 'idle'
  return props.result.items[index] ? 'correct' : 'incorrect'
}

const expected = (index: number) => props.result?.correct_answer.roles?.[index]
</script>

<template>
  <ul class="flex flex-col gap-3">
    <li
      v-for="(token, index) in question.data.tokens"
      :key="index"
      class="grid gap-2 rounded-card border border-border bg-surface p-4 sm:grid-cols-2 sm:items-center"
    >
      <p class="text-lg font-semibold">{{ token }}</p>
      <div>
        <AppSelect
          :model-value="roles[index]"
          :options="question.data.roles"
          :placeholder="t('question.chooseRole')"
          :aria-label="t('question.roleOf', { part: token })"
          :state="stateOf(index)"
          :disabled="disabled"
          @update:model-value="update(index, $event)"
        />
        <p
          v-if="result && stateOf(index) === 'incorrect' && expected(index)"
          class="mt-1.5 text-success-ink"
        >
          <span class="font-semibold">{{ $t('question.correctRole') }}</span> {{ expected(index) }}
        </p>
      </div>
    </li>
  </ul>
</template>

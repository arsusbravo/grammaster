<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from './AppIcon.vue'

export type OptionState = 'idle' | 'selected' | 'correct' | 'incorrect' | 'missed'

const props = defineProps<{
  label: string
  marker?: string
  state?: OptionState
  disabled?: boolean
  /** `radio` (pick one) or `checkbox` (pick several) instead of a plain toggle button. */
  control?: 'radio' | 'checkbox'
}>()

const { t } = useI18n()
const state = computed(() => props.state ?? 'idle')
// A chosen option keeps showing as checked after the result is shown.
const checked = computed(() => ['selected', 'correct', 'incorrect'].includes(state.value))

const styles: Record<OptionState, string> = {
  idle: 'border-border-strong bg-surface text-ink hover:bg-surface-muted',
  selected: 'border-secondary bg-surface-muted text-ink ring-2 ring-secondary',
  correct: 'border-success bg-success-soft text-success-ink',
  incorrect: 'border-error bg-error-soft text-error-ink',
  missed: 'border-dashed border-success bg-surface text-success-ink',
}

// Result is always communicated by icon + text as well as colour.
const badge = computed(() => {
  if (state.value === 'correct') return { icon: 'check' as const, text: t('question.correct') }
  if (state.value === 'incorrect') return { icon: 'x' as const, text: t('question.incorrect') }
  if (state.value === 'missed') return { icon: 'check' as const, text: t('question.missed') }
  return null
})
</script>

<template>
  <button
    type="button"
    :disabled="disabled"
    :role="control"
    :aria-checked="control ? checked : undefined"
    :aria-pressed="control ? undefined : state === 'selected' ? true : undefined"
    class="flex min-h-14 w-full items-center gap-3 rounded-control border-2 px-4 py-3 text-left text-base transition-colors disabled:cursor-default"
    :class="styles[state]"
  >
    <span
      v-if="marker"
      class="flex size-8 shrink-0 items-center justify-center rounded-full border-2 border-current text-sm font-bold"
      aria-hidden="true"
    >
      {{ marker }}
    </span>
    <span class="flex-1">{{ label }}</span>
    <span v-if="badge" class="flex shrink-0 items-center gap-1 text-sm font-bold">
      <AppIcon :name="badge.icon" />
      {{ badge.text }}
    </span>
  </button>
</template>

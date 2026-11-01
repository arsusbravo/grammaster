<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from './AppIcon.vue'
import type { OptionState } from './OptionButton.vue'

const props = defineProps<{
  label: string
  /** Small caption under the word, e.g. the chosen sentence-part role. */
  caption?: string
  state?: OptionState
  draggable?: boolean
  disabled?: boolean
}>()

const { t } = useI18n()
const state = computed(() => props.state ?? 'idle')

const styles: Record<OptionState, string> = {
  idle: 'border-border-strong bg-surface text-ink hover:bg-surface-muted',
  selected: 'border-secondary bg-surface-muted text-ink ring-2 ring-secondary',
  correct: 'border-success bg-success-soft text-success-ink',
  incorrect: 'border-error bg-error-soft text-error-ink',
  missed: 'border-dashed border-success bg-surface text-success-ink',
}

const result = computed(() => {
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
    :aria-pressed="state === 'selected' ? true : undefined"
    class="inline-flex min-h-12 min-w-12 flex-col items-center justify-center rounded-control border-2 px-3.5 py-1.5 text-base font-semibold transition-colors disabled:cursor-default"
    :class="[styles[state], draggable && 'cursor-grab touch-none active:cursor-grabbing']"
  >
    <span class="flex items-center gap-1.5">
      <AppIcon v-if="draggable && !result" name="grip" class="text-ink-muted" />
      {{ label }}
      <AppIcon v-if="result" :name="result.icon" />
    </span>
    <span v-if="caption" class="text-xs font-medium opacity-90">{{ caption }}</span>
    <span v-if="result" class="sr-only">{{ result.text }}</span>
  </button>
</template>

<script setup lang="ts">
import { computed, useId } from 'vue'
import AppIcon from './AppIcon.vue'

const model = defineModel<string>({ default: '' })

const props = defineProps<{
  label?: string
  hint?: string
  error?: string
  type?: string
  /** Result after checking; shown with an icon, never by colour alone. */
  state?: 'idle' | 'correct' | 'incorrect'
  /** Compact variant used inside a sentence (fill in the blank). */
  inline?: boolean
  disabled?: boolean
  placeholder?: string
  autocomplete?: string
  ariaLabel?: string
  /** An answer field: the keyboard must not capitalise, autocorrect or spell-check the student's spelling. */
  answer?: boolean
}>()

const id = useId()
const invalid = computed(() => !!props.error || props.state === 'incorrect')

const border = computed(() => {
  if (props.state === 'correct') return 'border-success bg-success-soft text-success-ink'
  if (invalid.value) return 'border-error bg-error-soft text-error-ink'
  return 'border-border-strong bg-surface text-ink'
})
</script>

<template>
  <div :class="inline ? 'inline-flex flex-col align-baseline' : 'flex flex-col gap-1.5'">
    <label v-if="label" :for="id" class="text-base font-semibold">{{ label }}</label>
    <div class="relative">
      <input
        :id="id"
        v-model="model"
        :type="type ?? 'text'"
        :disabled="disabled"
        :placeholder="placeholder"
        :autocomplete="answer ? 'off' : autocomplete"
        :autocapitalize="answer ? 'off' : undefined"
        :autocorrect="answer ? 'off' : undefined"
        :spellcheck="answer ? false : undefined"
        :aria-label="ariaLabel"
        :aria-invalid="invalid || undefined"
        :aria-describedby="error || hint ? `${id}-help` : undefined"
        class="min-h-12 w-full rounded-control border-2 px-4 text-base placeholder:text-ink-muted disabled:cursor-not-allowed disabled:opacity-70"
        :class="[
          border,
          inline && 'w-40 text-center font-semibold',
          state && state !== 'idle' && 'pr-11',
        ]"
      />
      <span
        v-if="state === 'correct' || state === 'incorrect'"
        class="pointer-events-none absolute inset-y-0 right-3 flex items-center"
        :class="state === 'correct' ? 'text-success' : 'text-error'"
      >
        <AppIcon :name="state === 'correct' ? 'check' : 'x'" />
      </span>
    </div>
    <p
      v-if="error || hint"
      :id="`${id}-help`"
      class="text-sm"
      :class="error ? 'font-semibold text-error-ink' : 'text-ink-muted'"
    >
      {{ error ?? hint }}
    </p>
  </div>
</template>

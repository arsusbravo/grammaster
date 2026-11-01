<script setup lang="ts">
import { computed, useId } from 'vue'
import AppIcon from './AppIcon.vue'

const model = defineModel<string | null>({ default: null })

const props = defineProps<{
  options: string[]
  label?: string
  ariaLabel?: string
  placeholder?: string
  /** Result after checking; shown with an icon, never by colour alone. */
  state?: 'idle' | 'correct' | 'incorrect'
  disabled?: boolean
}>()

const id = useId()

const border = computed(() => {
  if (props.state === 'correct') return 'border-success bg-success-soft text-success-ink'
  if (props.state === 'incorrect') return 'border-error bg-error-soft text-error-ink'
  return 'border-border-strong bg-surface text-ink'
})
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="id" class="text-base font-semibold">{{ label }}</label>
    <div class="relative">
      <select
        :id="id"
        v-model="model"
        :disabled="disabled"
        :aria-label="ariaLabel"
        class="min-h-12 w-full appearance-none rounded-control border-2 py-2 pr-16 pl-4 text-base disabled:cursor-not-allowed disabled:opacity-90"
        :class="border"
      >
        <option :value="null" disabled>{{ placeholder }}</option>
        <option v-for="option in options" :key="option" :value="option">{{ option }}</option>
      </select>
      <span
        class="pointer-events-none absolute inset-y-0 right-3 flex items-center gap-1.5"
        aria-hidden="true"
      >
        <AppIcon
          v-if="state === 'correct' || state === 'incorrect'"
          :name="state === 'correct' ? 'check' : 'x'"
          :class="state === 'correct' ? 'text-success' : 'text-error'"
        />
        <svg class="size-4 text-ink-muted" viewBox="0 0 20 20" fill="currentColor">
          <path d="M5.5 7.5 10 12l4.5-4.5-1-1L10 10 6.5 6.5z" />
        </svg>
      </span>
    </div>
  </div>
</template>

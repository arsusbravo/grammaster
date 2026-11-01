<script setup lang="ts">
import { computed } from 'vue'
import AppSpinner from './AppSpinner.vue'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'accent' | 'ghost'
    block?: boolean
    type?: 'button' | 'submit'
    disabled?: boolean
    /** Something is happening because of this button: shows a spinner and ignores clicks. */
    loading?: boolean
  }>(),
  { variant: 'primary', type: 'button' },
)

const variants = {
  primary: 'bg-primary text-on-primary hover:bg-primary-hover',
  secondary: 'bg-secondary text-on-secondary hover:bg-secondary-hover',
  accent: 'bg-accent text-on-accent hover:bg-accent-hover',
  ghost: 'border-2 border-border-strong bg-transparent text-ink hover:bg-surface-muted',
}

const classes = computed(() => variants[props.variant])
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :aria-busy="loading || undefined"
    class="inline-flex min-h-12 items-center justify-center gap-2 rounded-control px-6 text-base font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50"
    :class="[classes, block && 'w-full', loading && 'disabled:cursor-progress disabled:opacity-80']"
  >
    <AppSpinner v-if="loading" />
    <slot />
  </button>
</template>

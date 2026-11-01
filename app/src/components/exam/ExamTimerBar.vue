<script setup lang="ts">
import { computed } from 'vue'
import type { Urgency } from '@/stores/exam'

// A slider-style timer: a thumb glides from the full width to zero as the time runs out.
// Decorative (the digital clock next to it is the accessible timer), so it is hidden from assistive tech.
const props = defineProps<{
  /** Seconds left. */
  remaining: number
  /** Seconds the exam lasts in total. */
  total: number
  urgency: Urgency
}>()

const fraction = computed(() =>
  props.total > 0 ? Math.min(1, Math.max(0, props.remaining / props.total)) : 0,
)
const percent = computed(() => `${(fraction.value * 100).toFixed(2)}%`)

const tone = computed(
  () =>
    ({
      normal: { fill: 'bg-secondary', thumb: 'border-secondary' },
      low: { fill: 'bg-warning', thumb: 'border-warning' },
      critical: { fill: 'bg-error', thumb: 'border-error' },
    })[props.urgency],
)
</script>

<template>
  <div
    class="relative mx-[10px] h-2 rounded-full bg-surface-muted"
    aria-hidden="true"
    data-testid="timer-bar"
  >
    <div
      class="h-full rounded-full transition-[width,background-color] duration-1000 ease-linear"
      :class="tone.fill"
      :style="{ width: percent }"
    />
    <div
      class="absolute top-1/2 size-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-[3px] bg-surface shadow-card transition-[left,border-color] duration-1000 ease-linear"
      :class="tone.thumb"
      :style="{ left: percent }"
    />
  </div>
</template>

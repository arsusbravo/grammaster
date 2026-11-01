<script setup lang="ts">
const paths = {
  check: 'M5 13l4 4L19 7',
  x: 'M6 6l12 12M18 6L6 18',
  alert: 'M12 4l9.5 16.5h-19L12 4zM12 10v4.5M12 17.5v.01',
  info: 'M12 3.5a8.5 8.5 0 1 0 0 17 8.5 8.5 0 1 0 0-17zM12 11v5M12 7.8v.01',
  home: 'M4 11l8-7 8 7v8.5a1 1 0 0 1-1 1h-4V15H9v5.5H5a1 1 0 0 1-1-1V11z',
  exam: 'M9 4.5h6V7H9zM7.5 5.5H6a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-13a1 1 0 0 0-1-1h-1.5M9 12h6M9 16h4',
  chart: 'M5 20v-9M12 20V4M19 20v-6',
  user: 'M12 4a4 4 0 1 0 0 8 4 4 0 1 0 0-8zM4.5 20.5c0-3.6 3.4-6 7.5-6s7.5 2.4 7.5 6',
  clock: 'M12 3.5a8.5 8.5 0 1 0 0 17 8.5 8.5 0 1 0 0-17zM12 7.5V12l3 2',
  sun: 'M12 8a4 4 0 1 0 0 8 4 4 0 1 0 0-8zM12 2.5v2M12 19.5v2M4.6 4.6 6 6M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4',
  moon: 'M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z',
  monitor:
    'M4.5 5h15a1 1 0 0 1 1 1v9.5a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zM8.5 20h7M12 16.5V20',
  grip: 'M9 6v.01M9 12v.01M9 18v.01M15 6v.01M15 12v.01M15 18v.01',
} as const

export type IconName = keyof typeof paths

defineProps<{ name: IconName }>()
</script>

<template>
  <svg
    class="size-[1.25em] shrink-0"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2.25"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path :d="paths[name]" />
  </svg>
</template>

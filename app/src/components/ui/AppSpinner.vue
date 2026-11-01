<script setup lang="ts">
// A small spinning ring. Decorative: the button or region around it says what is happening.
</script>

<template>
  <svg
    class="size-[1.1em] shrink-0 motion-safe:animate-spin"
    viewBox="0 0 24 24"
    fill="none"
    aria-hidden="true"
  >
    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25" />
    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
  </svg>
</template>

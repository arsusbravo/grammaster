<script setup lang="ts">
// Transparent logo; navy parts are swapped for a light tint in dark mode.
// Visibility is switched in style.css so it follows the same scheme rules as the colour tokens.
import logoDark from '@/assets/logo-dark.png'
import logoLight from '@/assets/logo.png'

defineProps<{ alt?: string }>()
</script>

<template>
  <span class="inline-flex">
    <img
      :src="logoLight"
      :alt="alt ?? ''"
      class="brand-logo-light h-full w-auto"
      draggable="false"
    />
    <img :src="logoDark" :alt="alt ?? ''" class="brand-logo-dark h-full w-auto" draggable="false" />
  </span>
</template>

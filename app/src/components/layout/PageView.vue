<script setup lang="ts">
import { pageTransition } from '@/lib/pageTransition'

// The current page, animated. Pages are keyed by path: changing only the query (practice filters)
// keeps the page and lets it react itself.
function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'instant' })
}
</script>

<template>
  <!-- `overflow-x: clip` stops a sliding page from causing a horizontal scrollbar (and does not break sticky elements). -->
  <div class="overflow-x-clip">
    <RouterView v-slot="{ Component, route }">
      <Transition :name="pageTransition" mode="out-in" appear @before-enter="scrollToTop">
        <component :is="Component" :key="route.path" />
      </Transition>
    </RouterView>
  </div>
</template>

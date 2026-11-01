<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppShell from '@/components/layout/AppShell.vue'
import PageView from '@/components/layout/PageView.vue'
import TopLoader from '@/components/layout/TopLoader.vue'
import UpdatePrompt from '@/components/layout/UpdatePrompt.vue'
import { useOfflineStore } from '@/stores/offline'

const route = useRoute()
const bare = computed(() => route.meta.layout === 'bare')

// Watch the connection and send answers that were given offline.
onMounted(() => useOfflineStore().start())
</script>

<template>
  <TopLoader />
  <PageView v-if="bare" />
  <AppShell v-else>
    <PageView />
  </AppShell>
  <!-- Not while taking an exam or on the login screen. -->
  <UpdatePrompt v-if="!bare" />
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import AppIcon, { type IconName } from '@/components/ui/AppIcon.vue'
import BrandLogo from '@/components/ui/BrandLogo.vue'
import OfflineNotice from './OfflineNotice.vue'

const { t } = useI18n()

const items: { to: string; label: string; icon: IconName }[] = [
  { to: '/', label: 'nav.practice', icon: 'home' },
  { to: '/examens', label: 'nav.exams', icon: 'exam' },
  { to: '/voortgang', label: 'nav.progress', icon: 'chart' },
  { to: '/account', label: 'nav.account', icon: 'user' },
]
</script>

<template>
  <div class="flex min-h-dvh flex-col">
    <header class="sticky top-0 z-20 border-b border-border bg-surface/95 pt-safe backdrop-blur">
      <div class="mx-auto flex h-16 w-full max-w-5xl items-center gap-3 px-page">
        <RouterLink to="/" class="flex h-10 items-center gap-3 rounded-control">
          <BrandLogo class="h-9" />
          <span class="font-display text-xl font-semibold tracking-tight">{{ t('app.name') }}</span>
        </RouterLink>

        <nav class="ml-auto hidden gap-1 md:flex" :aria-label="t('nav.main')">
          <RouterLink
            v-for="item in items"
            :key="item.to"
            :to="item.to"
            class="flex min-h-11 items-center gap-2 rounded-control px-4 font-semibold text-ink-muted hover:bg-surface-muted"
            active-class="!bg-surface-muted !text-ink"
          >
            <AppIcon :name="item.icon" />
            {{ t(item.label) }}
          </RouterLink>
        </nav>
      </div>
    </header>

    <OfflineNotice />

    <main class="mx-auto w-full max-w-5xl flex-1 px-page py-6 pb-28 md:pb-10">
      <slot />
    </main>

    <!-- Bottom navigation on small screens; padded for the home indicator -->
    <nav
      class="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-surface pb-safe md:hidden"
      :aria-label="t('nav.main')"
    >
      <ul class="mx-auto flex max-w-xl px-page">
        <li v-for="item in items" :key="item.to" class="flex-1">
          <RouterLink
            :to="item.to"
            class="flex min-h-16 flex-col items-center justify-center gap-0.5 text-xs font-semibold text-ink-muted"
            active-class="!text-ink"
          >
            <AppIcon :name="item.icon" class="size-6" />
            {{ t(item.label) }}
          </RouterLink>
        </li>
      </ul>
    </nav>
  </div>
</template>

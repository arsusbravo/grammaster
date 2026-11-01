<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { errorKind } from '@/api/errors'
import { fieldErrors } from '@/api/errors'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import BrandLogo from '@/components/ui/BrandLogo.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import { safeRedirect } from '@/lib/redirect'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const email = ref('')
const password = ref('')
const errors = ref<Record<string, string>>({})
const failure = ref<'network' | 'server' | null>(null)
const busy = ref(false)

const canSubmit = computed(() => email.value.trim() !== '' && password.value !== '' && !busy.value)

async function submit() {
  if (!canSubmit.value) return
  busy.value = true
  errors.value = {}
  failure.value = null

  try {
    await auth.login(email.value.trim(), password.value)
    await router.replace(safeRedirect(route.query.redirect))
  } catch (e) {
    errors.value = fieldErrors(e)
    const kind = errorKind(e)
    if (kind === 'network' || (kind === 'server' && !Object.keys(errors.value).length)) {
      failure.value = kind === 'network' ? 'network' : 'server'
    }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="pt-safe pb-safe">
    <div class="mx-auto flex min-h-dvh max-w-md flex-col justify-center px-6 py-10">
      <BrandLogo :alt="$t('app.name')" class="mx-auto h-24" />
      <h1 class="mt-6 text-center text-3xl">{{ $t('login.title') }}</h1>
      <p class="mt-2 text-center text-ink-muted">{{ $t('login.subtitle') }}</p>

      <form class="mt-8 flex flex-col gap-5" novalidate @submit.prevent="submit">
        <FeedbackBanner v-if="failure" kind="warning">{{ $t(`errors.${failure}`) }}</FeedbackBanner>
        <AppInput
          v-model="email"
          type="email"
          :label="$t('login.email')"
          :error="errors.email"
          autocomplete="email"
        />
        <AppInput
          v-model="password"
          type="password"
          :label="$t('login.password')"
          :error="errors.password"
          autocomplete="current-password"
        />
        <AppButton type="submit" block :disabled="!canSubmit" :loading="busy">{{
          $t('login.submit')
        }}</AppButton>
      </form>

      <p class="mt-6 text-center text-ink-muted">
        {{ $t('login.noAccount') }}
        <RouterLink
          :to="{ name: 'register', query: route.query }"
          class="font-semibold text-secondary underline underline-offset-4"
        >
          {{ $t('login.register') }}
        </RouterLink>
      </p>
    </div>
  </div>
</template>

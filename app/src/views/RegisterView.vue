<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { errorKind, fieldErrors } from '@/api/errors'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import BrandLogo from '@/components/ui/BrandLogo.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import LevelPicker from '@/components/ui/LevelPicker.vue'
import { safeRedirect } from '@/lib/redirect'
import { useAuthStore } from '@/stores/auth'
import { useCatalogStore } from '@/stores/catalog'

const auth = useAuthStore()
const catalog = useCatalogStore()
const route = useRoute()
const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const levelId = ref<number | null>(null)
const errors = ref<Record<string, string>>({})
const failure = ref<'network' | 'server' | null>(null)
const busy = ref(false)

const canSubmit = computed(
  () =>
    name.value.trim() !== '' && email.value.trim() !== '' && password.value !== '' && !busy.value,
)

onMounted(() => {
  // The level picker is optional: registering works even if the levels cannot be loaded.
  catalog.loadLevels().catch(() => undefined)
})

async function submit() {
  if (!canSubmit.value) return
  busy.value = true
  errors.value = {}
  failure.value = null

  try {
    await auth.register({
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      level_id: levelId.value,
    })
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
      <BrandLogo :alt="$t('app.name')" class="mx-auto h-20" />
      <h1 class="mt-6 text-center text-3xl">{{ $t('register.title') }}</h1>
      <p class="mt-2 text-center text-ink-muted">{{ $t('register.subtitle') }}</p>

      <form class="mt-8 flex flex-col gap-5" novalidate @submit.prevent="submit">
        <FeedbackBanner v-if="failure" kind="warning">{{ $t(`errors.${failure}`) }}</FeedbackBanner>
        <AppInput
          v-model="name"
          :label="$t('register.name')"
          :error="errors.name"
          autocomplete="name"
        />
        <AppInput
          v-model="email"
          type="email"
          :label="$t('register.email')"
          :error="errors.email"
          autocomplete="email"
        />
        <AppInput
          v-model="password"
          type="password"
          :label="$t('register.password')"
          :hint="$t('register.passwordHint')"
          :error="errors.password"
          autocomplete="new-password"
        />
        <AppInput
          v-model="passwordConfirmation"
          type="password"
          :label="$t('register.passwordConfirmation')"
          autocomplete="new-password"
        />
        <div v-if="catalog.levels.length" class="flex flex-col gap-1.5">
          <span class="text-base font-semibold">{{ $t('register.level') }}</span>
          <LevelPicker v-model="levelId" :levels="catalog.levels" :label="$t('register.level')" />
          <p v-if="errors.level_id" class="text-sm font-semibold text-error-ink">
            {{ errors.level_id }}
          </p>
        </div>
        <AppButton type="submit" block :disabled="!canSubmit" :loading="busy">{{
          $t('register.submit')
        }}</AppButton>
      </form>

      <p class="mt-6 text-center text-ink-muted">
        {{ $t('register.haveAccount') }}
        <RouterLink
          :to="{ name: 'login', query: route.query }"
          class="font-semibold text-secondary underline underline-offset-4"
        >
          {{ $t('register.login') }}
        </RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { errorKind, fieldErrors } from '@/api/errors'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import LevelPicker from '@/components/ui/LevelPicker.vue'
import ThemePicker from '@/components/ui/ThemePicker.vue'
import { usePwaInstall } from '@/composables/usePwaInstall'
import { useAuthStore } from '@/stores/auth'
import { useCatalogStore } from '@/stores/catalog'
import { useThemeStore } from '@/stores/theme'

const auth = useAuthStore()
const catalog = useCatalogStore()
const theme = useThemeStore()
const router = useRouter()
const pwa = usePwaInstall()

type Failure = 'network' | 'server' | null
const failureOf = (e: unknown, errors: Record<string, string>): Failure => {
  const kind = errorKind(e)
  if (kind === 'network') return 'network'
  return kind === 'server' && Object.keys(errors).length === 0 ? 'server' : null
}

// --- name ---------------------------------------------------------------------------------------
const name = ref(auth.user?.name ?? '')
const nameErrors = ref<Record<string, string>>({})
const nameFailure = ref<Failure>(null)
const nameSaved = ref(false)
const nameBusy = ref(false)

// The user may still be loading when this page opens.
watch(
  () => auth.user?.name,
  (value) => {
    if (value !== undefined) name.value = value
  },
)

const nameChanged = computed(
  () => name.value.trim() !== '' && name.value.trim() !== auth.user?.name,
)

async function saveName() {
  if (!nameChanged.value || nameBusy.value) return
  nameBusy.value = true
  nameErrors.value = {}
  nameFailure.value = null
  nameSaved.value = false

  try {
    await auth.updateName(name.value.trim())
    name.value = auth.user?.name ?? name.value
    nameSaved.value = true
  } catch (e) {
    nameErrors.value = fieldErrors(e)
    nameFailure.value = failureOf(e, nameErrors.value)
  } finally {
    nameBusy.value = false
  }
}

// --- password -----------------------------------------------------------------------------------
const currentPassword = ref('')
const newPassword = ref('')
const confirmation = ref('')
const passwordErrors = ref<Record<string, string>>({})
const passwordFailure = ref<Failure>(null)
const passwordSaved = ref(false)
const passwordBusy = ref(false)

const canChangePassword = computed(
  () => currentPassword.value !== '' && newPassword.value !== '' && !passwordBusy.value,
)

async function savePassword() {
  if (!canChangePassword.value) return
  passwordBusy.value = true
  passwordErrors.value = {}
  passwordFailure.value = null
  passwordSaved.value = false

  try {
    await auth.changePassword({
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmation.value,
    })
    currentPassword.value = ''
    newPassword.value = ''
    confirmation.value = ''
    passwordSaved.value = true
  } catch (e) {
    passwordErrors.value = fieldErrors(e)
    passwordFailure.value = failureOf(e, passwordErrors.value)
  } finally {
    passwordBusy.value = false
  }
}

// --- level --------------------------------------------------------------------------------------
const levelId = ref<number | null>(auth.user?.level_id ?? null)
const levelSaved = ref(false)

onMounted(() => {
  catalog.loadLevels().catch(() => undefined)
})

async function changeLevel(id: number | null) {
  levelId.value = id
  levelSaved.value = false
  try {
    await auth.setLevel(id)
    levelSaved.value = true
  } catch {
    levelId.value = auth.user?.level_id ?? null
  }
}

async function logout() {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="mx-auto max-w-md">
    <h1 class="text-3xl">{{ $t('account.title') }}</h1>

    <!-- Details -->
    <section
      class="mt-6 rounded-card border border-border bg-surface p-5"
      aria-labelledby="profile"
    >
      <h2 id="profile" class="mb-4 text-xl">{{ $t('account.profile') }}</h2>
      <form class="flex flex-col gap-4" novalidate @submit.prevent="saveName">
        <FeedbackBanner v-if="nameFailure" kind="warning">{{
          $t(`errors.${nameFailure}`)
        }}</FeedbackBanner>
        <AppInput
          v-model="name"
          :label="$t('account.name')"
          :error="nameErrors.name"
          autocomplete="name"
          @update:model-value="nameSaved = false"
        />
        <div>
          <p class="text-sm text-ink-muted">{{ $t('account.email') }}</p>
          <p class="text-lg font-semibold">{{ auth.user?.email }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <AppButton type="submit" :disabled="!nameChanged || nameBusy" :loading="nameBusy">{{
            $t('account.nameSave')
          }}</AppButton>
          <p v-if="nameSaved" class="text-sm font-semibold text-success-ink" role="status">
            {{ $t('account.nameSaved') }}
          </p>
        </div>
      </form>
    </section>

    <!-- Level -->
    <section class="mt-8" aria-labelledby="account-level">
      <h2 id="account-level" class="mb-3 text-xl">{{ $t('account.level') }}</h2>
      <LevelPicker
        :model-value="levelId"
        :levels="catalog.levels"
        @update:model-value="changeLevel"
      />
      <p v-if="levelSaved" class="mt-2 text-sm font-semibold text-success-ink" role="status">
        {{ $t('account.levelSaved') }}
      </p>
    </section>

    <!-- Theme -->
    <section class="mt-8" aria-labelledby="account-theme">
      <h2 id="account-theme" class="mb-1 text-xl">{{ $t('account.theme') }}</h2>
      <p class="mb-3 text-sm text-ink-muted">{{ $t('account.themeHint') }}</p>
      <ThemePicker :model-value="theme.theme" @update:model-value="theme.set($event)" />
    </section>

    <!-- Password -->
    <section
      class="mt-8 rounded-card border border-border bg-surface p-5"
      aria-labelledby="account-password"
    >
      <h2 id="account-password" class="mb-4 text-xl">{{ $t('account.password') }}</h2>
      <form class="flex flex-col gap-4" novalidate @submit.prevent="savePassword">
        <FeedbackBanner v-if="passwordFailure" kind="warning">{{
          $t(`errors.${passwordFailure}`)
        }}</FeedbackBanner>
        <AppInput
          v-model="currentPassword"
          type="password"
          :label="$t('account.currentPassword')"
          :error="passwordErrors.current_password"
          autocomplete="current-password"
        />
        <AppInput
          v-model="newPassword"
          type="password"
          :label="$t('account.newPassword')"
          :hint="$t('account.newPasswordHint')"
          :error="passwordErrors.password"
          autocomplete="new-password"
        />
        <AppInput
          v-model="confirmation"
          type="password"
          :label="$t('account.confirmPassword')"
          autocomplete="new-password"
        />
        <AppButton type="submit" :disabled="!canChangePassword" :loading="passwordBusy">{{
          $t('account.passwordSave')
        }}</AppButton>
        <p v-if="passwordSaved" class="text-sm font-semibold text-success-ink" role="status">
          {{ $t('account.passwordSaved') }}
        </p>
      </form>
    </section>

    <!-- Install the app (only where it makes sense) -->
    <section
      v-if="pwa.canInstall.value || pwa.showIosHint.value || pwa.standalone.value"
      class="mt-8 rounded-card border border-border bg-surface p-5"
      aria-labelledby="account-install"
    >
      <h2 id="account-install" class="mb-2 text-xl">{{ $t('account.install') }}</h2>
      <p v-if="pwa.standalone.value" class="text-ink-muted">{{ $t('account.installed') }}</p>
      <template v-else>
        <p class="text-ink-muted">{{ $t('account.installText') }}</p>
        <AppButton v-if="pwa.canInstall.value" class="mt-4" @click="pwa.install()">
          {{ $t('account.installButton') }}
        </AppButton>
        <p v-else class="mt-3 font-semibold">{{ $t('account.installIos') }}</p>
      </template>
    </section>

    <AppButton variant="ghost" class="mt-10" block @click="logout">{{
      $t('account.logout')
    }}</AppButton>
  </div>
</template>

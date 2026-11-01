<script setup lang="ts">
// Dev-only design reference. Copy here is intentionally English; the sample content shown inside
// the question mock-ups is Dutch, as students will see it.
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { applyTheme, readTheme } from '@/lib/theme'
import themeCss from '@/styles/theme.css?raw'
import { contrastRatio, parseTokens } from '@/design/contrast'
import AppButton from '@/components/ui/AppButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import AppInput from '@/components/ui/AppInput.vue'
import BrandLogo from '@/components/ui/BrandLogo.vue'
import FeedbackBanner from '@/components/ui/FeedbackBanner.vue'
import QuestionRenderer from '@/components/question/QuestionRenderer.vue'
import * as fx from '@/design/fixtures'
import type { Answer, AnswerResult, Question } from '@/types/api'

type Choice = 'system' | 'light' | 'dark'
const forced = document.documentElement.dataset.theme
const choice = ref<Choice>(forced === 'light' || forced === 'dark' ? forced : 'system')
const systemDark = ref(window.matchMedia('(prefers-color-scheme: dark)').matches)
const media = window.matchMedia('(prefers-color-scheme: dark)')
const onMedia = (e: MediaQueryListEvent) => (systemDark.value = e.matches)

onMounted(() => media.addEventListener('change', onMedia))
onUnmounted(() => {
  media.removeEventListener('change', onMedia)
  applyTheme(readTheme())
})

function setChoice(value: Choice) {
  choice.value = value
  if (value === 'system') delete document.documentElement.dataset.theme
  else document.documentElement.dataset.theme = value
}

const scheme = computed<'light' | 'dark'>(() =>
  choice.value === 'system' ? (systemDark.value ? 'dark' : 'light') : choice.value,
)

const tokens = parseTokens(themeCss)
const hex = (name: string) => tokens[name][scheme.value]

const swatches = [
  {
    title: 'Brand',
    items: [
      ['primary', 'on-primary'],
      ['secondary', 'on-secondary'],
      ['accent', 'on-accent'],
    ],
  },
  {
    title: 'Surfaces',
    items: [
      ['canvas', 'ink'],
      ['surface', 'ink'],
      ['surface-muted', 'ink'],
    ],
  },
  {
    title: 'Feedback',
    items: [
      ['success-soft', 'success-ink'],
      ['error-soft', 'error-ink'],
      ['warning-soft', 'warning-ink'],
    ],
  },
  {
    title: 'Text colours',
    items: [
      ['surface', 'ink'],
      ['surface', 'ink-muted'],
      ['surface', 'secondary'],
      ['surface', 'accent-ink'],
    ],
  },
]

const email = ref('')

interface Demo {
  title: string
  question: Question
  answer: ReturnType<typeof ref<Answer | null>>
  states: { label: string; answer: Answer; result: AnswerResult }[]
}

const demos: Demo[] = [
  {
    title: 'multiple_choice (single)',
    question: fx.multipleChoice,
    answer: ref<Answer | null>(null),
    states: [
      {
        label: 'Correct',
        answer: { selected: [1] },
        result: fx.result(1, true, { selected: [1] }),
      },
      {
        label: 'Incorrect (correct one is shown)',
        answer: { selected: [0] },
        result: fx.result(1, false, { selected: [1] }),
      },
    ],
  },
  {
    title: 'multiple_choice (several answers)',
    question: fx.multipleChoiceMulti,
    answer: ref<Answer | null>(null),
    states: [
      {
        label: 'Partly right',
        answer: { selected: [0, 2] },
        result: fx.result(2, false, { selected: [0, 1] }),
      },
    ],
  },
  {
    title: 'fill_blank',
    question: fx.fillBlank,
    answer: ref<Answer | null>(null),
    states: [
      {
        label: 'Correct',
        answer: { blanks: ['Schrijf', 'schrijft'] },
        result: fx.result(3, true, { blanks: ['schrijf', 'schrijft'] }, [true, true]),
      },
      {
        label: 'One wrong',
        answer: { blanks: ['Schrijf', 'schrijf'] },
        result: fx.result(3, false, { blanks: ['schrijf', 'schrijft'] }, [true, false]),
      },
    ],
  },
  {
    title: 'order_words (drag, or arrow keys on a focused word)',
    question: fx.orderWords,
    answer: ref<Answer | null>(null),
    states: [
      {
        label: 'Correct',
        answer: { order: ['Gisteren', 'ging', 'ik', 'zwemmen'] },
        result: fx.result(4, true, { order: ['Gisteren', 'ging', 'ik', 'zwemmen'] }),
      },
      {
        label: 'Incorrect',
        answer: { order: ['ik', 'ging', 'Gisteren', 'zwemmen'] },
        result: fx.result(4, false, {
          order: ['Gisteren', 'ging', 'ik', 'zwemmen'],
          alternative_orders: [['Ik', 'ging', 'gisteren', 'zwemmen']],
        }),
      },
    ],
  },
  {
    title: 'mark_error',
    question: fx.markError,
    answer: ref<Answer | null>(null),
    states: [
      {
        label: 'Correct',
        answer: { index: 1 },
        result: fx.result(5, true, { index: 1, correction: 'fietsen' }),
      },
      {
        label: 'Incorrect',
        answer: { index: 0 },
        result: fx.result(5, false, { index: 1, correction: 'fietsen' }),
      },
    ],
  },
  {
    title: 'parse_sentence',
    question: fx.parseSentence,
    answer: ref<Answer | null>(null),
    states: [
      {
        label: 'Correct',
        answer: { roles: ['onderwerp', 'persoonsvorm', 'lijdend voorwerp'] },
        result: fx.result(6, true, { roles: ['onderwerp', 'persoonsvorm', 'lijdend voorwerp'] }, [
          true,
          true,
          true,
        ]),
      },
      {
        label: 'One wrong',
        answer: { roles: ['onderwerp', 'persoonsvorm', 'onderwerp'] },
        result: fx.result(6, false, { roles: ['onderwerp', 'persoonsvorm', 'lijdend voorwerp'] }, [
          true,
          true,
          false,
        ]),
      },
    ],
  },
]
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-12 pb-16">
    <header class="flex flex-wrap items-center gap-4">
      <BrandLogo class="h-14" alt="Grammatica" />
      <div>
        <h1 class="text-3xl">Design reference</h1>
        <p class="text-ink-muted">Dev only. Scheme: {{ scheme }}</p>
      </div>
      <div class="ml-auto flex gap-2" role="group" aria-label="Colour scheme">
        <AppButton
          v-for="c in ['system', 'light', 'dark'] as const"
          :key="c"
          :variant="choice === c ? 'primary' : 'ghost'"
          @click="setChoice(c)"
        >
          {{ c }}
        </AppButton>
      </div>
    </header>

    <!-- Colours -->
    <section aria-labelledby="colours" class="space-y-6">
      <h2 id="colours" class="text-2xl">Colours</h2>
      <p class="text-ink-muted">
        From the logo: navy <code>#0b2240</code> and gold <code>#b5853a</code>. Raw gold on white is
        only 3.2:1, so gold is used for fills (with navy text) and text uses the darker
        <code>accent-ink</code>. Ratios below are live (WCAG AA needs 4.5:1 for text).
      </p>
      <div v-for="group in swatches" :key="group.title">
        <h3 class="mb-2 text-lg">{{ group.title }}</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
          <div
            v-for="[bg, fg] in group.items"
            :key="bg + fg"
            class="rounded-card border border-border p-4"
            :style="{ background: `var(--${bg})`, color: `var(--${fg})` }"
          >
            <p class="text-2xl font-bold">Aa</p>
            <p class="mt-1 text-sm font-semibold">{{ bg }}</p>
            <p class="text-xs">
              {{ hex(bg) }} · {{ fg }} {{ contrastRatio(hex(bg), hex(fg)).toFixed(1) }}:1
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Typography -->
    <section aria-labelledby="type" class="space-y-4">
      <h2 id="type" class="text-2xl">Typography</h2>
      <p class="text-ink-muted">
        Headings: Fraunces (soft serif, echoes the scholarly emblem). Body: Atkinson Hyperlegible
        Next (designed for legibility). Both are self-hosted.
      </p>
      <h1 class="text-4xl">Heading 1: Werkwoordspelling</h1>
      <h2 class="text-3xl">Heading 2: Lidwoorden de/het</h2>
      <h3 class="text-xl">Heading 3: Samengestelde woorden</h3>
      <p class="explanation">
        Uitlegtekst: bij een werkwoord in de tegenwoordige tijd schrijf je de stam plus
        <em>t</em> als het onderwerp <em>hij</em>, <em>zij</em> of <em>het</em> is. Bij
        <em>ik</em> schrijf je alleen de stam. Lange uitleg moet prettig leesbaar blijven, ook op
        een telefoon.
      </p>
      <p class="text-sm text-ink-muted">Kleine hulptekst: minimaal 14px, voldoende contrast.</p>
    </section>

    <!-- Buttons -->
    <section aria-labelledby="buttons" class="space-y-4">
      <h2 id="buttons" class="text-2xl">Buttons</h2>
      <p class="text-ink-muted">Minimum 48px high (large tap target), rounded.</p>
      <div class="flex flex-wrap gap-3">
        <AppButton>Primary</AppButton>
        <AppButton variant="secondary">Secondary</AppButton>
        <AppButton variant="accent">Accent</AppButton>
        <AppButton variant="ghost">Ghost</AppButton>
        <AppButton disabled>Disabled</AppButton>
      </div>
      <AppButton block>Full width (mobile)</AppButton>
    </section>

    <!-- Inputs -->
    <section aria-labelledby="inputs" class="space-y-4">
      <h2 id="inputs" class="text-2xl">Inputs</h2>
      <div class="grid gap-5 sm:grid-cols-2">
        <AppInput v-model="email" label="E-mailadres" hint="We sturen nooit spam." />
        <AppInput model-value="fout@" label="E-mailadres" error="Vul een geldig e-mailadres in." />
        <AppInput model-value="Geen invoer mogelijk" label="Uitgeschakeld" disabled />
      </div>
    </section>

    <!-- Question states: the real components with sample data -->
    <section aria-labelledby="questions" class="space-y-12">
      <div>
        <h2 id="questions" class="text-2xl">Question component states</h2>
        <p class="mt-1 text-ink-muted">
          The real question components (same contract: <code>v-model</code>, <code>disabled</code>,
          <code>result</code>) with sample data. Try the interactive ones. Right/wrong always
          combines colour, an icon and a text label.
        </p>
      </div>

      <div v-for="demo in demos" :key="demo.title" class="space-y-6">
        <h3 class="text-xl">{{ demo.title }}</h3>

        <div class="space-y-2">
          <p class="text-sm font-semibold text-ink-muted">Interactive</p>
          <QuestionRenderer v-model="demo.answer.value" :question="demo.question" />
        </div>

        <div v-for="state in demo.states" :key="state.label" class="space-y-2">
          <p class="text-sm font-semibold text-ink-muted">{{ state.label }}</p>
          <QuestionRenderer
            :model-value="state.answer"
            :question="demo.question"
            disabled
            :result="state.result"
          />
        </div>
      </div>

      <div class="space-y-3">
        <h3 class="text-xl">Feedback banners</h3>
        <FeedbackBanner kind="correct"
          >Helemaal goed. Je gebruikte de juiste werkwoordsvorm.</FeedbackBanner
        >
        <FeedbackBanner kind="incorrect"
          >Dat was niet juist. Lees de uitleg hieronder.</FeedbackBanner
        >
        <FeedbackBanner kind="warning">Je hebt nog 2 minuten voor dit examen.</FeedbackBanner>
        <FeedbackBanner kind="info">Tip: zet de zin eerst in de verleden tijd.</FeedbackBanner>
      </div>
    </section>

    <section aria-labelledby="shell" class="space-y-3">
      <h2 id="shell" class="text-2xl">Layout notes</h2>
      <ul class="list-disc space-y-1 pl-6 text-ink-muted">
        <li>Designed for 390px wide first. Bottom navigation below 768px, top navigation above.</li>
        <li>Header and bottom bar respect <code>env(safe-area-inset-*)</code>.</li>
        <li>Visible focus ring on every control, reduced-motion respected.</li>
        <li class="flex items-center gap-2">
          <AppIcon name="clock" /> Resize the window to see the navigation switch.
        </li>
      </ul>
    </section>
  </div>
</template>

import { createI18n } from 'vue-i18n'
import nl from './locales/nl.json'

// UI strings live in locale files so another language can be added later.
export const i18n = createI18n({
  legacy: false,
  locale: 'nl',
  fallbackLocale: 'nl',
  messages: { nl },
})

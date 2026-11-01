import { readFileSync } from 'node:fs'
import { fileURLToPath, URL } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import { defineConfig } from 'vitest/config'

// The installed app takes its name from the language file, so a rename there is picked up automatically.
const nl = JSON.parse(readFileSync(new URL('./src/locales/nl.json', import.meta.url), 'utf8'))
const appName: string = nl.app.name

// The app lives under /student/ on the same address as the API and the admin.
// For a Capacitor build use VITE_BASE=/ (the app is then served from the root of the webview).
const base = process.env.VITE_BASE ?? '/student/'

export default defineConfig({
  base,
  plugins: [
    {
      // index.html uses %APP_NAME% (title, home screen name): one place to rename the app.
      name: 'app-name',
      transformIndexHtml: (html: string) => html.replaceAll('%APP_NAME%', appName),
    },
    vue(),
    tailwindcss(),
    VitePWA({
      // Capacitor wraps the app itself and must not register a service worker.
      disable: process.env.VITE_PWA === 'false',
      // A new version waits until the student taps "Vernieuwen": never reload in the middle of an exam.
      registerType: 'prompt',
      injectRegister: false,
      includeAssets: [
        'favicon-16.png',
        'favicon-32.png',
        'apple-touch-icon.png',
        'logo.png',
        'logo-dark.png',
      ],
      manifest: {
        id: base,
        name: appName,
        short_name: appName,
        description: nl.app.tagline,
        lang: 'nl',
        dir: 'ltr',
        start_url: base,
        scope: base,
        display: 'standalone',
        orientation: 'portrait',
        categories: ['education'],
        theme_color: '#0b2240',
        background_color: '#f7f9fc',
        icons: [
          ...[48, 72, 96, 128, 144, 152, 192, 256, 384, 512].map((size) => ({
            src: `icons/icon-${size}.png`,
            sizes: `${size}x${size}`,
            type: 'image/png',
            purpose: 'any',
          })),
          {
            src: 'icons/maskable-192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'maskable',
          },
          {
            src: 'icons/maskable-512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable',
          },
        ],
        shortcuts: [
          { name: 'Oefenen', url: base, icons: [{ src: 'icons/icon-96.png', sizes: '96x96' }] },
          {
            name: 'Examens',
            url: `${base}examens`,
            icons: [{ src: 'icons/icon-96.png', sizes: '96x96' }],
          },
          {
            name: 'Voortgang',
            url: `${base}voortgang`,
            icons: [{ src: 'icons/icon-96.png', sizes: '96x96' }],
          },
        ],
      },
      workbox: {
        // Offline, any page of the app opens from the cached shell (the app itself handles the routes).
        navigateFallback: `${base}index.html`,
        navigateFallbackDenylist: [/^\/api\//, /^\/admin/, /^\/livewire/],
        globPatterns: ['**/*.{js,css,html,woff2,svg,png,webmanifest}'],
        // The 1 MB original logo and the 2732 px splash images are not needed to run the app.
        globIgnores: ['**/logo-original.png', '**/splash/**'],
        cleanupOutdatedCaches: true,
        runtimeCaching: [
          {
            // Levels and topics are public and the same for everybody: safe to share between users and to show offline.
            // Personal data (progress, exams, the account) is NOT cached here; see lib/offline for what the app keeps itself.
            urlPattern: ({ url }) =>
              url.pathname === '/api/v1/levels' || url.pathname === '/api/v1/topics',
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'api-catalog',
              expiration: { maxEntries: 20, maxAgeSeconds: 60 * 60 * 24 * 14 },
              cacheableResponse: { statuses: [200] },
            },
          },
        ],
      },
    }),
  ],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    host: '0.0.0.0',
    port: 5174,
    strictPort: true,
    // Bind mounts on Docker Desktop do not reliably emit file events.
    watch: { usePolling: true },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test/setup.ts'],
  },
})

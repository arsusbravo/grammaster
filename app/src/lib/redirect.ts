/** Only follow redirects inside the app ("/oefenen?level=vmbo"), never to another site. */
export function safeRedirect(value: unknown, fallback = '/'): string {
  return typeof value === 'string' && value.startsWith('/') && !value.startsWith('//')
    ? value
    : fallback
}

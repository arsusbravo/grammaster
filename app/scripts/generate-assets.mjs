// Derives all brand image assets from the original logo (src/assets/logo-original.png, never modified).
// Run with: npm run assets
import sharp from 'sharp'
import { mkdir } from 'node:fs/promises'

const SOURCE = 'src/assets/logo-original.png'
const NAVY_SURFACE = '#0b2240'
const INKS = { navy: [11, 34, 64], gold: [181, 133, 58] }
const LIGHT_INK = [232, 238, 247] // navy replacement on dark backgrounds

const { data, info } = await sharp(SOURCE).removeAlpha().raw().toBuffer({ resolveWithObject: true })
const { width, height } = info

// Background colour = average of the four corners (the source has a near-white fill).
const corner = (x, y) => [0, 1, 2].map((c) => data[(y * width + x) * 3 + c])
const corners = [
  corner(2, 2),
  corner(width - 3, 2),
  corner(2, height - 3),
  corner(width - 3, height - 3),
]
const bg = [0, 1, 2].map((c) => corners.reduce((s, v) => s + v[c], 0) / 4)

// Each pixel is bg*(1-a) + ink*a with one of two inks; pick the ink that explains it best.
function solve(px) {
  let best = null
  for (const [name, ink] of Object.entries(INKS)) {
    const alphas = [0, 1, 2].map((c) => (bg[c] - px[c]) / (bg[c] - ink[c]))
    const a = Math.min(1, Math.max(0, alphas.reduce((s, v) => s + v, 0) / 3))
    const err = [0, 1, 2].reduce((s, c) => s + Math.abs(bg[c] * (1 - a) + ink[c] * a - px[c]), 0)
    if (!best || err < best.err) best = { name, a, err }
  }
  return best
}

function render(navyInk) {
  const out = Buffer.alloc(width * height * 4)
  for (let i = 0; i < width * height; i++) {
    const px = [data[i * 3], data[i * 3 + 1], data[i * 3 + 2]]
    const { name, a } = solve(px)
    const ink = name === 'navy' ? navyInk : INKS.gold
    out.set([...ink, a < 0.04 ? 0 : Math.round(a * 255)], i * 4)
  }
  return sharp(out, { raw: { width, height, channels: 4 } })
}

async function trimmed(navyInk) {
  const buf = await render(navyInk).png().toBuffer()
  return sharp(await sharp(buf).trim({ threshold: 1 }).png().toBuffer())
}

const light = await trimmed(INKS.navy)
const dark = await trimmed(LIGHT_INK)
await light.clone().png().toFile('src/assets/logo.png')
await dark.clone().png().toFile('src/assets/logo-dark.png')
await light.clone().png().toFile('public/logo.png')
await dark.clone().png().toFile('public/logo-dark.png')

const lightBuf = await light.clone().png().toBuffer()
const darkBuf = await dark.clone().png().toBuffer()

// Logo centred on a solid square; `inner` is the fraction of the square the logo may occupy.
async function square(size, inner, background, logoBuf) {
  const logo = await sharp(logoBuf)
    .resize({ width: Math.round(size * inner), height: Math.round(size * inner), fit: 'inside' })
    .toBuffer()
  return sharp({ create: { width: size, height: size, channels: 4, background } })
    .composite([{ input: logo, gravity: 'center' }])
    .png()
}

await mkdir('public/icons', { recursive: true })
await mkdir('public/splash', { recursive: true })

// Browser favicons and iOS touch icon (navy tile keeps the pale cap visible on any tab colour).
for (const size of [16, 32]) {
  await (await square(size, 0.82, NAVY_SURFACE, darkBuf)).toFile(`public/favicon-${size}.png`)
}
await (await square(180, 0.74, NAVY_SURFACE, darkBuf)).toFile('public/apple-touch-icon.png')

// PWA icons: "any" keeps generous padding, "maskable" stays inside the 80% safe zone.
for (const size of [48, 72, 96, 128, 144, 152, 192, 256, 384, 512]) {
  await (await square(size, 0.74, NAVY_SURFACE, darkBuf)).toFile(`public/icons/icon-${size}.png`)
}
for (const size of [192, 512]) {
  await (
    await square(size, 0.56, NAVY_SURFACE, darkBuf)
  ).toFile(`public/icons/maskable-${size}.png`)
}

// Splash screens (Capacitor / iOS launch image size), light and dark.
await (await square(2732, 0.4, '#f7f9fc', lightBuf)).toFile('public/splash/splash-light.png')
await (await square(2732, 0.4, '#081426', darkBuf)).toFile('public/splash/splash-dark.png')

console.log(
  'background',
  bg.map(Math.round),
  'logo size',
  (await light.metadata()).width,
  'x',
  (await light.metadata()).height,
)

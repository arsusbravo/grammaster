<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ config('app.name') }} · {{ __('landing.title') }}</title>
    <meta name="description" content="{{ __('landing.description') }}">
    <meta name="theme-color" content="#f7f9fc" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#081426" media="(prefers-color-scheme: dark)">
    <meta property="og:title" content="{{ config('app.name') }} · {{ __('landing.title') }}">
    <meta property="og:description" content="{{ __('landing.description') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="/brand/favicon-32.png">
    <link rel="preload" href="/brand/fonts/fraunces-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/brand/fonts/atkinson-hyperlegible-next-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <style>
        /* Same fonts and colours as the student app (app/src/styles/theme.css), self-hosted, no CDN. */
        @font-face {
            font-family: 'Fraunces Variable';
            font-style: normal;
            font-display: swap;
            font-weight: 100 900;
            src: url('/brand/fonts/fraunces-latin-wght-normal.woff2') format('woff2-variations');
        }
        @font-face {
            font-family: 'Atkinson Hyperlegible Next Variable';
            font-style: normal;
            font-display: swap;
            font-weight: 200 800;
            src: url('/brand/fonts/atkinson-hyperlegible-next-latin-wght-normal.woff2') format('woff2-variations');
        }

        :root {
            color-scheme: light dark;
            --canvas: light-dark(#f7f9fc, #081426);
            --surface: light-dark(#ffffff, #0f2038);
            --surface-muted: light-dark(#eef2f8, #162a48);
            --border: light-dark(#cbd5e3, #2d4468);
            --ink: light-dark(#0b2240, #e8eef7);
            --ink-muted: light-dark(#4a5b74, #a9b8ce);
            --primary: light-dark(#0b2240, #dce6f5);
            --primary-hover: light-dark(#173a68, #ffffff);
            --on-primary: light-dark(#ffffff, #0b2240);
            --secondary: light-dark(#1f4e8c, #8db4ea);
            --accent: light-dark(#b5853a, #c9974a);
            --accent-hover: light-dark(#c69a52, #dba95c);
            --on-accent: light-dark(#0b2240, #081426);
            --accent-ink: light-dark(#7d5710, #dcae62);
            --focus: light-dark(#1f4e8c, #8db4ea);
            --radius-control: 0.875rem;
            --radius-card: 1.25rem;
            --shadow-card: 0 1px 2px rgb(11 34 64 / 0.06), 0 4px 16px rgb(11 34 64 / 0.06);
            --font-sans: 'Atkinson Hyperlegible Next Variable', ui-sans-serif, system-ui, sans-serif;
            --font-display: 'Fraunces Variable', ui-serif, Georgia, serif;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }
        body {
            margin: 0;
            background: var(--canvas);
            color: var(--ink);
            font-family: var(--font-sans);
            font-size: 1.0625rem;
            line-height: 1.6;
        }
        h1, h2, h3 { font-family: var(--font-display); line-height: 1.15; margin: 0; letter-spacing: -0.01em; }
        p { margin: 0; }
        a { color: var(--secondary); }
        :focus-visible { outline: 3px solid var(--focus); outline-offset: 3px; border-radius: 6px; }

        .wrap {
            width: 100%;
            max-width: 68rem;
            margin: 0 auto;
            padding-left: max(1rem, env(safe-area-inset-left));
            padding-right: max(1rem, env(safe-area-inset-right));
        }

        .skip {
            position: absolute; left: 1rem; top: -4rem; z-index: 10;
            background: var(--primary); color: var(--on-primary);
            padding: .75rem 1rem; border-radius: var(--radius-control); text-decoration: none;
        }
        .skip:focus { top: calc(.5rem + env(safe-area-inset-top)); }

        header { padding-top: env(safe-area-inset-top); }
        .bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 4.5rem; }
        .logo img { display: block; height: 2.5rem; width: auto; }
        .logo { display: inline-flex; align-items: center; min-height: 48px; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 48px; padding: .625rem 1.5rem;
            border-radius: var(--radius-control);
            font: inherit; font-weight: 700; text-decoration: none;
            border: 2px solid transparent;
            transition: background-color .15s, border-color .15s;
        }
        .btn-primary { background: var(--accent); color: var(--on-accent); }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-ghost { background: transparent; color: var(--ink); border-color: var(--border); }
        .btn-ghost:hover { background: var(--surface-muted); }
        .btn-solid { background: var(--primary); color: var(--on-primary); }
        .btn-solid:hover { background: var(--primary-hover); }

        .hero { padding-top: 3rem; padding-bottom: 4rem; } /* only vertical: .wrap owns the side padding */
        .eyebrow { color: var(--accent-ink); font-weight: 700; letter-spacing: .06em; text-transform: uppercase; font-size: .875rem; }
        .hero h1 { font-size: clamp(2.25rem, 7vw, 4rem); margin-top: .75rem; max-width: 18ch; }
        .hero .lead { margin-top: 1.25rem; max-width: 40rem; color: var(--ink-muted); font-size: 1.25rem; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
        .levels { margin-top: 1.75rem; color: var(--accent-ink); font-weight: 700; }

        section { padding: 3.5rem 0; }
        section.alt { background: var(--surface-muted); }
        h2 { font-size: clamp(1.75rem, 4.5vw, 2.5rem); }

        .grid { display: grid; gap: 1.25rem; margin-top: 2rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-top: 4px solid var(--accent);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            padding: 1.5rem;
        }
        .card h3 { font-size: 1.375rem; }
        .card p { margin-top: .625rem; color: var(--ink-muted); }

        .steps { list-style: none; margin: 2rem 0 0; padding: 0; display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); counter-reset: step; }
        .steps li { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-card); padding: 1.5rem; }
        .steps li::before {
            counter-increment: step; content: counter(step);
            display: inline-grid; place-items: center; width: 2.5rem; height: 2.5rem; margin-bottom: .75rem;
            border-radius: 999px; background: var(--accent); color: var(--on-accent);
            font-family: var(--font-display); font-weight: 700; font-size: 1.25rem;
        }
        .steps h3 { font-size: 1.25rem; }
        .steps p { margin-top: .5rem; color: var(--ink-muted); }

        .cta { text-align: center; }
        .cta p { margin: 1rem auto 0; max-width: 34rem; color: var(--ink-muted); font-size: 1.125rem; }
        .cta .actions { justify-content: center; }

        footer { border-top: 1px solid var(--border); padding: 1.5rem 0 calc(1.5rem + env(safe-area-inset-bottom)); color: var(--ink-muted); font-size: .9375rem; }
        .foot { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .5rem 1.5rem; }
        footer a { display: inline-flex; align-items: center; min-height: 48px; }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .btn { transition: none; }
        }
    </style>
</head>
<body>
    <a class="skip" href="#inhoud">{{ __('landing.skip') }}</a>

    <header>
        <div class="wrap bar">
            <a class="logo" href="/" aria-label="{{ config('app.name') }}">
                <picture>
                    <source srcset="/brand/logo-dark.png" media="(prefers-color-scheme: dark)">
                    <img src="/brand/logo.png" alt="{{ config('app.name') }}">
                </picture>
            </a>
            <a class="btn btn-ghost" href="/student/login">{{ __('landing.login') }}</a>
        </div>
    </header>

    <main id="inhoud">
        <div class="wrap hero">
            <p class="eyebrow">{{ __('landing.hero.eyebrow') }}</p>
            <h1>{{ __('landing.hero.title') }}</h1>
            <p class="lead">{{ __('landing.hero.text') }}</p>
            <div class="actions">
                <a class="btn btn-primary" href="/student/">{{ __('landing.start') }}</a>
                <a class="btn btn-ghost" href="/student/registreren">{{ __('landing.register') }}</a>
            </div>
            <p class="levels">{{ __('landing.hero.levels') }}</p>
        </div>

        <section class="alt" aria-labelledby="features-title">
            <div class="wrap">
                <h2 id="features-title">{{ __('landing.features.title') }}</h2>
                <div class="grid">
                    @foreach (__('landing.features.items') as $item)
                        <article class="card">
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section aria-labelledby="steps-title">
            <div class="wrap">
                <h2 id="steps-title">{{ __('landing.steps.title') }}</h2>
                <ol class="steps">
                    @foreach (__('landing.steps.items') as $item)
                        <li>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['text'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="alt cta" aria-labelledby="cta-title">
            <div class="wrap">
                <h2 id="cta-title">{{ __('landing.cta.title') }}</h2>
                <p>{{ __('landing.cta.text') }}</p>
                <div class="actions">
                    <a class="btn btn-primary" href="/student/registreren">{{ __('landing.register') }}</a>
                    <a class="btn btn-solid" href="/student/login">{{ __('landing.login') }}</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="wrap foot">
            <span>© {{ date('Y') }} {{ config('app.name') }}. {{ __('landing.footer.rights') }}</span>
            <a href="/admin">{{ __('landing.footer.admin') }}</a>
        </div>
    </footer>
</body>
</html>

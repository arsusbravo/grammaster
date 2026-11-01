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


        /* Try-out */
        :root {
            --border-strong: light-dark(#6b7c96, #7f93b3);
            --success: light-dark(#17663a, #5fd08a);
            --success-soft: light-dark(#e4f4ea, #0f2e1d);
            --success-ink: light-dark(#0f4a2a, #9be5b8);
            --error: light-dark(#b3261e, #ff8a80);
            --error-soft: light-dark(#fcebe9, #3b1513);
            --error-ink: light-dark(#7a1812, #ffb4ab);
        }
        .try-head p { margin-top: .75rem; max-width: 40rem; color: var(--ink-muted); font-size: 1.125rem; }
        .tabs-label { margin-top: 1.75rem; font-weight: 700; }
        .tabs { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
        .tab {
            min-height: 48px; padding: .5rem 1.25rem; cursor: pointer;
            border-radius: 999px; border: 2px solid var(--border); background: var(--surface); color: var(--ink);
            font: inherit; font-weight: 700;
        }
        .tab[aria-selected="true"] { background: var(--primary); color: var(--on-primary); border-color: var(--primary); }
        .tab:not([aria-selected="true"]):hover { background: var(--surface-muted); }
        .panel { margin-top: 1.5rem; }
        .panel[hidden] { display: none; }
        .qlist { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(19rem, 1fr)); align-items: start; }
        .q { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-card); box-shadow: var(--shadow-card); padding: 1.5rem; }
        .q .meta { color: var(--accent-ink); font-weight: 700; font-size: .875rem; letter-spacing: .04em; text-transform: uppercase; }
        .q h3 { margin-top: .5rem; font-size: 1.3125rem; }
        .q .hint { margin-top: .5rem; color: var(--ink-muted); font-size: .9375rem; }
        .q fieldset { border: 0; padding: 0; margin: 1rem 0 0; min-width: 0; }
        .opt {
            display: flex; align-items: center; gap: .75rem; min-height: 48px; padding: .5rem .875rem; margin-top: .5rem;
            border: 2px solid var(--border-strong); border-radius: var(--radius-control); background: var(--surface); cursor: pointer;
        }
        .opt:has(input:checked) { border-color: var(--secondary); background: var(--surface-muted); }
        .opt input { width: 1.25rem; height: 1.25rem; accent-color: var(--secondary); flex: none; }
        .sentence { margin-top: 1rem; line-height: 2.6; }
        .blank {
            display: inline-block; width: 9rem; max-width: 100%; min-height: 44px; padding: .25rem .75rem; margin: 0 .25rem;
            border: 2px solid var(--border-strong); border-radius: var(--radius-control);
            background: var(--surface); color: var(--ink); font: inherit;
        }
        .tokens { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
        .token {
            min-height: 48px; padding: .25rem 1rem; cursor: pointer;
            border: 2px solid var(--border-strong); border-radius: var(--radius-control); background: var(--surface); color: var(--ink); font: inherit;
        }
        .token[aria-pressed="true"] { border-color: var(--secondary); background: var(--surface-muted); }
        .is-right { border-color: var(--success) !important; background: var(--success-soft) !important; }
        .is-wrong { border-color: var(--error) !important; background: var(--error-soft) !important; }
        .q .btn { margin-top: 1.25rem; }
        .q .btn[disabled] { opacity: .6; cursor: default; }
        .fb { margin-top: 1rem; padding: .875rem 1rem; border-radius: var(--radius-control); border: 2px solid; }
        .fb[hidden] { display: none; }
        .fb.ok { background: var(--success-soft); color: var(--success-ink); border-color: var(--success); }
        .fb.bad { background: var(--error-soft); color: var(--error-ink); border-color: var(--error); }
        .fb strong { display: block; }
        .fb p + p { margin-top: .5rem; }
        .more { margin-top: 1.5rem; padding: 1.5rem; text-align: center; background: var(--surface); border: 2px solid var(--accent); border-radius: var(--radius-card); }
        .more[hidden] { display: none; }
        .more h3 { font-size: 1.5rem; }
        .more p { margin: .625rem auto 0; max-width: 34rem; color: var(--ink-muted); }
        .more .score { color: var(--ink); font-weight: 700; }
        .more .actions { justify-content: center; }
        .textlink { display: inline-flex; align-items: center; min-height: 48px; font-weight: 700; margin-top: .5rem; }

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
            @if (count($samples) > 0)
                <a class="textlink" href="#proberen">{{ __('landing.try.link') }} &darr;</a>
            @endif
        </div>

        @if (count($samples) > 0)
        <section class="alt" id="proberen" aria-labelledby="try-title">
            <div class="wrap" id="try">
                <div class="try-head">
                    <h2 id="try-title">{{ __('landing.try.title') }}</h2>
                    <p>{{ __('landing.try.text') }}</p>
                </div>

                <noscript><p class="hint">{{ __('landing.try.noscript') }}</p></noscript>

                <p class="tabs-label" id="tabs-label">{{ __('landing.try.levels') }}</p>
                <div class="tabs" role="tablist" aria-labelledby="tabs-label">
                    @foreach ($samples as $i => $group)
                        <button type="button" class="tab" role="tab" id="tab-{{ $group['level']['slug'] }}"
                                aria-controls="panel-{{ $group['level']['slug'] }}"
                                aria-selected="{{ $i === 0 ? 'true' : 'false' }}" tabindex="{{ $i === 0 ? '0' : '-1' }}">{{ $group['level']['name'] }}</button>
                    @endforeach
                </div>

                @foreach ($samples as $i => $group)
                    <div class="panel" role="tabpanel" id="panel-{{ $group['level']['slug'] }}"
                         aria-labelledby="tab-{{ $group['level']['slug'] }}" data-total="{{ count($group['questions']) }}" @if ($i > 0) hidden @endif>
                        <div class="qlist">
                            @foreach ($group['questions'] as $n => $question)
                                <form class="q" data-id="{{ $question['id'] }}" data-type="{{ $question['type'] }}" novalidate>
                                    <p class="meta">{{ __('landing.try.question', ['number' => $n + 1, 'total' => count($group['questions'])]) }}@if (! empty($question['topic']['name'])) · {{ $question['topic']['name'] }}@endif</p>
                                    <h3 id="q-{{ $question['id'] }}">{{ $question['prompt'] }}</h3>

                                    @if ($question['type'] === 'multiple_choice')
                                        <fieldset aria-labelledby="q-{{ $question['id'] }}">
                                            <p class="hint">{{ $question['data']['multiple'] ? __('landing.try.multiple') : __('landing.try.single') }}</p>
                                            @foreach ($question['data']['options'] as $o => $option)
                                                <label class="opt">
                                                    <input type="{{ $question['data']['multiple'] ? 'checkbox' : 'radio' }}" name="q{{ $question['id'] }}" value="{{ $o }}">
                                                    <span>{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </fieldset>
                                    @elseif ($question['type'] === 'fill_blank')
                                        <p class="sentence">
                                            @foreach (explode(\App\Services\SampleQuestions::BLANK, $question['data']['text']) as $b => $part)
                                                {{ $part }}@if ($b < count($question['data']['blanks']))<input class="blank" type="text" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false"
                                                    aria-label="{{ __('landing.try.blank') }} {{ $b + 1 }}{{ ! empty($question['data']['blanks'][$b]['hint']) ? ' ('.$question['data']['blanks'][$b]['hint'].')' : '' }}"
                                                    placeholder="{{ $question['data']['blanks'][$b]['hint'] ?? '' }}">@endif
                                            @endforeach
                                        </p>
                                    @else
                                        <fieldset aria-labelledby="q-{{ $question['id'] }}">
                                            <p class="hint">{{ __('landing.try.mark') }}</p>
                                            <div class="tokens">
                                                @foreach ($question['data']['tokens'] as $t => $token)
                                                    <button type="button" class="token" aria-pressed="false" data-index="{{ $t }}">{{ $token }}</button>
                                                @endforeach
                                            </div>
                                        </fieldset>
                                    @endif

                                    <button type="submit" class="btn btn-solid">{{ __('landing.try.check') }}</button>
                                    <div class="fb" role="status" aria-live="polite" hidden></div>
                                </form>
                            @endforeach
                        </div>

                        <div class="more" hidden>
                            <h3>{{ __('landing.try.more_title') }}</h3>
                            <p class="score"></p>
                            <p>{{ __('landing.try.more_text') }}</p>
                            <div class="actions">
                                <a class="btn btn-primary" href="/student/registreren">{{ __('landing.register') }}</a>
                                <a class="btn btn-ghost" href="/student/login">{{ __('landing.login') }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach

                <script type="application/json" id="try-text">@json(__('landing.try'))</script>
            </div>
        </section>
        @endif

        <section aria-labelledby="features-title">
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

        <section class="alt" aria-labelledby="steps-title">
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

        <section class="cta" aria-labelledby="cta-title">
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
    <script>
    (() => {
        const root = document.getElementById('try')
        if (!root) return
        const t = JSON.parse(document.getElementById('try-text').textContent)
        const fill = (text, values) => Object.entries(values).reduce((out, [k, v]) => out.replace(':' + k, v), text)

        // Level tabs (arrow keys, Home, End).
        const tabs = [...root.querySelectorAll('[role=tab]')]
        const select = (tab, focus) => {
            tabs.forEach((other) => {
                const on = other === tab
                other.setAttribute('aria-selected', on ? 'true' : 'false')
                other.tabIndex = on ? 0 : -1
                document.getElementById(other.getAttribute('aria-controls')).hidden = !on
            })
            if (focus) tab.focus()
        }
        tabs.forEach((tab, i) => {
            tab.addEventListener('click', () => select(tab, false))
            tab.addEventListener('keydown', (event) => {
                const keys = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }
                if (!(event.key in keys)) return
                event.preventDefault()
                select(tabs[(keys[event.key] + tabs.length) % tabs.length], true)
            })
        })

        const element = (tag, text, className) => {
            const node = document.createElement(tag)
            node.textContent = text
            if (className) node.className = className
            return node
        }

        const collect = (form) => {
            const type = form.dataset.type
            if (type === 'multiple_choice') {
                const selected = [...form.querySelectorAll('input:checked')].map((input) => Number(input.value))
                return selected.length ? { selected } : null
            }
            if (type === 'fill_blank') {
                const blanks = [...form.querySelectorAll('.blank')].map((input) => input.value.trim() || null)
                return blanks.every(Boolean) ? { blanks } : null
            }
            const picked = form.querySelector('.token[aria-pressed=true]')
            return picked ? { index: Number(picked.dataset.index) } : null
        }

        const lock = (form) => form.querySelectorAll('input, .token, button[type=submit]').forEach((node) => (node.disabled = true))

        const show = (form, result) => {
            const type = form.dataset.type
            const feedback = form.querySelector('.fb')
            feedback.className = 'fb ' + (result.is_correct ? 'ok' : 'bad')
            feedback.replaceChildren(element('strong', (result.is_correct ? '\u2713 ' + t.correct : '\u2717 ' + t.incorrect)))

            if (!result.is_correct) {
                const right = result.correct_answer
                let text = ''
                if (type === 'multiple_choice') {
                    const labels = [...form.querySelectorAll('.opt span')]
                    text = right.selected.map((i) => labels[i].textContent).join(' / ')
                } else if (type === 'fill_blank') {
                    text = right.blanks.join(', ')
                } else {
                    text = right.correction
                }
                feedback.append(element('p', t.answer_is + ' ' + text))
            }
            if (result.explanation) feedback.append(element('p', t.explanation + ': ' + result.explanation))
            feedback.hidden = false

            // Show which parts were right or wrong (colour comes with the text above).
            if (type === 'fill_blank') {
                form.querySelectorAll('.blank').forEach((input, i) => input.classList.add(result.items[i] ? 'is-right' : 'is-wrong'))
            } else if (type === 'mark_error') {
                form.querySelectorAll('.token').forEach((token) => {
                    if (Number(token.dataset.index) === result.correct_answer.index) token.classList.add('is-right')
                    else if (token.getAttribute('aria-pressed') === 'true') token.classList.add('is-wrong')
                })
            } else {
                const right = new Set(result.correct_answer.selected)
                form.querySelectorAll('input').forEach((input) => {
                    const row = input.closest('.opt')
                    if (right.has(Number(input.value))) row.classList.add('is-right')
                    else if (input.checked) row.classList.add('is-wrong')
                })
            }
        }

        root.querySelectorAll('.token').forEach((token) =>
            token.addEventListener('click', () => {
                token.closest('.tokens').querySelectorAll('.token').forEach((other) => other.setAttribute('aria-pressed', other === token ? 'true' : 'false'))
            }),
        )

        root.querySelectorAll('.panel').forEach((panel) => {
            let done = 0
            let right = 0

            panel.querySelectorAll('form.q').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault()
                    const feedback = form.querySelector('.fb')
                    const button = form.querySelector('button[type=submit]')
                    const answer = collect(form)

                    if (!answer) {
                        feedback.className = 'fb bad'
                        feedback.textContent = '\u2717 ' + t.empty
                        feedback.hidden = false
                        return
                    }

                    button.disabled = true
                    button.setAttribute('aria-busy', 'true')
                    try {
                        const response = await fetch('/api/v1/samples/' + form.dataset.id + '/answer', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify({ answer }),
                        })
                        if (!response.ok) throw new Error(String(response.status))
                        const { data } = await response.json()

                        lock(form)
                        show(form, data)
                        done += 1
                        if (data.is_correct) right += 1

                        if (done === Number(panel.dataset.total)) {
                            const more = panel.querySelector('.more')
                            more.querySelector('.score').textContent = fill(t.score, { correct: right, total: done })
                            more.hidden = false
                        }
                    } catch {
                        button.disabled = false
                        feedback.className = 'fb bad'
                        feedback.textContent = '\u2717 ' + t.error
                        feedback.hidden = false
                    } finally {
                        button.removeAttribute('aria-busy')
                    }
                })
            })
        })
    })()
    </script>
</body>
</html>

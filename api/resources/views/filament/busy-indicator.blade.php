{{--
    Feedback while the admin is working:
    - the button you just pressed shows a spinner at once (Filament's own one only appears after 200 ms,
      so a normal fast save never showed anything) and keeps it for at least half a second;
    - a thin bar slides across the top of the page while any Livewire request is running.
    Requests that finish within 150 ms do not show the bar, so typing in live fields does not make it flash.
--}}
<style>
    #admin-busy-bar {
        position: fixed;
        inset-inline: 0;
        top: 0;
        z-index: 9999;
        height: 3px;
        overflow: hidden;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.18s ease;
        background: color-mix(in srgb, var(--primary-500, #1f4e8c) 20%, transparent);
    }
    #admin-busy-bar.is-visible {
        opacity: 1;
    }
    #admin-busy-bar > span {
        display: block;
        height: 100%;
        width: 40%;
        border-radius: 9999px;
        background: var(--primary-500, #1f4e8c);
        animation: admin-busy-slide 1.15s ease-in-out infinite;
    }
    @keyframes admin-busy-slide {
        0% { transform: translateX(-100%) scaleX(0.3); }
        50% { transform: translateX(80%) scaleX(0.6); }
        100% { transform: translateX(230%) scaleX(0.3); }
    }

    /* The pressed button: a spinner right away, and no second click while it works. */
    button[data-saving='true'],
    a[data-saving='true'] {
        pointer-events: none;
        cursor: progress;
    }
    button[data-saving='true']::after {
        content: '';
        box-sizing: border-box;
        flex: none;
        width: 1.05em;
        height: 1.05em;
        margin-inline-start: 0.5rem;
        border: 2px solid currentColor;
        border-inline-end-color: transparent;
        border-radius: 9999px;
        animation: admin-busy-spin 0.7s linear infinite;
    }
    /* Filament's own (delayed) spinner would be a second one. */
    button[data-saving='true'] .fi-loading-indicator {
        display: none !important;
    }
    @keyframes admin-busy-spin {
        to { transform: rotate(360deg); }
    }

    @media (prefers-reduced-motion: reduce) {
        #admin-busy-bar > span {
            animation: none;
            width: 100%;
            opacity: 0.7;
        }
        button[data-saving='true']::after {
            animation: none;
            border-style: dotted;
        }
    }
</style>

<script>
    document.addEventListener('livewire:init', () => {
        const SHOW_BAR_AFTER_MS = 150
        const MIN_VISIBLE_MS = 500
        const BUTTON_WINDOW_MS = 400

        let pending = 0
        let bar = null
        let shownAt = 0
        let showTimer = null
        let hideTimer = null

        // The button that was just pressed (a request that starts right after belongs to it).
        let pressed = null
        let pressedAt = 0
        const remember = (el) => {
            if (el) {
                pressed = el
                pressedAt = Date.now()
            }
        }
        document.addEventListener('click', (e) => remember(e.target.closest('button')), true)
        document.addEventListener('submit', (e) => remember(e.submitter), true)

        const ensureBar = () => {
            if (bar) return bar
            bar = document.createElement('div')
            bar.id = 'admin-busy-bar'
            bar.setAttribute('aria-hidden', 'true')
            bar.innerHTML = '<span></span>'
            document.body.appendChild(bar)
            return bar
        }

        const barStart = () => {
            clearTimeout(hideTimer)
            hideTimer = null
            if (!showTimer && !(bar && bar.classList.contains('is-visible'))) {
                showTimer = setTimeout(() => {
                    showTimer = null
                    ensureBar().classList.add('is-visible')
                    shownAt = Date.now()
                }, SHOW_BAR_AFTER_MS)
            }
        }

        const barStop = () => {
            clearTimeout(showTimer)
            showTimer = null
            if (bar && bar.classList.contains('is-visible') && !hideTimer) {
                hideTimer = setTimeout(() => {
                    hideTimer = null
                    bar.classList.remove('is-visible')
                }, Math.max(0, MIN_VISIBLE_MS - (Date.now() - shownAt)))
            }
        }

        Livewire.interceptRequest(({ onFinish, onCancel, onFailure }) => {
            pending++
            barStart()

            // Spinner on the button that caused this request.
            let button = null
            let keepMarked = null
            const mark = () => {
                button.dataset.saving = 'true'
                button.setAttribute('aria-busy', 'true')
            }
            if (pressed && pressed.isConnected && Date.now() - pressedAt < BUTTON_WINDOW_MS && pressed.dataset.saving !== 'true') {
                button = pressed
                mark()
                // When the response arrives Livewire updates the page and strips attributes it does not know
                // (ours too). Put the marker back until the minimum display time has passed.
                keepMarked = new MutationObserver(() => {
                    if (button.isConnected && button.dataset.saving !== 'true') mark()
                })
                keepMarked.observe(button, { attributes: true, attributeFilter: ['data-saving', 'aria-busy'] })
            }
            const startedAt = Date.now()
            pressed = null

            let finished = false
            const finish = () => {
                if (finished) return
                finished = true

                pending = Math.max(0, pending - 1)
                if (pending === 0) barStop()

                if (button) {
                    const keep = Math.max(0, MIN_VISIBLE_MS - (Date.now() - startedAt))
                    setTimeout(() => {
                        keepMarked.disconnect()
                        delete button.dataset.saving
                        button.removeAttribute('aria-busy')
                    }, keep)
                }
            }

            onFinish(finish)
            onCancel(finish)
            onFailure(finish)
        })
    })
</script>

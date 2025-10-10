<div wire:poll.700ms
     style="position: absolute; bottom: 100px; right: 15px; pointer-events: none; z-index:1050;">

    <style>
        /* entrance */
        @keyframes slideIn {
            from { transform: translateY(6px); opacity: 0; }
            to   { transform: translateY(0); opacity: 1; }
        }

        /* gentle pulsing red glow */
        @keyframes glowPulse {
            0%   { box-shadow: 0 6px 18px rgba(255,0,0,0.06), 0 0 6px rgba(255,0,0,0.18); }
            50%  { box-shadow: 0 10px 30px rgba(255,0,0,0.12), 0 0 14px rgba(255,0,0,0.30); }
            100% { box-shadow: 0 6px 18px rgba(255,0,0,0.06), 0 0 6px rgba(255,0,0,0.18); }
        }

        .unknown-stack {
            display: flex;
            flex-direction: column-reverse; /* newest on top visually */
            gap: 8px;
            max-height: 420px;
        }

        .unknown-item {
            min-width: 220px;
            max-width: 360px;
            background: linear-gradient(180deg, rgba(255,250,250,0.98), rgba(255,245,245,0.98));
            border-radius: 8px;
            padding: 8px 12px;
            border: 1px solid rgba(255,0,0,0.25);
            font-size: 0.92rem;
            color: #2b2b2b;
            display: flex;
            flex-direction: column;
            gap: 4px;
            transform-origin: center;
            animation: slideIn 220ms cubic-bezier(.2,.9,.3,1) 0s both, glowPulse 2.4s ease-in-out infinite;
            box-shadow: 0 6px 18px rgba(255,0,0,0.06), 0 0 10px rgba(255,0,0,0.18);
            pointer-events: auto; /* allow hover styles if desired */
        }

        /* red text for the tag for emphasis */
        .unknown-tag {
            font-weight: 700;
            letter-spacing: 0.3px;
            color: #b80000;
            text-shadow: 0 0 6px rgba(255,0,0,0.18);
        }

        .unknown-meta {
            font-size: 0.78rem;
            color: #6b6f76;
        }

        /* fade-out class applied individually */
        .unknown-item.fade-out {
            opacity: 0;
            transform: translateY(8px) scale(0.995);
            transition: opacity 600ms ease, transform 600ms ease;
        }
    </style>

    <div class="unknown-stack">
        @foreach($unknowns as $u)
            @php
                // created_at timestamp in milliseconds (approx)
                $createdMs = ((int) $u->created_at->timestamp) * 1000;
                $fadeMs = 600; // fade duration in ms (kept in sync with CSS)
            @endphp

            {{-- data-created is used by the script to compute per-item remaining time --}}
            <div class="unknown-item"
                 wire:key="unknown-{{ $u->id }}"
                 data-id="{{ $u->id }}"
                 data-created="{{ $createdMs }}"
                 data-display="{{ $displaySeconds }}"
                 data-fade="{{ $fadeMs }}">
                <div class="unknown-tag">Unknown Tag: {{ $u->rfid_tag }}</div>
            </div>
        @endforeach
    </div>

    {{-- per-item scheduling script (runs after page load and after Livewire updates) --}}
    <script>
        (function () {
            window._unknownTimers = window._unknownTimers || {};

            function scheduleTimers(root = document) {
                root.querySelectorAll && root.querySelectorAll('.unknown-item[data-id]').forEach(function (el) {
                    var id = el.dataset.id;
                    if (!id) return;
                    // don't schedule twice
                    if (window._unknownTimers[id]) return;

                    var created = parseInt(el.dataset.created || '0', 10);
                    var displaySeconds = parseFloat(el.dataset.display || '6');
                    var fadeMs = parseInt(el.dataset.fade || '600', 10);

                    var now = Date.now();
                    var remaining = (displaySeconds * 1000) - (now - created);

                    // if already expired, fade immediately
                    if (remaining <= 0) {
                        triggerFadeAndRemove(el, fadeMs);
                        return;
                    }

                    // start fade so it ends when the server stops returning the entry
                    var fadeStart = Math.max(0, remaining - fadeMs);

                    var fadeTimer = setTimeout(function () {
                        triggerFadeAndRemove(el, fadeMs);
                    }, Math.round(fadeStart));

                    // store timer handles so we can avoid duplicates and clear if needed
                    window._unknownTimers[id] = {
                        fadeTimer: fadeTimer
                    };
                });
            }

            function triggerFadeAndRemove(el, fadeMs) {
                // add fade class (CSS handles opacity + transform)
                el.classList.add('fade-out');

                // after fade duration remove DOM node (visual removal)
                setTimeout(function () {
                    try {
                        if (el && el.parentNode) el.parentNode.removeChild(el);
                    } catch (e) { /* ignore */ }
                    if (window._unknownTimers && el && el.dataset && el.dataset.id) {
                        delete window._unknownTimers[el.dataset.id];
                    }
                }, fadeMs + 20);
            }

            // initial schedule on load
            document.addEventListener('DOMContentLoaded', function () {
                scheduleTimers(document);
            });

            // Livewire hooks: runs after each update so newly rendered items get timers
            (function attachLivewireHooks() {
                if (window.livewire && window.Livewire) {
                    // run once when Livewire is ready
                    document.addEventListener('livewire:load', function () {
                        scheduleTimers(document);
                    });

                    // run after each Livewire DOM patch (ensures newly added items are scheduled)
                    Livewire.hook('message.processed', function (message, component) {
                        // component.el may be available; safe fallback to document
                        scheduleTimers(component && component.el ? component.el : document);
                    });
                } else {
                    // fallback: periodically check for new nodes (very low cost)
                    setInterval(function () {
                        scheduleTimers(document);
                    }, 1200);
                }
            })();
        })();
    </script>
</div>

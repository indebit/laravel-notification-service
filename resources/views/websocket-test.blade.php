<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WebSocket test (Reverb) — debug only</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-900 antialiased scheme-light dark:scheme-dark dark:bg-neutral-950 dark:text-neutral-100">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="mt-0 text-xl font-semibold tracking-tight">WebSocket test (Laravel Reverb)</h1>
        <p class="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
            Debug / testing only. Uses the Pusher wire protocol over
            <code class="rounded bg-neutral-200 px-1 py-0.5 font-mono text-xs dark:bg-neutral-800">ws://localhost:8080/app/notification-system-key</code>
            (match <code class="rounded bg-neutral-200 px-1 py-0.5 font-mono text-xs dark:bg-neutral-800">REVERB_APP_KEY</code> in <code class="rounded bg-neutral-200 px-1 py-0.5 font-mono text-xs dark:bg-neutral-800">.env</code> if yours differs).
        </p>

        <p class="mt-4 text-sm">
            Status:
            <span id="status" class="inline-block rounded px-2.5 py-1 text-xs font-semibold capitalize bg-neutral-500 text-white">disconnected</span>
        </p>

        <div class="my-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-2">
            <label for="channel" class="text-sm font-semibold sm:shrink-0">Channel</label>
            <input
                type="text"
                id="channel"
                class="min-w-[12rem] flex-1 rounded-md border border-neutral-300 bg-white px-2.5 py-2 font-mono text-sm shadow-sm focus:border-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-400/30 dark:border-neutral-600 dark:bg-neutral-900 dark:focus:border-neutral-500"
                value="notifications."
                autocomplete="off"
                placeholder="notifications.{batch-or-notification-id}"
            >
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <button
                type="button"
                id="btnConnect"
                class="cursor-pointer rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white"
            >
                Connect &amp; Subscribe
            </button>
            <button
                type="button"
                id="btnClear"
                class="cursor-pointer rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-900 dark:text-neutral-100 dark:hover:bg-neutral-800"
            >
                Clear log
            </button>
        </div>

        <div
            id="log"
            class="h-[22rem] overflow-auto whitespace-pre-wrap break-words rounded-lg border border-neutral-300 bg-neutral-900 p-3 font-mono text-xs text-neutral-300 dark:border-neutral-700 dark:bg-neutral-950"
            aria-live="polite"
        ></div>
    </div>

    <script>
        (function () {
            var WS_URL = 'ws://localhost:8080/app/notification-system-key';
            var ws = null;
            var socketId = null;
            var logEl = document.getElementById('log');
            var statusEl = document.getElementById('status');
            var channelInput = document.getElementById('channel');

            var statusClasses = {
                connecting: 'inline-block rounded px-2.5 py-1 text-xs font-semibold capitalize bg-amber-500 text-neutral-900',
                connected: 'inline-block rounded px-2.5 py-1 text-xs font-semibold capitalize bg-green-600 text-white',
                disconnected: 'inline-block rounded px-2.5 py-1 text-xs font-semibold capitalize bg-neutral-500 text-white',
            };

            var tagClasses = {
                'tag-info': 'bg-sky-900/90 text-sky-200',
                'tag-event': 'bg-yellow-900/90 text-yellow-200',
                'tag-error': 'bg-red-900/90 text-red-200',
                'tag-success': 'bg-emerald-900/90 text-emerald-200',
            };

            function now() {
                return new Date().toISOString();
            }

            function setStatus(state) {
                statusEl.textContent = state;
                statusEl.className = statusClasses[state] || statusClasses.disconnected;
            }

            function appendLog(text, tag, tagClass) {
                var line = document.createElement('div');
                line.className = 'mb-2.5 border-b border-neutral-700 pb-2 last:mb-0 last:border-b-0 last:pb-0';
                var ts = document.createElement('span');
                ts.className = 'mr-2 text-neutral-500';
                ts.textContent = now();
                line.appendChild(ts);
                if (tag) {
                    var t = document.createElement('span');
                    var tc = tagClass || 'tag-info';
                    t.className = 'mr-1.5 rounded px-1 py-0.5 text-[0.7rem] font-medium uppercase ' + (tagClasses[tc] || tagClasses['tag-info']);
                    t.textContent = tag;
                    line.appendChild(t);
                }
                var body = document.createElement('span');
                body.textContent = text;
                line.appendChild(body);
                logEl.appendChild(line);
                logEl.scrollTop = logEl.scrollHeight;
            }

            function subscribe() {
                var channel = channelInput.value.trim();
                if (!channel) {
                    appendLog('Enter a full channel name (e.g. notifications.{uuid}).', 'error', 'tag-error');
                    return;
                }
                if (!ws || ws.readyState !== WebSocket.OPEN) {
                    appendLog('Not connected yet.', 'error', 'tag-error');
                    return;
                }
                ws.send(JSON.stringify({
                    event: 'pusher:subscribe',
                    data: { channel: channel }
                }));
                appendLog('Sent pusher:subscribe for "' + channel + '"', 'info', 'tag-info');
            }

            function startConnection() {
                if (ws) {
                    try { ws.close(); } catch (e) {}
                    ws = null;
                }
                socketId = null;
                setStatus('connecting');
                appendLog('Connecting to ' + WS_URL + ' …', 'info', 'tag-info');

                ws = new WebSocket(WS_URL);

                ws.onopen = function () {
                    appendLog('WebSocket TCP open; waiting for pusher:connection_established …', 'info', 'tag-info');
                };

                ws.onclose = function () {
                    setStatus('disconnected');
                    appendLog('WebSocket closed.', 'info', 'tag-info');
                    ws = null;
                    socketId = null;
                };

                ws.onerror = function () {
                    appendLog('WebSocket error (see browser devtools).', 'error', 'tag-error');
                };

                ws.onmessage = function (event) {
                    var msg;
                    try {
                        msg = JSON.parse(event.data);
                    } catch (e) {
                        appendLog(String(event.data), 'raw', 'tag-event');
                        return;
                    }

                    if (msg.event === 'pusher:connection_established') {
                        setStatus('connected');
                        try {
                            var inner = JSON.parse(msg.data);
                            socketId = inner.socket_id;
                        } catch (e) {
                            socketId = null;
                        }
                        appendLog('Connected. socket_id=' + (socketId || '(parse error)'), 'success', 'tag-success');
                        subscribe();
                        return;
                    }

                    if (msg.event === 'pusher:ping') {
                        ws.send(JSON.stringify({ event: 'pusher:pong', data: {} }));
                        appendLog('pusher:ping → sent pusher:pong', 'info', 'tag-info');
                        return;
                    }

                    if (msg.event === 'pusher_internal:subscription_succeeded') {
                        appendLog('Subscribed: ' + (msg.channel || JSON.stringify(msg)), 'success', 'tag-success');
                        return;
                    }

                    if (msg.event === 'pusher:subscription_error') {
                        appendLog(typeof msg.data === 'string' ? msg.data : JSON.stringify(msg, null, 2), 'error', 'tag-error');
                        return;
                    }

                    appendLog(JSON.stringify(msg, null, 2), 'event', 'tag-event');
                };
            }

            function connectAndSubscribe() {
                if (ws && ws.readyState === WebSocket.OPEN && socketId) {
                    subscribe();
                    return;
                }
                startConnection();
            }

            document.getElementById('btnConnect').addEventListener('click', connectAndSubscribe);
            document.getElementById('btnClear').addEventListener('click', function () {
                logEl.innerHTML = '';
            });

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startConnection);
            } else {
                startConnection();
            }
        })();
    </script>
</body>
</html>

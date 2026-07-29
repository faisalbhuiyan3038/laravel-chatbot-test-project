<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FAQ Chat</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: -apple-system, Segoe UI, Roboto, sans-serif;
            background: #f4f5f7; display: flex; justify-content: center;
        }
        #chat {
            width: 100%; max-width: 640px; height: 100vh;
            display: flex; flex-direction: column; background: #fff;
            box-shadow: 0 0 24px rgba(0,0,0,0.06);
        }
        header {
            padding: 16px 20px; background: #2b2d42; color: #fff; font-weight: 600;
        }
        #messages {
            flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 12px;
        }
        .msg { max-width: 80%; padding: 10px 14px; border-radius: 14px; line-height: 1.4; white-space: pre-wrap; }
        .msg.user { align-self: flex-end; background: #2b2d42; color: #fff; border-bottom-right-radius: 4px; }
        .msg.assistant { align-self: flex-start; background: #eef0f4; color: #1a1a1a; border-bottom-left-radius: 4px; }
        .msg.thinking { color: #888; font-style: italic; }
        .details-toggle {
            align-self: flex-start; margin-top: -6px; font-size: 12px; color: #6b7280;
            background: none; border: none; cursor: pointer; padding: 2px 4px;
        }
        .details-toggle:hover { text-decoration: underline; }
        .details-panel {
            align-self: flex-start; max-width: 85%; font-size: 12.5px; color: #444;
            background: #fafafa; border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 10px 12px; display: none;
        }
        .details-panel.open { display: block; }
        .details-panel .timing { display: flex; gap: 14px; margin-bottom: 8px; color: #555; }
        .details-panel .source { padding: 4px 0; border-top: 1px solid #eee; }
        .details-panel .source:first-child { border-top: none; }
        .score-badge {
            display: inline-block; background: #e0e7ff; color: #3730a3;
            border-radius: 6px; padding: 1px 6px; font-size: 11px; margin-right: 6px;
        }
        footer { padding: 14px; border-top: 1px solid #eee; }
        #chat-form { display: flex; gap: 8px; }
        #chat-input {
            flex: 1; padding: 10px 14px; border: 1px solid #ddd; border-radius: 20px; font-size: 14px;
        }
        #chat-form button {
            padding: 10px 20px; border: none; border-radius: 20px; background: #2b2d42; color: #fff; cursor: pointer;
        }
        #chat-form button:disabled { opacity: 0.5; cursor: default; }
        .msg.thinking::after {
            content: '▍';
            display: inline-block;
            margin-left: 2px;
            animation: blink 0.9s steps(1) infinite;
        }
        @keyframes blink {
            50% { opacity: 0; }
        }
    </style>
</head>
<body>
    <div id="chat">
        <header>AV-CRM FAQ Assistant — dev console</header>
        <div id="messages"></div>
        <footer>
            <form id="chat-form">
                <input type="text" id="chat-input" placeholder="Ask a question..." autocomplete="off" required>
                <button type="submit">Send</button>
            </form>
        </footer>
    </div>

    <script>
        const CHAT_ASK_URL = @json(route('chat.ask'));
        const THINKING_PHRASES = [
            'Pondering...',
            'Digging through the knowledge base...',
            'Consulting the FAQ oracle...',
            'Untangling your question...',
            'Doing suspiciously fast math...',
            'Politely interrogating the database...',
            'Summoning relevant wisdom...',
        ];

        const messages = document.getElementById('messages');
        const form = document.getElementById('chat-form');
        const input = document.getElementById('chat-input');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function formatDuration(ms) {
            if (ms < 1000) return `${Math.round(ms)}ms`;
        
            const seconds = ms / 1000;
            if (seconds < 60) {
                return `${seconds.toFixed(seconds < 10 ? 2 : 1)}s`; // more precision below 10s, less above
            }
        
            const minutes = Math.floor(seconds / 60);
            const remSeconds = Math.round(seconds % 60);
            return `${minutes}m ${remSeconds}s`;
        }

        function scrollToBottom() {
            messages.scrollTop = messages.scrollHeight;
        }

        function addMessage(role, text) {
            const el = document.createElement('div');
            el.className = `msg ${role}`;
            el.textContent = text;
            messages.appendChild(el);
            scrollToBottom();
            return el;
        }

        function startThinking() {
            const el = document.createElement('div');
            el.className = 'msg assistant thinking';
            messages.appendChild(el);
            scrollToBottom();
        
            let phraseIndex = 0;
            let charIndex = 0;
            let typingTimer = null;
            let pauseTimer = null;
            let stopped = false;
        
            function typeNextChar() {
                if (stopped) return;
        
                const phrase = THINKING_PHRASES[phraseIndex];
        
                if (charIndex <= phrase.length) {
                    el.textContent = phrase.slice(0, charIndex);
                    charIndex++;
                    scrollToBottom();
                    // slight random variance so it doesn't feel robotically uniform
                    typingTimer = setTimeout(typeNextChar, 22 + Math.random() * 35);
                } else {
                    // full phrase visible — hold briefly, then clear and type the next one
                    pauseTimer = setTimeout(() => {
                        phraseIndex = (phraseIndex + 1) % THINKING_PHRASES.length;
                        charIndex = 0;
                        el.textContent = '';
                        typeNextChar();
                    }, 900);
                }
            }
        
            typeNextChar();
        
            return {
                el,
                stop: () => {
                    stopped = true;
                    clearTimeout(typingTimer);
                    clearTimeout(pauseTimer);
                },
            };
        }

        function buildDetailsPanel(timing, sources, grounded) {
            const panel = document.createElement('div');
            panel.className = 'details-panel';

            const timingHtml = `
                <div class="timing">
                    <span>⏱ total: <strong>${formatDuration(timing.total_ms)}</strong></span>
                    <span>🔍 retrieval: <strong>${formatDuration(timing.retrieval_ms)}</strong></span>
                    <span>🧠 generation: <strong>${formatDuration(timing.generation_ms)}</strong></span>
                </div>`;

            const sourcesHtml = grounded && sources.length
                ? sources.map(s => `
                    <div class="source">
                        <span class="score-badge">${s.score}</span>
                        FAQ #${s.id} — ${escapeHtml(s.question)}
                    </div>`).join('')
                : '<div class="source">No sources used (off-topic / below threshold).</div>';

            panel.innerHTML = timingHtml + sourcesHtml;
            return panel;
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const question = input.value.trim();
            if (!question) return;

            addMessage('user', question);
            input.value = '';
            input.disabled = true;

            const thinking = startThinking();
            let assistantBubble = null;
            let answerText = '';

            try {
                const response = await fetch(CHAT_ASK_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ question }),
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });

                    let boundary;
                    while ((boundary = buffer.indexOf('\n\n')) !== -1) {
                        const frame = buffer.slice(0, boundary);
                        buffer = buffer.slice(boundary + 2);

                        const line = frame.split('\n').find(l => l.startsWith('data:'));
                        if (!line) continue;

                        const payload = JSON.parse(line.slice(5).trim());

                        if (payload.token) {
                            // First token arrived — swap the "thinking" bubble for the real one
                            if (!assistantBubble) {
                                thinking.stop();
                                thinking.el.remove();
                                assistantBubble = addMessage('assistant', '');
                            }
                            answerText += payload.token;
                            assistantBubble.textContent = answerText;
                            scrollToBottom();
                        }

                        if (payload.done) {
                            // Handles the off-topic case too, where no tokens ever streamed
                            if (!assistantBubble) {
                                thinking.stop();
                                thinking.el.remove();
                            }

                            const toggle = document.createElement('button');
                            toggle.className = 'details-toggle';
                            toggle.textContent = '▾ Details';
                            const panel = buildDetailsPanel(payload.timing, payload.sources, payload.grounded);

                            toggle.addEventListener('click', () => {
                                panel.classList.toggle('open');
                                toggle.textContent = panel.classList.contains('open') ? '▴ Hide details' : '▾ Details';
                            });

                            messages.appendChild(toggle);
                            messages.appendChild(panel);
                            scrollToBottom();
                        }
                    }
                }
            } catch (err) {
                thinking.stop();
                thinking.el.remove();
                addMessage('assistant', 'Something went wrong — please try again.');
                console.error(err);
            } finally {
                input.disabled = false;
                input.focus();
            }
        });
    </script>
</body>
</html>
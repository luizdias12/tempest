/* ========================================= INICIO CHATBOT ===============================================*/
    (function() {
        
        let location = window.location.pathname.substring(1);
        // let suggestions = geraSugestoes(location); // para reaproveitar depois
        let suggestions = [];

        // ─── Configuração ───────────────────────────────────────────
        let CONFIG = {
            endpoint: '../controller/chatbot/chatbot.php', // ← altere para o caminho do seu backend
            botName: 'Vivi',
            welcomeMsg: 'Olá! Sou a Vivi, sua assistente virtual. Estou em desenvolvimento, aguarde por melhorias! 😊', // mensagem de boas-vindas
            suggestions: [...suggestions] // sugestões iniciais,
        };
        // ────────────────────────────────────────────────────────────

        const toggle = document.getElementById('chat-toggle');
        const win = document.getElementById('chat-window');
        const msgs = document.getElementById('chat-messages');
        const input = document.getElementById('chat-input');
        const sendBtn = document.getElementById('chat-send');
        const badge = document.getElementById('chat-badge');
        const closeBtn = document.getElementById('chat-header-close');
        const suggsEl = document.getElementById('chat-suggestions');
        const icoChat = document.getElementById('ico-chat');
        const icoClose = toggle.querySelector('.ico-close');

        let isOpen = false;
        let isLoading = false;
        let history = []; // [{role, content}]

        // ─── Abrir / fechar ───
        function openChat() {
            isOpen = true;
            win.classList.add('open');
            icoChat.style.display = 'none';
            icoClose.style.display = 'block';
            badge.style.display = 'none';
            input.focus();
            scrollBottom();
        }

        function closeChat() {
            isOpen = false;
            win.classList.remove('open');
            icoChat.style.display = '';
            icoClose.style.display = 'none';
        }
        toggle.addEventListener('click', () => isOpen ? closeChat() : openChat());
        closeBtn.addEventListener('click', closeChat);

        // ─── Mensagem de boas-vindas ───
        addBotMessage(CONFIG.welcomeMsg);
        renderSuggestions(CONFIG.suggestions);

        // ─── Envio ───
        function sendMessage(text) {
            text = text.trim();
            if (!text || isLoading) return;
            clearSuggestions();
            addUserMessage(text);
            history.push({
                role: 'user',
                content: text
            });
            input.value = '';
            adjustTextarea();
            callAPI(text);
        }

        document.getElementById('chat-form').addEventListener('submit', e => {
            e.preventDefault();
            sendMessage(input.value);
        });
        sendBtn.addEventListener('click', () => sendMessage(input.value));

        input.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage(input.value);
            }
        });
        input.addEventListener('input', () => {
            adjustTextarea();
            sendBtn.disabled = !input.value.trim();
        });

        // ─── Sugestões ───
        function renderSuggestions(list) {
            suggsEl.innerHTML = '';
            list.forEach(s => {
                const chip = document.createElement('button');
                chip.className = 'suggestion-chip';
                chip.textContent = s;
                chip.addEventListener('click', () => sendMessage(s));
                suggsEl.appendChild(chip);
            });
        }

        function clearSuggestions() {
            suggsEl.innerHTML = '';
        }

        // ─── Adicionar mensagens ───
        function addUserMessage(text) {
            const row = createRow('user');
            row.innerHTML = `
                <div class="msg-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/>
                    </svg>
                </div>
                <div>
                    <div class="msg-bubble">${escHtml(text)}</div>
                    <span class="msg-time">${now()}</span>
                </div>`;
            msgs.appendChild(row);
            scrollBottom();
        }

        function addBotMessage(text) {
            const row = createRow('bot');
            row.innerHTML = `
                <div class="msg-icon"><img src="data:image/png;base64,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" alt="Vivi" style="width:28px;height:28px;border-radius:50%;object-fit:cover;object-position:top;"></div>
                <div>
                    <div class="msg-bubble">${formatBotText(text)}</div>
                    <span class="msg-time">${now()}</span>
                </div>`;
            msgs.appendChild(row);
            history.push({
                role: 'assistant',
                content: text
            });
            scrollBottom();
        }

        function addTyping() {
            const row = createRow('bot');
            row.id = 'typing-row';
            row.innerHTML = `
                <div class="msg-icon"><img src="data:image/png;base64,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" alt="Vivi" style="width:28px;height:28px;border-radius:50%;object-fit:cover;object-position:top;"></div>
                <div class="typing-bubble"><span></span><span></span><span></span></div>`;
            msgs.appendChild(row);
            scrollBottom();
        }

        function removeTyping() {
            const el = document.getElementById('typing-row');
            if (el) el.remove();
        }

        // ─── Chamada ao backend ───
        async function callAPI(userText) {
            isLoading = true;
            sendBtn.disabled = true;
            addTyping();

            try {
                const res = await fetch(CONFIG.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: userText,
                        history
                    }),
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                removeTyping();
                addBotMessage(data.reply ?? 'Não consegui processar a resposta.');

            } catch (err) {
                removeTyping();
                addBotMessage('Ops, houve um problema ao conectar. Tente novamente em instantes.');
                console.error('[Chat widget]', err);
            }

            isLoading = false;
            sendBtn.disabled = !input.value.trim();

            // Notificação se janela fechada
            if (!isOpen) showBadge();
        }

        // ─── Helpers ───
        function createRow(type) {
            const div = document.createElement('div');
            div.className = `msg-row ${type}`;
            return div;
        }

        function escHtml(s) {
            return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
        }

        function formatBotText(s) {
            // Markdown básico: **bold**, *italic*, saltos de linha
            return escHtml(s)
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>');
        }

        function now() {
            return new Date().toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function scrollBottom() {
            requestAnimationFrame(() => {
                msgs.scrollTop = msgs.scrollHeight;
            });
        }

        function adjustTextarea() {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        }

        function showBadge() {
            badge.textContent = '1';
            badge.style.display = 'flex';
        }
    })();
/* ============================================================ FIM CHATBOT ========================================================================== */
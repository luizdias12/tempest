/* ========================================= INICIO CHATBOT VIVI ===============================================*/
(function () {
    'use strict';

    const win = document.getElementById('chat-window');
    const toggle = document.getElementById('chat-toggle');

    if (!win || !toggle) return;

    const CONFIG = {
        endpoint: win.dataset.endpoint || '/vivi/chat',
        feedbackEndpoint: win.dataset.feedbackEndpoint || '/vivi/feedback',
        statusEndpoint: win.dataset.statusEndpoint || '/vivi/status',
        welcomeMsg: 'Olá! Sou a Vivi, assistente virtual da intranet. Posso ajudar com dúvidas sobre processos, sistemas e documentos internos. Como posso ajudar?',
        sugestoes: [
            'Como abrir um chamado no Helpdesk?',
            'Como consultar meu holerite?',
            'Onde encontro os documentos internos?',
            'Como redefinir minha senha?'
        ],
        maxInput: 1000,
        maxHistory: 8,
        storageKey: 'vivi.conversa.v1',
        timeoutMs: 75000
    };

    const msgs = document.getElementById('chat-messages');
    const input = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send');
    const badge = document.getElementById('chat-badge');
    const closeBtn = document.getElementById('chat-header-close');
    const clearBtn = document.getElementById('chat-clear');
    const suggsEl = document.getElementById('chat-suggestions');
    const statusDot = document.getElementById('chat-status-dot');
    const statusText = document.getElementById('chat-status-text');
    const form = document.getElementById('chat-form');
    const avatarImg = document.querySelector('#ico-chat img');
    const avatarUrl = avatarImg ? avatarImg.src : '';

    const SVG_USUARIO = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>';
    const SVG_UTIL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>';
    const SVG_INUTIL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"/></svg>';

    let isOpen = false;
    let isLoading = false;
    let naoLidas = 0;
    let statusEm = 0;
    let statusOnline = null;
    let history = carregarHistorico();

    // ─── Abrir / fechar ───
    function abrirChat() {
        isOpen = true;
        win.classList.add('open');
        win.setAttribute('aria-hidden', 'false');
        win.inert = false;
        document.body.classList.add('vivi-aberto');
        toggle.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
        naoLidas = 0;
        ocultarBadge();
        verificarStatus();
        scrollBottom();
        setTimeout(function () { input.focus(); }, 50);
    }

    function fecharChat() {
        isOpen = false;
        win.classList.remove('open');
        win.setAttribute('aria-hidden', 'true');
        win.inert = true;
        document.body.classList.remove('vivi-aberto');
        toggle.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    }

    toggle.addEventListener('click', function () {
        isOpen ? fecharChat() : abrirChat();
    });
    closeBtn.addEventListener('click', fecharChat);
    clearBtn.addEventListener('click', novaConversa);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) fecharChat();
    });

    // ─── Envio ───
    function enviarMensagem(texto) {
        texto = String(texto || '').trim();

        if (!texto || isLoading) return;

        if (texto.length > CONFIG.maxInput) {
            texto = texto.slice(0, CONFIG.maxInput);
        }

        adicionarMensagem('user', texto, true);
        input.value = '';
        ajustarTextarea();
        atualizarEstadoEnvio();
        limparSugestoes();
        enviarParaApi(texto);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        enviarMensagem(input.value);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            enviarMensagem(input.value);
        }
    });

    input.addEventListener('input', function () {
        ajustarTextarea();
        atualizarEstadoEnvio();
    });

    async function enviarParaApi(texto) {
        if (isLoading) return;

        isLoading = true;
        atualizarEstadoEnvio();
        adicionarDigitando();

        const controller = new AbortController();
        const timer = setTimeout(function () { controller.abort(); }, CONFIG.timeoutMs);

        try {
            const respostaHttp = await fetch(CONFIG.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                signal: controller.signal,
                body: JSON.stringify({
                    message: texto,
                    history: history.slice(-CONFIG.maxHistory).map(function (m) {
                        return { role: m.role, content: m.content };
                    })
                })
            });

            const payload = await respostaHttp.json().catch(function () { return null; });

            if (!respostaHttp.ok || !payload || payload.success !== true) {
                let mensagem = 'Não consegui responder agora. Tente novamente.';

                if (payload && payload.error && payload.error.message) {
                    mensagem = payload.error.message;
                } else if (respostaHttp.status === 429) {
                    mensagem = 'Muitas mensagens em pouco tempo. Aguarde um instante.';
                }

                throw new Error(mensagem);
            }

            const resposta = payload.data && typeof payload.data.reply === 'string'
                ? payload.data.reply.trim()
                : '';

            if (!resposta) {
                throw new Error('A Vivi não retornou uma resposta.');
            }

            const bolha = adicionarMensagem('assistant', resposta, true);
            adicionarFeedback(bolha, texto, resposta);
        } catch (erro) {
            const mensagem = erro && erro.name === 'AbortError'
                ? 'A resposta demorou demais. Tente novamente.'
                : (erro && erro.message ? erro.message : 'Ops, houve um problema ao conectar.');

            adicionarErro(mensagem, texto);
        } finally {
            clearTimeout(timer);
            removerDigitando();
            isLoading = false;
            atualizarEstadoEnvio();

            if (!isOpen) mostrarBadge();
        }
    }

    // ─── Nova conversa ───
    function novaConversa() {
        history = [];
        salvarHistorico();
        msgs.innerHTML = '';
        naoLidas = 0;
        ocultarBadge();
        adicionarMensagem('assistant', CONFIG.welcomeMsg, false);
        renderizarSugestoes();
        input.value = '';
        ajustarTextarea();
        atualizarEstadoEnvio();
        input.focus();
    }

    // ─── Sugestões ───
    function renderizarSugestoes() {
        suggsEl.innerHTML = '';

        CONFIG.sugestoes.forEach(function (sugestao) {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'suggestion-chip';
            chip.textContent = sugestao;
            chip.addEventListener('click', function () { enviarMensagem(sugestao); });
            suggsEl.appendChild(chip);
        });
    }

    function limparSugestoes() {
        suggsEl.innerHTML = '';
    }

    // ─── Mensagens ───
    function criarIcone(tipo) {
        const icon = document.createElement('div');
        icon.className = 'msg-icon';

        if (tipo === 'bot') {
            if (avatarUrl) {
                const img = document.createElement('img');
                img.src = avatarUrl;
                img.alt = '';
                img.width = 28;
                img.height = 28;
                icon.appendChild(img);
            } else {
                icon.textContent = 'V';
            }
        } else {
            icon.innerHTML = SVG_USUARIO;
        }

        return icon;
    }

    function adicionarMensagem(papel, texto, persistir, ts) {
        const tipo = papel === 'user' ? 'user' : 'bot';
        const row = document.createElement('div');
        row.className = 'msg-row ' + tipo;

        const wrap = document.createElement('div');
        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';

        if (tipo === 'user') {
            bubble.textContent = texto;
        } else {
            bubble.innerHTML = formatar(texto);
        }

        const time = document.createElement('span');
        time.className = 'msg-time';
        time.textContent = hora(ts);

        wrap.appendChild(bubble);
        wrap.appendChild(time);
        row.appendChild(criarIcone(tipo));
        row.appendChild(wrap);
        msgs.appendChild(row);

        if (persistir) {
            history.push({ role: papel, content: texto, ts: ts || Date.now() });
            salvarHistorico();
        }

        scrollBottom();

        return wrap;
    }

    function adicionarErro(texto, textoParaRetry) {
        const row = document.createElement('div');
        row.className = 'msg-row bot msg-error';

        const wrap = document.createElement('div');
        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';
        bubble.textContent = texto;

        if (textoParaRetry) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'retry-btn';
            btn.textContent = 'Tentar novamente';
            btn.addEventListener('click', function () {
                row.remove();
                enviarParaApi(textoParaRetry);
            });

            bubble.appendChild(document.createElement('br'));
            bubble.appendChild(btn);
        }

        wrap.appendChild(bubble);
        row.appendChild(criarIcone('bot'));
        row.appendChild(wrap);
        msgs.appendChild(row);
        scrollBottom();
    }

    function adicionarDigitando() {
        removerDigitando();

        const row = document.createElement('div');
        row.className = 'msg-row bot';
        row.id = 'typing-row';

        const bubble = document.createElement('div');
        bubble.className = 'typing-bubble';
        bubble.innerHTML = '<span></span><span></span><span></span>';

        row.appendChild(criarIcone('bot'));
        row.appendChild(bubble);
        msgs.appendChild(row);
        scrollBottom();
    }

    function removerDigitando() {
        const el = document.getElementById('typing-row');
        if (el) el.remove();
    }

    // ─── Feedback ───
    function adicionarFeedback(wrap, pergunta, resposta) {
        if (!wrap || !pergunta || !resposta) return;

        const acoes = document.createElement('div');
        acoes.className = 'msg-feedback';
        acoes.setAttribute('aria-label', 'Avaliar resposta');

        [
            { rating: 'up', rotulo: 'Resposta útil', icone: SVG_UTIL },
            { rating: 'down', rotulo: 'Resposta não ajudou', icone: SVG_INUTIL }
        ].forEach(function (item) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'msg-feedback-btn';
            btn.dataset.rating = item.rating;
            btn.title = item.rotulo;
            btn.setAttribute('aria-label', item.rotulo);
            btn.innerHTML = item.icone;
            btn.addEventListener('click', function () {
                enviarFeedback(item.rating, pergunta, resposta, acoes);
            });
            acoes.appendChild(btn);
        });

        wrap.appendChild(acoes);
    }

    async function enviarFeedback(rating, pergunta, resposta, acoes) {
        if (acoes.dataset.estado) return;

        acoes.dataset.estado = 'enviando';
        const botoes = acoes.querySelectorAll('button');
        botoes.forEach(function (btn) { btn.disabled = true; });

        const escolhido = acoes.querySelector('[data-rating="' + rating + '"]');

        if (escolhido) escolhido.classList.add('selecionado');

        try {
            const respostaHttp = await fetch(CONFIG.feedbackEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    rating: rating,
                    message: String(pergunta).slice(0, 1000),
                    reply: String(resposta).slice(0, 4000)
                })
            });

            if (!respostaHttp.ok) throw new Error('falha ao registrar');

            acoes.dataset.estado = 'enviado';
            const aviso = document.createElement('span');
            aviso.className = 'msg-feedback-ok';
            aviso.textContent = 'Obrigado pelo feedback!';
            acoes.replaceChildren(aviso);
        } catch (e) {
            delete acoes.dataset.estado;
            botoes.forEach(function (btn) { btn.disabled = false; });

            if (escolhido) escolhido.classList.remove('selecionado');

            acoes.title = 'Não foi possível registrar o feedback.';
        }
    }

    // ─── Status do serviço ───
    async function verificarStatus() {
        if (statusOnline !== null && Date.now() - statusEm < 60000) return;

        statusEm = Date.now();

        try {
            const resposta = await fetch(CONFIG.statusEndpoint, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const payload = await resposta.json();

            statusOnline = !!(payload && payload.success === true && payload.data && payload.data.disponivel === true);
        } catch (e) {
            statusOnline = false;
        }

        aplicarStatus();
    }

    function aplicarStatus() {
        if (statusOnline === null) {
            statusText.textContent = 'Verificando…';
            statusDot.classList.remove('offline');
            return;
        }

        statusText.textContent = statusOnline ? 'Online' : 'Indisponível';
        statusDot.classList.toggle('offline', !statusOnline);
    }

    // ─── Badge ───
    function mostrarBadge() {
        naoLidas++;
        badge.textContent = naoLidas > 9 ? '9+' : String(naoLidas);
        badge.style.display = 'flex';
    }

    function ocultarBadge() {
        badge.style.display = 'none';
    }

    // ─── Helpers ───
    function hora(ts) {
        try {
            return new Date(ts || Date.now()).toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return '';
        }
    }

    function scrollBottom() {
        requestAnimationFrame(function () {
            msgs.scrollTop = msgs.scrollHeight;
        });
    }

    function ajustarTextarea() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }

    function atualizarEstadoEnvio() {
        sendBtn.disabled = isLoading || input.value.trim() === '';
    }

    function escaparHtml(texto) {
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function formatar(texto) {
        return escaparHtml(texto)
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>')
            .replace(/`([^`\n]+)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
    }

    function carregarHistorico() {
        try {
            const bruto = sessionStorage.getItem(CONFIG.storageKey);

            if (!bruto) return [];

            const dados = JSON.parse(bruto);

            if (!Array.isArray(dados)) return [];

            return dados
                .filter(function (m) {
                    return m
                        && (m.role === 'user' || m.role === 'assistant')
                        && typeof m.content === 'string'
                        && m.content.trim() !== '';
                })
                .slice(-40)
                .map(function (m) {
                    return {
                        role: m.role,
                        content: m.content.slice(0, 2000),
                        ts: Number(m.ts) || Date.now()
                    };
                });
        } catch (e) {
            return [];
        }
    }

    function salvarHistorico() {
        try {
            sessionStorage.setItem(CONFIG.storageKey, JSON.stringify(history.slice(-40)));
        } catch (e) {
            // sessionStorage indisponível (modo privado/restrições do navegador)
        }
    }

    // ─── Inicialização ───
    win.inert = true;
    atualizarEstadoEnvio();

    if (history.length === 0) {
        adicionarMensagem('assistant', CONFIG.welcomeMsg, false);
        renderizarSugestoes();
    } else {
        history.forEach(function (m) {
            adicionarMensagem(m.role, m.content, false, m.ts);
        });
    }
})();
/* ========================================= FIM CHATBOT VIVI ===============================================*/

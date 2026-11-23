<?php
$viviAvatar = asset('img/vivi.jpg');
?>
<!-- Botão flutuante -->
<button id="chat-toggle" type="button" aria-label="Abrir assistente Vivi" aria-expanded="false" aria-controls="chat-window">
    <span id="ico-chat"><img src="<?= $viviAvatar ?>" alt="" width="40" height="40"></span>
    <svg class="ico-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
    </svg>
    <span id="chat-badge" role="status" aria-label="Novas mensagens"></span>
</button>

<!-- Janela do chat -->
<div id="chat-window" role="dialog" aria-labelledby="chat-title" aria-hidden="true"
     data-endpoint="/vivi/chat" data-feedback-endpoint="/vivi/feedback" data-status-endpoint="/vivi/status">

    <!-- Header -->
    <div id="chat-header">
        <div class="chat-avatar"><img src="<?= $viviAvatar ?>" alt="" width="36" height="36"></div>
        <div class="chat-header-info">
            <strong id="chat-title">Vivi</strong>
            <span><span class="chat-status-dot" id="chat-status-dot"></span><span id="chat-status-text">Verificando…</span></span>
        </div>
        <div class="chat-header-actions">
            <button id="chat-clear" type="button" aria-label="Iniciar nova conversa" title="Nova conversa">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12a9 9 0 1 0 3-6.7" />
                    <polyline points="3 4 3 9 8 9" />
                </svg>
            </button>
            <button id="chat-header-close" type="button" aria-label="Fechar chat">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mensagens -->
    <div id="chat-messages" role="log" aria-live="polite" aria-relevant="additions" aria-label="Histórico da conversa"></div>

    <!-- Sugestões rápidas -->
    <div id="chat-suggestions" aria-label="Sugestões de perguntas"></div>

    <!-- Input -->
    <form id="chat-form">
        <textarea
            id="chat-input"
            rows="1"
            maxlength="1000"
            placeholder="Digite sua dúvida…"
            aria-label="Mensagem para a Vivi"
            autocomplete="off"></textarea>
        <button id="chat-send" type="submit" aria-label="Enviar mensagem" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="22" y1="2" x2="11" y2="13" />
                <polygon points="22 2 15 22 11 13 2 9 22 2" />
            </svg>
        </button>
    </form>

    <div id="chat-footer">Assistente virtual · respostas geradas por IA podem conter erros</div>
</div>

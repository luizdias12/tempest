<?php

?>
<div class="chat-app">
    <aside class="chat-panel">
        <div class="chat-panel-head">
            <div class="chat-nova">
                <button type="button" class="btn-novo" id="chatBtnNova"><i class="fa-solid fa-plus"></i> Nova conversa</button>
            </div>
            <div class="chat-busca" id="chatBuscaWrap" hidden>
                <input type="text" id="chatBuscaContato" class="chat-input" placeholder="Buscar por nome..." autocomplete="off">
            </div>
            <div class="chat-resultados" id="chatResultados" hidden></div>
        </div>
        <div class="chat-conversas" id="chatConversas"></div>
        <div class="chat-ctxmenu" id="chatCtxMenu" hidden style="display:none">
            <button type="button" data-ctx="limpar" title="Apaga o histórico de mensagens apenas para você"><i class="fa-solid fa-eraser"></i> Limpar conversa</button>
            <button type="button" data-ctx="apagar" title="Remove a conversa da sua lista (o outro lado continua com ela)"><i class="fa-solid fa-trash-can"></i> Apagar conversa</button>
        </div>
    </aside>

    <section class="chat-main">
        <header class="chat-header">
            <span class="chat-titulo" id="chatTitulo">Selecione uma conversa</span>
            <span class="chat-sub" id="chatSubtitulo"></span>
        </header>

        <div class="chat-msgs" id="chatMsgs">
            <p class="chat-empty">Escolha uma conversa ao lado para começar.</p>
        </div>

        <div class="chat-preview" id="chatPreview" hidden></div>

        <div class="chat-emojis" id="chatEmojis" hidden style="display:none"></div>

        <form class="chat-form" id="chatForm">
            <button type="button" class="chat-btn-icon" id="chatBtnAnexo" title="Anexar arquivo"><i class="fa-solid fa-paperclip"></i></button>
            <button type="button" class="chat-btn-icon" id="chatBtnEmoji" title="Emojis">&#9786;</button>
            <textarea id="chatTexto" class="chat-input" rows="1" placeholder="Escreva sua mensagem..." maxlength="2000"></textarea>
            <button type="submit" class="btn-novo btn-enviar" id="chatEnviar"><i class="fa-solid fa-paper-plane"></i></button>
        </form>

        <input type="file" id="chatAnexoInput" hidden>
    </section>
</div>

<script>
    window.CHAT_EU = {
        cpf: '<?= htmlspecialchars($chatUser['cpf'] ?? '') ?>',
        secao: '<?= htmlspecialchars($chatUser['secao'] ?? '') ?>',
        nome: '<?= htmlspecialchars($chatUser['nome'] ?? '') ?>'
    };
</script>
<script src="<?= asset('js/chat.js') ?>"></script>
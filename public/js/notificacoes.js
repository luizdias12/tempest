(function() {
    'use strict';

    var badge = document.getElementById('chatNotifBadge');

    if (!badge) return;

    var chatLink = badge.closest('a[href]');
    var API = '/chat/notificar';
    var INTERVALO = 15000;
    var COOLDOWN = 60000;

    var CHAVE_AVISO = 'notif_aviso_denied';
    var AVISO_DIAS = 30;

    var totalAtual = 0;
    var ultimaChave = null;
    var ultimaNotif = 0;
    var pediuPermissao = false;
    var parado = false;
    var toastEl = null;
    var timeoutToast = null;

    function naPaginaChat() {
        return window.location.pathname.indexOf('/chat') === 0;
    }

    function atualizarBadge(total) {
        badge.textContent = total > 99 ? '99+' : total;
        badge.hidden = total <= 0;
        if (chatLink) chatLink.classList.toggle('com-badge', total > 0);
    }

    function dispararNotificacao(data) {
        if (!(window.Notification && Notification.permission === 'granted')) return;
        if (!data.ultima) return;
        if (naPaginaChat()) return;

        var agora = Date.now();
        if (agora - ultimaNotif < COOLDOWN) return;
        ultimaNotif = agora;

        var u = data.ultima;
        var corpo = u.remetente + ': ' + (u.texto || (u.tipo === 'imagem' ? '[Imagem]' : '[Mensagem]'));

        try {
            var n = new Notification('💬 ' + u.titulo, {
                body: corpo,
                tag: 'chat-' + u.conversa_id
            });

            n.onclick = function() {
                window.focus();
                window.location.href = '/chat';
                n.close();
            };
        } catch (e) {
            /* ignora falha de criação */
        }
    }

    function removerToast() {
        if (timeoutToast) {
            clearTimeout(timeoutToast);
            timeoutToast = null;
        }
        if (!toastEl) return;

        var node = toastEl;
        toastEl = null;

        if (node.classList.contains('toast-out')) return;
        node.classList.add('toast-out');
        setTimeout(function() {
            if (node.parentNode) node.parentNode.removeChild(node);
        }, 300);
    }

    function avisoJaMostrado() {
        try {
            var t = parseInt(localStorage.getItem(CHAVE_AVISO) || '0', 10);
            return !isNaN(t) && (Date.now() - t) < AVISO_DIAS * 24 * 60 * 60 * 1000;
        } catch (e) {
            return false;
        }
    }

    function marcarAvisoMostrado() {
        try {
            localStorage.setItem(CHAVE_AVISO, String(Date.now()));
        } catch (e) {
            /* ignora */
        }
    }

    function limparAvisoMostrado() {
        try {
            localStorage.removeItem(CHAVE_AVISO);
        } catch (e) {
            /* ignora */
        }
    }

    function mostrarAviso() {
        if (!(window.Notification && Notification.permission === 'denied')) return;
        if (toastEl || avisoJaMostrado()) return;

        marcarAvisoMostrado();

        var container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.className = 'toast toast-warning';
        toast.setAttribute('role', 'status');

        var span = document.createElement('span');
        span.className = 'toast-message';
        span.textContent = 'Notificações estão bloqueadas neste navegador. Para ativar: cadeado na URL → Configurações do site → Notificações → Permitir, e em seguida recarregue a página.';

        var close = document.createElement('button');
        close.className = 'toast-close';
        close.type = 'button';
        close.innerHTML = '&times;';
        close.addEventListener('click', removerToast);

        toast.appendChild(span);
        toast.appendChild(close);
        container.appendChild(toast);
        toastEl = toast;

        timeoutToast = setTimeout(removerToast, 8000);
    }

    function verificarPermissao() {
        if (!(window.Notification && Notification.permission)) return;

        if (Notification.permission === 'granted' || Notification.permission === 'default') {
            if (Notification.permission === 'granted') {
                limparAvisoMostrado();
                removerToast();
            }
            return;
        }

        if (document.visibilityState === 'visible') {
            mostrarAviso();
        }
    }

    function checar() {
        if (parado) return;

        fetch(API, { headers: { 'Accept': 'application/json' } })
            .then(function(r) {
                if (r.status === 401) {
                    parado = true;
                    throw new Error('nao autenticado');
                }
                if (!r.ok) throw new Error('http ' + r.status);
                return r.json();
            })
            .then(function(j) {
                if (!j || !j.success) return;

                var d = j.data || {};
                var total = parseInt(d.total || 0, 10);

                atualizarBadge(total);

                var u = d.ultima || null;
                var chave = u ? (u.conversa_id + '|' + (u.criado_em || '')) : null;

                if (totalAtual > 0 && total > totalAtual && chave && chave !== ultimaChave) {
                    dispararNotificacao(d);
                }

                totalAtual = total;
                ultimaChave = chave;
            })
            .catch(function() {});
    }

    function pedirPermissao() {
        if (!(window.Notification && Notification.permission === 'default')) return;
        if (pediuPermissao) return;
        pediuPermissao = true;

        try {
            Notification.requestPermission().then(function(result) {
                if (result === 'granted') {
                    limparAvisoMostrado();
                    removerToast();
                }
                if (result === 'denied') {
                    verificarPermissao();
                }
            });
        } catch (e) {
            /* ignora */
        }
    }

    function iniciar() {
        verificarPermissao();
        checar();
        setInterval(checar, INTERVALO);

        document.addEventListener('pointerdown', pedirPermissao, { once: true });
        document.addEventListener('click', pedirPermissao, { once: true });
    }

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            verificarPermissao();
            checar();
        }
    });

    window.addEventListener('focus', verificarPermissao);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
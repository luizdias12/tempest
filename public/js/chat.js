(function() {
    'use strict';

    var EU = window.CHAT_EU || { cpf: '', nome: '' };
    var API = '/chat/';

    var REACOES = ['\uD83D\uDC4D', '\u2764\uFE0F', '\uD83D\uDE02', '\uD83D\uDE2E', '\uD83D\uDE22', '\uD83D\uDE4F'];

    var EMOJIS = [
        '\uD83D\uDE00', '\uD83D\uDE01', '\uD83D\uDE02', '\uD83E\uDD23', '\uD83D\uDE0A', '\uD83D\uDE07',
        '\uD83D\uDE09', '\uD83D\uDE0D', '\uD83D\uDE18', '\uD83E\uDD70', '\uD83D\uDE0E', '\uD83E\uDD29',
        '\uD83E\uDD73', '\uD83D\uDE0F', '\uD83D\uDE1C', '\uD83E\uDD2A', '\uD83D\uDE05', '\uD83D\uDE43',
        '\uD83E\uDD72', '\uD83D\uDE22', '\uD83D\uDE2D', '\uD83D\uDE21', '\uD83E\uDD2C', '\uD83D\uDE31',
        '\uD83E\uDD2F', '\uD83E\uDD75', '\uD83D\uDE34', '\uD83E\uDD14', '\uD83D\uDE10', '\uD83E\uDD17',
        '\uD83D\uDEAD', '\uD83D\uDC40', '\uD83D\uDC4D', '\uD83D\uDC4E', '\uD83D\uDC4F', '\uD83D\uDE4F',
        '\uD83E\uDD1D', '\uD83D\uDCAA', '\u270C\uFE0F', '\uD83D\uDC4C', '\uD83E\uDD19', '\uD83D\uDC4B',
        '\u2764\uFE0F', '\uD83D\uDC94', '\uD83D\uDC99', '\uD83D\uDC9B', '\uD83D\uDC9A', '\uD83D\uDC98',
        '\uD83E\uDD4A', '\uD83D\uDC49', '\u2728', '\uD83C\uDF89', '\uD83C\uDF82', '\uD83C\uDF81',
        '\u2615', '\uD83D\uDC8C', '\u2600\uFE0F', '\uD83D\uDDFF'
    ];

    var estado = {
        conversas: [],
        aberta: null,
        apos: 0,
        renderizadas: {},
        source: null,
        ultimoMarcado: 0,
        anexo: null,
        ultDig: 0,
        digTimer: null,
        digitandoNome: null,
        subBase: ''
    };

    var els = {};

    var ctx = { id: null };

    /* ===== UTILITÁRIOS ===== */

    function getJson(url, opts) {
        return fetch(url, opts).then(function(r) {
            if (!r.ok) {
                return r.json().then(function(j) { throw j; });
            }
            return r.json();
        });
    }

    function check(res) {
        if (!res || !res.success) {
            var msg = res && res.error ? res.error.message : 'Erro na requisição.';
            toast('error', msg);
            throw new Error(msg);
        }
        return res.data;
    }

    function get(url) {
        return getJson(url).then(check);
    }

    function enviar(method, url, data) {
        return getJson(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data || {})
        }).then(check);
    }

    function toast(tipo, msg) {
        if (typeof mostrarToast === 'function') {
            mostrarToast(tipo, msg);
        } else if (typeof console !== 'undefined') {
            console.log('[' + tipo + '] ' + msg);
        }
    }

    function esc(s) {
        var div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
    }

    function debounce(fn, ms) {
        var t;
        return function() {
            var args = arguments;
            clearTimeout(t);
            t = setTimeout(function() { fn.apply(null, args); }, ms || 300);
        };
    }

    function fmtHora(dt) {
        if (!dt) return '';
        var d = new Date(String(dt).replace(' ', 'T'));
        if (isNaN(d.getTime())) return '';
        var hoje = new Date();
        if (d.toDateString() === hoje.toDateString()) {
            return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        }
        return String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0');
    }

    function scrollBottom() {
        var box = els.msgs;
        if (box) box.scrollTop = box.scrollHeight;
    }

    function primeiro(raiz, sel) {
        return raiz.querySelector(sel);
    }

    /* ===== CONVERSAS ===== */

    function carregarConversas() {
        return get(API + 'conversas').then(function(lista) {
            estado.conversas = lista;
            if (estado.aberta) {
                var aberta = getConversa(estado.aberta);
                if (aberta) aberta.nao_lidas = 0;
            }
            renderLista();
            atualizarTitulo();
        });
    }

    function renderLista() {
        var box = els.conversas;
        box.innerHTML = '';

        if (!estado.conversas.length) {
            box.innerHTML = '<p class="chat-vazio">Nenhuma conversa ainda. Clique em <strong>Nova conversa</strong>.</p>';
            return;
        }

        estado.conversas.forEach(function(c) {
            var item = document.createElement('div');
            item.className = 'chat-conv' + (c.id === estado.aberta ? ' ativa' : '');
            item.setAttribute('data-id', c.id);

            item.innerHTML =
                '<div class="chat-conv-info">' +
                    '<span class="chat-conv-nome">' + esc(c.titulo) + '</span>' +
                    '<span class="chat-conv-preview">' + esc(c.ultima_msg || 'Sem mensagens') + '</span>' +
                '</div>' +
                '<div class="chat-conv-meta">' +
                    '<span class="chat-conv-tempo">' + esc(fmtHora(c.ultima_em)) + '</span>' +
                    (c.nao_lidas > 0 ? '<span class="chat-conv-badge">' + c.nao_lidas + '</span>' : '') +
                '</div>';

            item.addEventListener('click', function() { abrirConversa(Number(c.id)); });
            item.addEventListener('contextmenu', function(ev) {
                ev.preventDefault();
                abrirCtxMenu(Number(c.id), ev.clientX, ev.clientY);
            });
            box.appendChild(item);
        });
    }

    function atualizarTitulo() {
        var total = estado.conversas.reduce(function(s, c) { return s + (c.nao_lidas || 0); }, 0);
        var base = 'Chat' + (total > 0 ? ' (' + total + ')' : '');
        document.title = base;
    }

    /* ===== MENSAGENS ===== */

    function abrirConversa(id) {
        if (!id) return;
        estado.aberta = id;
        estado.apos = 0;
        estado.ultimoMarcado = 0;
        estado.renderizadas = {};
        estado.anexo = null;
        if (els.anexoInput) els.anexoInput.value = '';
        atualizarPreview();
        pararDigitando();

        fecharStream();
        renderLista();
        atualizarHeader();

        els.msgs.innerHTML = '<p class="chat-empty">Carregando mensagens...</p>';
        els.form.hidden = false;
        els.texto.disabled = false;

        atualizarThread().then(function() {
            if (estado.aberta === id) {
                abrirStream(id);
            }
            carregarConversas();
        });
    }

    function atualizarThread() {
        if (!estado.aberta) return Promise.resolve();

        var id = estado.aberta;
        estado.apos = 0;
        estado.renderizadas = {};
        estado.ultimoMarcado = 0;

        return get(API + 'mensagens/' + id).then(function(rows) {
            if (estado.aberta !== id) return;

            els.msgs.innerHTML = '';
            if (!rows.length) {
                els.msgs.innerHTML = '<p class="chat-empty">Sem mensagens. Que tal começar? :)</p>';
            } else {
                appendMensagens(rows, true);
            }

            marcarLido(id, rows.length ? rows[rows.length - 1].id : 0);
        }).catch(function() {});
    }

    function atualizarHeader() {
        var c = getConversa(estado.aberta);
        els.titulo.textContent = c ? c.titulo : 'Selecione uma conversa';
        estado.subBase = c && c.subtitulo ? c.subtitulo : '';
        if (!estado.digitandoNome) {
            els.subtitulo.textContent = estado.subBase;
        }
    }

    function getConversa(id) {
        for (var i = 0; i < estado.conversas.length; i++) {
            if (estado.conversas[i].id === id) return estado.conversas[i];
        }
        return null;
    }

    function montarBalao(m, euMesmo) {
        var autor = '<span class="chat-msg-autor">' + esc(m.remetente_nome || 'Funcionário') + '</span>';
        var hora = '<span class="chat-msg-hora">' + esc(fmtHora(m.criado_em)) + '</span>';
        var editado = m.editado_em ? '<span class="chat-msg-editado">editado</span>' : '';
        var recibo = euMesmo
            ? '<span class="chat-msg-status' + (m.lida ? ' lida' : '') + '">' + (m.lida ? '\u2713\u2713' : '\u2713') + '</span>'
            : '';
        var acoes = euMesmo && m.apagado !== 1
            ? '<span class="chat-msg-acoes">' +
                  '<button type="button" class="chat-bt-mini" data-editar title="Editar">\u270f</button>' +
                  '<button type="button" class="chat-bt-mini" data-excluir title="Apagar">\uD83D\uDDD1\uFE0F</button>' +
              '</span>'
            : '';

        var corpo;

        if (m.apagado) {
            corpo = '<div class="chat-msg-apagada">\uD83D\uDDD1\uFE0F Mensagem apagada</div>';
        } else if (m.tipo === 'imagem' && m.anexo) {
            corpo = '<a class="chat-msg-anexo-img" href="' + esc(m.anexo) + '" target="_blank" rel="noopener">' +
                        '<img src="' + esc(m.anexo) + '" alt="' + esc(m.anexo_nome || 'imagem') + '" loading="lazy">' +
                    '</a>' +
                    (m.texto ? '<div class="chat-msg-texto">' + esc(m.texto).replace(/\n/g, '<br>') + '</div>' : '');
        } else if (m.tipo === 'arquivo' && m.anexo) {
            corpo = '<a class="chat-msg-anexo-arq" href="' + esc(m.anexo) + '" target="_blank" rel="noopener">' +
                        '<i class="fa-solid fa-file"></i><span>' + esc(m.anexo_nome || 'Arquivo') + '</span>' +
                    '</a>' +
                    (m.texto ? '<div class="chat-msg-texto">' + esc(m.texto).replace(/\n/g, '<br>') + '</div>' : '');
        } else {
            corpo = '<div class="chat-msg-texto">' + esc(m.texto).replace(/\n/g, '<br>') + '</div>';
        }

        var reacoes = m.reacoes && m.reacoes.length ? reacoesHtml(m) : '';

        var quick = '<div class="chat-rea-wrap">' +
                        '<button type="button" class="chat-rea-toggle" data-rea-toggle title="Reagir">\uD83D\uDE00</button>' +
                        '<div class="chat-rea-bar" style="display: none;">' + REACOES.map(function(e) {
                            return '<button type="button" class="chat-rea-btn" data-reagir data-emoji="' + esc(e) + '">' + esc(e) + '</button>';
                        }).join('') + '</div>' +
                    '</div>';

        return quick +
            '<div class="chat-balao">' +
                '<div class="chat-msg-cab">' +
                    (euMesmo ? '<span class="chat-msg-autor">Você</span>' : autor) +
                    '<span class="chat-msg-tempo">' + editado + hora + recibo + acoes + '</span>' +
                '</div>' +
                corpo +
                reacoes +
            '</div>';
    }

    function reacoesHtml(m) {
        return '<div class="chat-rea-chips">' + m.reacoes.map(function(r) {
            var meu = r.cpfs && r.cpfs.indexOf(EU.cpf) >= 0;
            return '<span class="chat-rea-chip' + (meu ? ' mine' : '') + '" data-reagir data-emoji="' + esc(r.emoji) +
                '" title="' + esc((r.cpfs || []).join(', ')) + '">' + esc(r.emoji) + ' ' + r.total + '</span>';
        }).join('') + '</div>';
    }

    function renderMensagem(m) {
        var id = Number(m.id);
        var euMesmo = String(m.remetente) === String(EU.cpf);
        var div = document.createElement('div');
        div.className = 'chat-msg' + (euMesmo ? ' mine' : '');
        div.setAttribute('data-id', id);
        div.innerHTML = montarBalao(m, euMesmo);
        els.msgs.appendChild(div);

        var ed = primeiro(div, '[data-editar]');
        if (ed) {
            ed.addEventListener('click', function() { iniciarEdicao(id, div, m); });
        }

        var ex = primeiro(div, '[data-excluir]');
        if (ex) {
            ex.addEventListener('click', function() {
                if (window.confirm('Apagar esta mensagem?')) excluirMensagem(id);
            });
        }

        var tg = primeiro(div, '[data-rea-toggle]');
        if (tg) {
            tg.addEventListener('click', function() {
                var bar = primeiro(div, '.chat-rea-bar');
                if (!bar) return;
                var visivel = bar.style.display !== 'none';
                bar.style.display = visivel ? 'none' : 'flex';
            });
        }

        Array.prototype.forEach.call(div.querySelectorAll('[data-reagir]'), function(b) {
            b.addEventListener('click', function() {
                var emoji = b.getAttribute('data-emoji');
                var bar = primeiro(div, '.chat-rea-bar');
                if (bar && bar.style.display === 'flex') { bar.style.display = 'none'; }
                reagir(id, emoji);
            });
        });
    }

    function appendMensagens(rows, scroll) {
        var vazio = primeiro(els.msgs, '.chat-empty');
        if (vazio) vazio.remove();

        var temNovas = false;
        rows.forEach(function(m) {
            var id = Number(m.id);
            if (estado.renderizadas[id]) return;
            estado.renderizadas[id] = true;
            temNovas = true;
            renderMensagem(m);
        });

        if (temNovas && estado.aberta && abaVisivel()) {
            var conv = getConversa(estado.aberta);
            if (conv) conv.nao_lidas = 0;
        }

        if (scroll) scrollBottom();
    }

    function abaVisivel() {
        return document.visibilityState === 'visible';
    }

    function marcarLido(id, mensagemId) {
        var p = Promise.resolve();
        if (!id || !mensagemId) return p;
        if (!abaVisivel()) return p;
        if (mensagemId <= estado.ultimoMarcado) return p;
        estado.ultimoMarcado = mensagemId;
        return enviar('POST', API + 'marcar', {
            conversa_id: id,
            mensagem_id: mensagemId
        }).catch(function() {});
    }

    /* ===== SSE ===== */

    function abrirStream(id) {
        if (!window.EventSource) {
            iniciarFallback(id);
            return;
        }

        var source = new EventSource(API + 'stream/' + id);
        estado.source = source;

        function aoEvento(ev) {
            var data = {};
            try {
                data = JSON.parse(ev.data || '{}');
            } catch (e) {}

            if (Number(data.conversa_id) !== id) return;

            if (ev.type === 'novidade') {
                if (data.mensagens && data.mensagens.length) {
                    var msgs = data.mensagens;
                    appendMensagens(msgs, true);
                    marcarLido(id, data.apos || (msgs[msgs.length - 1] || {}).id).then(carregarConversas);
                } else {
                    carregarConversas();
                }
                return;
            }

            if (ev.type === 'digitando') {
                if (data.cpf && data.cpf !== EU.cpf && data.nome) {
                    mostrarDigitando(data.nome);
                }
                return;
            }

            if (ev.type === 'leitura') {
                if (data.cpf === EU.cpf) return;
                atualizarThread();
                return;
            }

            if (ev.type === 'edicao' || ev.type === 'exclusao' || ev.type === 'reacao') {
                atualizarThread().then(carregarConversas);
                return;
            }

            if (ev.type === 'ping') {
                carregarConversas();
            }
        }

        ['novidade', 'digitando', 'leitura', 'edicao', 'exclusao', 'reacao', 'ping'].forEach(function(nome) {
            source.addEventListener(nome, aoEvento);
        });

        source.onerror = function() {
            // EventSource reconecta sozinho; nada a fazer aqui
        };
    }

    function fecharStream() {
        if (estado.source) {
            estado.source.close();
            estado.source = null;
        }
        if (estado.fallbackTimer) {
            clearInterval(estado.fallbackTimer);
            estado.fallbackTimer = null;
        }
    }

    function iniciarFallback(id) {
        estado.fallbackTimer = setInterval(function() {
            if (estado.aberta !== id) return;
            atualizarThread().then(carregarConversas).catch(function() {});
        }, 5000);
    }

    function mostrarDigitando(nome) {
        estado.digitandoNome = nome;
        els.subtitulo.textContent = nome + ' digitando...';
        clearTimeout(estado.digTimer);
        estado.digTimer = setTimeout(pararDigitando, 3000);
    }

    function pararDigitando() {
        estado.digitandoNome = null;
        if (els.subtitulo) els.subtitulo.textContent = estado.subBase || '';
    }

    /* ===== ENVIO ===== */

    function enviarMensagem() {
        var texto = els.texto.value.trim();
        var arquivo = estado.anexo;
        if ((!texto && !arquivo) || !estado.aberta) return;

        els.enviar.disabled = true;

        var p;

        if (arquivo) {
            var fd = new FormData();
            fd.append('conversa_id', String(estado.aberta));
            if (texto) fd.append('texto', texto);
            fd.append('anexo', arquivo);
            p = getJson(API + 'anexo', { method: 'POST', body: fd }).then(check);
        } else {
            p = enviar('POST', API + 'enviar', {
                conversa_id: estado.aberta,
                texto: texto
            });
        }

        p.then(function(msg) {
            appendMensagens([msg], true);
            carregarConversas();
        }).catch(function() {}).finally(function() {
            estado.anexo = null;
            if (els.anexoInput) els.anexoInput.value = '';
            atualizarPreview();
            els.texto.value = '';
            els.texto.style.height = '';
            els.enviar.disabled = false;
            els.texto.focus();
        });
    }

    /* ===== EDIÇÃO / EXCLUSÃO / REAÇÕES ===== */

    function atualizarThreadEditavel() {
        return atualizarThread().then(carregarConversas);
    }

    function iniciarEdicao(id, div, m) {
        if (m.tipo !== 'texto' || m.apagado) return;

        var textoDiv = primeiro(div, '.chat-msg-texto');
        if (!textoDiv) return;

        var ta = document.createElement('textarea');
        ta.className = 'chat-input chat-edita-input';
        ta.value = m.texto;

        var salvar = document.createElement('button');
        salvar.type = 'button';
        salvar.className = 'btn-novo';
        salvar.textContent = 'Salvar';

        var canc = document.createElement('button');
        canc.type = 'button';
        canc.className = 'btn-novo btn-novo-secondary';
        canc.textContent = 'Cancelar';

        function submeter() {
            var texto = ta.value.trim();
            if (!texto) return;
            enviar('POST', API + 'editar', {
                conversa_id: estado.aberta,
                mensagem_id: id,
                texto: texto
            }).then(atualizarThreadEditavel).catch(function() {});
        }

        salvar.addEventListener('click', submeter);
        canc.addEventListener('click', function() { atualizarThread(); });

        ta.addEventListener('keydown', function(ev) {
            if (ev.key === 'Enter' && !ev.shiftKey) {
                ev.preventDefault();
                submeter();
            }
            if (ev.key === 'Escape') {
                atualizarThread();
            }
        });

        var wrap = document.createElement('div');
        wrap.className = 'chat-edita';
        wrap.appendChild(ta);
        wrap.appendChild(canc);
        wrap.appendChild(salvar);

        textoDiv.replaceWith(wrap);
        ta.focus();
        ta.setSelectionRange(ta.value.length, ta.value.length);
    }

    function excluirMensagem(id) {
        enviar('POST', API + 'excluir', {
            conversa_id: estado.aberta,
            mensagem_id: id
        }).then(atualizarThreadEditavel).catch(function() {});
    }

    function reagir(mensagemId, emoji) {
        if (!estado.aberta) return;

        enviar('POST', API + 'reacao', {
            conversa_id: estado.aberta,
            mensagem_id: mensagemId,
            emoji: emoji
        }).then(atualizarThread).catch(function() {});
    }

    /* ===== ANEXOS ===== */

    function aoSelecionarAnexo() {
        var f = els.anexoInput.files && els.anexoInput.files[0];

        if (!f) {
            estado.anexo = null;
            atualizarPreview();
            return;
        }

        if (f.size > 5242880) {
            toast('error', 'Arquivo excede o limite de 5 MB.');
            els.anexoInput.value = '';
            estado.anexo = null;
            atualizarPreview();
            return;
        }

        estado.anexo = f;
        atualizarPreview();
    }

    function atualizarPreview() {
        var box = els.preview;
        if (!box) return;

        box.innerHTML = '';
        if (!estado.anexo) {
            box.hidden = true;
            return;
        }

        var f = estado.anexo;
        var inner = '<div class="chat-preview-item">';

        if (f.type && String(f.type).indexOf('image/') === 0) {
            inner += '<img src="' + URL.createObjectURL(f) + '" alt="">';
        } else {
            inner += '<i class="fa-solid fa-file"></i>';
        }

        inner += '<span class="chat-preview-nome">' + esc(f.name) + '</span>' +
                 '<button type="button" class="chat-preview-remover" data-preview-remover title="Remover">\u00d7</button>' +
                 '</div>';

        box.innerHTML = inner;
        box.hidden = false;

        var rem = primeiro(box, '[data-preview-remover]');
        if (rem) {
            rem.addEventListener('click', function() {
                estado.anexo = null;
                els.anexoInput.value = '';
                atualizarPreview();
            });
        }
    }

    /* ===== EMOJIS ===== */

    function montarEmojis() {
        var box = els.emojis;
        if (!box) return;

        box.innerHTML = EMOJIS.map(function(e) {
            return '<button type="button" class="chat-emoji-btn" data-emoji="' + esc(e) + '">' + esc(e) + '</button>';
        }).join('');
    }

    function inserirEmoji(emoji) {
        var ta = els.texto;
        if (!ta) return;

        var ini = ta.selectionStart == null ? ta.value.length : ta.selectionStart;
        var fim = ta.selectionEnd == null ? ini : ta.selectionEnd;

        ta.value = ta.value.slice(0, ini) + emoji + ta.value.slice(fim);
        ta.focus();
        ta.selectionStart = ta.selectionEnd = ini + emoji.length;
        ta.dispatchEvent(new Event('input'));
    }

    /* ===== NOVA CONVERSA ===== */

    function abrirBusca() {
        els.buscaWrap.hidden = false;
        els.resultados.hidden = true;
        els.buscaContato.value = '';
        els.resultados.innerHTML = '';
        els.buscaContato.focus();
    }

    function fecharBusca() {
        els.buscaWrap.hidden = true;
        els.resultados.hidden = true;
        els.resultados.innerHTML = '';
    }

    function buscarContatos(termo) {
        if (termo.length < 2) {
            els.resultados.hidden = true;
            els.resultados.innerHTML = '';
            return;
        }
        get(API + 'contatos?q=' + encodeURIComponent(termo)).then(function(rows) {
            var box = els.resultados;
            box.innerHTML = '';
            if (!rows.length) {
                box.innerHTML = '<p class="chat-vazio">Nenhum contato encontrado.</p>';
            }
            rows.forEach(function(c) {
                var meta = c.setor
                    ? (c.setor + (c.filial ? ' \u00b7 ' + c.filial : ''))
                    : (c.filial || ('Filial ' + c.codfilial));
                var item = document.createElement('div');
                item.className = 'chat-contato';
                item.innerHTML =
                    '<div class="chat-contato-avatar"><i class="fa-solid fa-user"></i></div>' +
                    '<div class="chat-contato-info">' +
                        '<span class="chat-contato-nome">' + esc(c.nome) + '</span>' +
                        '<span class="chat-contato-meta">' + esc(meta) + '</span>' +
                    '</div>';

                item.addEventListener('click', function() {
                    iniciarConversa(c.cpf);
                });
                box.appendChild(item);
            });
            box.hidden = false;
        }).catch(function() {});
    }

    function iniciarConversa(cpf) {
        enviar('POST', API + 'nova', { contato_cpf: cpf }).then(function(res) {
            fecharBusca();
            return carregarConversas().then(function() {
                abrirConversa(Number(res.conversa_id));
            });
        });
    }

    /* ===== MENU DE CONTEXTO ===== */

    function abrirCtxMenu(conversaId, x, y) {
        if (!els.ctxMenu) return;

        els.ctxMenu.style.display = 'block';
        els.ctxMenu.hidden = false;

        var w = els.ctxMenu.offsetWidth;
        var h = els.ctxMenu.offsetHeight;
        var panel = els.panel ? els.panel.getBoundingClientRect() : null;

        var left = panel ? x - panel.left : x;
        var top = panel ? y - panel.top : y;

        left = Math.max(8, Math.min(left, (panel ? panel.width : window.innerWidth) - w - 8));
        top = Math.max(8, Math.min(top, (panel ? panel.height : window.innerHeight) - h - 8));

        els.ctxMenu.style.left = left + 'px';
        els.ctxMenu.style.top = top + 'px';

        ctx.id = conversaId;
    }

    function fecharCtxMenu() {
        if (!els.ctxMenu) return;
        els.ctxMenu.style.display = 'none';
        els.ctxMenu.hidden = true;
        ctx.id = null;
    }

    function limparConversa() {
        if (!ctx.id) return;

        var id = ctx.id;

        if (!window.confirm('Limpar o histórico desta conversa para você? O outro participante continua vendo as mensagens.')) {
            return;
        }

        enviar('POST', API + 'limpar', { conversa_id: id }).then(function() {
            toast('success', 'Histórico limpo.');

            if (estado.aberta === id) {
                atualizarThread().then(function() {
                    if (estado.aberta === id) abrirStream(id);
                    carregarConversas();
                });
            } else {
                carregarConversas();
            }
        }).finally(function() {
            fecharCtxMenu();
        });
    }

    function apagarConversa() {
        if (!ctx.id) return;

        var id = ctx.id;

        if (!window.confirm('Apagar esta conversa da sua lista?')) {
            return;
        }

        enviar('POST', API + 'apagar', { conversa_id: id }).then(function() {
            toast('success', 'Conversa removida.');

            fecharStream();

            if (estado.aberta === id) {
                estado.aberta = null;
                fecharCtxMenu();
                carregarConversas().then(function() {
                    atualizarHeader();
                    els.msgs.innerHTML = '<p class="chat-empty">Escolha uma conversa ao lado para começar.</p>';
                    els.form.hidden = true;
                    els.texto.disabled = true;
                });
            } else {
                carregarConversas();
            }
        }).finally(function() {
            fecharCtxMenu();
        });
    }

    /* ===== INIT ===== */

    document.addEventListener('DOMContentLoaded', function() {
        els.conversas = document.getElementById('chatConversas');
        els.msgs = document.getElementById('chatMsgs');
        els.titulo = document.getElementById('chatTitulo');
        els.subtitulo = document.getElementById('chatSubtitulo');
        els.form = document.getElementById('chatForm');
        els.texto = document.getElementById('chatTexto');
        els.enviar = document.getElementById('chatEnviar');
        els.btnNova = document.getElementById('chatBtnNova');
        els.buscaWrap = document.getElementById('chatBuscaWrap');
        els.buscaContato = document.getElementById('chatBuscaContato');
        els.resultados = document.getElementById('chatResultados');
        els.btnAnexo = document.getElementById('chatBtnAnexo');
        els.btnEmoji = document.getElementById('chatBtnEmoji');
        els.anexoInput = document.getElementById('chatAnexoInput');
        els.emojis = document.getElementById('chatEmojis');
        els.preview = document.getElementById('chatPreview');
        els.ctxMenu = document.getElementById('chatCtxMenu');
        els.panel = els.ctxMenu ? els.ctxMenu.parentElement : null;

        if (!els.conversas || !els.msgs) return;

        els.form.addEventListener('submit', function(e) {
            e.preventDefault();
            enviarMensagem();
        });

        els.texto.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                enviarMensagem();
            }
        });

        els.texto.addEventListener('input', function() {
            this.style.height = '';
            this.style.height = Math.min(this.scrollHeight, 140) + 'px';

            if (estado.aberta && this.value.trim()) {
                var agora = Date.now();
                if (agora - (estado.ultDig || 0) > 2000) {
                    estado.ultDig = agora;
                    enviar('POST', API + 'digitando', {
                        conversa_id: estado.aberta
                    }).catch(function() {});
                }
            }
        });

        els.btnNova.addEventListener('click', function() {
            if (els.buscaWrap.hidden) {
                abrirBusca();
            } else {
                fecharBusca();
            }
        });

        els.buscaContato.addEventListener('input', debounce(function() {
            buscarContatos(els.buscaContato.value.trim());
        }, 300));

        if (els.btnAnexo) {
            els.btnAnexo.addEventListener('click', function() {
                els.anexoInput.click();
            });
        }

        if (els.anexoInput) {
            els.anexoInput.addEventListener('change', aoSelecionarAnexo);
        }

        if (els.btnEmoji && els.emojis) {
            montarEmojis();

            els.btnEmoji.addEventListener('click', function(ev) {
                ev.stopPropagation();
                var visivel = els.emojis.style.display !== 'none';
                els.emojis.style.display = visivel ? 'none' : 'grid';
                els.emojis.hidden = visivel;
            });

            els.emojis.addEventListener('click', function(ev) {
                var b = ev.target.closest('[data-emoji]');
                if (b) inserirEmoji(b.getAttribute('data-emoji'));
            });

            document.addEventListener('click', function(ev) {
                var visivel = els.emojis.style.display !== 'none';
                if (visivel &&
                    !els.emojis.contains(ev.target) &&
                    !els.btnEmoji.contains(ev.target)) {
                    els.emojis.style.display = 'none';
                    els.emojis.hidden = true;
                }
            });
        }

        if (els.ctxMenu) {
            els.ctxMenu.addEventListener('click', function(ev) {
                var b = ev.target.closest('[data-ctx]');
                if (!b) return;
                var acao = b.getAttribute('data-ctx');
                if (acao === 'limpar') limparConversa();
                else if (acao === 'apagar') apagarConversa();
            });

            document.addEventListener('click', function(ev) {
                if (!els.ctxMenu.hidden && !els.ctxMenu.contains(ev.target)) {
                    fecharCtxMenu();
                }
            });

            document.addEventListener('keydown', function(ev) {
                if (ev.key === 'Escape') fecharCtxMenu();
            });

            window.addEventListener('blur', function() {
                fecharCtxMenu();
            });
        }

        document.addEventListener('visibilitychange', function() {
            if (!abaVisivel() || !estado.aberta) return;
            atualizarThread().then(carregarConversas);
        });

        carregarConversas();
    });
})();
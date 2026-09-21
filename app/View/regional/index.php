<?php

use App\Service\AuthService;

?>

<div class="filter-bar">
    <div class="region-tabs">
        <button type="button" class="region-tab active" id="consulta" onclick="mostrarAba(this, 'consulta')"><i class="fa-solid fa-magnifying-glass"></i> Consulta</button>
        <?php
            if (AuthService::canManageCarousel() && !AuthService::isExterno()): ?>
            <button type="button" class="region-tab region-tab--gestao" id="gestao" onclick="mostrarAba(this, 'gestao')"><i class="fa-solid fa-gear"></i> Gestão</button>
        <?php endif; ?>
    </div>
    <div class="region-toolbar">
        
    </div>
</div>

<!-- Stats -->
<div class="region-stats" id="statsArea">
    <div class="region-stat-card">
        <div class="region-stat-icon region-stat-icon--regional"><i class="fa-solid fa-building"></i></div>
        <div class="region-stat-info">
            <span class="region-stat-num" id="statRegionais">-</span>
            <span class="region-stat-label">Regionais</span>
        </div>
    </div>
</div>

<!-- ==================== CONSULTA ==================== -->
<div class="region-tab-wrap header-bar tab" id="consultaArea">
    <h2 class="header_title">Consulta de Regionais</h2>
    <div class="region-filtros">
        <label for="fRegional">Regional:</label>
        <select class="region-select" id="fRegional">
            <option value="">Todas</option>
        </select>
        <input type="text" id="fBusca" class="region-search" placeholder="Buscar loja, gerente ou subgerente..." autocomplete="off">
    </div>
</div>

<div class="region-tab-wrap tab" id="cardsArea">
    <div id="cardsContainer"></div>
</div>

<!-- ==================== GESTÃO ==================== -->
<div class="region-tab-wrap tab" id="gestaoArea" hidden>
    <div class="region-subtabs">
        <button type="button" class="region-subtab active" data-sub="cad" onclick="mostrarGestao(this, 'cad')"><i class="fa-solid fa-building"></i> Cadastro</button>
        <button type="button" class="region-subtab" data-sub="reg" onclick="mostrarGestao(this, 'reg')"><i class="fa-solid fa-map-location-dot"></i> Regionais</button>
        <button type="button" class="region-subtab" data-sub="fil" onclick="mostrarGestao(this, 'fil')"><i class="fa-solid fa-diagram-project"></i> Filiais x Regional</button>
    </div>

    <!-- Cadastro de Gerentes -->
    <div class="region-tab-wrap header-bar tab" id="gestCad">
        <h2 class="header_title">Cadastro de Gerentes</h2>
        <div class="filter-form">
            <label for="fFilial">Filial:</label>
            <select class="region-select region-filial-select" id="fFilial">
                <option value="0">Selecione a Filial</option>
            </select>
            <button type="button" class="btn-novo" id="addArea" hidden onclick="addLine('tableResger')"><i class="fa-solid fa-plus"></i> Novo</button>
        </div>
    </div>

    <div class="region-tab-wrap table-wrapper tab" id="resultCad" hidden>
        <table class="table" id="tableResger">
            <thead>
                <tr>
                    <th scope="col">Regional</th>
                    <th scope="col">Gerente</th>
                    <th scope="col">Email</th>
                    <th scope="col">Corporativo</th>
                    <th scope="col">Subgerente</th>
                    <th scope="col">Email</th>
                    <th scope="col">Corporativo</th>
                    <th scope="col">Ações</th>
                    <th scope="col" hidden></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Regionais -->
    <div class="region-tab-wrap header-bar tab" id="gestReg" hidden>
        <h2 class="header_title">Gerentes Regionais</h2>
        <button type="button" class="btn-novo" id="btnNovaRegional" onclick="toggleFormRegional()"><i class="fa-solid fa-plus"></i> Nova Regional</button>
    </div>

    <div class="region-tab-wrap tab" id="formRegional" hidden>
        <div class="region-inline-form" id="inlineFormRegional">
            <label>Região:</label>
            <input type="number" id="fNovaRegRegiao" min="1" placeholder="Nº">
            <label>Nome:</label>
            <input type="text" id="fNovaRegNome" placeholder="Nome da regional">
            <label>CPF:</label>
            <input type="text" id="fNovaRegCpf" placeholder="000.000.000-00" maxlength="14">
            <button type="button" class="btn-novo-sm" onclick="salvarNovaRegional()"><i class="fa-solid fa-check"></i> Salvar</button>
            <button type="button" class="btn-cancel-sm" onclick="toggleFormRegional()"><i class="fa-solid fa-xmark"></i> Cancelar</button>
        </div>
    </div>

    <div class="region-tab-wrap table-wrapper tab" id="resultGer" hidden>
        <table class="table" id="tableRegCad">
            <thead>
                <tr>
                    <th scope="col">Região</th>
                    <th scope="col">Gerente</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Filiais x Regional -->
    <div class="region-tab-wrap header-bar tab" id="gestFil" hidden>
        <h2 class="header_title">Filiais x Regional</h2>
        <button type="button" class="btn-novo" id="btnNovaVinculo" onclick="toggleFormVinculo()"><i class="fa-solid fa-plus"></i> Vincular Filial</button>
    </div>

    <div class="region-tab-wrap tab" id="formVinculo" hidden>
        <div class="region-inline-form" id="inlineFormVinculo">
            <label>Filial:</label>
            <select id="fNovaVincFilial" class="region-select" style="max-width:320px">
                <option value="">Selecione</option>
            </select>
            <label>Regional:</label>
            <select id="fNovaVincRegional" class="region-select" style="max-width:240px">
                <option value="">Selecione</option>
            </select>
            <button type="button" class="btn-novo-sm" onclick="salvarNovoVinculo()"><i class="fa-solid fa-check"></i> Vincular</button>
            <button type="button" class="btn-cancel-sm" onclick="toggleFormVinculo()"><i class="fa-solid fa-xmark"></i> Cancelar</button>
        </div>
    </div>

    <div class="region-tab-wrap table-wrapper tab" id="resultReg" hidden>
        <table class="table" id="tableRegFil">
            <thead>
                <tr>
                    <th scope="col">Cod. Filial</th>
                    <th scope="col">Filial</th>
                    <th scope="col">Regional</th>
                    <th scope="col">Ações</th>
                    <th scope="col" hidden></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="region-confirm-overlay" id="confirmDialog">
    <div class="region-confirm-box">
        <div class="region-confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="region-confirm-title" id="confirmTitle">Confirmar exclusão</h3>
        <p class="region-confirm-msg" id="confirmMsg">Tem certeza que deseja excluir?</p>
        <div class="region-confirm-actions">
            <button type="button" class="region-confirm-cancel" onclick="fecharConfirm()">Cancelar</button>
            <button type="button" class="region-confirm-ok" id="confirmOk" onclick="executarConfirm()">Excluir</button>
        </div>
    </div>
</div>

<script>
    (function() {
        var API = '/regional/';
        var _confirmCallback = null;

        /* ===== UTILITÁRIOS ===== */

        function getJson(url, opts) {
            return fetch(url, opts).then(function(r) {
                return r.json();
            });
        }

        function get(url) {
            return getJson(url).then(check);
        }

        function check(res) {
            if (!res || !res.success) {
                var msg = res && res.error ? res.error.message : 'Erro na requisição.';
                toast('error', msg);
                throw new Error(msg);
            }
            return res.data;
        }

        function enviar(method, url, data) {
            return getJson(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data || {})
            }).then(check);
        }

        function toast(tipo, msg) {
            if (typeof mostrarToast === 'function') {
                mostrarToast(tipo, msg);
            } else {
                console.log('[' + tipo + '] ' + msg);
            }
        }

        function setVisible(id, visivel) {
            var el = document.getElementById(id);
            if (el) el.hidden = !visivel;
        }

        function esc(s) {
            var div = document.createElement('div');
            div.textContent = s == null ? '' : String(s);
            return div.innerHTML;
        }

        /* ===== CONFIRMAÇÃO ===== */

        window.mostrarConfirm = function(titulo, msg, okLabel, callback) {
            document.getElementById('confirmTitle').textContent = titulo;
            document.getElementById('confirmMsg').textContent = msg;
            document.getElementById('confirmOk').textContent = okLabel || 'Excluir';
            _confirmCallback = callback;
            document.getElementById('confirmDialog').classList.add('show');
        };

        window.fecharConfirm = function() {
            document.getElementById('confirmDialog').classList.remove('show');
            _confirmCallback = null;
        };

        window.executarConfirm = function() {
            var cb = _confirmCallback;
            fecharConfirm();
            if (typeof cb === 'function') cb();
        };

        /* ===== NAVEGAÇÃO ===== */

        function mostrarAbaGlobal(botao, abaId) {
            var botoes = document.querySelectorAll('.region-tab');
            for (var i = 0; i < botoes.length; i++) {
                botoes[i].classList.toggle('active', botoes[i] === botao);
            }

            if (abaId === 'gestao') {
                setVisible('statsArea', false);
                setVisible('consultaArea', false);
                setVisible('cardsArea', false);
                setVisible('gestaoArea', true);
                mostrarSubGestao(null, 'cad');
            } else {
                setVisible('statsArea', true);
                setVisible('consultaArea', true);
                setVisible('cardsArea', true);
                setVisible('gestaoArea', false);
                renderConsulta();
            }
        }

        window.mostrarAba = mostrarAbaGlobal;

        function mostrarSubGestao(botao, sub) {
            if (typeof sub === 'undefined' || sub === null) {
                sub = botao;
            }
            sub = String(sub);
            var mapa = {
                'cad': ['gestCad', 'resultCad'],
                'reg': ['gestReg', 'formRegional', 'resultGer'],
                'fil': ['gestFil', 'formVinculo', 'resultReg']
            };
            var visiveis = mapa[sub] || [];

            var subs = document.querySelectorAll('.region-subtab');
            for (var i = 0; i < subs.length; i++) {
                subs[i].classList.toggle('active', subs[i].getAttribute('data-sub') === sub);
            }

            var areas = document.querySelectorAll('#gestaoArea .region-tab-wrap.tab');
            for (var j = 0; j < areas.length; j++) {
                areas[j].hidden = visiveis.indexOf(areas[j].getAttribute('id')) === -1;
            }

            if (sub === 'cad') {
                listaFilial();
                resetCadastro();
            }
            if (sub === 'reg') {
                setFormRegional(false);
                regLista();
            }
            if (sub === 'fil') {
                setFormVinculo(false);
                filialByregional();
            }
        }

        window.mostrarGestao = mostrarSubGestao;

        /* ===================== CONSULTA ===================== */

        var CONSULTA = {
            lista: []
        };

        function consulta() {
            get(API + 'consulta').then(function(rows) {
                CONSULTA.lista = agrupar(rows);
                preencherFiltroRegional();
                renderConsulta();
                atualizarStats(rows);
            });
        }

        function agrupar(rows) {
            var mapa = {};
            var ordem = [];

            for (var i = 0; i < rows.length; i++) {
                var r = rows[i];
                var reg = String(r.regiao);

                if (!mapa[reg]) {
                    mapa[reg] = {
                        regiao: r.regiao,
                        nome: r.regional || '',
                        email: r.email || '',
                        corporativo: r.corporativo || '',
                        lojas: []
                    };
                    ordem.push(reg);
                }

                if (r.codfilial != null) {
                    mapa[reg].lojas.push({
                        codfilial: r.codfilial,
                        filial: r.filial || '',
                        gerente: r.gerente || '',
                        subgerente: r.subgerente || '',
                        cpf_g1: r.cpf_g1 || '',
                        cpf_g2: r.cpf_g2 || '',
                        email_g1: r.email_g1 || '',
                        corp_g1: r.corp_g1 || '',
                        email_g2: r.email_g2 || '',
                        corp_g2: r.corp_g2 || ''
                    });
                }
            }

            var lista = [];
            for (var k = 0; k < ordem.length; k++) {
                lista.push(mapa[ordem[k]]);
            }
            return lista;
        }

        function atualizarStats(rows) {
            var regionais = {};
            var lojas = 0;
            var gerentes = 0;

            for (var i = 0; i < rows.length; i++) {
                regionais[rows[i].regiao] = true;
                if (rows[i].codfilial != null) {
                    lojas++;
                    if (rows[i].cpf_g1) gerentes++;
                    if (rows[i].cpf_g2) gerentes++;
                }
            }

            var el;
            el = document.getElementById('statRegionais');
            if (el) el.textContent = Object.keys(regionais).length;
            el = document.getElementById('statLojas');
            if (el) el.textContent = lojas;
            el = document.getElementById('statGerentes');
            if (el) el.textContent = gerentes;
        }

        function preencherFiltroRegional() {
            var sel = document.getElementById('fRegional');
            sel.innerHTML = '<option value="">Todas</option>';

            for (var i = 0; i < CONSULTA.lista.length; i++) {
                var opt = document.createElement('option');
                opt.value = String(CONSULTA.lista[i].regiao);
                opt.textContent = CONSULTA.lista[i].nome || ('Regional ' + CONSULTA.lista[i].regiao);
                sel.appendChild(opt);
            }
        }

        function renderConsulta() {
            var regSel = document.getElementById('fRegional').value;
            var q = (document.getElementById('fBusca').value || '').trim().toLowerCase();

            var container = document.getElementById('cardsContainer');
            container.innerHTML = '';

            var nReg = 0;
            var nLojas = 0;

            for (var i = 0; i < CONSULTA.lista.length; i++) {
                var reg = CONSULTA.lista[i];

                if (regSel !== '' && String(reg.regiao) !== regSel) continue;
                if (q !== '' && !regCorresponde(reg, q)) continue;

                nReg++;
                nLojas += reg.lojas.length;
                container.appendChild(montaCard(reg));
            }

            if (nReg === 0) {
                container.innerHTML = '<p class="region-empty">Nenhuma regional encontrada.</p>';
            }
        }

        function regCorresponde(reg, q) {
            if ((reg.nome || '').toLowerCase().indexOf(q) !== -1) return true;

            for (var i = 0; i < reg.lojas.length; i++) {
                var l = reg.lojas[i];
                if ((l.filial || '').toLowerCase().indexOf(q) !== -1) return true;
                if ((l.gerente || '').toLowerCase().indexOf(q) !== -1) return true;
                if ((l.subgerente || '').toLowerCase().indexOf(q) !== -1) return true;
            }
            return false;
        }

        function montaCard(reg) {
            var card = document.createElement('div');
            card.className = 'region-card';

            var head = document.createElement('div');
            head.className = 'region-card-head region-card-head--new';

            var headTitle = document.createElement('div');
            headTitle.className = 'region-card-head-title';

            var badge = document.createElement('span');
            badge.className = 'region-badge';
            badge.textContent = 'R' + reg.regiao;
            headTitle.appendChild(badge);

            var title = document.createElement('h3');
            title.className = 'region-card-title';
            title.textContent = reg.nome || '';
            headTitle.appendChild(title);

            var countBadge = document.createElement('span');
            countBadge.className = 'region-count';
            countBadge.textContent = reg.lojas.length + ' loja' + (reg.lojas.length !== 1 ? 's' : '');
            headTitle.appendChild(countBadge);

            head.appendChild(headTitle);

            var contato = document.createElement('div');
            contato.className = 'region-card-contact';
            contato.innerHTML =
                (reg.email ? '<span><i class="fa-regular fa-envelope"></i> ' + esc(reg.email) + '</span>' : '') +
                (reg.corporativo ? '<span><i class="fa-solid fa-mobile-screen"></i> ' + esc(reg.corporativo) + '</span>' : '');
            head.appendChild(contato);

            card.appendChild(head);

            var wrap = document.createElement('div');
            wrap.className = 'region-card-table-wrap';

            var table = document.createElement('table');
            var thead = document.createElement('thead');
            thead.innerHTML = '<tr><th scope="col">Loja</th><th scope="col">Gerente</th><th scope="col">Contato</th><th scope="col">Subgerente</th><th scope="col">Contato</th></tr>';
            var tbody = document.createElement('tbody');
            table.appendChild(thead);
            table.appendChild(tbody);
            wrap.appendChild(table);

            if (reg.lojas.length > 0) {
                for (var i = 0; i < reg.lojas.length; i++) {
                    tbody.appendChild(montaRow(reg.lojas[i]));
                }
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="region-empty">Nenhuma loja vinculada.</td></tr>';
            }

            card.appendChild(wrap);

            return card;
        }

        function montaRow(l) {
            var tr = document.createElement('tr');

            var g1 = esc(l.gerente) || '<span class="region-empty">-</span>';
            var g2 = esc(l.subgerente) || '<span class="region-empty">-</span>';

            var contatoG1 = formatContato(l.email_g1, l.corp_g1);
            var contatoG2 = formatContato(l.email_g2, l.corp_g2);

            tr.innerHTML =
                '<td>' + esc(l.codfilial) + ' - ' + esc(l.filial) + '</td>' +
                '<td>' + g1 + '</td>' +
                '<td class="region-contact-cell">' + contatoG1 + '</td>' +
                '<td>' + g2 + '</td>' +
                '<td class="region-contact-cell">' + contatoG2 + '</td>';

            return tr;
        }

        function formatContato(email, corp) {
            var parts = [];
            if (email) parts.push('<span><i class="fa-regular fa-envelope"></i> ' + esc(email) + '</span>');
            if (corp) parts.push('<span><i class="fa-solid fa-phone"></i> ' + esc(corp) + '</span>');
            return parts.length > 0 ? parts.join(' ') : '<span class="region-empty">-</span>';
        }

        document.getElementById('fRegional').addEventListener('change', renderConsulta);
        document.getElementById('fBusca').addEventListener('input', renderConsulta);

        /* ===================== GESTÃO: CADASTRO DE GERENTES ===================== */

        function listaFilial() {
            var sel = document.getElementById('fFilial');
            sel.innerHTML = '<option value="0">Selecione a Filial</option>';
            get(API + 'regionalFilial').then(function(result) {
                for (var i = 0; i < result.length; i++) {
                    sel.appendChild(montaFilial(result[i]));
                }
            });
        }

        function montaFilial(obj) {
            var opt = document.createElement('option');
            opt.value = obj.codgfilial;
            opt.textContent = obj.filialS;
            return opt;
        }

        function resetCadastro() {
            var tbody = document.querySelector('#tableResger tbody');
            if (tbody) tbody.innerHTML = '';
            var addArea = document.getElementById('addArea');
            if (addArea) addArea.hidden = true;
        }

        function getByFilial(filial) {
            var tbody = document.querySelector('#tableResger tbody');
            tbody.innerHTML = '';
            return get(API + 'byFilial/' + filial).then(function(result) {
                if (result.length > 0) {
                    for (var i = 0; i < result.length; i++) {
                        tbody.appendChild(montaCadtable(result[i]));
                    }
                } else {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td colspan="9" class="region-empty">Não há dados cadastrados!</td>';
                    tbody.appendChild(tr);
                }
            });
        }

        function montaCadtable(obj) {
            var tr = document.createElement('tr');
            var g1 = obj.gerente == null ? '' : obj.gerente;
            var g2 = obj.subgerente == null ? '' : obj.subgerente;

            tr.innerHTML =
                '<td>' + esc(obj.regional) + '</td>' +
                '<td>' + esc(g1) + '</td>' +
                '<td class="region-contact-cell">' + esc(obj.email_g1 || '') + '</td>' +
                '<td class="region-contact-cell">' + esc(obj.corp_g1 || '') + '</td>' +
                '<td>' + esc(g2) + '</td>' +
                '<td class="region-contact-cell">' + esc(obj.email_g2 || '') + '</td>' +
                '<td class="region-contact-cell">' + esc(obj.corp_g2 || '') + '</td>' +
                '<td class="region-actions">' +
                '<button type="button" class="region-btn-edit" data-act="editar"><i class="fa-solid fa-pencil"></i></button>' +
                '<button type="button" class="region-btn-del" data-act="deletar"><i class="fa-solid fa-trash"></i></button>' +
                '</td>' +
                '<td hidden>' + esc(obj.id) + '</td>';

            tr.querySelector('[data-act="editar"]').addEventListener('click', function() {
                editar(obj.id, obj.regiao, obj.g1, obj.g2, obj.codfilial, obj.email_g1, obj.corp_g1, obj.email_g2, obj.corp_g2);
            });
            tr.querySelector('[data-act="deletar"]').addEventListener('click', function() {
                mostrarConfirm(
                    'Excluir gerência',
                    'Deseja excluir o vínculo de gerente desta filial?',
                    'Excluir',
                    function() {
                        enviar('DELETE', API + 'deletaGerente/' + obj.id).then(function() {
                            document.querySelector('#tableResger tbody').innerHTML = '';
                            getByFilial(obj.codfilial);
                            toast('success', 'Gerência excluída.');
                        });
                    }
                );
            });

            return tr;
        }

        function addLine(tableId) {
            if (tableId !== 'tableResger') return;

            var btnNew = document.getElementById('addArea');
            if (btnNew) btnNew.disabled = true;

            var tbody = document.querySelector('#tableResger tbody');
            tbody.innerHTML = '';
            var tr = document.createElement('tr');
            tr.innerHTML = '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td hidden></td>';
            tbody.appendChild(tr);

            var cels = tr.children;

            var selectRegional = document.createElement('select');
            selectRegional.className = 'region-select';
            selecionaRegional(0, selectRegional);
            cels[0].appendChild(selectRegional);

            var selectG = document.createElement('select');
            selectG.className = 'region-select';
            seleciona('onlyGerente', selectG);
            cels[1].appendChild(selectG);

            var inputEmailG1 = document.createElement('input');
            inputEmailG1.type = 'email';
            inputEmailG1.className = 'region-input';
            inputEmailG1.placeholder = 'email@...';
            cels[2].appendChild(inputEmailG1);

            var inputCorpG1 = document.createElement('input');
            inputCorpG1.type = 'text';
            inputCorpG1.className = 'region-input';
            inputCorpG1.placeholder = 'Corporativo';
            cels[3].appendChild(inputCorpG1);

            var selectS = document.createElement('select');
            selectS.className = 'region-select';
            seleciona('onlysubGerente', selectS);
            cels[4].appendChild(selectS);

            var inputEmailG2 = document.createElement('input');
            inputEmailG2.type = 'email';
            inputEmailG2.className = 'region-input';
            inputEmailG2.placeholder = 'email@...';
            cels[5].appendChild(inputEmailG2);

            var inputCorpG2 = document.createElement('input');
            inputCorpG2.type = 'text';
            inputCorpG2.className = 'region-input';
            inputCorpG2.placeholder = 'Corporativo';
            cels[6].appendChild(inputCorpG2);

            cels[7].className = 'region-actions';
            cels[7].innerHTML =
                '<button type="button" class="region-btn-save"><i class="fa-regular fa-floppy-disk"></i></button>' +
                '<button type="button" class="region-btn-cancel"><i class="fa-regular fa-circle-xmark"></i></button>';

            cels[7].querySelector('.region-btn-save').addEventListener('click', function() {
                var codregional = selectRegional.value;
                var g1 = selectG.value;
                var g2 = selectS.value;
                var filial = document.getElementById('fFilial').value;

                enviar('POST', API + 'gravaGerente', {
                    codregional: codregional,
                    codfilial: filial,
                    g1: g1,
                    g2: g2,
                    email_g1: inputEmailG1.value.trim(),
                    corp_g1: inputCorpG1.value.trim(),
                    email_g2: inputEmailG2.value.trim(),
                    corp_g2: inputCorpG2.value.trim()
                }).then(function() {
                    document.querySelector('#tableResger tbody').innerHTML = '';
                    getByFilial(filial);
                    toast('success', 'Gerente gravado.');
                    if (btnNew) btnNew.disabled = false;
                });
            });

            cels[7].querySelector('.region-btn-cancel').addEventListener('click', function() {
                getByFilial(document.getElementById('fFilial').value);
                if (btnNew) btnNew.disabled = false;
            });
        }

        window.addLine = addLine;

        function seleciona(endpoint, select) {
            select.innerHTML = '<option value="0"></option>';
            get(API + endpoint).then(function(res) {
                for (var i = 0; i < res.length; i++) {
                    select.appendChild(optGerente(res[i]));
                }
            });
        }

        function selecionaRegional(f, select) {
            select.innerHTML = '<option value="0"></option>';
            get(API + 'listaRegional').then(function(res) {
                for (var i = 0; i < res.length; i++) {
                    select.appendChild(optRegional(res[i]));
                }
                if (f !== 0) select.value = f;
            });
        }

        function optGerente(obj) {
            var opt = document.createElement('option');
            opt.value = obj.cpf;
            opt.textContent = obj.nome;
            return opt;
        }

        function optRegional(obj) {
            var opt = document.createElement('option');
            opt.value = obj.regiao;
            opt.textContent = obj.nome;
            return opt;
        }

        function editar(id, regiao, g1, g2, f, emailG1, corpG1, emailG2, corpG2) {
            var tbody = document.querySelector('#tableResger tbody');
            var index = -1;
            var rows = tbody.querySelectorAll('tr');
            for (var i = 0; i < rows.length; i++) {
                if (rows[i].children[8] && rows[i].children[8].textContent === String(id)) {
                    index = i;
                    break;
                }
            }
            if (index < 0) return;

            var cells = rows[index].children;
            cells[7].className = 'region-actions';
            cells[7].innerHTML =
                '<button type="button" class="region-btn-save"><i class="fa-regular fa-floppy-disk"></i></button>' +
                '<button type="button" class="region-btn-cancel"><i class="fa-regular fa-circle-xmark"></i></button>';

            var selectRegional = document.createElement('select');
            selectRegional.className = 'region-select';
            selecionaRegional(regiao || 0, selectRegional);
            cells[0].textContent = '';
            cells[0].appendChild(selectRegional);

            var selectG = document.createElement('select');
            selectG.className = 'region-select';
            realizarSelecao('onlyGerente', g1, selectG);
            cells[1].textContent = '';
            cells[1].appendChild(selectG);

            var inputEmailG1 = document.createElement('input');
            inputEmailG1.type = 'email';
            inputEmailG1.className = 'region-input';
            inputEmailG1.value = emailG1 || '';
            cells[2].textContent = '';
            cells[2].appendChild(inputEmailG1);

            var inputCorpG1 = document.createElement('input');
            inputCorpG1.type = 'text';
            inputCorpG1.className = 'region-input';
            inputCorpG1.value = corpG1 || '';
            cells[3].textContent = '';
            cells[3].appendChild(inputCorpG1);

            var selectS = document.createElement('select');
            selectS.className = 'region-select';
            realizarSelecao('onlysubGerente', g2, selectS);
            cells[4].textContent = '';
            cells[4].appendChild(selectS);

            var inputEmailG2 = document.createElement('input');
            inputEmailG2.type = 'email';
            inputEmailG2.className = 'region-input';
            inputEmailG2.value = emailG2 || '';
            cells[5].textContent = '';
            cells[5].appendChild(inputEmailG2);

            var inputCorpG2 = document.createElement('input');
            inputCorpG2.type = 'text';
            inputCorpG2.className = 'region-input';
            inputCorpG2.value = corpG2 || '';
            cells[6].textContent = '';
            cells[6].appendChild(inputCorpG2);

            cells[7].querySelector('.region-btn-save').addEventListener('click', function() {
                enviar('PUT', API + 'updateRegional/' + id, {
                    codregional: selectRegional.value,
                    g1: selectG.value,
                    g2: selectS.value,
                    email_g1: inputEmailG1.value.trim(),
                    corp_g1: inputCorpG1.value.trim(),
                    email_g2: inputEmailG2.value.trim(),
                    corp_g2: inputCorpG2.value.trim()
                }).then(function() {
                    document.querySelector('#tableResger tbody').innerHTML = '';
                    getByFilial(f);
                    toast('success', 'Registro atualizado.');
                });
            });

            cells[7].querySelector('.region-btn-cancel').addEventListener('click', function() {
                document.querySelector('#tableResger tbody').innerHTML = '';
                getByFilial(f);
            });
        }

        function realizarSelecao(endpoint, valor, select) {
            select.innerHTML = '<option value="0"></option>';
            get(API + endpoint).then(function(res) {
                for (var i = 0; i < res.length; i++) {
                    select.appendChild(optGerente(res[i]));
                }
                if (valor) select.value = valor;
            });
        }

        /* ===================== GESTÃO: REGIONAIS ===================== */

        function setFormRegional(aberto) {
            var form = document.getElementById('formRegional');
            var btn = document.getElementById('btnNovaRegional');
            form.hidden = !aberto;
            btn.innerHTML = aberto ?
                '<i class="fa-solid fa-xmark"></i> Cancelar' :
                '<i class="fa-solid fa-plus"></i> Nova Regional';
        }

        window.toggleFormRegional = function() {
            var form = document.getElementById('formRegional');
            var abrir = form.hidden;
            setFormRegional(abrir);
            if (abrir) {
                document.getElementById('fNovaRegRegiao').value = '';
                document.getElementById('fNovaRegNome').value = '';
                document.getElementById('fNovaRegCpf').value = '';
            }
        };

        window.salvarNovaRegional = function() {
            var regiao = parseInt(document.getElementById('fNovaRegRegiao').value, 10);
            var nome = document.getElementById('fNovaRegNome').value.trim();
            var cpf = document.getElementById('fNovaRegCpf').value.replace(/\D/g, '');

            if (!regiao || regiao <= 0) {
                toast('error', 'Informe o número da regional.');
                return;
            }
            if (!nome) {
                toast('error', 'Informe o nome da regional.');
                return;
            }

            enviar('POST', API + 'criaRegional', {
                regiao: regiao,
                nome: nome,
                cpf: cpf || null
            }).then(function() {
                toast('success', 'Regional criada.');
                toggleFormRegional();
                regLista();
                consulta();
            });
        };

        function regLista() {
            var tbody = document.querySelector('#tableRegCad tbody');
            tbody.innerHTML = '';
            get(API + 'listaRegional').then(function(result) {
                for (var i = 0; i < result.length; i++) {
                    tbody.appendChild(tableRegional(result[i]));
                }

                var rows = tbody.querySelectorAll('tr');
                for (var k = 0; k < rows.length; k++) {
                    (function(row) {
                        var regiao = row.children[0].textContent;

                        row.children[1].innerHTML = '';

                        var input = document.createElement('input');
                        input.className = 'region-input';
                        input.setAttribute('data-regiao', String(regiao));
                        input.value = row.getAttribute('data-nome') || '';
                        row.children[1].appendChild(input);

                        var cellAct = row.children[2];
                        cellAct.className = 'region-actions';
                        cellAct.innerHTML = '';

                        var btnSave = document.createElement('button');
                        btnSave.type = 'button';
                        btnSave.className = 'region-btn-save';
                        btnSave.innerHTML = '<i class="fa-regular fa-floppy-disk"></i>';
                        btnSave.addEventListener('click', function() {
                            enviar('PUT', API + 'alteraRegional/' + input.getAttribute('data-regiao'), {
                                nome: input.value
                            }).then(function() {
                                tbody.innerHTML = '';
                                regLista();
                                toast('success', 'Regional atualizada.');
                            });
                        });

                        var btnDel = document.createElement('button');
                        btnDel.type = 'button';
                        btnDel.className = 'region-btn-del';
                        btnDel.innerHTML = '<i class="fa-solid fa-trash"></i>';
                        btnDel.addEventListener('click', function() {
                            mostrarConfirm(
                                'Excluir regional',
                                'Deseja excluir a regional ' + input.value + ' e todos os seus vínculos? Esta ação não pode ser desfeita.',
                                'Excluir',
                                function() {
                                    enviar('DELETE', API + 'excluiRegional/' + input.getAttribute('data-regiao')).then(function() {
                                        tbody.innerHTML = '';
                                        regLista();
                                        consulta();
                                        toast('success', 'Regional excluída.');
                                    });
                                }
                            );
                        });

                        cellAct.appendChild(btnSave);
                        cellAct.appendChild(btnDel);
                    })(rows[k]);
                }
            });
        }

        function tableRegional(obj) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-nome', obj.nome || '');
            tr.innerHTML = '<td>' + esc(obj.regiao) + '</td><td>' + esc(obj.nome) + '</td><td class="region-actions"></td>';
            return tr;
        }

        /* ===================== GESTÃO: FILIAIS X REGIONAL ===================== */

        function setFormVinculo(aberto) {
            var form = document.getElementById('formVinculo');
            var btn = document.getElementById('btnNovaVinculo');
            form.hidden = !aberto;
            btn.innerHTML = aberto ?
                '<i class="fa-solid fa-xmark"></i> Cancelar' :
                '<i class="fa-solid fa-plus"></i> Vincular Filial';
        }

        window.toggleFormVinculo = function() {
            var form = document.getElementById('formVinculo');
            var abrir = form.hidden;
            setFormVinculo(abrir);
            if (abrir) {
                populaFormVinculo();
            }
        };

        function populaFormVinculo() {
            var selFil = document.getElementById('fNovaVincFilial');
            var selReg = document.getElementById('fNovaVincRegional');

            selFil.innerHTML = '<option value="">Selecione</option>';
            selReg.innerHTML = '<option value="">Selecione</option>';

            get(API + 'regionalFilial').then(function(res) {
                for (var i = 0; i < res.length; i++) {
                    var opt = document.createElement('option');
                    opt.value = res[i].codgfilial;
                    opt.textContent = res[i].filialS;
                    selFil.appendChild(opt);
                }
            });

            get(API + 'listaRegional').then(function(res) {
                for (var i = 0; i < res.length; i++) {
                    selReg.appendChild(optRegional(res[i]));
                }
            });
        }

        window.salvarNovoVinculo = function() {
            var filial = document.getElementById('fNovaVincFilial').value;
            var codRegional = document.getElementById('fNovaVincRegional').value;

            if (!filial || filial === '') {
                toast('error', 'Selecione a filial.');
                return;
            }
            if (!codRegional || codRegional === '') {
                toast('error', 'Selecione a regional.');
                return;
            }

            enviar('POST', API + 'vinculaFilial', {
                cod_regional: parseInt(codRegional, 10),
                filial: filial
            }).then(function() {
                toast('success', 'Filial vinculada.');
                toggleFormVinculo();
                filialByregional();
            });
        };

        function filialByregional() {
            var tbody = document.querySelector('#tableRegFil tbody');
            tbody.innerHTML = '';
            get(API + 'filialByRegional').then(function(result) {
                for (var i = 0; i < result.length; i++) {
                    tbody.appendChild(montaFilialReg(result[i]));
                }

                var rows = tbody.querySelectorAll('tr');
                for (var k = 0; k < rows.length; k++) {
                    (function(row) {
                        var id = row.getAttribute('data-id');
                        var gfilial = row.children[3].textContent;
                        var codRegional = row.getAttribute('data-codregional') || '';

                        var cellRegional = row.children[2];
                        var cellAcoes = row.children[4];

                        var sel = document.createElement('select');
                        sel.className = 'region-select';
                        get(API + 'listaRegional').then(function(result) {
                            for (var m = 0; m < result.length; m++) {
                                sel.appendChild(optRegional(result[m]));
                            }
                            if (codRegional) sel.value = codRegional;
                        });

                        cellRegional.textContent = '';
                        cellRegional.appendChild(sel);

                        cellAcoes.className = 'region-actions';
                        cellAcoes.innerHTML = '';

                        var btnSave = document.createElement('button');
                        btnSave.type = 'button';
                        btnSave.className = 'region-btn-save';
                        btnSave.innerHTML = '<i class="fa-regular fa-floppy-disk"></i>';
                        btnSave.addEventListener('click', function() {
                            enviar('PUT', API + 'updatefilialReg/' + gfilial, {
                                cod_regional: sel.value
                            }).then(function() {
                                tbody.innerHTML = '';
                                filialByregional();
                                toast('success', 'Filial atualizada.');
                            });
                        });

                        var btnDel = document.createElement('button');
                        btnDel.type = 'button';
                        btnDel.className = 'region-btn-del';
                        btnDel.innerHTML = '<i class="fa-solid fa-trash"></i>';
                        btnDel.addEventListener('click', function() {
                            mostrarConfirm(
                                'Desvincular filial',
                                'Deseja desvincular esta filial da regional?',
                                'Desvincular',
                                function() {
                                    enviar('DELETE', API + 'desvinculaFilial/' + id).then(function() {
                                        tbody.innerHTML = '';
                                        filialByregional();
                                        toast('success', 'Filial desvinculada.');
                                    });
                                }
                            );
                        });

                        cellAcoes.appendChild(btnSave);
                        cellAcoes.appendChild(btnDel);
                    })(rows[k]);
                }
            });
        }

        function montaFilialReg(obj) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-codregional', obj.cod_regional || '');
            tr.setAttribute('data-id', obj.id || '');
            tr.innerHTML =
                '<td>' + esc(obj.codfilial) + '</td>' +
                '<td>' + esc(obj.filial) + '</td>' +
                '<td>' + esc(obj.regional) + '</td>' +
                '<td hidden>' + esc(obj.codgfilial) + '</td>' +
                '<td class="region-actions"></td>';
            return tr;
        }

        /* ===================== EVENTOS ===================== */

        document.getElementById('fFilial').addEventListener('change', function() {
            var tbody = document.querySelector('#tableResger tbody');
            tbody.innerHTML = '';
            var value = this.value;
            var addArea = document.getElementById('addArea');
            if (value != '0') {
                if (addArea) addArea.hidden = false;
                getByFilial(value);
            } else {
                if (addArea) addArea.hidden = true;
            }
        });

        // Máscara CPF
        var cpfInput = document.getElementById('fNovaRegCpf');
        if (cpfInput) {
            cpfInput.addEventListener('input', function() {
                var v = this.value.replace(/\D/g, '');
                if (v.length > 11) v = v.slice(0, 11);
                if (v.length > 9) {
                    this.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
                } else if (v.length > 6) {
                    this.value = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
                } else if (v.length > 3) {
                    this.value = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
                } else {
                    this.value = v;
                }
            });
        }

        /* ===================== INIT ===================== */

        function init() {
            consulta();
            if (PERMITIDO_GESTAO) {
                listaFilial();
            }
        }

        init();
    })();
</script>
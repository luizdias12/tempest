<div class="filter-bar">
    <div class="region-tabs">
        <button type="button" class="region-tab active" id="consulta" onclick="mostrarAba(this, 'consulta')"><i class="fa-solid fa-magnifying-glass"></i> Consulta</button>
        <?php if (!empty($permiteGestao)): ?>
        <button type="button" class="region-tab region-tab--gestao" id="gestao" onclick="mostrarAba(this, 'gestao')"><i class="fa-solid fa-gear"></i> Gestão</button>
        <?php endif; ?>
    </div>
    <div class="region-toolbar">
        <span id="regionCount" class="region-count"></span>
    </div>
</div>

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

<div class="region-tab-wrap tab" id="gestaoArea" hidden>
    <div class="region-subtabs">
        <button type="button" class="region-subtab active" data-sub="cad" onclick="mostrarGestao(this, 'cad')"><i class="fa-solid fa-building"></i> Cadastro</button>
        <button type="button" class="region-subtab" data-sub="reg" onclick="mostrarGestao(this, 'reg')"><i class="fa-solid fa-map-location-dot"></i> Regionais</button>
        <button type="button" class="region-subtab" data-sub="fil" onclick="mostrarGestao(this, 'fil')"><i class="fa-solid fa-diagram-project"></i> Filiais x Regional</button>
    </div>

    <div class="region-tab-wrap header-bar tab" id="gestCad">
        <h2 class="header_title">Área de Cadastro</h2>
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
                    <th scope="col">Subgerente</th>
                    <th scope="col">Ações</th>
                    <th scope="col" hidden></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="region-tab-wrap header-bar tab" id="gestReg" hidden>
        <h2 class="header_title">Gerentes Regionais</h2>
    </div>

    <div class="region-tab-wrap table-wrapper tab" id="resultGer" hidden>
        <table class="table" id="tableRegCad">
            <thead>
                <tr>
                    <th scope="col">Região</th>
                    <th scope="col">Gerente</th>
                    <th scope="col" hidden></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="region-tab-wrap header-bar tab" id="gestFil" hidden>
        <h2 class="header_title">Filiais x Regional</h2>
    </div>

    <div class="region-tab-wrap table-wrapper tab" id="resultReg" hidden>
        <table class="table" id="tableRegFil">
            <thead>
                <tr>
                    <th scope="col">Cod. Filial</th>
                    <th scope="col">Filial</th>
                    <th scope="col">Regional</th>
                    <th scope="col" hidden></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<?php
ob_start();
?>
<div>
    <p><i class="fa-regular fa-envelope"></i> <span id="reg_mail"></span></p>
    <p><i class="fa-solid fa-phone"></i> <span id="reg_corp"></span></p>
</div>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modalRegional',
    'title' => 'Dados de Contato',
    'content' => $content,
]); ?>

<script>
(function () {
    var API = '/regional/';
    var PERMITIDO_GESTAO = <?= !empty($permiteGestao) ? 'true' : 'false' ?>;

    function getJson(url, opts) {
        return fetch(url, opts).then(function (r) { return r.json(); });
    }

    function get(url) {
        return getJson(url).then(check);
    }

    function check(res) {
        if (!res || !res.success) {
            var msg = res && res.error ? res.error.message : 'Erro na requisição.';
            if (typeof mostrarToast === 'function') {
                mostrarToast('error', msg);
            } else {
                console.error(msg);
            }
            throw new Error(msg);
        }
        return res.data;
    }

    function enviar(method, url, data) {
        return getJson(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data || {})
        }).then(check);
    }

    function setVisible(id, visivel) {
        var el = document.getElementById(id);
        if (el) el.hidden = !visivel;
    }

    function mostrarAbaGlobal(botao, abaId) {
        var botoes = document.querySelectorAll('.region-tab');
        for (var i = 0; i < botoes.length; i++) {
            botoes[i].classList.toggle('active', botoes[i] === botao);
        }

        if (abaId === 'gestao') {
            if (!PERMITIDO_GESTAO) return;
            setVisible('consultaArea', false);
            setVisible('cardsArea', false);
            setVisible('gestaoArea', true);
            mostrarSubGestao('cad');
        } else {
            setVisible('consultaArea', true);
            setVisible('cardsArea', true);
            setVisible('gestaoArea', false);
            renderConsulta();
        }
    }

    window.mostrarAba = mostrarAbaGlobal;

    function mostrarSubGestao(sub) {
        var mapa = {
            'cad': ['gestCad', 'resultCad'],
            'reg': ['gestReg', 'resultGer'],
            'fil': ['gestFil', 'resultReg']
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
            regLista();
        }
        if (sub === 'fil') {
            filialByregional();
        }
    }

    window.mostrarGestao = mostrarSubGestao;

    /* ===================== CONSULTA ===================== */

    var CONSULTA = { lista: [] };

    function consulta() {
        get(API + 'consulta').then(function (rows) {
            CONSULTA.lista = agrupar(rows);
            preencherFiltroRegional();
            renderConsulta();
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
                    cpf_g2: r.cpf_g2 || ''
                });
            }
        }

        var lista = [];
        for (var k = 0; k < ordem.length; k++) {
            lista.push(mapa[ordem[k]]);
        }
        return lista;
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
            container.appendChild(montaTable(reg));
        }

        regionCount(nReg, nLojas);

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

    function montaTable(reg) {
        var card = document.createElement('div');
        card.className = 'region-card';

        var head = document.createElement('div');
        head.className = 'region-card-head';

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
        thead.innerHTML = '<tr><th scope="col">Loja</th><th scope="col">Gerente</th><th scope="col">Subgerente</th></tr>';
        var tbody = document.createElement('tbody');
        table.appendChild(thead);
        table.appendChild(tbody);
        wrap.appendChild(table);

        if (reg.lojas.length > 0) {
            for (var i = 0; i < reg.lojas.length; i++) {
                tbody.appendChild(montaRow(reg.lojas[i]));
            }
        } else {
            tbody.innerHTML = '<tr><td colspan="3" class="region-empty">Nenhuma loja vinculada.</td></tr>';
        }

        card.appendChild(wrap);

        return card;
    }

    function montaRow(l) {
        var tr = document.createElement('tr');

        var g1 = (l.gerente === '')
            ? ''
            : '<a class="region-link-contact" data-cpf="' + esc(l.cpf_g1) + '">' + esc(l.gerente) + '</a>';
        var g2 = (l.subgerente === '')
            ? ''
            : '<a class="region-link-contact" data-cpf="' + esc(l.cpf_g2) + '">' + esc(l.subgerente) + '</a>';

        tr.innerHTML =
            '<td>' + esc(l.codfilial) + ' - ' + esc(l.filial) + '</td>' +
            '<td>' + g1 + '</td>' +
            '<td>' + g2 + '</td>';

        var links = tr.querySelectorAll('.region-link-contact');
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function () {
                mostraDados(this.getAttribute('data-cpf'));
            });
        }

        return tr;
    }

    function regionCount(n, nLojas) {
        var el = document.getElementById('regionCount');
        if (!el) return;

        var texto = n + ' regional' + (n === 1 ? '' : 'ais');
        if (nLojas > 0) {
            texto += ' · ' + nLojas + ' loja' + (nLojas === 1 ? '' : 's');
        }
        el.textContent = texto;
    }

    function mostraDados(cpf) {
        visitarModal();
        if (!cpf) return;
        get(API + 'usuario/' + cpf).then(function (res) {
            document.getElementById('reg_mail').textContent = res.email || '-';
            document.getElementById('reg_corp').textContent = res.corporativo || '-';
            var modal = document.getElementById('modalRegional');
            if (modal) modal.style.display = 'flex';
        });
    }

    function visitarModal() {
        var modal = document.getElementById('modalRegional');
        if (modal) {
            modal.style.display = 'flex';
            document.getElementById('reg_mail').textContent = '...';
            document.getElementById('reg_corp').textContent = '...';
        }
    }

    document.getElementById('fRegional').addEventListener('change', renderConsulta);
    document.getElementById('fBusca').addEventListener('input', renderConsulta);

    /* ===================== GESTÃO (somente TI) ===================== */

    function listaFilial() {
        var sel = document.getElementById('fFilial');
        sel.innerHTML = '<option value="0">Selecione a Filial</option>';
        get(API + 'regionalFilial').then(function (result) {
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
        return get(API + 'byFilial/' + filial).then(function (result) {
            if (result.length > 0) {
                for (var i = 0; i < result.length; i++) {
                    tbody.appendChild(montaCadtable(result[i]));
                }
            } else {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="5" class="region-empty">Não há dados cadastrados!</td>';
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
            '<td>' + esc(g2) + '</td>' +
            '<td class="region-actions">' +
            '<button type="button" class="region-btn-edit" data-act="editar"><i class="fa-solid fa-pencil"></i></button>' +
            '<button type="button" class="region-btn-del" data-act="deletar"><i class="fa-solid fa-trash"></i></button>' +
            '</td>' +
            '<td hidden>' + esc(obj.id) + '</td>';

        tr.querySelector('[data-act="editar"]').addEventListener('click', function () {
            editar(obj.id, obj.regiao, obj.g1, obj.g2, obj.codfilial);
        });
        tr.querySelector('[data-act="deletar"]').addEventListener('click', function () {
            deletar(obj.id, obj.codfilial);
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
        tr.innerHTML = '<td></td><td></td><td></td><td></td><td hidden></td>';
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

        var selectS = document.createElement('select');
        selectS.className = 'region-select';
        seleciona('onlysubGerente', selectS);
        cels[2].appendChild(selectS);

        cels[3].className = 'region-actions';
        cels[3].innerHTML =
            '<button type="button" class="region-btn-save"><i class="fa-regular fa-floppy-disk"></i></button>' +
            '<button type="button" class="region-btn-cancel"><i class="fa-regular fa-circle-xmark"></i></button>';

        cels[3].querySelector('.region-btn-save').addEventListener('click', function () {
            var codregional = selectRegional.value;
            var g1 = selectG.value;
            var g2 = selectS.value;
            var filial = document.getElementById('fFilial').value;

            enviar('POST', API + 'gravaGerente', {
                codregional: codregional,
                codfilial: filial,
                g1: g1,
                g2: g2
            }).then(function () {
                document.querySelector('#tableResger tbody').innerHTML = '';
                getByFilial(filial);
                if (btnNew) btnNew.disabled = false;
            });
        });

        cels[3].querySelector('.region-btn-cancel').addEventListener('click', function () {
            getByFilial(document.getElementById('fFilial').value);
            if (btnNew) btnNew.disabled = false;
        });
    }

    window.addLine = addLine;

    function seleciona(endpoint, select) {
        select.innerHTML = '<option value="0"></option>';
        get(API + endpoint).then(function (res) {
            for (var i = 0; i < res.length; i++) {
                select.appendChild(optGerente(res[i]));
            }
        });
    }

    function selecionaRegional(f, select) {
        select.innerHTML = '<option value="0"></option>';
        get(API + 'listaRegional').then(function (res) {
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

    function editar(id, regiao, g1, g2, f) {
        var tbody = document.querySelector('#tableResger tbody');
        var index = -1;
        var rows = tbody.querySelectorAll('tr');
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].children[4] && rows[i].children[4].textContent === String(id)) {
                index = i;
                break;
            }
        }
        if (index < 0) return;

        var cells = rows[index].children;
        cells[3].className = 'region-actions';
        cells[3].innerHTML =
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

        var selectS = document.createElement('select');
        selectS.className = 'region-select';
        realizarSelecao('onlysubGerente', g2, selectS);
        cells[2].textContent = '';
        cells[2].appendChild(selectS);

        cells[3].querySelector('.region-btn-save').addEventListener('click', function () {
            enviar('PUT', API + 'updateRegional/' + id, {
                codregional: selectRegional.value,
                g1: selectG.value,
                g2: selectS.value
            }).then(function () {
                document.querySelector('#tableResger tbody').innerHTML = '';
                getByFilial(f);
            });
        });

        cells[3].querySelector('.region-btn-cancel').addEventListener('click', function () {
            document.querySelector('#tableResger tbody').innerHTML = '';
            getByFilial(f);
        });
    }

    function realizarSelecao(endpoint, valor, select) {
        select.innerHTML = '<option value="0"></option>';
        get(API + endpoint).then(function (res) {
            for (var i = 0; i < res.length; i++) {
                select.appendChild(optGerente(res[i]));
            }
            if (valor) select.value = valor;
        });
    }

    function deletar(id, f) {
        enviar('DELETE', API + 'deletaGerente/' + id).then(function () {
            document.querySelector('#tableResger tbody').innerHTML = '';
            getByFilial(f);
        });
    }

    function filialByregional() {
        var tbody = document.querySelector('#tableRegFil tbody');
        tbody.innerHTML = '';
        get(API + 'filialByRegional').then(function (result) {
            for (var i = 0; i < result.length; i++) {
                tbody.appendChild(montaFilialReg(result[i]));
            }

            var rows = tbody.querySelectorAll('tr');
            for (var k = 0; k < rows.length; k++) {
                (function (row) {
                    var name = row.children[2].textContent;
                    var gfilial = row.children[3].textContent;
                    var codRegional = row.getAttribute('data-codregional') || '';

                    var sel = document.createElement('select');
                    sel.className = 'region-select';
                    get(API + 'listaRegional').then(function (result) {
                        for (var m = 0; m < result.length; m++) {
                            sel.appendChild(optRegional(result[m]));
                        }
                        if (codRegional) sel.value = codRegional;
                    });

                    row.children[2].textContent = '';
                    row.children[2].appendChild(sel);

                    var cellAct = row.children[3];
                    cellAct.className = 'region-actions';
                    cellAct.innerHTML = '';

                    var btnSave = document.createElement('button');
                    btnSave.type = 'button';
                    btnSave.className = 'region-btn-save';
                    btnSave.innerHTML = '<i class="fa-regular fa-floppy-disk"></i>';
                    btnSave.addEventListener('click', function () {
                        enviar('PUT', API + 'updatefilialReg/' + gfilial, {
                            cod_regional: sel.value
                        }).then(function () {
                            tbody.innerHTML = '';
                            filialByregional();
                        });
                    });

                    var btnCancel = document.createElement('button');
                    btnCancel.type = 'button';
                    btnCancel.className = 'region-btn-cancel';
                    btnCancel.innerHTML = '<i class="fa-regular fa-circle-xmark"></i>';
                    btnCancel.addEventListener('click', function () {
                        tbody.innerHTML = '';
                        filialByregional();
                    });

                    cellAct.appendChild(btnSave);
                    cellAct.appendChild(btnCancel);
                })(rows[k]);
            }
        });
    }

    function montaFilialReg(obj) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-codregional', obj.cod_regional || '');
        tr.innerHTML =
            '<td>' + esc(obj.codfilial) + '</td>' +
            '<td>' + esc(obj.filial) + '</td>' +
            '<td>' + esc(obj.regional) + '</td>' +
            '<td hidden>' + esc(obj.codgfilial) + '</td>';
        return tr;
    }

    function regLista() {
        var tbody = document.querySelector('#tableRegCad tbody');
        tbody.innerHTML = '';
        get(API + 'listaRegional').then(function (result) {
            for (var i = 0; i < result.length; i++) {
                tbody.appendChild(tableRegional(result[i]));
            }

            var rows = tbody.querySelectorAll('tr');
            for (var k = 0; k < rows.length; k++) {
                (function (row) {
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
                    btnSave.addEventListener('click', function () {
                        enviar('PUT', API + 'alteraRegional/' + input.getAttribute('data-regiao'), {
                            nome: input.value
                        }).then(function () {
                            tbody.innerHTML = '';
                            regLista();
                        });
                    });

                    var btnCancel = document.createElement('button');
                    btnCancel.type = 'button';
                    btnCancel.className = 'region-btn-cancel';
                    btnCancel.innerHTML = '<i class="fa-regular fa-circle-xmark"></i>';
                    btnCancel.addEventListener('click', function () {
                        tbody.innerHTML = '';
                        regLista();
                    });

                    cellAct.appendChild(btnSave);
                    cellAct.appendChild(btnCancel);
                })(rows[k]);
            }
        });
    }

    function tableRegional(obj) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-nome', obj.nome || '');
        tr.innerHTML = '<td>' + esc(obj.regiao) + '</td><td>' + esc(obj.nome) + '</td><td class="region-actions"></td><td hidden></td>';
        return tr;
    }

    function esc(s) {
        var div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
    }

    document.getElementById('fFilial').addEventListener('change', function () {
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

    function init() {
        consulta();
        if (PERMITIDO_GESTAO) {
            listaFilial();
        }
    }

    init();
})();
</script>
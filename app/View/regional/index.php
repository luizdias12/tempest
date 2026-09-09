<div class="filter-bar">
    <div class="region-tabs">
        <button type="button" class="region-tab active" id="table" onclick="mostrarAba(this, 'table')"><i class="fa-solid fa-list"></i> Lista</button>
        <button type="button" class="region-tab" id="cad" onclick="mostrarAba(this, 'cad')"><i class="fa-solid fa-pen-to-square"></i> Cadastro</button>
        <button type="button" class="region-tab" id="reg" onclick="mostrarAba(this, 'reg')"><i class="fa-solid fa-map-location-dot"></i> Regionais</button>
        <button type="button" class="region-tab" id="filial" onclick="mostrarAba(this, 'filial')"><i class="fa-solid fa-building"></i> Filiais</button>
    </div>
</div>

<div class="region-tab-wrap header-bar tab" id="tableArea">
    <h2 class="header_title">Listagem de Regionais</h2>
</div>

<div class="region-tab-wrap header-bar tab" id="cadArea" hidden>
    <h2 class="header_title">Área de Cadastro</h2>
    <div class="filter-form">
        <label for="fFilial">Filial:</label>
        <select class="region-select region-filial-select" id="fFilial">
            <option value="0">Selecione a Filial</option>
        </select>
        <button type="button" class="btn-novo" id="addArea" hidden onclick="addLine('tableResger')"><i class="fa-solid fa-plus"></i> Novo</button>
    </div>
</div>

<div class="region-tab-wrap header-bar tab" id="regArea" hidden>
    <h2 class="header_title">Gerentes Regionais</h2>
</div>

<div class="region-tab-wrap header-bar tab" id="filialArea" hidden>
    <h2 class="header_title">Filiais x Regional</h2>
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
    const API = '/regional/';

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

    function mostrarAbaGlobal(botao, abaId) {
        var botoes = document.querySelectorAll('.region-tab');
        for (var i = 0; i < botoes.length; i++) {
            botoes[i].classList.toggle('active', botoes[i] === botao);
        }

        var mapaAbas = {
            'table': ['tableArea'],
            'cad': ['cadArea', 'resultCad'],
            'reg': ['regArea', 'resultGer'],
            'filial': ['filialArea', 'resultReg']
        };
        var visiveis = mapaAbas[abaId] || [];

        var areas = document.querySelectorAll('.region-tab-wrap.tab');
        for (var j = 0; j < areas.length; j++) {
            areas[j].hidden = visiveis.indexOf(areas[j].getAttribute('id')) === -1;
        }

        if (abaId === 'cad') {
            listaFilial();
        }
        if (abaId === 'filial') {
            filialByregional();
        }
        if (abaId === 'reg') {
            regLista();
        }
    }

    window.mostrarAba = mostrarAbaGlobal;

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
                tr.innerHTML = '<td colspan="4" class="region-empty">Não há dados cadastrados!</td>';
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
        selecionaRegional('soReg_0', selectRegional);
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

    function revealRegistros(regiao, linha) {
        if (linha.classList.contains('morfolo')) {
            linha.remove();
            return;
        }
        var filial = linha.children[0].textContent;
        var regional = linha.children[2].textContent;
        var sub = document.createElement('tr');
        sub.className = 'morfolo';

        var cell = document.createElement('td');
        cell.setAttribute('colspan', '3');

        sub.innerHTML =
            '<tr><th scope="col">Loja</th><th scope="col">Regional</th></tr>' +
            '<tr><td>' + esc(filial) + '</td><td>' + esc(regional) + '</td></tr>';

        linha.parentNode.insertBefore(sub, linha.nextSibling);
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

                    var sel = document.createElement('select');
                    sel.className = 'region-select';
                    get(API + 'listaRegional').then(function (result) {
                        for (var m = 0; m < result.length; m++) {
                            sel.appendChild(optRegional(result[m]));
                        }
                        var fx = codR(name);
                        if (fx) sel.value = fx;
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
                        var col = sel.parentNode;
                        col.textContent = name;
                        col.parentNode.children[3].innerHTML = '';
                    });

                    cellAct.appendChild(btnSave);
                    cellAct.appendChild(btnCancel);
                })(rows[k]);
            }
        });
    }

    function montaFilialReg(obj) {
        var tr = document.createElement('tr');
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

    function codR(value) {
        var fx = {
            'DIMAS': 1,
            'KENNY': 3,
            'DARCISIO': 4,
            'JUAN': 5,
            'JONATHAN': 6,
            'ELIZANGELA': 7,
            'THIAGO NEVES': 8
        };
        return fx[value] || null;
    }

    function esc(s) {
        var div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
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

    function listaRegional() {
        var area = document.getElementById('tableArea');
        area.innerHTML = '<h2 class="header_title">Listagem de Regionais</h2>';

        get(API + 'listaRegional').then(function (result) {
            var pendentes = result.length;
            function processar(i) {
                if (i >= result.length) return;
                var obj = result[i];
                var card = montaTable(obj);
                area.appendChild(card);

                get(API + 'byRegional/' + obj.regiao).then(function (rs) {
                    var subTbody = card.querySelector('tbody');
                    subTbody.innerHTML = '';
                    for (var j = 0; j < rs.length; j++) {
                        subTbody.appendChild(montaSubtable(rs[j]));
                    }
                    processar(i + 1);
                });
            }
            processar(0);
        });
    }

    function montaTable(obj) {
        var card = document.createElement('div');
        card.className = 'region-card';

        var title = document.createElement('h3');
        title.className = 'region-card-title';
        title.textContent = 'R' + obj.regiao + ' - ' + (obj.nome || '');

        var contact = document.createElement('p');
        contact.className = 'region-card-contact';
        contact.innerHTML = '<i class="fa-regular fa-envelope"></i> ' + esc(obj.email || '') + ' &nbsp; <i class="fa-solid fa-mobile-screen"></i> ' + esc(obj.corporativo || '');

        var table = document.createElement('table');
        var thead = document.createElement('thead');
        thead.innerHTML = '<tr><th scope="col">Loja</th><th scope="col">Gerente</th><th scope="col">Subgerente</th></tr>';
        var tbody = document.createElement('tbody');
        table.appendChild(thead);
        table.appendChild(tbody);

        card.appendChild(title);
        card.appendChild(contact);
        card.appendChild(table);

        return card;
    }

    function montaSubtable(obj) {
        var tr = document.createElement('tr');

        var g1 = (obj.gerente == null || obj.gerente === '')
            ? ''
            : '<a class="region-link-contact" data-cpf="' + esc(obj.cpf_g1) + '">' + esc(obj.gerente) + '</a>';
        var g2 = (obj.subgerente == null || obj.subgerente === '')
            ? ''
            : '<a class="region-link-contact" data-cpf="' + esc(obj.cpf_g2) + '">' + esc(obj.subgerente) + '</a>';

        tr.innerHTML =
            '<td>' + esc(obj.codfilial) + ' - ' + esc(obj.filial) + '</td>' +
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
        listaRegional();
        listaFilial();
    }

    init();
})();
</script>
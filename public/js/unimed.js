(function() {
    'use strict';

    var select = document.getElementById('unimedCompetencia');
    var corpo = document.getElementById('unimedRegistros');
    var totalEl = document.getElementById('unimedTotal');
    var wrapper = document.getElementById('unimedWrapper');

    if (!select || !corpo || !wrapper) return;

    function formatarData(iso) {
        var partes = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(iso || ''));
        return partes ? partes[3] + '/' + partes[2] + '/' + partes[1] : String(iso || '');
    }

    function formatarValor(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    function linha(celulas) {
        var tr = document.createElement('tr');

        celulas.forEach(function(texto) {
            var td = document.createElement('td');
            td.textContent = texto;
            tr.appendChild(td);
        });

        return tr;
    }

    function mensagem(texto) {
        var tr = document.createElement('tr');
        var td = document.createElement('td');
        td.colSpan = 5;
        td.textContent = texto;
        tr.appendChild(td);
        corpo.appendChild(tr);
    }

    function limpar() {
        corpo.innerHTML = '';
        if (totalEl) totalEl.textContent = '';
    }

    function carregar() {
        var opcao = select.options[select.selectedIndex];

        if (!opcao) return;

        var mes = opcao.getAttribute('data-mes');
        var ano = opcao.getAttribute('data-ano');

        wrapper.classList.add('is-loading');
        limpar();

        fetch('/unimed/consulta?mes=' + encodeURIComponent(mes) + '&ano=' + encodeURIComponent(ano), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function(r) {
                return r.json();
            })
            .then(function(j) {
                if (!j || !j.success) {
                    mensagem((j && j.error && j.error.message) || 'Não foi possível consultar o extrato.');
                    return;
                }

                var dados = j.data || {};
                var registros = dados.registros || [];

                if (!registros.length) {
                    mensagem('Nenhum registro encontrado para esta competência.');
                    return;
                }

                registros.forEach(function(r) {
                    corpo.appendChild(linha([
                        r.nome_cliente,
                        r.nome_prestador,
                        formatarData(r.data_atendimento),
                        r.tipo_procedimento,
                        formatarValor(r.valor)
                    ]));
                });

                if (totalEl) totalEl.textContent = 'Total: ' + formatarValor(dados.total);
            })
            .catch(function() {
                limpar();
                mensagem('Erro ao consultar o extrato. Tente novamente.');
            })
            .then(function() {
                wrapper.classList.remove('is-loading');
            });
    }

    select.addEventListener('change', carregar);
    carregar();
})();

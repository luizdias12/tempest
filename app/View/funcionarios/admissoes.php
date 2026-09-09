<div class="filter-bar">
    <div class="filter-form">
        <span class="admissoes-total">Total de admissões pendentes: <strong id="admissoes-total"><?= array_sum(array_column($data, 'pendentes')) ?></strong></span>
        <span class="admissoes-refresh" id="admissoes-refresh">Atualizado às <strong id="admissoes-hora">--:--:--</strong></span>
    </div>
</div>

<div class="table-wrapper">
    <table id="admissoes-table">
        <thead>
            <tr>
                <th scope="col">Pendentes</th>
                <th scope="col">Data de Admissão</th>
                <th scope="col">Último Envio</th>
            </tr>
        </thead>
        <tbody id="admissoes-tbody">
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="3" class="historico-empty">Nenhuma admissão pendente.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($data as $item): ?>
                <tr>
                    <td><?= (int) $item['pendentes'] ?></td>
                    <td><?= !empty($item['dataadmissao']) ? date('d/m/Y', strtotime($item['dataadmissao'])) : '-' ?></td>
                    <td><?= !empty($item['dataevento']) ? date('d/m/Y H:i:s', strtotime($item['dataevento'])) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    const REFRESH_MS = 30000;
    const tbody = document.getElementById('admissoes-tbody');
    const totalEl = document.getElementById('admissoes-total');
    const horaEl = document.getElementById('admissoes-hora');

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function atualizarHora() {
        if (horaEl) {
            horaEl.textContent = new Date().toLocaleTimeString('pt-BR');
        }
    }

    function renderizar(data) {
        const rows = (data || []).map(function (item) {
            const pendentes = parseInt(item.pendentes, 10) || 0;
            return '<tr>' +
                '<td>' + pendentes + '</td>' +
                '<td>' + escHtml(item.dataadmissao || '-') + '</td>' +
                '<td>' + escHtml(item.dataevento || '-') + '</td>' +
                '</tr>';
        }).join('');

        if (rows) {
            tbody.innerHTML = rows;
        } else {
            tbody.innerHTML = '<tr><td colspan="3" class="historico-empty">Nenhuma admissão pendente.</td></tr>';
        }

        const total = (data || []).reduce(function (acc, item) {
            return acc + (parseInt(item.pendentes, 10) || 0);
        }, 0);
        totalEl.textContent = total;
        atualizarHora();
    }

    function carregar() {
        fetch('/funcionarios/admissoes/json', { headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.error && res.data) {
                    renderizar(res.data);
                }
            })
            .catch(function () {});
    }

    atualizarHora();
    setInterval(carregar, REFRESH_MS);
})();
</script>
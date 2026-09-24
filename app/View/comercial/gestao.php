<?php

use App\Service\DateTimeService;
?>

<div class="header-bar">
    <a href="#" class="btn-novo" data-modal-open="modal-plantao"><i class="fa-solid fa-plus"></i> Novo plantão</a>
    <a href="/comercial" class="btn-novo btn-novo-outline"><i class="fa-solid fa-eye"></i> Visualização</a>
</div>

<div class="filter-bar">
    <form method="GET" action="/comercial/gestao" class="filter-form">
        <label for="filtro-mes">Mês de referência:</label>
        <select name="mesref" id="filtro-mes">
            <?php foreach ($meses as $mes): ?>
                <option value="<?= htmlspecialchars($mes['codigo']) ?>" <?= $mes['codigo'] === $mesref ? 'selected' : '' ?>><?= htmlspecialchars($mes['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filtrar</button>
        <a href="/comercial/gestao" class="btn-clear">Limpar filtro</a>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th scope="col">Data</th>
                <th scope="col">Comprador</th>
                <th scope="col">Referência</th>
                <th scope="col">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($escalas as $escala): ?>
                <tr>
                    <td><?= !empty($escala['data']) ? date('d/m/Y', strtotime($escala['data'])) : '-' ?></td>
                    <td><?= htmlspecialchars(initcap($escala['nome'] ?? '')) ?></td>
                    <td>
                        <?php if (!empty($escala['feriado'])): ?>
                            <span class="badge badge-pill badge-info"><i class="fa-solid fa-flag"></i> <?= htmlspecialchars($escala['feriado']) ?></span>
                        <?php else: ?>
                            <span class="badge badge-pill badge-secondary"><i class="fa-solid fa-calendar-day"></i> Sabado</span>
                        <?php endif; ?>
                    </td>
                    <td class="doc-acoes">
                        <button type="button"
                            class="btn-doc btn-doc-ver"
                            data-modal-open="modal-plantao"
                            data-editar="<?= (int) $escala['id'] ?>"
                            data-data="<?= htmlspecialchars($escala['data'] ?? '') ?>"
                            data-cpf="<?= htmlspecialchars($escala['comprador'] ?? '') ?>"
                            data-feriado="<?= htmlspecialchars($escala['feriado'] ?? '') ?>">
                            <i class="fa-solid fa-pen"></i> Editar
                        </button>
                        <button type="button" class="btn-doc btn-doc-danger" data-excluir="<?= (int) $escala['id'] ?>">
                            <i class="fa-solid fa-trash"></i> Excluir
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($escalas)): ?>
                <tr><td colspan="4" class="doc-tree-empty">Nenhum plantão cadastrado para este mês.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php ob_start(); ?>
    <form method="POST" action="/comercial/salvar" id="plantao-form" class="doc-upload-form">
        <input type="hidden" name="id" id="plantao-id" value="">
        <label>Data:
            <input type="date" name="data" id="plantao-data" required value="<?= DateTimeService::today() ?>">
        </label>
        <label>Comprador:
            <select name="comprador" id="plantao-comprador" required>
                <option value="">Selecione...</option>
                <?php foreach ($compradores as $comprador): ?>
                    <option value="<?= htmlspecialchars($comprador['cpf']) ?>"><?= htmlspecialchars($comprador['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Feriado (opcional):
            <input type="text" name="feriado" id="plantao-feriado" maxlength="255" placeholder="Ex.: Natal, Ano Novo, Tiradentes">
            <small>Deixe em branco para marcar o dia como Sábado.</small>
        </label>
        <button type="submit" class="btn">Salvar</button>
    </form>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-plantao',
    'title' => 'Plantão comercial',
    'content' => $content,
]); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function escHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function mostrarToast(tipo, msg) {
        var cont = document.querySelector('.toast-container');
        if (!cont) {
            cont = document.createElement('div');
            cont.className = 'toast-container';
            document.body.appendChild(cont);
        }

        var t = document.createElement('div');
        t.className = 'toast toast-' + tipo;
        t.innerHTML = '<span class="toast-message">' + escHtml(msg) + '</span><button class="toast-close">&times;</button>';
        cont.appendChild(t);

        t.querySelector('.toast-close').addEventListener('click', function() { t.remove(); });
        setTimeout(function() { t.remove(); }, 4000);
    }

    function postAcao(url, dados, okCallback) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'fetch'
            },
            body: new URLSearchParams(dados).toString()
        }).then(function(r) {
            return r.json();
        }).then(function(res) {
            if (res.success) {
                mostrarToast('success', res.message);
                if (okCallback) okCallback();
            } else {
                mostrarToast('error', res.message);
            }
        }).catch(function() {
            mostrarToast('error', 'Erro de comunicação com o servidor.');
        });
    }

    var form = document.getElementById('plantao-form');
    var idField = document.getElementById('plantao-id');
    var dataField = document.getElementById('plantao-data');
    var feriadoField = document.getElementById('plantao-feriado');
    var compradorSel = document.getElementById('plantao-comprador');

    function limparForm() {
        idField.value = '';
        dataField.value = '';
        feriadoField.value = '';
        compradorSel.value = '';
    }

    document.querySelectorAll('[data-modal-open="modal-plantao"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var editar = btn.getAttribute('data-editar');

            if (!editar) {
                limparForm();
                return;
            }

            idField.value = editar;
            dataField.value = btn.getAttribute('data-data') || '';
            feriadoField.value = btn.getAttribute('data-feriado') || '';
            compradorSel.value = btn.getAttribute('data-cpf') || '';
        });
    });

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!compradorSel.value) {
                mostrarToast('error', 'Selecione um comprador.');
                return;
            }

            var dados = new URLSearchParams(new FormData(form));

            postAcao(form.action, dados, function() {
                var modal = document.getElementById('modal-plantao');
                if (modal) modal.style.display = 'none';
                setTimeout(function() { location.reload(); }, 500);
            });
        });
    }

    document.querySelectorAll('[data-excluir]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Excluir este plantão?')) return;

            postAcao('/comercial/excluir', { id: btn.getAttribute('data-excluir') }, function() {
                setTimeout(function() { location.reload(); }, 500);
            });
        });
    });
});
</script>
<div class="header-bar">
    <a href="#" class="btn-novo" data-modal-open="modal-novo-slide"><i class="fa-solid fa-plus"></i> Novo slide</a>
    <a href="/" class="btn-novo btn-novo-outline"><i class="fa-solid fa-eye"></i> Visualização</a>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th scope="col">Prévia</th>
                <th scope="col">Arquivo</th>
                <th scope="col">Período</th>
                <th scope="col">Ordem</th>
                <th scope="col">Status</th>
                <th scope="col">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($slides as $i => $slide): ?>
                <tr>
                    <td>
                        <?php if ($slide['url']): ?>
                            <?php if ($slide['tipo'] === 'video'): ?>
                                <video class="carousel-thumb" src="<?= $slide['url'] ?>" muted></video>
                            <?php else: ?>
                                <img class="carousel-thumb" src="<?= $slide['url'] ?>" alt="<?= htmlspecialchars($slide['filename']) ?>">
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-pill badge-error">Sem arquivo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($slide['filename']) ?></td>
                    <td>
                        <?php if ($slide['dtinicio'] || $slide['dtfim']): ?>
                            De <?= $slide['dtinicio'] ? date('d/m/Y', strtotime($slide['dtinicio'])) : 'sempre' ?>
                            até <?= $slide['dtfim'] ? date('d/m/Y', strtotime($slide['dtfim'])) : 'sempre' ?>
                        <?php else: ?>
                            Sempre
                        <?php endif; ?>
                    </td>
                    <td class="carousel-ordem">
                        <button type="button" class="btn-doc btn-doc-ordem" data-ordem="subir" data-id="<?= (int) $slide['id'] ?>" title="Subir na ordem" <?= $i === 0 ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-arrow-up"></i>
                        </button>
                        <span class="carousel-ordem-num"><?= (int) $slide['ord'] ?></span>
                        <button type="button" class="btn-doc btn-doc-ordem" data-ordem="descer" data-id="<?= (int) $slide['id'] ?>" title="Descer na ordem" <?= $i === count($slides) - 1 ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-arrow-down"></i>
                        </button>
                    </td>
                    <td>
                        <button type="button"
                            class="btn-doc <?= $slide['ativo'] === 'S' ? 'btn-doc-ver' : 'btn-doc-off' ?>"
                            data-ativo-toggle="<?= (int) $slide['id'] ?>"
                            data-ativo-atual="<?= $slide['ativo'] === 'S' ? 'S' : 'N' ?>">
                            <i class="fa-solid <?= $slide['ativo'] === 'S' ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                            <?= $slide['ativo'] === 'S' ? 'Ativo' : 'Inativo' ?>
                        </button>
                    </td>
                    <td class="doc-acoes">
                        <form method="POST" action="/carousel/excluir" class="doc-form-inline" onsubmit="return confirm('Excluir o slide? O arquivo também será removido.');">
                            <input type="hidden" name="id" value="<?= (int) $slide['id'] ?>">
                            <button type="submit" class="btn-doc btn-doc-danger"><i class="fa-solid fa-trash"></i> Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($slides)): ?>
                <tr><td colspan="6" class="doc-tree-empty">Nenhum slide cadastrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php ob_start(); ?>
    <form method="POST" action="/carousel/salvar" enctype="multipart/form-data" class="doc-upload-form">
        <label>Arquivo (imagem ou vídeo, máx. 100 MB):
            <input type="file" name="slideArquivo" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm,.mov" required>
        </label>
        <label>Data de início (opcional):
            <input type="date" name="dtinicio">
        </label>
        <label>Data de fim (opcional):
            <input type="date" name="dtfim">
        </label>
        <label class="doc-upload-geral">
            <input type="checkbox" name="ativo" value="S" checked>
            Ativo
        </label>
        <button type="submit" class="btn">Enviar slide</button>
    </form>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-novo-slide',
    'title' => 'Novo slide',
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

    document.querySelectorAll('[data-ativo-toggle]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-ativo-toggle');
            var ativo = btn.getAttribute('data-ativo-atual') === 'S' ? 'N' : 'S';

            postAcao('/carousel/ativo', { id: id, ativo: ativo }, function() {
                setTimeout(function() { location.reload(); }, 400);
            });
        });
    });

    document.querySelectorAll('[data-ordem]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            postAcao('/carousel/ordem', {
                id: btn.getAttribute('data-id'),
                direcao: btn.getAttribute('data-ordem')
            }, function() {
                setTimeout(function() { location.reload(); }, 400);
            });
        });
    });
});
</script>
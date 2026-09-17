<?php

use App\Service\AuthService;
use App\Service\DocService;
?>

<div class="header-bar">
    <a href="#" class="btn-novo" data-modal-open="modal-novo-documento"><i class="fa-solid fa-plus"></i> Novo documento</a>
    <a href="#" class="btn-novo btn-novo-outline" data-modal-open="modal-novo-diretorio"><i class="fa-solid fa-folder-plus"></i> Novo diretório</a>
    <a href="#" class="btn-novo btn-novo-outline" data-modal-open="modal-novo-subdiretorio"><i class="fa-solid fa-folder-open"></i> Nova subpasta</a>
    <a href="#" class="btn-novo btn-novo-secondary" data-modal-open="modal-copiar-permissoes"><i class="fa-solid fa-copy"></i> Copiar permissões</a>
    <a href="/documentos" class="btn-novo btn-novo-outline"><i class="fa-solid fa-eye"></i> Visualização</a>
</div>

<button type="button" class="scroll-top-btn" id="scrollTopBtn" title="Voltar ao topo" aria-label="Voltar ao topo"><i class="fa-solid fa-arrow-up"></i></button>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <label for="filtro-dir">Diretório:</label>
        <select name="id_dir" id="filtro-dir">
            <option value="">Todos os diretórios</option>
            <?php foreach ($diretorios as $dir): ?>
                <option value="<?= (int) $dir['id'] ?>" <?= (int) $dir['id'] === (int) ($filtroDir ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($dir['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="filtro-subdir">Subpasta:</label>
        <select name="id_subdir" id="filtro-subdir">
            <option value="">Todas as subpastas</option>
            <?php foreach ($subdiretorios as $sub): ?>
                <option value="<?= (int) $sub['id'] ?>" data-id-dir="<?= (int) $sub['id_dir'] ?>" <?= (int) $sub['id'] === (int) ($filtroSubdir ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($sub['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="filtro-nome">Documento:</label>
        <input type="text" id="filtro-nome" name="nome" value="<?= htmlspecialchars($filtroNome ?? '') ?>" placeholder="Nome do documento">
        <label for="filtro-funcao">Função com acesso:</label>
        <select name="id_funcao" id="filtro-funcao">
            <option value="">Todas as funções</option>
            <?php foreach ($funcoes as $funcao): ?>
                <option value="<?= htmlspecialchars((string) $funcao['codigo']) ?>" <?= (string) $funcao['codigo'] === (string) ($filtroFuncao ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($funcao['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filtrar</button>

        <?php if (!empty($filtroDir) || !empty($filtroSubdir) || !empty($filtroNome) || !empty($filtroFuncao)): ?>
            <a href="/documentos/gestao" class="btn-clear">Limpar filtro</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th></th>
                <th>Diretório</th>
                <th>Subpasta</th>
                <th>Documento</th>
                <th>Versão</th>
                <th>Tamanho</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documentos as $doc): ?>
                <tr>
                    <td><i class="fa-solid <?= DocService::iconeTipo($doc['tipo']) ?>"></i></td>
                    <td><?= htmlspecialchars($doc['diretorio']) ?></td>
                    <td><?= htmlspecialchars($doc['subdiretorio']) ?></td>
                    <td>
                        <?php if ($doc['existe']): ?>
                            <a class="doc-file-link" href="/documentos/visualizar/<?= (int) $doc['id_doc'] ?>" target="_blank">
                                <?= htmlspecialchars($doc['titulo']) ?>
                            </a>
                        <?php else: ?>
                            <?= htmlspecialchars($doc['titulo']) ?>
                        <?php endif; ?>
                        <?php if (!$doc['existe']): ?>
                            <span class="badge badge-pill badge-error">Sem arquivo</span>
                        <?php endif; ?>
                        <?php if ($doc['geral'] === 'S'): ?>
                            <span class="badge badge-pill badge-info">Geral</span>
                        <?php endif; ?>
                    </td>
                    <td>v<?= (int) $doc['versao'] ?></td>
                    <td><?= htmlspecialchars($doc['tamanho_texto']) ?></td>
                    <td class="doc-acoes">
                        <?php if ($doc['existe']): ?>
                            <a class="btn-doc btn-doc-ver" href="/documentos/visualizar/<?= (int) $doc['id_doc'] ?>" target="_blank">
                                <i class="fa-solid fa-eye"></i> Ver
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn-doc btn-doc-versao" data-modal-open="modal-nova-versao" data-id-doc="<?= (int) $doc['id_doc'] ?>" data-titulo="<?= htmlspecialchars($doc['titulo'], ENT_QUOTES) ?>">
                            <i class="fa-solid fa-arrow-up"></i> Nova versão
                        </button>
                        <button type="button" class="btn-doc btn-doc-perm" data-perm-toggle="<?= (int) $doc['id_doc'] ?>">
                            <i class="fa-solid fa-user-shield"></i> Permissões
                        </button>
                        <button type="button" class="btn-doc btn-doc-versoes" data-modal-open="modal-versoes" data-id-doc="<?= (int) $doc['id_doc'] ?>" data-titulo="<?= htmlspecialchars($doc['titulo'], ENT_QUOTES) ?>">
                            <i class="fa-solid fa-clock-rotate-left"></i> Versões
                        </button>
                        <form method="POST" action="/documentos/excluir-documento" class="doc-form-inline" onsubmit="return confirm('Excluir o documento e todas as suas versões?');">
                            <input type="hidden" name="id_doc" value="<?= (int) $doc['id_doc'] ?>">
                            <button type="submit" class="btn-doc btn-doc-danger"><i class="fa-solid fa-trash"></i> Excluir</button>
                        </form>
                    </td>
                </tr>
                <tr class="doc-perm-linha" data-perm-linha="<?= (int) $doc['id_doc'] ?>" hidden>
                    <td colspan="7">
                        <div class="doc-perm-painel">
                            <div class="doc-perm-geral">
                                <label>
                                    <input type="checkbox" data-perm-geral="<?= (int) $doc['id_doc'] ?>">
                                    Acesso geral (todas as funções)
                                </label>
                            </div>
                            <div class="doc-perm-chips" data-perm-chips="<?= (int) $doc['id_doc'] ?>"></div>
                            <div class="doc-perm-add">
                                <input type="text" data-perm-busca="<?= (int) $doc['id_doc'] ?>" placeholder="Buscar função para adicionar..." autocomplete="off">
                                <div class="doc-perm-opcoes" data-perm-opcoes="<?= (int) $doc['id_doc'] ?>" hidden></div>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($documentos)): ?>
                <tr><td colspan="7" class="doc-tree-empty">Nenhum documento cadastrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (AuthService::hasPermission('ti')): ?>
<div class="doc-acoes-dirs">
    <?php foreach ($diretorios as $dir): ?>
        <div class="doc-dir-admin">
            <i class="fa-solid fa-folder"></i> <?= htmlspecialchars($dir['nome']) ?>
            <form method="POST" action="/documentos/excluir-diretorio" class="doc-form-inline" onsubmit="return confirm('Excluir o diretório? (só é possível se estiver vazio)');">
                <input type="hidden" name="id" value="<?= (int) $dir['id'] ?>">
                <button type="submit" class="btn-doc btn-doc-danger"><i class="fa-solid fa-trash"></i></button>
            </form>            <?php $subs = array_filter($subdiretorios, fn($s) => (int) $s['id_dir'] === (int) $dir['id']); ?>
            <?php if (!empty($subs)): ?>
                <div class="doc-dir-admin-subs">
                    <?php foreach ($subs as $sub): ?>
                        <div>
                            <i class="fa-solid fa-folder-open"></i> <?= htmlspecialchars($sub['nome']) ?>
                            <form method="POST" action="/documentos/excluir-subdiretorio" class="doc-form-inline" onsubmit="return confirm('Excluir a subpasta? (só é possível se estiver vazia)');">
                                <input type="hidden" name="id" value="<?= (int) $sub['id'] ?>">
                                <button type="submit" class="btn-doc btn-doc-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php ob_start(); ?>
    <form method="POST" action="/documentos/upload" enctype="multipart/form-data" class="doc-upload-form">
        <label>Diretório:
            <select name="id_dir" id="doc-id-dir" required>
                <option value="">Selecione...</option>
                <?php foreach ($diretorios as $dir): ?>
                    <option value="<?= (int) $dir['id'] ?>"><?= htmlspecialchars($dir['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Subdiretório:
            <select name="id_subdir" id="doc-id-subdir" required>
                <option value="">Selecione o diretório primeiro...</option>
                <?php foreach ($subdiretorios as $sub): ?>
                    <option value="<?= (int) $sub['id'] ?>" data-id-dir="<?= (int) $sub['id_dir'] ?>"><?= htmlspecialchars($sub['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Arquivo (máx. 100 MB):
            <input type="file" name="docArquivo" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.bmp,.jpg,.jpeg,.png,.mp4" required>
        </label>
        <label class="doc-upload-geral">
            <input type="checkbox" name="geral" value="S" id="doc-upload-geral" checked>
            Permitido para todas as funções
        </label>
        <label id="doc-funcoes-label">
            Funções com acesso:
            <div class="doc-perm-painel">
                <div class="doc-perm-chips" id="doc-funcoes-chips"></div>
                <div class="doc-perm-add">
                    <input type="text" id="doc-funcoes-busca" placeholder="Buscar função para adicionar..." autocomplete="off">
                    <div class="doc-perm-opcoes" id="doc-funcoes-opcoes" hidden></div>
                </div>
            </div>
            <small>Digite para filtrar e clique na função para adicionar. Clique no × para remover.</small>
        </label>
        <div id="doc-funcoes-hidden"></div>
        <button type="submit" class="btn">Enviar documento</button>
    </form>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-novo-documento',
    'title' => 'Novo documento',
    'content' => $content,
]); ?>

<?php ob_start(); ?>
    <form method="POST" action="/documentos/diretorio" class="doc-upload-form">
        <label>Nome do diretório:
            <input type="text" name="nome" required maxlength="100" placeholder="Ex.: TREINAMENTOS MARKETING">
        </label>
        <button type="submit" class="btn">Criar diretório</button>
    </form>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-novo-diretorio',
    'title' => 'Novo diretório',
    'content' => $content,
]); ?>

<?php ob_start(); ?>
    <form method="POST" action="/documentos/subdiretorio" class="doc-upload-form">
        <label>Diretório:
            <select name="id_dir" id="doc-sub-id-dir" required>
                <option value="">Selecione...</option>
                <?php foreach ($diretorios as $dir): ?>
                    <option value="<?= (int) $dir['id'] ?>"><?= htmlspecialchars($dir['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Nome da subpasta:
            <input type="text" name="nome" required maxlength="100" placeholder="Ex.: TREINAMENTOS LOJA 31">
        </label>
        <button type="submit" class="btn">Criar subpasta</button>
    </form>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-novo-subdiretorio',
    'title' => 'Nova subpasta',
    'content' => $content,
]); ?>

<?php ob_start(); ?>
    <form method="POST" action="/documentos/nova-versao" enctype="multipart/form-data" class="doc-upload-form">
        <input type="hidden" name="id_doc" id="nversao-id-doc">
        <p id="nversao-titulo" class="doc-perm-titulo"></p>
        <label>Arquivo (máx. 100 MB):
            <input type="file" name="docArquivo" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.bmp,.jpg,.jpeg,.png,.mp4" required>
        </label>
        <p class="doc-perm-titulo">A nova versão passa a ser a atual do documento. As permissões são mantidas — não precisa informar funções novamente.</p>
        <button type="submit" class="btn">Enviar nova versão</button>
    </form>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-nova-versao',
    'title' => 'Nova versão do documento',
    'content' => $content,
]); ?>

<?php ob_start(); ?>
    <form method="POST" action="/documentos/copiar-permissoes" class="doc-upload-form">
        <label>Função de origem (tem as permissões):
            <select name="funcao_origem" required>
                <option value="">Selecione...</option>
                <?php foreach ($funcoes as $funcao): ?>
                    <option value="<?= htmlspecialchars((string) $funcao['codigo']) ?>"><?= htmlspecialchars($funcao['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Função de destino (recebe as permissões):
            <select name="funcao_destino" required>
                <option value="">Selecione...</option>
                <?php foreach ($funcoes as $funcao): ?>
                    <option value="<?= htmlspecialchars((string) $funcao['codigo']) ?>"><?= htmlspecialchars($funcao['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="doc-perm-titulo">Copia para a função de destino todas as permissões de documentos que a função de origem possui, sem remover as permissões já existentes no destino.</p>
        <button type="submit" class="btn">Copiar permissões</button>
    </form>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-copiar-permissoes',
    'title' => 'Copiar permissões entre funções',
    'content' => $content,
]); ?>

<?php ob_start(); ?>
    <div id="versoes-lista" class="doc-versoes"></div>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-versoes',
    'size' => 'large',
    'title' => 'Versões do documento',
    'content' => $content,
]); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var permissoes = <?= json_encode($permissoes, JSON_UNESCAPED_UNICODE) ?>;
    var versoes = <?= json_encode($versoes, JSON_UNESCAPED_UNICODE) ?>;

    var dirSel = document.getElementById('doc-id-dir');
    var subSel = document.getElementById('doc-id-subdir');
    var geralChk = document.getElementById('doc-upload-geral');
    var funcoesLabel = document.getElementById('doc-funcoes-label');

    if (dirSel && subSel) {
        function filtrarSubdiretorios() {
            var dir = dirSel.value;
            var atual = subSel.value;
            var valido = false;
            var options = subSel.querySelectorAll('option[data-id-dir]');
            for (var i = 0; i < options.length; i++) {
                if (options[i].getAttribute('data-id-dir') === dir) {
                    options[i].style.display = '';
                    if (options[i].value === atual) valido = true;
                } else {
                    options[i].style.display = 'none';
                }
            }
            if (!valido) subSel.value = '';
        }

        dirSel.addEventListener('change', filtrarSubdiretorios);
        filtrarSubdiretorios();
    }

    var filtroDirSel = document.getElementById('filtro-dir');
    var filtroSubSel = document.getElementById('filtro-subdir');

    if (filtroDirSel && filtroSubSel) {
        function filtrarFiltroSubdirs() {
            var dir = filtroDirSel.value;
            var atual = filtroSubSel.value;
            var valido = false;
            var options = filtroSubSel.querySelectorAll('option[data-id-dir]');

            for (var i = 0; i < options.length; i++) {
                if (options[i].getAttribute('data-id-dir') === dir) {
                    options[i].style.display = '';
                    if (options[i].value === atual) valido = true;
                } else {
                    options[i].style.display = 'none';
                }
            }

            if (!valido) filtroSubSel.value = '';
        }

        filtroDirSel.addEventListener('change', filtrarFiltroSubdirs);
        filtrarFiltroSubdirs();
    }

    if (geralChk && funcoesLabel) {
        function alternarFuncoes() {
            funcoesLabel.style.display = geralChk.checked ? 'none' : 'block';
        }
        geralChk.addEventListener('change', alternarFuncoes);
        alternarFuncoes();
    }

    var funcoesLista = <?= json_encode($funcoes, JSON_UNESCAPED_UNICODE) ?>;
    var funcoesMap = {};
    funcoesLista.forEach(function(f) { funcoesMap[f.codigo] = f.nome; });

    function normTxt(s) {
        return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

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

    function chipPermissao(idDoc, cod) {
        var nome = funcoesMap[cod] || cod;
        return '<span class="doc-chip">' + escHtml(nome) +
            '<button type="button" class="doc-chip-remover" data-rem-doc="' + idDoc + '" data-rem-cod="' + escHtml(cod) + '" title="Remover permissão">&times;</button>' +
            '</span>';
    }

    function renderChips(idDoc, container) {
        var dados = permissoes[idDoc] || { geral: false, funcoes: [] };
        var html = '';

        if (dados.funcoes.length === 0) {
            html = '<span class="doc-chip doc-chip-vazio">Nenhuma função com acesso</span>';
        } else {
            dados.funcoes
                .slice()
                .sort(function(a, b) {
                    var na = (funcoesMap[a] || a).toLowerCase();
                    var nb = (funcoesMap[b] || b).toLowerCase();
                    return na.localeCompare(nb);
                })
                .forEach(function(cod) { html += chipPermissao(idDoc, cod); });
        }

        container.innerHTML = html;
    }

    function abrirPainelPermissoes(idDoc) {
        var linha = document.querySelector('[data-perm-linha="' + idDoc + '"]');
        if (!linha) return;

        var aberta = !linha.hidden;
        linha.hidden = aberta;
        if (aberta) return;

        var dados = permissoes[idDoc] || { geral: false, funcoes: [] };
        var geralChk = document.querySelector('[data-perm-geral="' + idDoc + '"]');
        if (geralChk) geralChk.checked = !!dados.geral;

        var chips = document.querySelector('[data-perm-chips="' + idDoc + '"]');
        if (chips) renderChips(idDoc, chips);
    }

    document.querySelectorAll('[data-perm-toggle]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            abrirPainelPermissoes(btn.getAttribute('data-perm-toggle'));
        });
    });

    document.querySelectorAll('[data-perm-geral]').forEach(function(chk) {
        chk.addEventListener('change', function() {
            var idDoc = chk.getAttribute('data-perm-geral');
            var ligar = chk.checked;

            if (!ligar) {
                var dados = permissoes[idDoc] || { geral: false, funcoes: [] };

                if (dados.funcoes.length === 0 && !confirm('Sem funções com acesso, o documento ficará invisível para todos. Continuar?')) {
                    chk.checked = true;
                    return;
                }
            }

            postAcao('/documentos/geral', { id_doc: idDoc, geral: ligar ? 'S' : 'N' }, function() {
                var dados = permissoes[idDoc] = permissoes[idDoc] || { geral: false, funcoes: [] };
                dados.geral = ligar;
                if (ligar) dados.funcoes = [];

                var chips = document.querySelector('[data-perm-chips="' + idDoc + '"]');
                if (chips) renderChips(idDoc, chips);
            });
        });
    });

    document.addEventListener('click', function(e) {
        var rm = e.target.closest('.doc-chip-remover');
        if (!rm) return;

        var idDoc = rm.getAttribute('data-rem-doc');
        var cod = rm.getAttribute('data-rem-cod');

        postAcao('/documentos/permissao/remover', { id_doc: idDoc, codfuncao: cod }, function() {
            var dados = permissoes[idDoc] = permissoes[idDoc] || { geral: false, funcoes: [] };
            dados.funcoes = dados.funcoes.filter(function(f) { return f !== cod; });

            var chips = document.querySelector('[data-perm-chips="' + idDoc + '"]');
            if (chips) renderChips(idDoc, chips);
        });
    });

    function montarCombo(input, opcoes, onSelect) {
        function filtrar() {
            var q = normTxt(input.value);
            var html = '';
            var cont = 0;

            funcoesLista.forEach(function(f) {
                if (normTxt(f.nome).indexOf(q) !== -1 && cont < 50) {
                    html += '<button type="button" class="doc-perm-opcao" data-cod="' + escHtml(f.codigo) + '">' + escHtml(f.nome) + '</button>';
                    cont++;
                }
            });

            if (html === '') html = '<div class="doc-perm-sem-resultado">Nenhuma função encontrada</div>';
            opcoes.innerHTML = html;
            opcoes.hidden = false;
        }

        function esconder() {
            setTimeout(function() { opcoes.hidden = true; }, 150);
        }

        input.addEventListener('focus', filtrar);
        input.addEventListener('input', filtrar);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var opt = opcoes.querySelector('.doc-perm-opcao');
                if (opt) opt.click();
            }
            if (e.key === 'Escape') opcoes.hidden = true;
        });
        input.addEventListener('blur', esconder);

        opcoes.addEventListener('mousedown', function(e) { e.preventDefault(); });
        opcoes.addEventListener('click', function(e) {
            var opt = e.target.closest('.doc-perm-opcao');
            if (opt) onSelect(opt.getAttribute('data-cod'));
        });

        return { filtrar: filtrar };
    }

    document.querySelectorAll('[data-perm-busca]').forEach(function(input) {
        var idDoc = input.getAttribute('data-perm-busca');
        var opcoes = document.querySelector('[data-perm-opcoes="' + idDoc + '"]');

        var combo = montarCombo(input, opcoes, function(cod) {
            postAcao('/documentos/permissao/adicionar', { id_doc: idDoc, codfuncao: cod }, function() {
                var dados = permissoes[idDoc] = permissoes[idDoc] || { geral: false, funcoes: [] };

                if (dados.funcoes.indexOf(cod) === -1) dados.funcoes.push(cod);

                var chips = document.querySelector('[data-perm-chips="' + idDoc + '"]');
                if (chips) renderChips(idDoc, chips);

                input.value = '';
                combo.filtrar();
            });
        });
    });

    var uploadBusca = document.getElementById('doc-funcoes-busca');
    var uploadOpcoes = document.getElementById('doc-funcoes-opcoes');
    var uploadChips = document.getElementById('doc-funcoes-chips');
    var uploadHidden = document.getElementById('doc-funcoes-hidden');
    var funcoesUpload = [];

    function sincronizarFuncoesUpload() {
        var html = '';

        if (funcoesUpload.length === 0) {
            html = '<span class="doc-chip doc-chip-vazio">Nenhuma função selecionada</span>';
        } else {
            funcoesUpload.forEach(function(cod) {
                html += '<span class="doc-chip">' + escHtml(funcoesMap[cod] || cod) +
                    '<button type="button" class="doc-chip-remover" data-up-rem="' + escHtml(cod) + '" title="Remover">&times;</button></span>';
            });
        }

        uploadChips.innerHTML = html;

        uploadHidden.innerHTML = '';
        funcoesUpload.forEach(function(cod) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'funcoes[]';
            inp.value = cod;
            uploadHidden.appendChild(inp);
        });
    }

    if (uploadBusca && uploadOpcoes) {
        sincronizarFuncoesUpload();

        var comboUpload = montarCombo(uploadBusca, uploadOpcoes, function(cod) {
            if (funcoesUpload.indexOf(cod) === -1) funcoesUpload.push(cod);
            sincronizarFuncoesUpload();
            uploadBusca.value = '';
            comboUpload.filtrar();
        });
    }

    document.addEventListener('click', function(e) {
        var rm = e.target.closest('[data-up-rem]');
        if (!rm) return;

        funcoesUpload = funcoesUpload.filter(function(c) { return c !== rm.getAttribute('data-up-rem'); });
        sincronizarFuncoesUpload();
    });

    var formCopiar = document.querySelector('#modal-copiar-permissoes form');

    if (formCopiar) {
        formCopiar.addEventListener('submit', function(e) {
            e.preventDefault();

            var dados = new URLSearchParams(new FormData(formCopiar));

            fetch(formCopiar.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'fetch'
                },
                body: dados.toString()
            }).then(function(r) {
                return r.json();
            }).then(function(res) {
                if (res.success) {
                    mostrarToast('success', res.message);
                } else {
                    mostrarToast('error', res.message);
                }
            }).catch(function() {
                mostrarToast('error', 'Erro de comunicação com o servidor.');
            });
        });
    }

    document.querySelectorAll('[data-modal-open="modal-nova-versao"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('nversao-id-doc').value = btn.getAttribute('data-id-doc');
            document.getElementById('nversao-titulo').textContent = btn.getAttribute('data-titulo');
        });
    });

    document.querySelectorAll('[data-modal-open="modal-versoes"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var idDoc = btn.getAttribute('data-id-doc');
            var lista = versoes[idDoc] || [];
            var html = '';

            if (lista.length === 0) {
                html = '<p class="doc-tree-empty">Nenhuma versão.</p>';
            }

            lista.forEach(function(v) {
                var atual = v.atual ? ' <span class="badge badge-pill badge-info">Atual</span>' : '';
                var semArquivo = v.existe ? '' : ' <span class="badge badge-pill badge-error">Sem arquivo</span>';
                var restaurar = v.atual ? '' :
                    '<form method="POST" action="/documentos/versao" class="doc-form-inline">' +
                    '<input type="hidden" name="id_doc" value="' + idDoc + '">' +
                    '<input type="hidden" name="id_versao" value="' + v.id + '">' +
                    '<button type="submit" class="btn-doc"><i class="fa-solid fa-rotate-left"></i> Tornar atual</button>' +
                    '</form>';
                var excluir = v.atual ? '' :
                    '<form method="POST" action="/documentos/versao/excluir" class="doc-form-inline" onsubmit="return confirm(\'Excluir esta versão? O arquivo será removido se nenhuma outra versão usar o mesmo caminho.\');">' +
                    '<input type="hidden" name="id_doc" value="' + idDoc + '">' +
                    '<input type="hidden" name="id_versao" value="' + v.id + '">' +
                    '<button type="submit" class="btn-doc btn-doc-danger"><i class="fa-solid fa-trash"></i> Excluir</button>' +
                    '</form>';

                html += '<div class="doc-file">' +
                    '<span class="doc-file-versao">v' + v.versao + '</span>' +
                    '<span class="doc-file-tamanho">' + v.tamanho_texto + '</span>' +
                    '<span class="doc-file-tamanho">' + (v.dt_upload || '') + '</span>' +
                    atual + semArquivo + restaurar + excluir +
                    '</div>';
            });

            document.getElementById('versoes-lista').innerHTML = html;
        });
    });
});

(function() {
    var btn = document.getElementById('scrollTopBtn');
    if (!btn) return;

    window.addEventListener('scroll', function() {
        btn.classList.toggle('scroll-top-btn--visivel', window.scrollY > 300);
    });

    btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
</script>

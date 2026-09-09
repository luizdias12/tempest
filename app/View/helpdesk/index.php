<?php

use App\Service\StatusService;
use App\Service\GenericService;
use App\Service\HelpHistoricoService;

// dd($chamados);
?>

<div class="header-bar">
    <a href="#" class="btn-novo" data-modal-open="modal-novo-chamado"><i class="fa-solid fa-plus"></i> Novo chamado</a>
</div>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <label for="id">Nº:</label>
        <input type="text" id="id" name="id" value="<?= htmlspecialchars($id ?? '') ?>" placeholder="Nº do Chamado">
        <label for="emitente">Nome:</label>
        <input type="text" id="emitente" name="emitente" value="<?= htmlspecialchars($emitente ?? '') ?>" placeholder="Nome do usuário">
        <label for="status">Status:</label>
        <?php if (!empty($stats = StatusService::all())): ?>
            <?php component('select', [
                'id' => 'status',
                'name' => 'status',
                'placeholder' => 'Todos os status',
                'options' => $stats,
                'valueKey' => 'status',
                'labelKey' => 'descricao',
                'selected' => $status,
            ]); ?>
        <?php endif; ?>
        <label for="local">Local:</label>
        <?php if (!empty($locais = GenericService::listarLocais())): ?>
            <?php component('select', [
                'id' => 'local',
                'name' => 'local',
                'placeholder' => 'Todos os locais',
                'options' => $locais,
                'valueKey' => 'local',
                'labelKey' => 'local',
                'selected' => $local,
            ]); ?>
        <?php endif; ?>
        <label for="meus" class="filter-toggle">
            <input type="checkbox" id="meus" name="meus" value="1" <?= !empty($meus) ? 'checked' : '' ?>>
            Atribuídos a mim
        </label>
        <label for="openbyme" class="filter-toggle">
            <input type="checkbox" id="openbyme" name="openbyme" value="1" <?= !empty($openbyme) ? 'checked' : '' ?>>
            Meus chamados
        </label>
        <button type="submit">Filtrar</button>

        <?php
        if (!empty($id) || !empty($emitente) || !empty($status) || !empty($local) || !empty($meus) || !empty($openbyme)): ?>
            <a href="?" class="btn-clear">Limpar filtro</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th><i class="fa-solid fa-clipboard-list"></i></th>
                <th>Nº</th>
                <th></th>
                <th>Usuário</th>
                <th>Data Abertura</th>
                <th>Data Solução</th>
                <th>Andamento</th>
                <th>Grupo</th>
                <th>Sub-Grupo</th>
                <th>Tópico</th>
                <th>Status</th>
                <th>Local</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $pendentes = HelpHistoricoService::visualizacoesPendentes(array_column($chamados, 'id'));
            $atualizados = HelpHistoricoService::chamadosAtualizados(array_column($chamados, 'id'));
            $anexosAbertura = HelpHistoricoService::anexosAbertura(array_column($chamados, 'id'));
            $possuiAnexo = HelpHistoricoService::possuiAnexo(array_column($chamados, 'id'));
            // dd($possuiAnexo);
            ?>
            <?php foreach ($chamados as $chamado):
                $new = in_array($chamado['id'], $pendentes, true) ? 'Novo' : '';
                $updated = in_array($chamado['id'], $atualizados, true) ? 'Atualizado' : '';
                $file = in_array($chamado['id'], $possuiAnexo, true) ? '<i class="fa-solid fa-paperclip"></i>' : '';
            ?>

                <tr data-row
                    data-id="<?= htmlspecialchars($chamado['id']) ?>"
                    data-nome="<?= htmlspecialchars($chamado['nome']) ?>"
                    data-funcao="<?= htmlspecialchars($chamado['funcao']) ?>"
                    data-chapa="<?= htmlspecialchars($chamado['chapa']) ?>"
                    data-dt-abertura="<?= htmlspecialchars(!empty($chamado['dt_abertura']) ? date('d-m-Y H:i', strtotime($chamado['dt_abertura'])) : '-') ?>"
                    data-dt-solucao="<?= htmlspecialchars(!empty($chamado['dt_solucao']) ? date('d-m-Y H:i', strtotime($chamado['dt_solucao'])) : '-') ?>"
                    data-sla="<?= htmlspecialchars($chamado['sla'] ?? '-') ?>"
                    data-motivo-canc="<?= htmlspecialchars($chamado['codmotivo'] ?? '') ?>"
                    data-status="<?= htmlspecialchars($chamado['status'] ?? '') ?>"
                    data-idgrupo="<?= htmlspecialchars($chamado['idgrupo'] ?? '') ?>"
                    data-idsubgrupo="<?= htmlspecialchars($chamado['idsubgrupo'] ?? '') ?>"
                    data-id-resp="<?= htmlspecialchars($chamado['id_resp'] ?? '') ?>"
                    data-grupo="<?= htmlspecialchars($chamado['grupo'] ?? '-') ?>"
                    data-subgrupo="<?= htmlspecialchars($chamado['subgrupo'] ?? '-') ?>"
                    data-cab-problema="<?= htmlspecialchars($chamado['cab_problema'] ?? '-') ?>"
                    data-desc-problema="<?= htmlspecialchars($chamado['desc_problema'] ?? '-') ?>"
                    data-local="<?= htmlspecialchars($chamado['local'] ?? '-') ?>"
                    data-responsavel="<?= htmlspecialchars($chamado['responsavel'] ?? '-') ?>"
                    data-grupo-subgrupo="<?= htmlspecialchars($chamado['grupo'] . ' > ' . $chamado['subgrupo']) ?>"
                    data-ramal="<?= $chamado['ramal'] !== '' ? htmlspecialchars($chamado['ramal']) : '-' ?>"
                    data-email="<?= $chamado['email'] !== '' ? htmlspecialchars($chamado['email']) : '-' ?>"
                    data-cpf-ab="<?= htmlspecialchars($chamado['cpf_ab'] ?? '') ?>"
                    data-file-abertura="<?= handleAttach(htmlspecialchars($anexosAbertura[$chamado['id']]), $chamado['id']) ?>"
                    data-dtview="<?= htmlspecialchars(!empty($chamado['dtview']) ? date('d-m-Y H:i', strtotime($chamado['dtview'])) : '-') ?>">

                    <td>
                        <?php if (!empty($contagemHistoricos[$chamado['id']]) || $file !== ''): ?>
                            <span class="mini-badge badge-historico"><?= $file ?><?= $contagemHistoricos[$chamado['id']] ?></span>
                        <?php endif; ?>
                    </td>
                    <td><a href="#" class="chamado-id" data-modal-open="modal-chamado"><?= $chamado['id'] ?></a></td>
                    <td><span class="mini-badge badge-new"><?= $new !== '' ? $new : $updated ?></span></td>
                    <td><?= (initcap($chamado['nome'])) ?></td>
                    <td><?= !empty($chamado['dt_abertura']) ? date('d-m-Y H:i', strtotime($chamado['dt_abertura'])) : '-' ?></td>
                    <td><?= !empty($chamado['dt_solucao']) ? date('d-m-Y H:i', strtotime($chamado['dt_solucao'])) : '-' ?></td>
                    <td>
                        <?php if (!empty($andamentos[$chamado['id']])): ?>
                            <?php $andamento = $andamentos[$chamado['id']]; ?>
                            <div class="sla-progress" title="<?= htmlspecialchars($andamento['label']) ?> (SLA <?= $andamento['pct'] ?>%)">
                                <div class="sla-progress-bar <?= $andamento['pct'] >= 100 ? 'sla-past' : ($andamento['pct'] >= 80 ? 'sla-warn' : ($andamento['pct'] >= 50 ? 'sla-mid' : 'sla-ok')) ?>" style="width: <?= $andamento['pct'] ?>%"></div>
                            </div>
                            <span class="sla-label"><?= htmlspecialchars($andamento['label']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $chamado['grupo'] ?></td>
                    <td><?= $chamado['subgrupo'] ?></td>
                    <td><?= $chamado['cab_problema'] ?></td>
                    <td><?= $chamado['status_desc'] ?></td>
                    <td><?= $chamado['local'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?= pagination(
        $meta,
        null,
        array_filter(
            [
                'id' => $id ?? '',
                'emitente' => $emitente ?? '',
                'status' => $status ?? '',
                'local' => $local ?? '',
                'meus' => $meus ?? '',
                'openbyme' => $openbyme ?? ''
            ]
        )
    ) ?>
</div>

<?php
$filtrosUrl = http_build_query(array_filter([
    'page' => $meta['page'] ?? '',
    'id' => $id ?? '',
    'emitente' => $emitente ?? '',
    'status' => $status ?? '',
    'local' => $local ?? '',
    'meus' => $meus ?? '',
    'openbyme' => $openbyme ?? '',
], fn($v) => $v !== ''));

ob_start();
?>
<span hidden data-field="cpf-ab"></span>
<div class="chamado-view">
    <i class="fa-solid fa-check-double"></i> <span data-field="dtview"></span>
</div>
<div class="chamado-details">
    <div class="chamado-top">
        <h3>Tópico</h3>
    </div>
    <p><span data-field="cab-problema"></span></p>
    <div class="chamado-top">
        <h4>Descrição</h4>
    </div>
    <pre data-field="desc-problema"></pre>
    <p hidden data-file-abertura-wrap>
        <strong>Anexo da abertura:</strong>
        <a href="#" class="anexo-link" data-file-abertura-link download><i class="fa-solid fa-paperclip"></i> baixar</a>
    </p>
    <table style="margin: 1rem 0;">
        <thead>
            <tr>
                <th scope="col">Data Abertura</th>
                <th scope="col">Chapa</th>
                <th scope="col">Nome</th>
                <th scope="col">Função</th>
                <th scope="col">Ramal</th>
                <th scope="col">Email</th>
                <th scope="col">Local</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span data-field="dt-abertura"></span></td>
                <td><span data-field="chapa"></span></td>
                <td><span data-field="nome"></span></td>
                <td><span data-field="funcao"></span></td>
                <td><span data-field="ramal"></span></td>
                <td><span data-field="email"></span></td>
                <td><span data-field="local"></span></td>
            </tr>
        </tbody>
    </table>
    <form method="POST" action="/helpdesk/update<?= $filtrosUrl !== '' ? '?' . $filtrosUrl : '' ?>" class="chamado-form">
        <input type="hidden" name="id" data-field="id">
        <div class="chamado-form-row">
            <label>Status:
                <?php component('select', [
                    'name' => 'status',
                    'placeholder' => 'Selecione o status',
                    'options' => StatusService::all(),
                    'valueKey' => 'status',
                    'labelKey' => 'descricao',
                    'attrs' => 'data-field="status"' . ($isSuporte ? '' : ' disabled'),
                ]); ?>
            </label>
            <label class="motivo-cancelamento" data-motivo-cancelamento style="display:none;">Motivo do cancelamento:
                <select class="motivo-cancelamento" data-field="motivo-canc" name="motivo" id="motivo-cancelamento" <?= !$isSuporte ? "disabled" : "" ?>>
                    <option value="">Selecione o motivo</option>
                    <?php foreach ($motivosCancelamento as $motivoCanc): ?>
                        <option value="<?= htmlspecialchars($motivoCanc['id']) ?>"><?= htmlspecialchars($motivoCanc['motivo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Grupo:
                <select name="idgrupo" data-field="idgrupo" <?= !$isSuporte ? "disabled" : "" ?>>
                    <option value="">Selecione o grupo</option>
                    <?php foreach ($grupos as $grupo): ?>
                        <option value="<?= htmlspecialchars($grupo['id_grupo']) ?>"><?= htmlspecialchars($grupo['descricao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Sub-Grupo:
                <select name="idsubgrupo" data-field="idsubgrupo" <?= !$isSuporte ? "disabled" : "" ?>>
                    <option value="">Selecione o sub-grupo</option>
                    <?php foreach ($subgrupos as $sg): ?>
                        <option value="<?= htmlspecialchars($sg['id']) ?>" data-idgrupo="<?= htmlspecialchars($sg['idgrupo']) ?>"><?= htmlspecialchars($sg['descricao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Solicitante:
                <select name="cpf_ab" data-field="cpf-ab" <?= !$isSuporte ? "disabled" : "" ?>>
                    <option value="">Selecione o solicitante</option>
                    <?php foreach ($funcionarios as $func): ?>
                        <option value="<?= htmlspecialchars($func['cpf']) ?>"><?= htmlspecialchars($func['nome']) ?><?= !empty($func['chapa']) ? ' - ' . htmlspecialchars($func['chapa']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Responsável:
                <?php component('select', [
                    'name' => 'id_resp',
                    'placeholder' => 'Sem responsável',
                    'options' => $responsaveis,
                    'valueKey' => 'cpf',
                    'labelKey' => 'nome',
                    'attrs' => 'data-field="id-resp"' . ($isSuporte ? '' : ' disabled'),
                ]); ?>
            </label>
            <button type="submit" class="btn" <?= !$isSuporte ? "disabled" : "" ?>>Salvar alterações</button>
        </div>
    </form>

    <div class="chamado-top">
        <h4>Histórico</h4>
    </div>
    <form method="POST" action="/helpdesk/interacao<?= $filtrosUrl !== '' ? '?' . $filtrosUrl : '' ?>" class="chamado-form" enctype="multipart/form-data">
        <input type="hidden" name="id" data-field="id">
        <textarea name="mensagem" rows="6" placeholder="Escreva uma interação no chamado..." required></textarea>
        <label>Anexo (máx. 5 MB):
            <input type="file" name="helpAttach" accept=".jpg,.jpeg,.png,.bmp,.pdf,.xls,.xlsx,.doc,.docx">
        </label>
        <button type="submit" class="btn">Registrar histórico</button>
    </form>
    <div class="historico-list">
        <?php if (empty($hist)): ?>
            <p class="historico-empty">Nenhum histórico registrado.</p>
        <?php else: ?>
            <?php foreach ($hist as $item): ?>
                <div class="historico-item <?= !empty($openCpf) && !empty($item['id_usu']) && $item['id_usu'] === $openCpf ? 'historico-item--own' : 'historico-item--other' ?>">
                    <div class="historico-meta">
                        <strong><?= htmlspecialchars(initcap($item['nome'] ?? 'Sistema')) ?></strong>
                        <span><?= date('d-m-Y H:i', strtotime($item['data_hist'])) ?></span>
                    </div>
                    <p><?= htmlspecialchars($item['historico']) ?></p>
                    <?php if (!empty($item['file_str'])): ?>
                        <p><a href="<?= htmlspecialchars(handleAttach($item['file_str'], (int) $open)) ?>" class="anexo-link" download><i class="fa-solid fa-paperclip"></i> Anexo</a></p>
                    <?php endif; ?>
                    <?php if (!empty($item['dtview'])): ?>
                        <span class="historico-viewed">Visualizado em <?= date('d-m-Y H:i', strtotime($item['dtview'])) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-chamado',
    'size' => 'xlarge',
    'title' => 'Chamado - Nº <span data-field="id"></span> | <span data-field="grupo-subgrupo"></span>',
    'content' => $content,
]); ?>

<?php
ob_start();
?>
<form method="POST" action="/helpdesk/novo" class="chamado-form" enctype="multipart/form-data">
    <?php if ($isSuporte): ?>
        <div class="chamado-form-row">
            <label>Solicitante:
                <select name="cpf_ab" id="novo-cpf-ab">
                    <option value="">Eu (padrão)</option>
                    <?php foreach ($funcionarios as $func): ?>
                        <option value="<?= htmlspecialchars($func['cpf']) ?>"><?= htmlspecialchars($func['nome']) ?><?= !empty($func['chapa']) ? ' - ' . htmlspecialchars($func['chapa']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    <?php endif; ?>
    <div class="chamado-form-row">
        <label>Grupo:
            <select name="idgrupo" id="novo-idgrupo" required>
                <option value="">Selecione o grupo</option>
                <?php foreach ($grupos as $grupo): ?>
                    <option value="<?= htmlspecialchars($grupo['id_grupo']) ?>"><?= htmlspecialchars($grupo['descricao']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Sub-Grupo:
            <select name="idsubgrupo" id="novo-idsubgrupo" required>
                <option value="">Selecione o sub-grupo</option>
                <?php foreach ($subgrupos as $sg): ?>
                    <option value="<?= htmlspecialchars($sg['id']) ?>" data-idgrupo="<?= htmlspecialchars($sg['idgrupo']) ?>"><?= htmlspecialchars($sg['descricao']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <div class="chamado-form-row">
        <label>Tópico:
            <input type="text" name="cab_problema" maxlength="100" required placeholder="Resumo do problema">
        </label>
    </div>
    <label>Descrição:
        <textarea name="desc_problema" rows="4" maxlength="3000" required placeholder="Descreva o problema..."></textarea>
    </label>
    <label>Anexo (opcional, máx. 5 MB):
        <input type="file" name="helpAttach" accept=".jpg,.jpeg,.png,.bmp,.pdf,.xls,.xlsx,.doc,.docx">
    </label>
    <button type="submit" class="btn">Abrir chamado</button>
</form>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-novo-chamado',
    'title' => 'Novo chamado',
    'content' => $content,
]); ?>

<?php
ob_start();
?>
<div class="anexo-preview" data-anexo-preview>
    <p class="anexo-preview-empty">Selecione um anexo para visualizar.</p>
</div>
<?php $content = ob_get_clean(); ?>

<?php component('modal', [
    'id' => 'modal-anexo',
    'size' => 'large',
    'title' => 'Anexo',
    'content' => $content,
    'footer' => '<a href="#" class="btn-download" id="anexo-download-btn" download><i class="fa-solid fa-download"></i> Baixar arquivo</a>',
]); ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var openId = <?= json_encode((int) ($open ?? 0)) ?>;
        if (openId) {
            var row = document.querySelector('tr[data-id="' + openId + '"]');
            if (row) {
                var trigger = row.querySelector('[data-modal-open]');
                if (trigger) {
                    setTimeout(function() {
                        trigger.click();
                    }, 0);
                }
            }
        }

        var pares = [];

        function vincularFiltroSubgrupos(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            var grupoSel = modal.querySelector('select[name="idgrupo"]');
            var subSel = modal.querySelector('select[name="idsubgrupo"]');
            if (!grupoSel || !subSel) return;

            function filtrarSubgrupos() {
                var grupo = grupoSel.value;
                var atual = subSel.value;
                var options = subSel.querySelectorAll('option[data-idgrupo]');
                var valido = false;
                for (var i = 0; i < options.length; i++) {
                    if (options[i].getAttribute('data-idgrupo') === grupo) {
                        options[i].style.display = '';
                        if (options[i].value === atual) valido = true;
                    } else {
                        options[i].style.display = 'none';
                    }
                }
                if (!valido) subSel.value = '';
            }

            grupoSel.addEventListener('change', filtrarSubgrupos);
            pares.push([modal, filtrarSubgrupos]);
        }

        vincularFiltroSubgrupos('modal-chamado');
        vincularFiltroSubgrupos('modal-novo-chamado');

        if (pares.length) {
            document.addEventListener('click', function(e) {
                if (e.target.closest('[data-modal-open]')) {
                    setTimeout(function() {
                        for (var i = 0; i < pares.length; i++) {
                            if (pares[i][0].style.display === 'flex') {
                                pares[i][1]();
                            }
                        }
                    }, 0);
                }
            });
        }

        var sincronizarMotivos = [];

        function vincularMotivoCancelamento(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            var statusSel = modal.querySelector('select[name="status"]');
            var wrap = modal.querySelector('[data-motivo-cancelamento]');
            if (!statusSel || !wrap) return;
            var motivoSel = wrap.querySelector('select');

            function sincronizarMotivo() {
                var mostrar = statusSel.value === 'C';
                wrap.style.display = mostrar ? '' : 'none';
                motivoSel.required = mostrar;
            }

            statusSel.addEventListener('change', sincronizarMotivo);
            sincronizarMotivos.push([modal, sincronizarMotivo]);
        }

        vincularMotivoCancelamento('modal-chamado');

        if (sincronizarMotivos.length) {
            document.addEventListener('click', function(e) {
                if (e.target.closest('[data-modal-open]')) {
                    setTimeout(function() {
                        for (var i = 0; i < sincronizarMotivos.length; i++) {
                            if (sincronizarMotivos[i][0].style.display === 'flex') {
                                sincronizarMotivos[i][1]();
                            }
                        }
                    }, 0);
                }
            });
        }
    });

    function mostrarMensagemHistorico(list, msg) {
        list.innerHTML = '';
        var p = document.createElement('p');
        p.className = 'historico-empty';
        p.textContent = msg;
        list.appendChild(p);
    }

    function escHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function mostrarToastHistorico(tipo, msg) {
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

        t.querySelector('.toast-close').addEventListener('click', function() {
            t.remove();
        });
        setTimeout(function() {
            t.remove();
        }, 4000);
    }

    function modalId(modal) {
        var inp = modal.querySelector('input[name="id"][data-field="id"]');
        return inp ? inp.value : '';
    }

    function modalCpfAbertura(modal) {
        var id = modalId(modal);
        var row = document.querySelector('tr[data-id="' + id + '"]');
        return row ? row.getAttribute('data-cpf-ab') : '';
    }

    function carregarHistoricoModal(modal, list) {
        var id = modalId(modal);
        if (!id) return;

        mostrarMensagemHistorico(list, 'Carregando histórico...');

        fetch('/helpdesk/historico/' + encodeURIComponent(id))
            .then(function(res) {
                return res.json();
            })
            .then(function(data) {
                if (data.error) {
                    mostrarMensagemHistorico(list, data.error);
                    return;
                }
                renderHistorico(list, data.hist, modalCpfAbertura(modal));
            })
            .catch(function() {
                mostrarMensagemHistorico(list, 'Erro ao carregar histórico.');
            });
    }

    function enviarFormModal(form) {
        var modal = form.closest('.modal');
        var btn = form.querySelector('button[type="submit"]');
        var btnOriginal = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span> Enviando...';
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'fetch'
            },
            body: new FormData(form)
        }).then(function(r) {
            return r.json();
        }).then(function(res) {
            if (res.success) {
                mostrarToastHistorico('success', res.message);
                if (modal) {
                    var list = modal.querySelector('.historico-list');
                    if (list) carregarHistoricoModal(modal, list);

                    var textarea = form.querySelector('textarea[name="mensagem"]');
                    if (textarea) textarea.value = '';

                    var fileInput = form.querySelector('input[type="file"][name="helpAttach"]');
                    if (fileInput) fileInput.value = '';

                    var wrapper = form.querySelector('[data-anexo-status]');
                    if (wrapper) wrapper.remove();
                }
            } else {
                console.error('Erro ao enviar formulário:', res.message);
                mostrarToastHistorico('error', res.message);
            }
        }).catch(function(err) {
            console.error('Erro de comunicação ao enviar formulário:', err);
            mostrarToastHistorico('error', 'Erro de comunicação com o servidor.');
        }).finally(function() {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = btnOriginal;
            }
        });
    }

    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        if (!form.classList.contains('chamado-form')) return;

        var action = form.getAttribute('action') || '';
        if (action.indexOf('/helpdesk/update') === -1 && action.indexOf('/helpdesk/interacao') === -1) return;

        e.preventDefault();
        enviarFormModal(form);
    });

    function renderHistorico(list, items, cpfAb) {
        list.innerHTML = '';
        if (!items || items.length === 0) {
            mostrarMensagemHistorico(list, 'Nenhum histórico registrado.');
            return;
        }
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var div = document.createElement('div');
            var ehUsuario = !!cpfAb && !!item.id_usu && item.id_usu === cpfAb;
            div.className = ehUsuario ? 'historico-item historico-item--own' : 'historico-item historico-item--other';

            var meta = document.createElement('div');
            meta.className = 'historico-meta';

            var strong = document.createElement('strong');
            strong.textContent = item.nome || 'Sistema';
            meta.appendChild(strong);

            var span = document.createElement('span');
            span.textContent = item.data_hist || '';
            meta.appendChild(span);

            div.appendChild(meta);

            var p = document.createElement('p');
            p.textContent = item.historico || '';
            div.appendChild(p);

            if (item.file_str) {
                var pa = document.createElement('p');
                var a = document.createElement('a');
                a.href = item.file_str;
                a.className = 'anexo-link';
                a.textContent = ' Anexo';
                a.setAttribute('download', '');
                var icone = document.createElement('i');
                icone.className = 'fa-solid fa-paperclip';
                a.prepend(icone);
                pa.appendChild(a);
                div.appendChild(pa);
            }

            if (item.dtview) {
                var spanv = document.createElement('span');
                spanv.className = 'historico-viewed';
                var iconev = document.createElement('i');
                iconev.className = 'fa-solid fa-check-double';
                spanv.appendChild(iconev);
                spanv.appendChild(document.createTextNode(' ' + (item.dtview || '')));
                div.appendChild(spanv);
            }

            list.appendChild(div);
        }
    }

    function atualizarAnexo(form, fileInput, file) {
        var label = fileInput.closest('label');
        var destino = label ? label.parentNode : fileInput.parentNode;
        var wrapper = form.querySelector('[data-anexo-status]');
        if (wrapper) wrapper.remove();

        wrapper = document.createElement('div');
        wrapper.setAttribute('data-anexo-status', '');
        wrapper.style.cssText = 'display:flex;align-items:center;gap:8px;font-size:12px;color:#83e6e6;margin-top:6px;';
        destino.insertBefore(wrapper, label ? label.nextSibling : fileInput.nextSibling);

        var texto = document.createElement('span');
        texto.textContent = 'Anexo: ' + file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Remover';
        btn.style.cssText = 'padding:3px 10px;border:none;border-radius:6px;background:rgba(231,76,60,0.25);color:#ff9a8f;font-size:12px;cursor:pointer;';
        btn.addEventListener('click', function() {
            fileInput.value = '';
            wrapper.remove();
        });

        wrapper.appendChild(texto);
        wrapper.appendChild(btn);
    }

    document.addEventListener('paste', function(e) {
        var target = e.target;
        if (!target || target.tagName !== 'TEXTAREA') return;

        var files = e.clipboardData && e.clipboardData.files;
        if (!files || files.length === 0) return;

        var form = target.closest('form');
        if (!form) return;

        var fileInput = form.querySelector('input[type="file"][name="helpAttach"]');
        if (!fileInput) return;

        var file = files[0];
        var aceitos = ['jpg', 'jpeg', 'png', 'bmp', 'pdf', 'xls', 'xlsx', 'doc', 'docx'];
        var ext = (file.name.split('.').pop() || '').toLowerCase();

        if (aceitos.indexOf(ext) === -1) {
            alert('Anexo: extensão não permitida (' + file.name + ')');
            e.preventDefault();
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('Anexo: arquivo excede 5 MB (' + file.name + ')');
            e.preventDefault();
            return;
        }

        e.preventDefault();

        var dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;

        atualizarAnexo(form, fileInput, file);
    });

    document.addEventListener('change', function(e) {
        var input = e.target;
        if (!input || input.type !== 'file' || input.name !== 'helpAttach') return;

        var form = input.closest('form');
        if (!form) return;

        if (input.files && input.files.length > 0) {
            atualizarAnexo(form, input, input.files[0]);
        } else {
            var wrapper = form.querySelector('[data-anexo-status]');
            if (wrapper) wrapper.remove();
        }
    });

    document.addEventListener('change', function(e) {
        var checkbox = e.target;
        if (!checkbox || checkbox.type !== 'checkbox') return;

        var form = checkbox.closest('form.filter-form');
        if (!form) return;

        if (checkbox.checked) {
            form.querySelectorAll('.filter-toggle input[type="checkbox"]').forEach(function(other) {
                if (other !== checkbox) other.checked = false;
            });
        }

        form.submit();
    });
    
    document.addEventListener('change', function(e) {
        var select = e.target;
        if (!select || select.tagName !== 'SELECT') return;

        var form = select.closest('form.filter-form');
        if (!form) return;

        form.submit();
    });

    document.addEventListener('click', function(e) {
        var link = e.target.closest('.anexo-link');
        if (!link) return;

        var href = link.getAttribute('href') || '';
        if (!href) return;

        var ext = (href.split('.').pop() || '').toLowerCase();
        var imagens = ['jpg', 'jpeg', 'png', 'bmp'];
        var pdf = ext === 'pdf';

        if (imagens.indexOf(ext) === -1 && !pdf) {
            e.preventDefault();
            var a = document.createElement('a');
            a.href = href;
            a.download = '';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            return;
        }

        e.preventDefault();

        var modal = document.getElementById('modal-anexo');
        if (!modal) return;

        var nome = href.split('/').pop();
        var titulo = modal.querySelector('.modal-header h2');
        if (titulo) titulo.textContent = nome;

        var box = modal.querySelector('[data-anexo-preview]');
        if (pdf) {
            box.innerHTML = '<iframe class="anexo-preview-pdf" src="' + href + '"></iframe>';
        } else {
            box.innerHTML = '<img class="anexo-preview-img" src="' + href + '" alt="' + nome + '">';
        }

        var downloadBtn = document.getElementById('anexo-download-btn');
        if (downloadBtn) downloadBtn.href = href;

        modal.style.display = 'flex';
    });

    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('[data-modal-open]');
        if (!trigger) return;
        var row = trigger.closest('[data-row]');
        if (!row) return;
        var id = row.getAttribute('data-id');
        if (!id) return;
        var cpfAb = row.getAttribute('data-cpf-ab');
        var modal = document.getElementById(trigger.getAttribute('data-modal-open'));
        if (!modal) return;

        var anexoWrap = modal.querySelector('[data-file-abertura-wrap]');
        if (anexoWrap) {
            var anexoFile = row.getAttribute('data-file-abertura');
            if (anexoFile) {
                anexoWrap.hidden = false;
                var anexoLink = modal.querySelector('[data-file-abertura-link]');
                if (anexoLink) anexoLink.href = anexoFile;
            } else {
                anexoWrap.hidden = true;
            }
        }

        var list = modal.querySelector('.historico-list');
        if (!list) return;

        mostrarMensagemHistorico(list, 'Carregando histórico...');

        fetch('/helpdesk/historico/' + encodeURIComponent(id))
            .then(function(res) {
                return res.json();
            })
            .then(function(data) {
                if (data.error) {
                    mostrarMensagemHistorico(list, data.error);
                    return;
                }
                renderHistorico(list, data.hist, cpfAb);
            })
            .catch(function() {
                mostrarMensagemHistorico(list, 'Erro ao carregar histórico.');
            });
    });

    document.addEventListener('click', function(e) {
        var closeBtn = e.target.closest('.modal .close');
        if (!closeBtn) return;
        var modal = closeBtn.closest('.modal');
        if (!modal || modal.id !== 'modal-chamado') return;

        var url = new URL(window.location.href);
        if (url.searchParams.has('open')) {
            url.searchParams.delete('open');
            history.replaceState(null, '', url.toString());
        }
        location.reload();
    });

    setInterval(function() {
        if (document.hidden) return;
        var modaisAbertos = document.querySelectorAll('.modal');
        for (var i = 0; i < modaisAbertos.length; i++) {
            if (modaisAbertos[i].style.display === 'flex') return;
        }
        window.location.reload();
    }, 30000);
</script>
<?php

$bases = $bases ?? [];
$basesAtivas = $basesAtivas ?? [];
$calendarios = $calendarios ?? [];
$calendariosAtivos = $calendariosAtivos ?? [];
$regras = $regras ?? [];
$grupos = $grupos ?? [];
$subgrupos = $subgrupos ?? [];
$statusSla = $statusSla ?? [];

$diasLabels = [
    'segunda' => 'Seg',
    'terca' => 'Ter',
    'quarta' => 'Qua',
    'quinta' => 'Qui',
    'sexta' => 'Sex',
    'sabado' => 'Sab',
    'domingo' => 'Dom',
];

?>
<div class="header-bar">
    <h2 class="header_title">Gestão de SLA</h2>
</div>

<div class="sla-tabs">
    <button type="button" class="sla-tab ativa" data-aba="bases"><i class="fa-solid fa-book"></i> Bases</button>
    <button type="button" class="sla-tab" data-aba="calendarios"><i class="fa-solid fa-clock"></i> Calendários</button>
    <button type="button" class="sla-tab" data-aba="regras"><i class="fa-solid fa-bars-staggered"></i> Regras</button>
    <button type="button" class="sla-tab" data-aba="status"><i class="fa-solid fa-list-check"></i> Status por SLA</button>
</div>

<section class="sla-panel" data-panel="bases">
    <div class="header-bar">
        <a href="#" class="btn-novo" data-modal-open="modal-sla-base"><i class="fa-solid fa-plus"></i> Nova base</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">Descrição</th>
                    <th scope="col">Status</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bases as $base): ?>
                    <tr>
                        <td><?= htmlspecialchars($base['nome']) ?></td>
                        <td><?= htmlspecialchars((string) ($base['descricao'] ?? '')) ?: '-' ?></td>
                        <td><?= $base['ativo'] ? '<span class="badge badge-pill badge-info">Ativo</span>' : '<span class="badge badge-pill badge-secondary">Inativo</span>' ?></td>
                        <td class="doc-acoes">
                            <button type="button" class="btn-doc btn-doc-ver" data-modal-open="modal-sla-base"
                                data-editar-base="<?= (int) $base['id'] ?>"
                                data-nome="<?= htmlspecialchars($base['nome']) ?>"
                                data-descricao="<?= htmlspecialchars((string) ($base['descricao'] ?? '')) ?>"
                                data-ativo="<?= (int) $base['ativo'] ?>"><i class="fa-solid fa-pen"></i> Editar</button>
                            <button type="button" class="btn-doc btn-doc-danger" data-excluir-base="<?= (int) $base['id'] ?>"><i class="fa-solid fa-trash"></i> Excluir</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($bases)): ?>
                    <tr><td colspan="4" class="doc-tree-empty">Nenhuma base de SLA cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="sla-panel" data-panel="calendarios" hidden>
    <div class="header-bar">
        <a href="#" class="btn-novo" data-modal-open="modal-sla-calendario"><i class="fa-solid fa-plus"></i> Novo calendário</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">Dias</th>
                    <th scope="col">Expediente</th>
                    <th scope="col">Intervalo</th>
                    <th scope="col">Status</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($calendarios as $cal): ?>
                    <?php $marcados = array_keys(array_filter($diasLabels, fn($rotulo, $chave) => !empty($cal[$chave]), ARRAY_FILTER_USE_BOTH)); ?>
                    <tr>
                        <td><?= htmlspecialchars($cal['nome']) ?></td>
                        <td><?= htmlspecialchars(implode(', ', $marcados)) ?></td>
                        <td><?= htmlspecialchars(substr((string) $cal['hora_inicio'], 0, 5)) ?> às <?= htmlspecialchars(substr((string) $cal['hora_fim'], 0, 5)) ?></td>
                        <td>
                            <?php if (!empty($cal['intervalo_inicio']) && !empty($cal['intervalo_fim'])): ?>
                                <?= htmlspecialchars(substr((string) $cal['intervalo_inicio'], 0, 5)) ?> às <?= htmlspecialchars(substr((string) $cal['intervalo_fim'], 0, 5)) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= $cal['ativo'] ? '<span class="badge badge-pill badge-info">Ativo</span>' : '<span class="badge badge-pill badge-secondary">Inativo</span>' ?></td>
                        <td class="doc-acoes">
                            <button type="button" class="btn-doc btn-doc-ver" data-modal-open="modal-sla-calendario"
                                data-editar-calendario="<?= (int) $cal['id'] ?>"
                                data-nome="<?= htmlspecialchars($cal['nome']) ?>"
                                data-dias="<?= htmlspecialchars(implode(',', array_keys(array_filter($cal, fn($v, $k) => in_array($k, array_keys($diasLabels), true) && !empty($v), ARRAY_FILTER_USE_BOTH)))) ?>"
                                data-hora-inicio="<?= htmlspecialchars((string) $cal['hora_inicio']) ?>"
                                data-hora-fim="<?= htmlspecialchars((string) $cal['hora_fim']) ?>"
                                data-int-inicio="<?= htmlspecialchars((string) ($cal['intervalo_inicio'] ?? '')) ?>"
                                data-int-fim="<?= htmlspecialchars((string) ($cal['intervalo_fim'] ?? '')) ?>"
                                data-ativo="<?= (int) $cal['ativo'] ?>"><i class="fa-solid fa-pen"></i> Editar</button>
                            <button type="button" class="btn-doc btn-doc-danger" data-excluir-calendario="<?= (int) $cal['id'] ?>"><i class="fa-solid fa-trash"></i> Excluir</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($calendarios)): ?>
                    <tr><td colspan="6" class="doc-tree-empty">Nenhum calendário cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="sla-panel" data-panel="regras" hidden>
    <div class="header-bar">
        <a href="#" class="btn-novo" data-modal-open="modal-sla-regra"><i class="fa-solid fa-plus"></i> Nova regra</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th scope="col">Base de SLA</th>
                    <th scope="col">Grupo / Subgrupo</th>
                    <th scope="col">Calendário</th>
                    <th scope="col">1ª resposta</th>
                    <th scope="col">Resolução</th>
                    <th scope="col">Ordem</th>
                    <th scope="col">Status</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($regras as $regra): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($regra['sla_nome'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars(sprintf('%s > %s', $regra['grupo_txt'], $regra['subgrupo_txt'])) ?></td>
                        <td><?= htmlspecialchars($regra['calendario_txt']) ?></td>
                        <td><?= htmlspecialchars($regra['prazo_resp_txt']) ?></td>
                        <td><?= htmlspecialchars($regra['prazo_resol_txt']) ?></td>
                        <td><?= (int) $regra['ordem'] ?></td>
                        <td><?= $regra['ativo'] ? '<span class="badge badge-pill badge-info">Ativo</span>' : '<span class="badge badge-pill badge-secondary">Inativo</span>' ?></td>
                        <td class="doc-acoes">
                            <button type="button" class="btn-doc btn-doc-ver" data-modal-open="modal-sla-regra"
                                data-editar-regra="<?= (int) $regra['id'] ?>"
                                data-sla="<?= (int) $regra['sla_id'] ?>"
                                data-grupo="<?= (int) ($regra['grupo_id'] ?? 0) ?>"
                                data-subgrupo="<?= (int) ($regra['subgrupo_id'] ?? 0) ?>"
                                data-calendario="<?= (int) ($regra['calendario_id'] ?? 0) ?>"
                                data-prazo-resp="<?= (int) ($regra['prazo_primeira_resposta_min'] ?? 0) ?>"
                                data-prazo-resol="<?= (int) ($regra['prazo_resolucao_min'] ?? 0) ?>"
                                data-ordem="<?= (int) $regra['ordem'] ?>"
                                data-ativo="<?= (int) $regra['ativo'] ?>"><i class="fa-solid fa-pen"></i> Editar</button>
                            <button type="button" class="btn-doc btn-doc-danger" data-excluir-regra="<?= (int) $regra['id'] ?>"><i class="fa-solid fa-trash"></i> Excluir</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($regras)): ?>
                    <tr><td colspan="8" class="doc-tree-empty">Nenhuma regra de SLA cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="sla-panel" data-panel="status" hidden>
    <div class="header-bar">
        <a href="#" class="btn-novo" data-modal-open="modal-sla-status"><i class="fa-solid fa-plus"></i> Vincular status</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th scope="col">Base de SLA</th>
                    <th scope="col">Código</th>
                    <th scope="col">Contabiliza tempo</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statusSla as $st): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($st['sla_nome'] ?? '-')) ?></td>
                        <td><?= (int) $st['status_id'] ?></td>
                        <td><?= $st['contabiliza_tempo'] ? '<span class="badge badge-pill badge-info">Sim</span>' : '<span class="badge badge-pill badge-secondary">Não</span>' ?></td>
                        <td class="doc-acoes">
                            <button type="button" class="btn-doc btn-doc-ver" data-modal-open="modal-sla-status"
                                data-editar-status="<?= (int) $st['id'] ?>"
                                data-sla-status="<?= (int) $st['sla_id'] ?>"
                                data-codigo="<?= (int) $st['status_id'] ?>"
                                data-contabiliza="<?= (int) $st['contabiliza_tempo'] ?>"><i class="fa-solid fa-pen"></i> Editar</button>
                            <button type="button" class="btn-doc btn-doc-danger" data-excluir-status="<?= (int) $st['id'] ?>"><i class="fa-solid fa-trash"></i> Excluir</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($statusSla)): ?>
                    <tr><td colspan="4" class="doc-tree-empty">Nenhum status vinculado a um SLA.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php ob_start(); ?>
    <form method="POST" action="/sla/base/salvar" id="form-sla-base" class="doc-upload-form">
        <input type="hidden" name="id" id="sla-base-id" value="">
        <label>Nome:
            <input type="text" name="nome" id="sla-base-nome" maxlength="100" required>
        </label>
        <label>Descrição:
            <input type="text" name="descricao" id="sla-base-descricao" maxlength="255">
        </label>
        <label class="checkbox-linha"><input type="checkbox" name="ativo" value="1" id="sla-base-ativo" checked> Ativo</label>
        <button type="submit" class="btn">Salvar base</button>
    </form>
<?php $content = ob_get_clean(); ?>
<?php component('modal', ['id' => 'modal-sla-base', 'title' => 'Base de SLA', 'content' => $content]); ?>

<?php ob_start(); ?>
    <form method="POST" action="/sla/calendario/salvar" id="form-sla-calendario" class="doc-upload-form">
        <input type="hidden" name="id" id="sla-calendario-id" value="">
        <label>Nome:
            <input type="text" name="nome" id="sla-calendario-nome" maxlength="100" required>
        </label>
        <fieldset class="sla-dias">
            <legend>Dias de funcionamento</legend>
            <?php foreach ($diasLabels as $chave => $rotulo): ?>
                <label class="checkbox-linha"><input type="checkbox" name="dias[<?= $chave ?>]" value="1" class="sla-dia" data-chave="<?= $chave ?>"> <?= $rotulo ?></label>
            <?php endforeach; ?>
        </fieldset>
        <div class="sla-linha">
            <label>Início:
                <input type="time" name="hora_inicio" id="sla-calendario-inicio" required>
            </label>
            <label>Fim:
                <input type="time" name="hora_fim" id="sla-calendario-fim" required>
            </label>
        </div>
        <div class="sla-linha">
            <label>Intervalo início <span class="login-opcional">(opcional)</span>:
                <input type="time" name="intervalo_inicio" id="sla-calendario-int-inicio">
            </label>
            <label>Intervalo fim <span class="login-opcional">(opcional)</span>:
                <input type="time" name="intervalo_fim" id="sla-calendario-int-fim">
            </label>
        </div>
        <label class="checkbox-linha"><input type="checkbox" name="ativo" value="1" id="sla-calendario-ativo" checked> Ativo</label>
        <button type="submit" class="btn">Salvar calendário</button>
    </form>
<?php $content = ob_get_clean(); ?>
<?php component('modal', ['id' => 'modal-sla-calendario', 'title' => 'Calendário de SLA', 'content' => $content]); ?>

<?php ob_start(); ?>
    <form method="POST" action="/sla/regra/salvar" id="form-sla-regra" class="doc-upload-form">
        <input type="hidden" name="id" id="sla-regra-id" value="">
        <label>Base de SLA:
            <select name="sla_id" id="sla-regra-sla" required>
                <option value="">Selecione...</option>
                <?php foreach ($basesAtivas as $base): ?>
                    <option value="<?= (int) $base['id'] ?>"><?= htmlspecialchars($base['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="sla-linha">
            <label>Grupo:
                <select name="grupo_id" id="sla-regra-grupo">
                    <option value="">— Geral —</option>
                    <?php foreach ($grupos as $grupo): ?>
                        <option value="<?= (int) $grupo['id_grupo'] ?>"><?= htmlspecialchars($grupo['descricao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Subgrupo:
                <select name="subgrupo_id" id="sla-regra-subgrupo">
                    <option value="">— Geral —</option>
                    <?php foreach ($subgrupos as $subgrupo): ?>
                        <option value="<?= (int) $subgrupo['id'] ?>" data-grupo="<?= (int) $subgrupo['idgrupo'] ?>"><?= htmlspecialchars($subgrupo['descricao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Calendário:
            <select name="calendario_id" id="sla-regra-calendario">
                <option value="">— Padrão —</option>
                <?php foreach ($calendariosAtivos as $cal): ?>
                    <option value="<?= (int) $cal['id'] ?>"><?= htmlspecialchars($cal['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="sla-linha">
            <label>Prazo 1ª resposta (min):
                <input type="number" name="prazo_primeira_resposta_min" id="sla-regra-prazo-resp" min="0" placeholder="Opcional">
            </label>
            <label>Prazo resolução (min):
                <input type="number" name="prazo_resolucao_min" id="sla-regra-prazo-resol" min="0" placeholder="Opcional">
            </label>
        </div>
        <div class="sla-linha">
            <label>Ordem:
                <input type="number" name="ordem" id="sla-regra-ordem" value="0" min="0">
            </label>
            <label class="checkbox-linha"><input type="checkbox" name="ativo" value="1" id="sla-regra-ativo" checked> Ativo</label>
        </div>
        <button type="submit" class="btn">Salvar regra</button>
    </form>
<?php $content = ob_get_clean(); ?>
<?php component('modal', ['id' => 'modal-sla-regra', 'title' => 'Regra de SLA', 'content' => $content]); ?>

<?php ob_start(); ?>
    <form method="POST" action="/sla/status/salvar" id="form-sla-status" class="doc-upload-form">
        <input type="hidden" name="id" id="sla-status-id" value="">
        <label>Base de SLA:
            <select name="sla_id" id="sla-status-sla" required>
                <option value="">Selecione...</option>
                <?php foreach ($basesAtivas as $base): ?>
                    <option value="<?= (int) $base['id'] ?>"><?= htmlspecialchars($base['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Código do status:
            <input type="number" name="status_id" id="sla-status-codigo" min="1" required placeholder="Ex.: 1">
            <small>Identificação do status; o vínculo com o catálogo de chamados será definido futuramente.</small>
        </label>
        <label class="checkbox-linha"><input type="checkbox" name="contabiliza_tempo" value="1" id="sla-status-contabiliza" checked> Contabiliza tempo de atendimento</label>
        <button type="submit" class="btn">Salvar status</button>
    </form>
<?php $content = ob_get_clean(); ?>
<?php component('modal', ['id' => 'modal-sla-status', 'title' => 'Status por SLA', 'content' => $content]); ?>

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

    // Abas
    var tabs = document.querySelectorAll('.sla-tab');
    var panels = document.querySelectorAll('.sla-panel');

    function ativarAba(nome) {
        tabs.forEach(function(t) {
            t.classList.toggle('ativa', t.getAttribute('data-aba') === nome);
        });
        panels.forEach(function(p) {
            p.hidden = p.getAttribute('data-panel') !== nome;
        });
    }

    tabs.forEach(function(t) {
        t.addEventListener('click', function() {
            ativarAba(t.getAttribute('data-aba'));
        });
    });

    // Formulário de base
    var baseForm = document.getElementById('form-sla-base');
    var baseId = document.getElementById('sla-base-id');
    var baseNome = document.getElementById('sla-base-nome');
    var baseDescricao = document.getElementById('sla-base-descricao');
    var baseAtivo = document.getElementById('sla-base-ativo');

    function resetBaseForm() {
        baseId.value = '';
        baseNome.value = '';
        baseDescricao.value = '';
        baseAtivo.checked = true;
    }

    document.querySelectorAll('[data-modal-open="modal-sla-base"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-editar-base');
            if (!id) { resetBaseForm(); return; }
            baseId.value = id;
            baseNome.value = btn.getAttribute('data-nome') || '';
            baseDescricao.value = btn.getAttribute('data-descricao') || '';
            baseAtivo.checked = (btn.getAttribute('data-ativo') || '0') === '1';
        });
    });

    if (baseForm) {
        baseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            postAcao(baseForm.action, new URLSearchParams(new FormData(baseForm)), function() {
                document.getElementById('modal-sla-base').style.display = 'none';
                setTimeout(function() { location.reload(); }, 500);
            });
        });
    }

    // Formulário de calendário
    var calForm = document.getElementById('form-sla-calendario');
    var calId = document.getElementById('sla-calendario-id');
    var calNome = document.getElementById('sla-calendario-nome');
    var calInicio = document.getElementById('sla-calendario-inicio');
    var calFim = document.getElementById('sla-calendario-fim');
    var calIntInicio = document.getElementById('sla-calendario-int-inicio');
    var calIntFim = document.getElementById('sla-calendario-int-fim');
    var calAtivo = document.getElementById('sla-calendario-ativo');

    function resetCalForm() {
        calId.value = '';
        calNome.value = '';
        calInicio.value = '08:00';
        calFim.value = '18:00';
        calIntInicio.value = '';
        calIntFim.value = '';
        calAtivo.checked = true;
        document.querySelectorAll('.sla-dia').forEach(function(cb) {
            cb.checked = ['segunda', 'terca', 'quarta', 'quinta', 'sexta'].indexOf(cb.getAttribute('data-chave')) !== -1;
        });
    }

    document.querySelectorAll('[data-modal-open="modal-sla-calendario"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-editar-calendario');
            if (!id) { resetCalForm(); return; }
            calId.value = id;
            calNome.value = btn.getAttribute('data-nome') || '';
            calInicio.value = (btn.getAttribute('data-hora-inicio') || '08:00:00').substring(0, 5);
            calFim.value = (btn.getAttribute('data-hora-fim') || '18:00:00').substring(0, 5);
            calIntInicio.value = (btn.getAttribute('data-int-inicio') || '').substring(0, 5);
            calIntFim.value = (btn.getAttribute('data-int-fim') || '').substring(0, 5);
            calAtivo.checked = (btn.getAttribute('data-ativo') || '0') === '1';

            var dias = (btn.getAttribute('data-dias') || '').split(',');
            document.querySelectorAll('.sla-dia').forEach(function(cb) {
                cb.checked = dias.indexOf(cb.getAttribute('data-chave')) !== -1;
            });
        });
    });

    if (calForm) {
        calForm.addEventListener('submit', function(e) {
            e.preventDefault();
            postAcao(calForm.action, new URLSearchParams(new FormData(calForm)), function() {
                document.getElementById('modal-sla-calendario').style.display = 'none';
                setTimeout(function() { location.reload(); }, 500);
            });
        });
    }

    // Formulário de regra
    var regraForm = document.getElementById('form-sla-regra');
    var regraId = document.getElementById('sla-regra-id');
    var regraSla = document.getElementById('sla-regra-sla');
    var regraGrupo = document.getElementById('sla-regra-grupo');
    var regraSubgrupo = document.getElementById('sla-regra-subgrupo');
    var regraCal = document.getElementById('sla-regra-calendario');
    var regraPrazoResp = document.getElementById('sla-regra-prazo-resp');
    var regraPrazoResol = document.getElementById('sla-regra-prazo-resol');
    var regraOrdem = document.getElementById('sla-regra-ordem');
    var regraAtivo = document.getElementById('sla-regra-ativo');

    function filtrarSubgrupos() {
        var grupo = regraGrupo.value;
        regraSubgrupo.querySelectorAll('option').forEach(function(opt) {
            if (opt.value === '') { opt.hidden = false; return; }
            var g = opt.getAttribute('data-grupo');
            opt.hidden = !!g && !!grupo && g !== grupo;
        });
    }

    function resetRegraForm() {
        regraId.value = '';
        regraSla.value = '';
        regraGrupo.value = '';
        regraSubgrupo.value = '';
        regraCal.value = '';
        regraPrazoResp.value = '';
        regraPrazoResol.value = '';
        regraOrdem.value = '0';
        regraAtivo.checked = true;
        filtrarSubgrupos();
    }

    regraGrupo.addEventListener('change', filtrarSubgrupos);

    document.querySelectorAll('[data-modal-open="modal-sla-regra"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-editar-regra');
            if (!id) { resetRegraForm(); return; }
            regraId.value = id;
            regraSla.value = btn.getAttribute('data-sla') || '';
            regraGrupo.value = btn.getAttribute('data-grupo') || '';
            filtrarSubgrupos();
            regraSubgrupo.value = btn.getAttribute('data-subgrupo') || '';
            regraCal.value = btn.getAttribute('data-calendario') || '';
            regraPrazoResp.value = btn.getAttribute('data-prazo-resp') || '';
            regraPrazoResol.value = btn.getAttribute('data-prazo-resol') || '';
            regraOrdem.value = btn.getAttribute('data-ordem') || '0';
            regraAtivo.checked = (btn.getAttribute('data-ativo') || '0') === '1';
        });
    });

    if (regraForm) {
        regraForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!regraSla.value) {
                mostrarToast('error', 'Selecione uma base de SLA.');
                return;
            }

            if (!regraPrazoResp.value && !regraPrazoResol.value) {
                mostrarToast('error', 'Informe ao menos um prazo (1ª resposta ou resolução).');
                return;
            }

            postAcao(regraForm.action, new URLSearchParams(new FormData(regraForm)), function() {
                document.getElementById('modal-sla-regra').style.display = 'none';
                setTimeout(function() { location.reload(); }, 500);
            });
        });
    }

    // Formulário de status por SLA
    var statusForm = document.getElementById('form-sla-status');
    var statusId = document.getElementById('sla-status-id');
    var statusSla = document.getElementById('sla-status-sla');
    var statusCodigo = document.getElementById('sla-status-codigo');
    var statusContabiliza = document.getElementById('sla-status-contabiliza');

    function resetStatusForm() {
        statusId.value = '';
        statusSla.value = '';
        statusCodigo.value = '';
        statusContabiliza.checked = true;
    }

    document.querySelectorAll('[data-modal-open="modal-sla-status"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-editar-status');
            if (!id) { resetStatusForm(); return; }
            statusId.value = id;
            statusSla.value = btn.getAttribute('data-sla-status') || '';
            statusCodigo.value = btn.getAttribute('data-codigo') || '';
            statusContabiliza.checked = (btn.getAttribute('data-contabiliza') || '0') === '1';
        });
    });

    if (statusForm) {
        statusForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!statusSla.value) {
                mostrarToast('error', 'Selecione uma base de SLA.');
                return;
            }

            postAcao(statusForm.action, new URLSearchParams(new FormData(statusForm)), function() {
                document.getElementById('modal-sla-status').style.display = 'none';
                setTimeout(function() { location.reload(); }, 500);
            });
        });
    }

    // Exclusões
    document.querySelectorAll('[data-excluir-base]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Excluir esta base de SLA?')) return;
            postAcao('/sla/base/excluir', { id: btn.getAttribute('data-excluir-base') }, function() {
                setTimeout(function() { location.reload(); }, 500);
            });
        });
    });

    document.querySelectorAll('[data-excluir-calendario]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Excluir este calendário?')) return;
            postAcao('/sla/calendario/excluir', { id: btn.getAttribute('data-excluir-calendario') }, function() {
                setTimeout(function() { location.reload(); }, 500);
            });
        });
    });

    document.querySelectorAll('[data-excluir-regra]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Excluir esta regra de SLA?')) return;
            postAcao('/sla/regra/excluir', { id: btn.getAttribute('data-excluir-regra') }, function() {
                setTimeout(function() { location.reload(); }, 500);
            });
        });
    });

    document.querySelectorAll('[data-excluir-status]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Remover este status do SLA?')) return;
            postAcao('/sla/status/excluir', { id: btn.getAttribute('data-excluir-status') }, function() {
                setTimeout(function() { location.reload(); }, 500);
            });
        });
    });
});
</script>
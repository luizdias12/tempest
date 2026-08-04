<?php
use App\Service\StatusService;
use App\Service\GenericService;
use App\Service\HelpHistoricoService;
?>

<div class="header">
    <h1>Lista de Chamados</h1>
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
        <button type="submit">Filtrar</button>

        <?php
        if (!empty($id) || !empty($emitente) || !empty($status) || !empty($local)): ?>
            <a href="?" class="btn-clear">Limpar filtro</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th></th>
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
            <?php $pendentes = HelpHistoricoService::visualizacoesPendentes(array_column($chamados, 'id')); ?>
            <?php foreach ($chamados as $chamado): 
               $new = in_array($chamado['id'], $pendentes, true) ? 'Novo' : '';
            ?>

                <tr data-row
                    data-id="<?= htmlspecialchars($chamado['id']) ?>"
                    data-nome="<?= htmlspecialchars($chamado['nome']) ?>"
                    data-funcao="<?= htmlspecialchars($chamado['funcao']) ?>"
                    data-chapa="<?= htmlspecialchars($chamado['chapa']) ?>"
                    data-dt-abertura="<?= htmlspecialchars(!empty($chamado['dt_abertura']) ? date('d-m-Y H:i:s', strtotime($chamado['dt_abertura'])) : '-') ?>"
                    data-dt-solucao="<?= htmlspecialchars(!empty($chamado['dt_solucao']) ? date('d-m-Y H:i:s', strtotime($chamado['dt_solucao'])) : '-') ?>"
                    data-sla="<?= htmlspecialchars($chamado['sla'] ?? '-') ?>"
                    data-status="<?= htmlspecialchars($chamado['status_desc'] ?? '-') ?>"
                    data-grupo="<?= htmlspecialchars($chamado['grupo'] ?? '-') ?>"
                    data-subgrupo="<?= htmlspecialchars($chamado['subgrupo'] ?? '-') ?>"
                    data-cab-problema="<?= htmlspecialchars($chamado['cab_problema'] ?? '-') ?>"
                    data-desc-problema="<?= htmlspecialchars($chamado['desc_problema'] ?? '-') ?>"
                    data-local="<?= htmlspecialchars($chamado['local'] ?? '-') ?>"
                    data-responsavel="<?= htmlspecialchars($chamado['responsavel'] ?? '-') ?>"
                    data-grupo-subgrupo="<?= htmlspecialchars($chamado['grupo'] . ' > ' . $chamado['subgrupo']) ?>"
                    data-ramal="<?= htmlspecialchars($chamado['ramal'] ?? '-') ?>"
                    data-email="<?= htmlspecialchars($chamado['email'] ?? '-') ?>"
                    data-cpf-ab="<?= htmlspecialchars($chamado['cpf_ab'] ?? '-') ?>"
                >

                    <td><span class="badge badge-new"><?= $new ?? '' ?></span></td>
                    <td><a href="#" class="chamado-id" data-modal-open="modal-chamado"><?= $chamado['id'] ?></a></td>
                    <td></td>
                    <td><?= (initcap($chamado['nome'])) ?></td>
                    <td><?= !empty($chamado['dt_abertura']) ? date('d-m-Y H:i:s', strtotime($chamado['dt_abertura'])) : '-' ?></td>
                    <td><?= !empty($chamado['dt_solucao']) ? date('d-m-Y H:i:s', strtotime($chamado['dt_solucao'])) : '-' ?></td>
                    <td></td>
                    <td><?= $chamado['grupo'] ?></td>
                    <td><?= $chamado['subgrupo'] ?></td>
                    <td><?= $chamado['cab_problema'] ?></td>
                    <td><?= $chamado['status_desc'] ?></td>
                    <td><?= $chamado['local'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?= pagination($meta, null, array_filter(['id' => $id ?? '', 'emitente' => $emitente ?? '', 'status' => $status ?? '', 'local' => $local ?? ''])) ?>
</div>

<?php 
   $content = 
   '    <span hidden data-field="cpf-ab"></span>
        <div class="chamado-details">
            <h2 style="border: 1px solid #ccc; padding: 0.5rem; border-radius: 0.25rem;">Tópico</h2>
            <p><span data-field="cab-problema"></span></p>
            <h4 style="border: 1px solid #ccc; padding: 0.5rem; border-radius: 0.25rem;">Descrição</h4>
            <p><pre><span data-field="desc-problema"></span></pre></p>
            <table style="margin: 1rem 0;">
                <thead>
                    <th scope="col">Data Abertura</th>
                    <th scope="col">Chapa</th>
                    <th scope="col">Nome</th>
                    <th scope="col">Função</th>
                    <th scope="col">Ramal</th>
                    <th scope="col">Email</th>
                    <th scope="col">Local</th>
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
            <p><strong>SLA:</strong> <span data-field="sla"></span></p>
            <p><strong>Status:</strong> <span data-field="status"></span></p>
            <p><strong>Responsável:</strong> <span data-field="responsavel"></span></p>
        </div>
    ';
?>

<?php component('modal', [
    'id' => 'modal-chamado',
    'size' => 'xlarge',
    'title' => 'Chamado - Nº <span data-field="id"></span> | <span data-field="grupo-subgrupo"></span>',
    'content' => $content,
]); ?>
<?php
use App\Service\FilialService;
use App\Service\FuncionarioService;
use App\Service\SituacaoService;
?>

<div class="header">
    <h1>Lista de Funcionários Cadastrados</h1>
    <p>Tabela com os funcionários cadastrados no sistema.</p>
</div>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <!-- <label for="filial">Filial:</label>
        <input type="text" id="filial" name="filial" value="<?= htmlspecialchars($filial ?? '') ?>" placeholder="Código da filial"> -->
        <label for="filial">Filial:</label>
        <?php 
            $filiais = FilialService::all();
            if (!empty($filiais)): ?>
                <select id="filial" name="filial">
                    <option value="">Todas as filiais</option>
                    <?php foreach ($filiais as $fil): ?>
                        <option value="<?= htmlspecialchars($fil['codfilial']) ?>" <?= ($filial === $fil['codfilial']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fil['codfilial'] . " - " . initcap($fil['vilnomefilial'])) ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
        <label for="secao">Seção:</label>
        <input type="text" id="secao" name="secao" value="<?= htmlspecialchars($secao ?? '') ?>" placeholder="Código da seção">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome ?? '') ?>" placeholder="Nome do funcionário">
        <label for="situacao">Situação:</label>
        <?php 
            $situacoes = SituacaoService::all();
            if (!empty($situacoes)): ?>
                <select id="situacao" name="situacao">
                    <option value="">Todas as situações</option>
                    <?php foreach ($situacoes as $sit): ?>
                        <option value="<?= htmlspecialchars($sit['codinterno']) ?>" <?= ($situacao === $sit['codinterno']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sit['descricao']) ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
        <button type="submit">Filtrar</button>       
        <?php

        if (!empty($filial) || !empty($secao) || !empty($situacao) || !empty($nome)): ?>
            <a href="?" class="btn-clear">Limpar filtro</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Chapa</th>
                <th>Nome</th>
                <th>Filial</th>
                <th>Seçao</th>
                <th>Funçao</th>
                <th>Data Admissao</th>
                <th>Data Demissao</th>
                <th>Situaçao</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $usuario): ?>

                <tr>
                    <td><?= $usuario['chapa'] ?></td>
                    <td><?= (initcap($usuario['nome'])) ?></td>
                    <td><?= ($usuario['codfilial'] . " - " . initcap($usuario['filial'])) ?></td>
                    <td><?= ($usuario['codsecao'] . " - " . initcap($usuario['secao'])) ?></td>
                    <td><?= $usuario['funcao'] ?></td>
                    <td>
                        <?php if (!empty($usuario['dataadmissao'])): ?>
                            <?php
                            $diff = (!empty($usuario['datademissao'])) ? (new DateTime($usuario['dataadmissao']))->diff(new DateTime($usuario['datademissao'])) : (new DateTime($usuario['dataadmissao']))->diff(new DateTime());
                            $partes = [];
                            if ($diff->y > 0) $partes[] = $diff->y . ' ano' . ($diff->y > 1 ? 's' : '');
                            if ($diff->m > 0) $partes[] = $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
                            $partes[] = $diff->d . ' dia' . ($diff->d != 1 ? 's' : '');
                            $ultimo = array_pop($partes);
                            $tempoCasa = $partes ? implode(', ', $partes) . ' e ' . $ultimo : $ultimo;
                            $admissaoTexto = 'Admissão: ' . date('d/m/Y', strtotime($usuario['dataadmissao'])) . "\n" . 'Tempo de casa: ' . $tempoCasa;
                            ?>
                            <span class="popover-trigger" style="cursor:pointer;border-bottom:1px dashed rgba(255,255,255,0.3)" data-texto="<?= htmlspecialchars($admissaoTexto, ENT_QUOTES) ?>"><?= date('d-m-Y', strtotime($usuario['dataadmissao'])) ?></span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= !empty($usuario['datademissao']) ? date('d-m-Y', strtotime($usuario['datademissao'])) : '-' ?></td>
                    <td>
                        <?php if ($usuario['codsituacao'] == 'F'): ?>
                            <?php
                            $feriasData = null;
                            try {
                                $feriasData = FuncionarioService::dataFerias($usuario['chapa']);
                            } catch (\Throwable $e) {
                                $feriasData = null;
                            }
                            $periodo = $feriasData
                                ? date('d/m/Y', strtotime($feriasData['datainicio'])) . ' a ' . date('d/m/Y', strtotime($feriasData['datafim']))
                                : 'Indisponível';
                            ?>
                            <div class="ferias-wrapper">
                                <span class="status ferias popover-trigger" style="cursor:pointer" data-texto="Férias: <?= htmlspecialchars($periodo, ENT_QUOTES) ?>"><?= $usuario['situacao'] ?></span>
                            </div>
                        <?php else: ?>
                            <span class="status <?= $usuario['codsituacao'] == 'A' ? 'ativo' : ($usuario['codsituacao'] == 'D' ? 'demitido' : 'afastado') ?>"><?= $usuario['situacao'] ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?= pagination($meta, null, array_filter(['filial' => $filial ?? '', 'secao' => $secao ?? '', 'situacao' => $situacao ?? '', 'nome' => $nome ?? ''])) ?>
</div>

<script>
document.addEventListener('click', function(e) {
    var el = e.target.closest('.popover-trigger');
    if (!el) {
        document.querySelectorAll('.ferias-popover').forEach(function(p) { p.remove(); });
        return;
    }

    document.querySelectorAll('.ferias-popover').forEach(function(p) { p.remove(); });

    if (el.dataset.active === '1') {
        el.dataset.active = '0';
        return;
    }

    var popover = document.createElement('div');
    popover.className = 'ferias-popover';
    popover.textContent = el.dataset.texto;
    document.body.appendChild(popover);

    var rect = el.getBoundingClientRect();
    var popHeight = 32;
    var top = rect.bottom + 4;
    var left = rect.left;

    if (top + popHeight > window.innerHeight) {
        top = rect.top - popHeight - 4;
    }
    if (left < 0) left = 4;
    if (left + 200 > window.innerWidth) left = window.innerWidth - 210;

    popover.style.position = 'fixed';
    popover.style.top = top + 'px';
    popover.style.left = left + 'px';
    popover.style.display = 'block';

    el.dataset.active = '1';
});
</script>
<?php
/**
 * @var array $registros
 * @var string $data
 * @var int $somaGeral
 */
?>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <label for="data">Data da solução:</label>
        <input type="date" id="data" name="data" value="<?= htmlspecialchars($data ?? '') ?>">
        <button type="submit">Filtrar</button>

        <?php if (!empty($data)): ?>
            <a href="?" class="btn-clear">Limpar filtro</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Técnico</th>
                <th>Chamados resolvidos</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="2" class="historico-empty">Nenhum chamado resolvido encontrado.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($registros as $registro): ?>
                <tr>
                    <td><?= htmlspecialchars(initcap($registro['tecnico'])) ?></td>
                    <td><?= (int) $registro['total'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (!empty($registros)): ?>
            <tfoot>
                <tr>
                    <th>Total geral</th>
                    <th><?= (int) $somaGeral ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

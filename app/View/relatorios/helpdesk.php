<?php
/**
 * @var array $registros
 * @var string $data
 * @var int $totalResolvidos
 * @var int $totalAbertos
 */
?>

<div class="header-bar">
    <a href="/relatorios" class="btn-novo"><i class="fa-solid fa-arrow-left"></i> Menu</a>
</div>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <label for="data">Data:</label>
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
                <th>Resolvidos</th>
                <th>Abertos</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="3" class="historico-empty">Nenhum chamado encontrado.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($registros as $registro): ?>
                <tr<?= (int) $registro['resolvidos'] > 0 ? ' class="tr-resolvidos"' : '' ?>>
                    <td><?= htmlspecialchars(initcap($registro['tecnico'])) ?></td>
                    <td><?= (int) $registro['resolvidos'] ?></td>
                    <td><?= (int) $registro['abertos'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (!empty($registros)): ?>
            <tfoot>
                <tr>
                    <th>Total geral</th>
                    <th><?= (int) $totalResolvidos ?></th>
                    <th><?= (int) $totalAbertos ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

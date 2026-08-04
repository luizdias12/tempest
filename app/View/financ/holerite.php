<div class="holerite-info">
    <p><strong>Funcionário:</strong> <?= htmlspecialchars($funcionario['nome'] ?? '') ?></p>
    <p><strong>Chapa:</strong> <?= htmlspecialchars($chapa) ?></p>
    <p><strong>Função:</strong> <?= htmlspecialchars($funcionario['codfuncao'] ?? '') ?> - <?= htmlspecialchars($funcionario['funcao'] ?? '') ?></p>
</div>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <label for="mescomp">Mês:</label>
        <select id="mescomp" name="mescomp">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= ($mescomp == $m) ? 'selected' : '' ?>><?= str_pad($m, 2, '0', STR_PAD_LEFT) ?></option>
            <?php endfor; ?>
        </select>

        <label for="anocomp">Ano:</label>
        <select id="anocomp" name="anocomp">
            <?php for ($a = (int)date('Y'); $a >= (int)date('Y') - 2; $a--): ?>
                <option value="<?= $a ?>" <?= ($anocomp == $a) ? 'selected' : '' ?>><?= $a ?></option>
            <?php endfor; ?>
        </select>

        <label for="periodo">Período:</label>
        <select id="periodo" name="periodo">
            <option value="2" <?= ($periodo == 2) ? 'selected' : '' ?>>13º Salário</option>
            <option value="3" <?= ($periodo != 2) ? 'selected' : '' ?>>Folha Mensal</option>
        </select>

        <button type="submit">Consultar</button>
    </form>
</div>

<?php if ($totaisHolerite !== null): ?>

    <div class="holerite-info">
        <p><strong>Período:</strong> <?= str_pad($mescomp, 2, '0', STR_PAD_LEFT) ?>/<?= $anocomp ?> - <?= $periodo == 2 ? '13º Salário' : 'Folha Mensal' ?></p>
    </div>

    <?php if (!empty($totaisHolerite['proventos'])): ?>
        <div class="table-wrapper table-proventos" style="margin-top: 1.5rem;">
            <h3>Proventos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($totaisHolerite['proventos'] as $prov): ?>
                        <tr>
                            <td><?= htmlspecialchars($prov['codevento']) ?></td>
                            <td><?= htmlspecialchars($prov['descricao']) ?></td>
                            <td class="valor"><?= number_format((float)$prov['valor'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2"><strong>Total de Proventos</strong></td>
                        <td class="valor"><strong><?= number_format($totaisHolerite['total_proventos'], 2, ',', '.') ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>

    <?php if (!empty($totaisHolerite['decontos'])): ?>
        <div class="table-wrapper table-descontos" style="margin-top: 1.5rem;">
            <h3>Descontos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($totaisHolerite['decontos'] as $desc): ?>
                        <tr>
                            <td><?= htmlspecialchars($desc['codevento']) ?></td>
                            <td><?= htmlspecialchars($desc['descricao']) ?></td>
                            <td class="valor"><?= number_format((float)$desc['valor'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2"><strong>Total de Descontos</strong></td>
                        <td class="valor"><strong><?= number_format($totaisHolerite['total_descontos'], 2, ',', '.') ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>

    <div class="holerite-resumo" style="margin: 1.5rem 0; display: flex; gap: 2rem; flex-wrap: wrap;">
        <div class="resumo-card">
            <strong>FGTS</strong>
            <span class="valor-destaque">R$ <?= number_format($totaisHolerite['valor_fgts'], 2, ',', '.') ?></span>
        </div>
        <div class="resumo-card">
            <strong>Valor Líquido</strong>
            <span class="valor-destaque">R$ <?= number_format($totaisHolerite['valor_liquido'], 2, ',', '.') ?></span>
        </div>
    </div>

<?php endif; ?>
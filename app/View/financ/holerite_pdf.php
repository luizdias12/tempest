<?php
$meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
$tipoFolha = ($periodo == 2) ? 'Décimo Terceiro' : 'Folha Mensal';
$mesRef = str_pad((string)$mescomp, 2, '0', STR_PAD_LEFT);
?>

<div class="print-header">
    <h1>Demonstrativo de Pagamento</h1>
</div>

<table class="print-table">
    <tr>
        <th style="width: 20%;">Folha</th>
        <td><?= htmlspecialchars($tipoFolha) ?></td>
        <th style="width: 20%;">Período</th>
        <td><?= ($periodo == 2 ? $anocomp : $mesRef . '/' . $anocomp) ?></td>
    </tr>
    <tr>
        <th>Empresa</th>
        <td>CEMA CENTRAL MINEIRA ATACADISTA</td>
        <th>CNPJ</th>
        <td>03.083.231/0021-48</td>
    </tr>
    <tr>
        <th>Funcionário</th>
        <td><?= htmlspecialchars($funcionario['nome']) ?></td>
        <th>Cargo</th>
        <td><?= htmlspecialchars(($funcionario['codfuncao'] ? $funcionario['codfuncao'] . ' - ' : '') . $funcionario['funcao']) ?></td>
    </tr>
    <tr>
        <th>Chapa</th>
        <td><?= htmlspecialchars($chapa) ?></td>
        <th>Mês Referência</th>
        <td><?= $meses[$mescomp] ?? $mescomp ?> / <?= $anocomp ?></td>
    </tr>
</table>

<?php if (!empty($totaisHolerite['proventos'])): ?>
    <h3 style="margin: 15px 0 5px;">Proventos</h3>
    <table class="print-table">
        <thead>
            <tr>
                <th style="width: 12%;">Código</th>
                <th>Descrição</th>
                <th style="width: 20%;">Valor</th>
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
            <tr class="total-proventos">
                <td colspan="2">Total de Proventos</td>
                <td class="valor"><?= number_format((float)$totaisHolerite['total_proventos'], 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<?php if (!empty($totaisHolerite['decontos'])): ?>
    <h3 style="margin: 15px 0 5px;">Descontos</h3>
    <table class="print-table">
        <thead>
            <tr>
                <th style="width: 12%;">Código</th>
                <th>Descrição</th>
                <th style="width: 20%;">Valor</th>
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
            <tr class="total-descontos">
                <td colspan="2">Total de Descontos</td>
                <td class="valor"><?= number_format((float)$totaisHolerite['total_descontos'], 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<div class="print-resumo">
    <div>
        <strong>FGTS Depositado</strong>
        <span>R$ <?= number_format((float)$totaisHolerite['valor_fgts'], 2, ',', '.') ?></span>
    </div>
    <div>
        <strong>Valor Líquido</strong>
        <span>R$ <?= number_format((float)$totaisHolerite['valor_liquido'], 2, ',', '.') ?></span>
    </div>
</div>

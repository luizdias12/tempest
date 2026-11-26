<?php

/**
 * @var string $csrf
 * @var array|null $resultado
 * @var array $camposLayout
 */
?>

<div class="header-bar">
    <span><i class="fa-solid fa-file-import"></i> Importar extrato de Cooparticipação - Unimed (layout 176)</span>
</div>

<div class="filter-bar">
    <span>Antes de importar, verifique se o arquivo da Unimed está no layout 176 (campos separados por <strong>;</strong>).</span>
</div>

<form method="POST" action="/importacao/unimed" enctype="multipart/form-data" class="chamado-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

    <div class="chamado-form-row">
        <label for="mes">Mês competência
            <select name="mes" id="mes" required>
                <option value="">Selecione</option>
                <?php foreach (['01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril', '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'] as $valor => $nome): ?>
                    <option value="<?= $valor ?>"><?= $nome ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label for="ano">Ano competência
            <select name="ano" id="ano" required>
                <?php foreach ([date('Y') - 1, date('Y'), date('Y') + 1] as $ano): ?>
                    <option value="<?= (int) $ano ?>" <?= (int) $ano === (int) date('Y') ? 'selected' : '' ?>><?= (int) $ano ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label for="arquivo">Arquivo (.txt ou .csv)
            <input type="file" name="arquivo" id="arquivo" accept=".txt,.csv" required>
        </label>

        <button type="submit" class="btn-novo"><i class="fa-solid fa-cloud-arrow-up"></i> Importar</button>
    </div>
</form>

<?php if (!empty($resultado)): ?>
    <div class="filter-bar">
        <span><i class="fa-solid fa-file-lines"></i> Competência <?= htmlspecialchars($resultado['mes'] . '/' . $resultado['ano']) ?></span>
        <span>Linhas lidas: <?= (int) $resultado['linhas'] ?></span>
        <span class="badge badge-pill badge-info">Inseridas: <?= (int) $resultado['inseridos'] ?></span>
        <?php if ((int) $resultado['ignorados'] > 0): ?>
            <span class="badge badge-pill badge-warning">Ignoradas: <?= (int) $resultado['ignorados'] ?></span>
        <?php endif; ?>
        <?php if (!empty($resultado['erros'])): ?>
            <span class="badge badge-pill badge-error">Erros: <?= count($resultado['erros']) ?></span>
        <?php endif; ?>
        <?php if ((int) $resultado['ja_existiam'] > 0): ?>
            <span class="badge badge-pill badge-warning">Já existiam na competência: <?= (int) $resultado['ja_existiam'] ?></span>
        <?php endif; ?>
    </div>

    <?php if (!empty($resultado['erros'])): ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Linha</th>
                        <th scope="col">Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultado['erros'] as $erro): ?>
                        <tr>
                            <td><?= (int) $erro['linha'] ?></td>
                            <td><?= htmlspecialchars($erro['motivo']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th scope="col">Posição</th>
                <th scope="col">Campo do layout</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($camposLayout as $posicao => $campo): ?>
                <tr>
                    <td><?= (int) $posicao ?></td>
                    <td><?= htmlspecialchars($campo) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

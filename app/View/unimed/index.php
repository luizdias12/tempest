<?php

/**
 * @var array $competencias
 * @var string $titular
 */
?>

<div class="header-bar">
    <span><i class="fa-solid fa-hospital"></i> Extrato de Cooparticipação - Unimed</span>
    <?php if ($titular !== ''): ?>
        <span class="badge badge-pill badge-info"><i class="fa-solid fa-user"></i> <?= htmlspecialchars(initcap($titular)) ?></span>
    <?php endif; ?>
</div>

<?php if (empty($competencias)): ?>
    <div class="filter-bar">
        <span>Nenhuma competência importada até o momento.</span>
    </div>
<?php else: ?>
    <div class="filter-bar">
        <form class="filter-form" id="unimedFiltro">
            <label for="unimedCompetencia">Competência:</label>
            <select name="competencia" id="unimedCompetencia">
                <?php foreach ($competencias as $competencia): ?>
                    <option
                        value="<?= htmlspecialchars($competencia['mes'] . '/' . $competencia['ano']) ?>"
                        data-mes="<?= htmlspecialchars($competencia['mes']) ?>"
                        data-ano="<?= htmlspecialchars($competencia['ano']) ?>"
                    ><?= htmlspecialchars($competencia['mes'] . '/' . $competencia['ano']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="table-wrapper" id="unimedWrapper">
        <table>
            <thead>
                <tr>
                    <th scope="col">Nome Paciente</th>
                    <th scope="col">Prestador</th>
                    <th scope="col">Dt Atendimento</th>
                    <th scope="col">Tipo de Procedimento</th>
                    <th scope="col">Valor do Procedimento</th>
                </tr>
            </thead>
            <tbody id="unimedRegistros">
                <tr>
                    <td colspan="5">Carregando...</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"></td>
                    <td id="unimedTotal"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script src="<?= asset('js/unimed.js') ?>"></script>
<?php endif; ?>

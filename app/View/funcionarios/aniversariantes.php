<?php
use App\Service\FilialService;

$mesesNomes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
?>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <label for="mes">Mês:</label>
        <select id="mes" name="mes">
            <?php foreach ($mesesNomes as $num => $nome): ?>
                <option value="<?= $num ?>" <?= ($mes == $num) ? 'selected' : '' ?>>
                    <?= $nome ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="filial">Filial:</label>
        <?php
            $filiais = FilialService::all();
            if (!empty($filiais)): ?>
                <select id="filial" name="filial">
                    <option value="">Selecione a filial</option>
                    <?php foreach ($filiais as $fil): ?>
                        <option value="<?= htmlspecialchars($fil['codfilial']) ?>" <?= ($filial === $fil['codfilial']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fil['codfilial'] . " - " . initcap($fil['vilnomefilial'])) ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
        <button type="submit">Filtrar</button>
        <?php if (!empty($filial) || $mes != date('n')): ?>
            <a href="?" class="btn-clear">Limpar filtro</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($data)): ?>
    <div class="table-wrapper">
        <div class="empty-state">Nenhum aniversariante encontrado para <?= $mesesNomes[$mes] ?>.</div>
    </div>
<?php else: ?>
    <?php
        $porDia = [];
        foreach ($data as $usuario) {
            $nasc = $usuario['dtnascimento'] ? new DateTime($usuario['dtnascimento']) : null;
            $dia = $nasc ? (int) $nasc->format('d') : 0;
            $porDia[$dia][] = $usuario;
        }
        ksort($porDia);
        $diaHoje = (int) date('j');
    ?>
    <div class="aniversariantes-cards">
        <?php foreach ($porDia as $dia => $usuarios): ?>
            <article class="card_cs <?= ($dia === $diaHoje) ? 'congrats' : 'normal' ?>">
                <div class="imgBx_cs">
                    <h1><?= $dia ?></h1>
                </div>
                <div class="content_cs">
                    <?php foreach ($usuarios as $usuario): ?>
                        <div class="pessoa_cs">
                            <h3><?= initcap($usuario['nome'] ?? '') ?></h3>
                            <h4><?= !empty($usuario['funcao']) ? initcap($usuario['funcao']) : '-' ?></h4>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

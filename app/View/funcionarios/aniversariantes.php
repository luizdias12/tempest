<?php

use App\Service\FilialService;

$mesesNomes = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro'
];

/**
 * @var int $mes
 * @var int $filial
 * @var bool $hoje
 * @var array $data
 */

$nascimento = new DateTime($data['dtnascimento']);
$aniversarioHoje = $nascimento->format('m-d') === date('m-d');


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
                    <option value="<?= htmlspecialchars($fil['idintegracao']) ?>" <?= (((string) $filial === (string) $fil['idintegracao'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(initcap($fil['vilnomefilial'] . " - " . $fil['idintegracao'])) ?>
                    </option>
            <?php endforeach;
            endif; ?>
            </select>
            <label for="hoje" class="filter-toggle">
                <input type="checkbox" id="hoje" name="hoje" value="1" <?= !empty($hoje) ? 'checked' : '' ?>>
                Aniversariantes de Hoje
            </label>
            <button type="submit">Filtrar</button>
            <?php if (!empty($filial) || $mes != date('n')): ?>
                <a href="?" class="btn-clear">Limpar filtro</a>
            <?php endif; ?>
    </form>
</div>

<?php if (empty($data)): ?>
    <div class="table-wrapper">
        <div class="empty-state">
            <?= $hoje
                ? 'Nenhum aniversariante hoje.'
                : "Nenhum aniversariante encontrado para {$mesesNomes[$mes]}."
            ?>
        </div>
    </div>
<?php else: ?>
    <?php

    $agrupados = [];

    foreach ($data as $usuario) {
        if ($hoje) {
            $filial = $usuario['filial'] ?? 'Não identificada';
            $agrupados[$filial][] = $usuario;
        } else {
            $dia = !empty($usuario['dtnascimento'])
                ? (int) date('d', strtotime($usuario['dtnascimento']))
                : 0;

            $agrupados[$dia][] = $usuario;
        }
    }

    if (!$hoje) {
        ksort($agrupados);
    }

    $diaHoje = (int) date('j');

    ?>

    <div class="aniversariantes-cards">
        <?php foreach ($agrupados as $grupo => $usuarios): ?>
            <?php
            $dia = $hoje ? $diaHoje : (int) $grupo;
            $aniversarioHoje = (int) $dia === (int) date('j')
                && (int) $mes === (int) date('n');
            ?>
            <article class="card_cs <?= ($hoje || $aniversarioHoje) ? 'congrats' : 'normal' ?>">
                <div class="imgBx_cs">
                    <h1><?= $dia ?></h1>
                </div>
                <div class="content_cs">
                    <?php if ($hoje): ?>
                        <div class="div-aniver-hoje title">
                            <small class="div-aniver-hoje small"><?= htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <div class="pessoa_cs">
                            <h3>
                                <?= htmlspecialchars(
                                    initcap($usuario['nomesocial'] ?: $usuario['nome']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>
                            <h4>
                                <?= !empty($usuario['funcao'])
                                    ? htmlspecialchars(initcap($usuario['funcao']), ENT_QUOTES, 'UTF-8')
                                    : '-'
                                ?>
                            </h4>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
    document.addEventListener('change', function(e) {
        var checkbox = e.target;
        if (!checkbox || checkbox.type !== 'checkbox') return;

        var form = checkbox.closest('form.filter-form');
        if (!form) return;

        var btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML =
                '<span class="btn-spinner" aria-hidden="true"></span> Filtrando...';
        }

        var wrapper = document.querySelector(".table-wrapper");
        if (wrapper) {
            wrapper.classList.add("is-loading");
            wrapper.setAttribute("aria-busy", "true");
        }

        form.requestSubmit();
    });

    document.addEventListener('change', function(e) {
        if (!filial || filial.value === '') return;

        var select = e.target;
        if (!select || select.tagName !== 'SELECT') return;

        var form = select.closest('form.filter-form');
        if (!form) return;

        var btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML =
                '<span class="btn-spinner" aria-hidden="true"></span> Filtrando...';
        }

        var wrapper = document.querySelector(".table-wrapper");
        if (wrapper) {
            wrapper.classList.add("is-loading");
            wrapper.setAttribute("aria-busy", "true");
        }

        form.requestSubmit();
    });
</script>
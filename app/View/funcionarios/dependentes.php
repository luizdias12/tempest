<?php

$rotulo = $previa['competencia']['rotulo'];
$referencia = $previa['competencia']['referencia'];
$contadores = $previa['contadores'];

$seramZerados = $contadores['incsalfam'];
$seramMarcados = max(0, $contadores['elegiveis'] - $contadores['ja_marcados']);

?>

<div class="header-bar">
    <span><i class="fa-solid fa-calendar-days"></i> Competência <strong><?= htmlspecialchars($rotulo) ?></strong></span>
    <span class="badge badge-pill badge-info">Idade avaliada em <?= date('d/m/Y', strtotime($referencia)) ?></span>
</div>

<div class="grid" style="grid-template-columns: repeat(4, minmax(180px, 1fr));">
    <div class="resumo-card">
        <strong>Dependentes na base</strong>
        <span class="valor-destaque"><?= $contadores['total'] ?></span>
    </div>
    <div class="resumo-card">
        <strong>Serão zerados (INCSALFAM)</strong>
        <span class="valor-destaque"><?= $seramZerados ?></span>
    </div>
    <div class="resumo-card">
        <strong>Elegíveis (menos de 14 anos)</strong>
        <span class="valor-destaque"><?= $contadores['elegiveis'] ?></span>
    </div>
    <div class="resumo-card">
        <strong>Serão marcados</strong>
        <span class="valor-destaque"><?= $seramMarcados ?></span>
    </div>
</div>

<div class="card">
    <h3>O que será executado</h3>
    <ul class="attributes">
        <li><strong>Zera INCSALFAM</strong> em todos os dependentes que estão com o sinalizador ligado (<?= $seramZerados ?> linha(s)).</li>
        <li>
            <strong>Marca CARTAOVACINA e FREQESCOLAR</strong> nos dependentes com GRAUPARENTESCO em
            1, 3, I, N ou T que ainda não completaram 14 anos na competência (<?= $seramMarcados ?> linha(s) a alterar;
            <?= $contadores['ja_marcados'] ?> já estão no padrão).
        </li>
        <li>
            <strong>Fora da regra:</strong> <?= $contadores['fora_da_regra'] ?> dependente(s) estão marcados com
            CARTAOVACINA/FREQESCOLAR mas não atendem mais à regra — não serão alterados, confira na base se precisam de ajuste manual.
        </li>
        <li>Os dois updates rodam na mesma transação: se o segundo falhar, o primeiro é desfeito.</li>
    </ul>

    <form method="POST" action="/funcionarios/dependentes/atualizar" onsubmit="return confirm('Executar a atualização de dependentes para a competência <?= htmlspecialchars($rotulo) ?>?\n\n<?= $seramZerados ?> linha(s) com INCSALFAM serão zeradas e <?= $seramMarcados ?> serão marcadas.\n\nNão há como desfazer depois de concluído.');">
        <input type="hidden" name="confirmacao" value="SIM">
        <button type="submit" class="btn-novo btn-novo-success">
            <i class="fa-solid fa-play"></i> Executar para <?= htmlspecialchars($rotulo) ?>
        </button>
    </form>
</div>

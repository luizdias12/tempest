<?php
    // Efeito de neve ativo de 1º/dez a 6/jan; ?natal=1 força e ?natal=0 desliga.
    $hojeNatal = date('m-d');
    $temporadaNatal = $hojeNatal >= '12-01' || $hojeNatal <= '01-06';

    $forcarNatal = $_GET['natal'] ?? null;
    if ($forcarNatal === '1' || $forcarNatal === '0') {
        $temporadaNatal = $forcarNatal === '1';
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? '嵐' ?></title>

    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/vivi.css') ?>">
    <?php if ($temporadaNatal): ?>
        <link rel="stylesheet" href="<?= asset('css/natal.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">
</head>
<body>

    <?php
        /**
         * @var mixed $content
         */
    ?>

    <?php partial('sidebar'); ?>
    <?php partial('header', ['title' => $title ?? '嵐']); ?>

    <main class="container">
        <?= $content ?>
    </main>

    <?php partial('footer'); ?>
    <?php partial('vivi'); ?>

    <?php if ($temporadaNatal): ?>
        <div class="snowflakes" aria-hidden="true"></div>
        <div class="lights" aria-hidden="true"></div>
    <?php endif; ?>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/notificacoes.js') ?>"></script>
    <script src="<?= asset('js/vivi.js') ?>"></script>
    <?php if ($temporadaNatal): ?>
        <script>window.NATAL_SANTA = <?= json_encode(asset('img/papai-noel-treno.png')) ?>;</script>
        <script src="<?= asset('js/natal.js') ?>"></script>
    <?php endif; ?>

    <?php
    use App\Core\Alerts\AlertManager;
    $alerts = AlertManager::get();
    if (!empty($alerts)):
        component('alert', ['alerts' => $alerts]);
    endif;
    ?>
</body>
</html>
<?php
    // Efeito de neve ativo de 1º/dez a 6/jan; ?natal=1 força, ?natal=0 desliga
    // e ?natal=aurora exibe o protótipo "Aurora glacial".
    $hojeNatal = date('m-d');
    $temporadaNatal = $hojeNatal >= '11-27' || $hojeNatal <= '01-06';

    $forcarNatal = $_GET['natal'] ?? null;
    $modoAurora = 'aurora';
    if (in_array($forcarNatal, ['1', '0', 'aurora'], true)) {
        $temporadaNatal = $forcarNatal !== '0';
        $modoAurora = $forcarNatal === 'aurora';
    }
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? '' ?></title>

    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/vivi.css') ?>">
    <?php if ($temporadaNatal): ?>
        <link rel="stylesheet" href="<?= asset($modoAurora ? 'css/natal-aurora.css' : 'css/natal.css') ?>">
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
    <?php partial('header', ['title' => $title ?? '嵐', 'modoAurora' => $modoAurora]); ?>

    <main class="container">
        <?= $content ?>
    </main>

    <?php partial('footer'); ?>
    <?php partial('vivi'); ?>

    <?php if ($temporadaNatal): ?>
        <?php if ($modoAurora): ?>
            <div class="natal-aurora" aria-hidden="true">
                <canvas class="aurora-cortinas"></canvas>
                <canvas class="aurora-particulas"></canvas>
            </div>
            <div class="aurora-solo" aria-hidden="true">
                <canvas class="aurora-solo-canvas"></canvas>
                <canvas class="aurora-reflexo"></canvas>
            </div>
        <?php else: ?>
            <div class="snowflakes" aria-hidden="true"></div>
            <div class="lights" aria-hidden="true"></div>
        <?php endif; ?>
    <?php endif; ?>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/notificacoes.js') ?>"></script>
    <script src="<?= asset('js/vivi.js') ?>"></script>
    <?php if ($temporadaNatal): ?>
        <?php if ($modoAurora): ?>
            <script src="<?= asset('js/natal-aurora.js') ?>"></script>
        <?php else: ?>
            <script>window.NATAL_SANTA = <?= json_encode(asset('img/papai-noel-treno.png')) ?>;</script>
            <script src="<?= asset('js/natal.js') ?>"></script>
        <?php endif; ?>
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
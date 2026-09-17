<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? '嵐' ?></title>

    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

    <?php partial('sidebar'); ?>
    <?php partial('header', ['title' => $title ?? '嵐']); ?>

    <main class="container">
        <?= $content ?>
    </main>

    <?php partial('footer'); ?>
    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/notificacoes.js') ?>"></script>

    <?php
    use App\Core\Alerts\AlertManager;
    $alerts = AlertManager::get();
    if (!empty($alerts)):
        component('alert', ['alerts' => $alerts]);
    endif;
    ?>
</body>
</html>
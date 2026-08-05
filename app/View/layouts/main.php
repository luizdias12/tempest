<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? '嵐' ?></title>

    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
</head>
<body>

    <?php partial('sidebar'); ?>
    <?php partial('header', ['title' => $title ?? '嵐']); ?>

    <main class="container">
        <?= $content ?>
    </main>

    <?php partial('footer'); ?>
    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>

    <?php
    use App\Core\Alerts\AlertManager;
    $alerts = AlertManager::get();
    if (!empty($alerts)):
        component('alert', ['alerts' => $alerts]);
    endif;
    ?>
</body>
</html>
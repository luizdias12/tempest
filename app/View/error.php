<?php
/**
 * @var int $errorCode
 * @var string $errorMessage
 * @var string $referer
 */
?>

<div class="error-container">
    <div class="error-box">
        <h1><?= $errorCode ?></h1>
        <p><?= $errorMessage ?></p>
        <a href=<?= $referer ?>>Voltar para a página anterior</a>
    </div>
</div>
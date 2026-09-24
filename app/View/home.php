<?php

use App\Service\AuthService;
?>
<?php if (!empty($msgDia)): ?>
    <div class="modal" id="modal-mensagem-dia" style="display:flex">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Mensagem do dia</h2>
                <span class="close" data-close>&times;</span>
            </div>
            <div class="modal-body">
                <p><?= nl2br(htmlspecialchars($msgDia)) ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="close btn-novo" data-close>Entendi</button>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if (AuthService::canManageCarousel()): ?>
    <div class="header-bar">
            <a href="/carousel/gestao" class="btn-novo"><i class="fa-solid fa-gear"></i> Gestão</a>
    </div>
<?php endif; ?>

<h2><?= $usuario ?></h2>

<?php if (!empty($slides)): ?>
    <div class="carousel" id="homeCarousel">
        <div class="carousel-viewport">
            <?php foreach ($slides as $i => $slide): ?>
                <div class="carousel-slide <?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>">
                    <?php $link = $slide['link'] ?? null; ?>
                    <?php if ($link): ?>
                        <a href="<?= htmlspecialchars($link) ?>" class="carousel-slide-link" target="_blank" rel="noopener">
                    <?php endif; ?>
                    <?php if ($slide['tipo'] === 'video'): ?>
                        <video src="<?= $slide['url'] ?>" controls muted playsinline></video>
                    <?php else: ?>
                        <img src="<?= $slide['url'] ?>" alt="Slide <?= $i + 1 ?>">
                    <?php endif; ?>
                    <?php if ($link): ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="carousel-nav carousel-prev" aria-label="Anterior">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button type="button" class="carousel-nav carousel-next" aria-label="Próximo">
            <i class="fa-solid fa-chevron-right"></i>
        </button>

        <div class="carousel-indicators">
            <?php foreach ($slides as $i => $slide): ?>
                <button type="button" class="carousel-indicator <?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>" aria-label="Ir para o slide <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
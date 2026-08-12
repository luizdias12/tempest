<h2><?= $data ?></h2>

<?php if (!empty($slides)): ?>
    <div class="carousel" id="homeCarousel">
        <div class="carousel-viewport">
            <?php foreach ($slides as $i => $slide): ?>
                <div class="carousel-slide <?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>">
                    <?php if ($slide['tipo'] === 'video'): ?>
                        <video src="<?= $slide['url'] ?>" controls muted playsinline></video>
                    <?php else: ?>
                        <img src="<?= $slide['url'] ?>" alt="Slide <?= $i + 1 ?>">
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
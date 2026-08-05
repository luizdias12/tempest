<?php
$total = (int) ($meta['total'] ?? 0);
$page = max(1, (int) ($meta['page'] ?? 1));
$limit = max(1, (int) ($meta['limit'] ?? 10));
$totalPages = max(1, (int) ($meta['totalPages'] ?? 1));
$extraQuery = $extraQuery ?? [];

$page = min($page, $totalPages);
$url = static fn(int $page) => buildPaginationUrl($page, null, $extraQuery);

if ($totalPages <= 1) {
    return;
}

$startItem = (($page - 1) * $limit) + 1;
$endItem = min($page * $limit, $total);
?>

<div class="pagination-wrapper">

    <?php if ($total === 0): ?>
        <div class="pagination-info">
            Nenhum resultado encontrado.
        </div>
        <?php return; ?>
    <?php endif; ?>

    <div class="pagination-info">
        Exibindo <?= $startItem ?> – <?= $endItem ?> de <?= $total ?> resultados
    </div>

    <nav class="pagination" aria-label="Paginação">

        <?php if ($page > 1): ?>
            <a href="<?= $url(1) ?>" class="pagination-link arrow" aria-label="Primeira página">«</a>
            <a href="<?= $url($page - 1) ?>" class="pagination-link arrow" aria-label="Página anterior">‹</a>
        <?php else: ?>
            <span class="pagination-link disabled">«</span>
            <span class="pagination-link disabled">‹</span>
        <?php endif; ?>

        <?php
        $range = $range ?? 2;
        $start = max(1, $page - $range);
        $end = min($totalPages, $page + $range);
        ?>

        <?php if ($start > 1): ?>
            <a href="<?= $url(1) ?>" class="pagination-link">1</a>

            <?php if ($start > 2): ?>
                <span class="pagination-dots">…</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="pagination-link active" aria-current="page">
                    <?= $i ?>
                </span>
            <?php else: ?>
                <a href="<?= $url($i) ?>"
                    class="pagination-link">
                    <?= $i ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
                <span class="pagination-dots">…</span>
            <?php endif; ?>

            <a href="<?= $url($totalPages) ?>" class="pagination-link">
                <?= $totalPages ?>
            </a>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= $url($page + 1) ?>" class="pagination-link arrow" aria-label="Próxima página">›</a>
            <a href="<?= $url($totalPages) ?>" class="pagination-link arrow" aria-label="Última página">»</a>
        <?php else: ?>
            <span class="pagination-link disabled">›</span>
            <span class="pagination-link disabled">»</span>
        <?php endif; ?>

    </nav>
</div>
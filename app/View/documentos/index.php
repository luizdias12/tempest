<div class="header-bar">
    <?php if (!empty($isGestao)): ?>
        <a href="/documentos/gestao" class="btn-novo"><i class="fa-solid fa-gear"></i> Gestão</a>
    <?php endif; ?>
</div>

<div class="doc-tree">
    <?php if (empty($arvore)): ?>
        <p class="doc-tree-empty">Nenhum documento disponível para a sua função.</p>
    <?php endif; ?>

    <?php foreach ($arvore as $dir): ?>
        <details class="doc-dir" <?= $dir['qt_novo'] > 0 ? 'open' : '' ?>>
            <summary>
                <i class="fa-solid fa-folder"></i>
                <span class="doc-dir-nome"><?= htmlspecialchars($dir['nome']) ?></span>
                <?php if ($dir['qt_novo'] > 0): ?>
                    <span class="badge badge-pill badge-warning">Novo <?= $dir['qt_novo'] ?></span>
                <?php endif; ?>
                <?php if ($dir['qt_atualizado'] > 0): ?>
                    <span class="badge badge-pill badge-info">Atualizado <?= $dir['qt_atualizado'] ?></span>
                <?php endif; ?>
            </summary>
            <div class="doc-dir-body">
                <?php foreach ($dir['subdirs'] as $sub): ?>
                    <details class="doc-subdir">
                        <summary>
                            <i class="fa-solid fa-folder-open"></i>
                            <span class="doc-subdir-nome"><?= htmlspecialchars($sub['nome']) ?></span>
                            <?php if ($sub['qt_novo'] > 0): ?>
                                <span class="badge badge-pill badge-warning">Novo <?= $sub['qt_novo'] ?></span>
                            <?php endif; ?>
                            <?php if ($sub['qt_atualizado'] > 0): ?>
                                <span class="badge badge-pill badge-info">Atualizado <?= $sub['qt_atualizado'] ?></span>
                            <?php endif; ?>
                        </summary>
                        <div class="doc-subdir-body">
                            <?php foreach ($sub['docs'] as $doc): ?>
                                <div class="doc-file">
                                    <i class="fa-solid <?= $doc['icone'] ?> doc-file-icon"></i>
                                    <?php if ($doc['existe']): ?>
                                        <a class="doc-file-link" href="/documentos/abrir/<?= $doc['id_doc'] ?>" target="_blank">
                                            <?= htmlspecialchars($doc['titulo']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="doc-file-nome" title="Arquivo não encontrado no servidor"><?= htmlspecialchars($doc['titulo']) ?></span>
                                        <span class="badge badge-pill badge-error">Arquivo não encontrado</span>
                                    <?php endif; ?>
                                    <span class="doc-file-versao">v<?= (int) $doc['versao'] ?></span>
                                    <span class="doc-file-tamanho"><?= htmlspecialchars($doc['tamanho_texto']) ?></span>
                                    <?php if ($doc['novo']): ?>
                                        <span class="badge badge-pill badge-warning">Novo</span>
                                    <?php endif; ?>
                                    <?php if ($doc['atualizado']): ?>
                                        <span class="badge badge-pill badge-info">Atualizado</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($sub['docs'])): ?>
                                <p class="doc-tree-empty">Nenhum documento nesta pasta.</p>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
                <?php if (empty($dir['subdirs'])): ?>
                    <p class="doc-tree-empty">Nenhuma subpasta nesta pasta.</p>
                <?php endif; ?>
            </div>
        </details>
    <?php endforeach; ?>
</div>

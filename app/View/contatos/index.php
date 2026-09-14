<div class="filter-bar">
    <form method="GET" class="filter-form">
        <label for="contato-nome">Nome:</label>
        <input type="text" id="contato-nome" name="nome" value="<?= htmlspecialchars($filtroNome ?? '') ?>" placeholder="Nome da pessoa">
        <label for="contato-email">E-mail:</label>
        <input type="text" id="contato-email" name="email" value="<?= htmlspecialchars($filtroEmail ?? '') ?>" placeholder="E-mail">
        <label for="contato-ramal">Ramal:</label>
        <input type="text" id="contato-ramal" name="ramal" value="<?= htmlspecialchars($filtroRamal ?? '') ?>" placeholder="Ramal">
        <label for="contato-setor">Setor:</label>
        <select name="setor" id="contato-setor">
            <option value="">Todos os setores</option>
            <?php foreach ($setores as $setor): ?>
                <option value="<?= htmlspecialchars((string) $setor['id']) ?>" <?= (string) $setor['id'] === (string) ($filtroSetor ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($setor['setor']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="contato-filial">Filial:</label>
        <select name="filial" id="contato-filial">
            <option value="">Todas as filiais</option>
            <?php foreach ($filiais as $filial): ?>
                <option value="<?= htmlspecialchars((string) $filial['codgfilial']) ?>" <?= (string) $filial['codgfilial'] === (string) ($filtroFilial ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($filial['descricao']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filtrar</button>

        <?php if (!empty($filtroNome) || !empty($filtroEmail) || !empty($filtroRamal) || !empty($filtroSetor) || !empty($filtroFilial)): ?>
            <a href="/contatos" class="btn-clear">Limpar filtro</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th scope="col">Nome</th>
                <th scope="col">E-mail</th>
                <th scope="col">Ramal</th>
                <th scope="col">Corporativo</th>
                <th scope="col">Setor</th>
                <th scope="col">Filial</th>
                <th scope="col">Função</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="6">Nenhum contato encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($data as $contato): ?>
                    <tr>
                        <td><?= htmlspecialchars(initcap($contato['nome'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($contato['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($contato['email']) ?>" class="anexo-link"><?= htmlspecialchars($contato['email']) ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($contato['ramal'])): ?>
                                <span class="anexo-link"><?= htmlspecialchars($contato['ramal']) ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($contato['corporativo']) && $contato['corporativo'] !== '00000000000'): ?>
                                <span class="anexo-link"><?= htmlspecialchars($contato['corporativo']) ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(initcap(trim($contato['setor'] ?? '') ?: '-')) ?></td>
                        <td><?= htmlspecialchars(trim($contato['filial'] ?? '') ?: '-') ?></td>
                        <td><?= htmlspecialchars(initcap(trim($contato['funcao']) ?? '') ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?= pagination($meta, null, array_filter(['nome' => $filtroNome ?? '', 'email' => $filtroEmail ?? '', 'ramal' => $filtroRamal ?? '', 'setor' => $filtroSetor ?? ''])) ?>
</div>
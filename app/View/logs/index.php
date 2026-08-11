<div class="filter-bar">
    <form method="GET" class="filter-form">
        <label for="nivel">Nível:</label>
        <select id="nivel" name="nivel">
            <option value="">Todos os níveis</option>
            <?php foreach (['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'] as $n): ?>
                <option value="<?= $n ?>"<?= ($nivel ?? '') === $n ? ' selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
        <label for="tipo">Tipo:</label>
        <input type="text" id="tipo" name="tipo" value="<?= htmlspecialchars($tipo ?? '') ?>" placeholder="Tipo do log">
        <label for="modulo">Módulo:</label>
        <input type="text" id="modulo" name="modulo" value="<?= htmlspecialchars($modulo ?? '') ?>" placeholder="Módulo">
        <label for="busca">Busca:</label>
        <input type="text" id="busca" name="busca" value="<?= htmlspecialchars($busca ?? '') ?>" placeholder="Mensagem, rota ou usuário">
        <label for="data">Data:</label>
        <input type="date" id="data" name="data" value="<?= htmlspecialchars($data ?? '') ?>">
        <button type="submit">Filtrar</button>

        <?php if (!empty($nivel) || !empty($tipo) || !empty($modulo) || !empty($busca) || !empty($data)): ?>
            <a href="?" class="btn-clear">Limpar filtro</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Nível</th>
                <th>Tipo</th>
                <th>Módulo</th>
                <th>Ação</th>
                <th>Usuário</th>
                <th>HTTP</th>
                <th>Rota</th>
                <th>IP</th>
                <th>Mensagem</th>
                <th>Contexto</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="12" class="historico-empty">Nenhum log encontrado.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['id']) ?></td>
                    <td><?= htmlspecialchars(!empty($log['created_at']) ? date('d-m-Y H:i:s', strtotime($log['created_at'])) : '-') ?></td>
                    <td><span class="badge badge-<?= strtolower($log['nivel'] ?? '') ?>"><?= htmlspecialchars($log['nivel'] ?? '') ?></span></td>
                    <td><?= htmlspecialchars($log['tipo'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['modulo'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['acao'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['usuario_nome'] ?? '-') ?><?= !empty($log['chapa']) ? ' (' . htmlspecialchars($log['chapa']) . ')' : '' ?></td>
                    <td><?= htmlspecialchars($log['metodo_http'] ?? '-') ?></td>
                    <td title="<?= htmlspecialchars($log['rota'] ?? '') ?>"><?= htmlspecialchars(substr($log['rota'] ?? '-', 0, 40)) ?><?= strlen($log['rota'] ?? '') > 40 ? '…' : '' ?></td>
                    <td><?= htmlspecialchars($log['ip'] ?? '-') ?></td>
                    <td title="<?= htmlspecialchars($log['mensagem'] ?? '') ?>"><?= htmlspecialchars(substr($log['mensagem'] ?? '-', 0, 60)) ?><?= strlen($log['mensagem'] ?? '') > 60 ? '…' : '' ?></td>
                    <td>
                        <?php if (!empty($log['contexto'])): ?>
                            <?php
                            $contexto = is_string($log['contexto'])
                                ? json_decode($log['contexto'], true)
                                : $log['contexto'];
                            ?>
                            <details>
                                <summary>ver</summary>
                                <pre class="log-contexto"><?= htmlspecialchars(json_encode($contexto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                            </details>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= pagination($meta, null, array_filter([
    'nivel' => $nivel ?? '',
    'tipo' => $tipo ?? '',
    'modulo' => $modulo ?? '',
    'busca' => $busca ?? '',
    'data' => $data ?? '',
])) ?>

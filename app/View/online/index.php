<div class="filter-bar">
    <form method="GET" class="filter-form">
        <label for="busca">Nome/CPF:</label>
        <input type="text" id="busca" name="busca" value="<?= htmlspecialchars($busca ?? '') ?>" placeholder="Nome ou CPF">
        <label for="local">Local:</label>
        <?php if (!empty($locais)): ?>
            <?php component('select', [
                'id' => 'local',
                'name' => 'local',
                'placeholder' => 'Todos os locais',
                'options' => $locais,
                'valueKey' => 'local',
                'labelKey' => 'local',
                'selected' => $local ?? '',
            ]); ?>
        <?php endif; ?>
        <button type="submit">Filtrar</button>

        <?php if (!empty($busca) || !empty($local)): ?>
            <a href="?" class="btn-clear">Limpar filtro</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>Local</th>
                <th>IP</th>
                <th>Última atividade</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="6" class="historico-empty">Nenhum usuário online.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?= htmlspecialchars($usuario['nome'] ?? '-') ?></td>
                    <td><?= htmlspecialchars(maskCpf($usuario['cpf'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($usuario['local'] ?? 'Nao Identificado') ?></td>
                    <td><?= htmlspecialchars($usuario['ip'] ?? '-') ?></td>
                    <td><?= htmlspecialchars(!empty($usuario['dt_login']) ? date('d-m-Y H:i:s', strtotime($usuario['dt_login'])) : '-') ?></td>
                    <td>
                        <form method="POST" action="/online/deslogar" onsubmit="return confirm('Desconectar este usuário?');">
                            <input type="hidden" name="cpf" value="<?= htmlspecialchars($usuario['cpf']) ?>">
                            <button type="submit" class="btn btn-danger">Deslogar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= pagination($meta, null, array_filter([
    'busca' => $busca ?? '',
    'local' => $local ?? '',
])) ?>

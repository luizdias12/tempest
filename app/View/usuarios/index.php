
<div class="header">
    <h1>Lista de Usuarios Cadastrados</h1>
    <p>Tabela com os usuarios cadastrados no sistema.</p>
</div>
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>CPF</th>
                <th>Usuario</th>
                <th>Email</th>
                <th>Ramal</th>
                <th>Corporativo</th>
                <th>Suporte</th>
                <th>Admin</th>
                <th>Ativo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $usuario): ?>
            <tr></tr>
                <td><?= $usuario['cpf'] ?></td>
                <td><?= $usuario['usuario'] ?></td>
                <td><?= $usuario['email'] ?></td>
                <td><?= $usuario['ramal'] ?></td>
                <td><?= $usuario['corporativo'] ?></td>
                <td><span class="status <?= $usuario['suporte'] == 'S' ? 'ativo' : 'inativo' ?>"><?= $usuario['suporte'] == 'S' ? 'Sim' : 'Não' ?></td></span>
                <td><span class="status <?= $usuario['admin'] == 'S' ? 'ativo' : 'inativo' ?>"><?= $usuario['admin'] == 'S' ? 'Sim' : 'Não' ?></td></span>
                <td><span class="status <?= $usuario['ativo'] == 'S' ? 'ativo' : 'inativo' ?>"><?= $usuario['ativo'] == 'S' ? 'Sim' : 'Não' ?></td></span>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?= pagination($meta) ?>
</div>
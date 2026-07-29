<div class="header">
    <h1>Lista de Funcionários da TI</h1>
    <p>Tabela com os funcionários da área de Tecnologia da Informação.</p>
    <p>As ultimas alterações listadas são apenas dos motivos "Mérito" e "Promoção".</p>
    <a href="/ti/lista/download" class="btn-download">Baixar Excel</a>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Chapa</th>
                <th>Nome</th>
                <th>Filial</th>
                <th>Funçao</th>
                <th>Salario</th>
                <th>Data Admissao</th>
                <th>Situaçao</th>
                <th>Ultima Alteração</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $usuario): ?>
                <tr>
                    <td><?= $usuario['chapa'] ?></td>
                    <td><?= (initcap($usuario['nome'])) ?></td>
                    <td><?= ($usuario['codfilial'] . " - " . initcap($usuario['filial'])) ?></td>
                    <td><?= $usuario['funcao'] ?></td>
                    <td><?= number_format($usuario['salario'], 2, ',', '.') ?></td>
                    <td><?= date('d-m-Y', strtotime($usuario['dataadmissao'])) ?></td>
                    <td><span class="status <?= $usuario['codsituacao'] == 'A' ? 'ativo' : ($usuario['codsituacao'] == 'D' ? 'demitido' : ($usuario['codsituacao'] == 'F' ? 'ferias' : 'afastado')) ?>"><?= $usuario['situacao'] ?></span></td>
                    <td>
                        <?php if($usuario['ultima_alteracao']): ?>
                            <?= date('d-m-Y', strtotime($usuario['ultima_alteracao'])) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>   
                    </td>
                    <td><?= $usuario['motivo'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?= pagination($meta) ?>
</div>
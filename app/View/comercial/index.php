<?php

use App\Service\AuthService;
use App\Service\DateTimeService;

if (AuthService::canManageComercial()): ?>
    <div class="header-bar">
        <a href="/comercial/gestao" class="btn-novo"><i class="fa-solid fa-gear"></i> Gestão</a>
    </div>
<?php endif; ?>
<div class="two-grids-table">
    <div class="table-wrapper">
        <div class="container-wrapper">
            <h3 class="w-alternative">ESCALA DE PLANTÕES COMERCIAL - <?= $descricaoMes ?> / <?= DateTimeService::anoAtual(true) ?></h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th scope="col">Data</th>
                    <th scope="col">Nome</th>
                    <th scope="col">Sabado/Feriado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($escalas)): ?>
                    <tr>
                        <td colspan="3">Nao ha informaçoes</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($escalas as $escala): ?>
                        <tr>
                            <td><?= !empty($escala['data']) ? date('d-m-Y', strtotime($escala['data'])) : '-' ?></td>
                            <td><?= htmlspecialchars($escala['nome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($escala['referencia'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-wrapper">
        <div class="container-wrapper">
            <h3 class="w-alternative">ESCALA DE FÉRIAS COMERCIAL - <?= $descricaoMes ?> / <?= DateTimeService::anoAtual(true) ?></h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th scope="col">Filial</th>
                    <th scope="col">Nome</th>
                    <th scope="col">Inicio</th>
                    <th scope="col">Fim</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ferias)): ?>
                    <tr>
                        <td colspan="4">Nao ha informaçoes</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ferias as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars(initcap($f['filial']) ?? '') ?></td>
                            <td><?= htmlspecialchars(initcap($f['nome']) ?? '') ?></td>
                            <td><?= !empty($f['datainicio']) ? date('d-m-Y', strtotime($f['datainicio'])) : '-' ?></td>
                            <td><?= !empty($f['datafim']) ? date('d-m-Y', strtotime($f['datafim'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
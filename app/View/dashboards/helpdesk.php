<?php
/** @var array $dados */
/** @var array $periodos */
/** @var int $dias */

$buckets = $dados['buckets'];
$kpis = $dados['kpis'];
?>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <label for="dias">Período:</label>
        <select id="dias" name="dias" onchange="this.form.submit()">
            <?php foreach ($periodos as $opcao): ?>
                <option value="<?= $opcao ?>" <?= $opcao === $dias ? 'selected' : '' ?>>
                    Últimos <?= $opcao ?> dias
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Filtrar</button></noscript>
        <span class="filter-hint"><?= htmlspecialchars($dados['periodo']['de']) ?> a <?= htmlspecialchars($dados['periodo']['ate']) ?></span>
    </form>
</div>

<div class="dash-cards">
    <div class="dash-card">
        <span class="dash-card-label">Abertos agora</span>
        <strong class="dash-card-value"><?= number_format($kpis['abertos'], 0, ',', '.') ?></strong>
        <small class="dash-card-foot">Fila atual, sem Resolvido/Cancelado</small>
    </div>
    <div class="dash-card">
        <span class="dash-card-label">Não iniciados</span>
        <strong class="dash-card-value"><?= number_format($kpis['nao_iniciados'], 0, ',', '.') ?></strong>
        <small class="dash-card-foot">Aguardando atribuição</small>
    </div>
    <div class="dash-card">
        <span class="dash-card-label">Em atendimento</span>
        <strong class="dash-card-value"><?= number_format($kpis['em_atendimento'], 0, ',', '.') ?></strong>
        <small class="dash-card-foot">Em Análise + Desenvolvimento</small>
    </div>
    <div class="dash-card">
        <span class="dash-card-label">Aguardando terceiros</span>
        <strong class="dash-card-value"><?= number_format($kpis['aguardando_terceiros'], 0, ',', '.') ?></strong>
        <small class="dash-card-foot">Encaminhado, Suporte, Validação, Terceiro</small>
    </div>
    <div class="dash-card dash-card-warn">
        <span class="dash-card-label">Parados há <?= (int) $dados['dias_envelhecido'] ?>+ dias</span>
        <strong class="dash-card-value"><?= number_format($kpis['envelhecidos'], 0, ',', '.') ?></strong>
        <small class="dash-card-foot">Abertos e sem movimentação</small>
    </div>
    <div class="dash-card">
        <span class="dash-card-label">Resolvidos no período</span>
        <strong class="dash-card-value"><?= number_format($kpis['resolvidos'], 0, ',', '.') ?></strong>
        <small class="dash-card-foot">Tempo médio <?= htmlspecialchars($kpis['tempo_medio']) ?></small>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-panel dash-panel-wide">
        <h2 class="dash-panel-title">Abertos e resolvidos por dia</h2>
        <div class="chart-box">
            <canvas id="dashSerie"></canvas>
        </div>
    </div>

    <div class="dash-panel">
        <h2 class="dash-panel-title">Fase dos chamados do período</h2>
        <div class="chart-box chart-box-doughnut">
            <canvas id="dashBuckets"></canvas>
        </div>
        <ul class="dash-legend">
            <li><span class="dash-dot" style="background:#9aa4b2"></span>Não iniciado <strong><?= number_format($buckets['nao_iniciados'], 0, ',', '.') ?></strong></li>
            <li><span class="dash-dot" style="background:#2f6feb"></span>Em atendimento <strong><?= number_format($buckets['em_atendimento'], 0, ',', '.') ?></strong></li>
            <li><span class="dash-dot" style="background:#d98324"></span>Aguardando terceiros <strong><?= number_format($buckets['aguardando_terceiros'], 0, ',', '.') ?></strong></li>
            <li><span class="dash-dot" style="background:#3f9c6d"></span>Encerrado <strong><?= number_format($buckets['encerrados'], 0, ',', '.') ?></strong></li>
        </ul>
    </div>

    <div class="dash-panel">
        <h2 class="dash-panel-title">Chamados abertos por grupo</h2>
        <div class="chart-box">
            <canvas id="dashGrupos"></canvas>
        </div>
    </div>

    <div class="dash-panel">
        <h2 class="dash-panel-title">Chamados abertos por responsável</h2>
        <div class="chart-box">
            <canvas id="dashResponsaveis"></canvas>
        </div>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-panel">
        <h2 class="dash-panel-title">Fila aberta por status</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th class="dash-col-num">Chamados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dados['por_status'])): ?>
                        <tr><td colspan="2" class="historico-empty">Nenhum chamado aberto.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($dados['por_status'] as $item): ?>
                        <tr>
                            <td>
                                <span class="dash-status"><?= htmlspecialchars($item['status']) ?></span>
                                <?= htmlspecialchars($item['status_desc']) ?>
                            </td>
                            <td class="dash-col-num"><?= number_format($item['total'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="dash-panel">
        <h2 class="dash-panel-title">Chamados abertos mais antigos</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Chapa</th>
                        <th>Assunto</th>
                        <th>Status</th>
                        <th>Responsável</th>
                        <th class="dash-col-num">Dias</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dados['mais_antigos'])): ?>
                        <tr><td colspan="5" class="historico-empty">Nenhum chamado aberto.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($dados['mais_antigos'] as $item): ?>
                        <tr>
                            <td>
                                <a href="/helpdesk/index?id=<?= (int) ($item['id'] ?? 0) ?>&open=<?= (int) ($item['id'] ?? 0) ?>">
                                    <?= htmlspecialchars((string) ($item['chapa'] ?? '-')) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars((string) ($item['cab_problema'] ?? '-')) ?></td>
                            <td>
                                <span class="dash-status"><?= htmlspecialchars((string) ($item['status'] ?? '-')) ?></span>
                                <?= htmlspecialchars((string) ($item['status_desc'] ?? '')) ?>
                            </td>
                            <td><?= htmlspecialchars((string) ($item['responsavel'] ?? '-')) ?></td>
                            <td class="dash-col-num <?= ((int) ($item['dias_aberto'] ?? 0)) > (int) $dados['dias_envelhecido'] ? 'dash-col-warn' : '' ?>">
                                <?= (int) ($item['dias_aberto'] ?? 0) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    window.DASHBOARD = <?= json_encode([
        'serie' => $dados['serie'],
        'buckets' => $buckets,
        'por_grupo' => $dados['por_grupo'],
        'por_responsavel' => $dados['por_responsavel'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="<?= asset('js/dashboards.js') ?>"></script>

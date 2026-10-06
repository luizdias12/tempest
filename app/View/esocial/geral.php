<?php

use App\Service\EsocialService;

/** 
 * @var array $dados
 * @var int $mescomp
 * @var int $anocomp
 * @var int $tipoevento
 * */

?>

<div class="header-bar">
    <a href="/esocial" class="btn-novo"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
</div>

<?php if ($tipoevento === 2): ?>
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <label for="mescomp">Mês:</label>
            <select id="mescomp" name="mescomp">
                <?php $meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro']; ?>
                <?php foreach ($meses as $num => $nome): ?>
                    <option value="<?= $num ?>" <?= ($mescomp == $num) ? 'selected' : '' ?>><?= $nome ?></option>
                <?php endforeach; ?>
            </select>

            <label for="anocomp">Ano:</label>
            <select id="anocomp" name="anocomp">
                <?php for ($a = (int)date('Y'); $a >= 2018; $a--): ?>
                    <option value="<?= $a ?>" <?= ($anocomp == $a) ? 'selected' : '' ?>><?= $a ?></option>
                <?php endfor; ?>
            </select>

            <button type="submit">Consultar</button>
        </form>
    </div>
<?php endif; ?>

<?php if (empty($dados)): ?>
    <div class="error-container">
        <div class="error-box">
            <h1>Sem dados</h1>
            <p>Não há dados disponíveis para exibir.</p>
        </div>
    </div>
<?php else: ?>
    <div class="filter-bar">
        <div class="filter-form">
            <span class="esocial-refresh" id="esocial-refresh">Atualizado às <strong id="esocial-hora">--:--:--</strong></span>
        </div>
    </div>
    <div class="cards-container">
        <?php foreach ($dados as $evento): ?>
            <div class="evento-card">
                <div class="evento-card-header">
                    <div>
                        <span class="evento-codigo"><?= $evento['tipoevento'] ?></span>
                        <span class="evento-descricao">
                            <?= EsocialService::getEventoDesc($evento['tipoevento']) ?>
                        </span>
                    </div>

                    <div class="evento-total">
                        <?= $evento['total'] ?>
                        <small>total</small>
                    </div>
                </div>

                <div class="evento-status">

                    <div class="status-item <?= $evento['pendente'] ? 'nozero' : '' ?>">
                        <span>Pendente</span>
                        <strong><?= $evento['pendente'] ?></strong>
                    </div>

                    <div class="status-item <?= $evento['xml_gerado'] ? 'nozero' : '' ?>">
                        <span>XML Gerado</span>
                        <strong><?= $evento['xml_gerado'] ?></strong>
                    </div>

                    <div class="status-item <?= $evento['erro_gerar_xml'] ? 'nozero' : '' ?>">
                        <span>Erro ao Gerar XML</span>
                        <strong><?= $evento['erro_gerar_xml'] ?></strong>
                    </div>

                    <div class="status-item <?= $evento['integrado_taf'] ? 'nozero' : '' ?>">
                        <span>Integrado TAF</span>
                        <strong><?= $evento['integrado_taf'] ?></strong>
                    </div>

                    <div class="status-item <?= $evento['aceito_taf'] ? 'nozero' : '' ?>">
                        <span>Aceito TAF</span>
                        <strong><?= $evento['aceito_taf'] ?></strong>
                    </div>

                    <div class="status-item <?= $evento['erro_integracao_taf'] ? 'nozero' : '' ?>">
                        <span>Erro na Integração</span>
                        <strong><?= $evento['erro_integracao_taf'] ?></strong>
                    </div>

                    <div class="status-item <?= $evento['rejeitado_taf'] ? 'nozero' : '' ?>">
                        <span>Rejeitado TAF</span>
                        <strong><?= $evento['rejeitado_taf'] ?></strong>
                    </div>

                    <div class="status-item <?= $evento['rejeitado_ret'] ? 'nozero' : '' ?>">
                        <span>Rejeitado RET</span>
                        <strong><?= $evento['rejeitado_ret'] ?></strong>
                    </div>

                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
    (function() {
        const REFRESH_MS = 30000;
        const horaEl = document.getElementById('esocial-hora');

        function atualizarHora() {
            if (horaEl) {
                horaEl.textContent = new Date().toLocaleTimeString('pt-BR');
            }
        }

        atualizarHora();
        setInterval(function() {
            window.location.reload();
        }, REFRESH_MS);

    })();
</script>
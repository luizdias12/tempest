<?php

use App\Service\EsocialService;

/** 
 * @var array $data 
 * @var array $fullData 
 * */

?>
<?php if (empty($fullData)): ?>
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
        <?php foreach ($fullData as $evento): ?>
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

                    <div class="status-item">
                        <span>Pendente</span>
                        <strong><?= $evento['pendente'] ?></strong>
                    </div>

                    <div class="status-item">
                        <span>XML Gerado</span>
                        <strong><?= $evento['xml_gerado'] ?></strong>
                    </div>

                    <div class="status-item">
                        <span>Erro ao Gerar XML</span>
                        <strong><?= $evento['erro_gerar_xml'] ?></strong>
                    </div>

                    <div class="status-item">
                        <span>Integrado TAF</span>
                        <strong><?= $evento['integrado_taf'] ?></strong>
                    </div>

                    <div class="status-item">
                        <span>Aceito TAF</span>
                        <strong><?= $evento['aceito_taf'] ?></strong>
                    </div>

                    <div class="status-item">
                        <span>Erro na Integração</span>
                        <strong><?= $evento['erro_integracao_taf'] ?></strong>
                    </div>

                    <div class="status-item">
                        <span>Rejeitado TAF</span>
                        <strong><?= $evento['rejeitado_taf'] ?></strong>
                    </div>

                    <div class="status-item">
                        <span>Rejeitado RET</span>
                        <strong><?= $evento['rejeitado_ret'] ?></strong>
                    </div>

                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>

(function () {
    const REFRESH_MS = 30000;
    const horaEl = document.getElementById('esocial-hora');

    function atualizarHora() {
        if (horaEl) {
            horaEl.textContent = new Date().toLocaleTimeString('pt-BR');
        }
    }

    atualizarHora();
    setInterval(atualizarHora, REFRESH_MS);
})();

</script>
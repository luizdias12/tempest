<?php

use App\Service\HelpdeskService;

$pendentes = HelpdeskService::chamadosPendentesUsuario() ?? [];

foreach ($pendentes as $chamado) {
    $dataPend = $chamado['data_status'] ?? $chamado['data_hist'] ?? '';

    // if (empty($dataPend) || businessHoursBetween($dataPend) < 48) {
    //     continue;
    // }

    echo "<p>Chamado: {$chamado['id']} - Horas Uteis Pendente: " . businessHoursBetween($dataPend) . " </p>";
}   
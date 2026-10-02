<?php

namespace App\Service;

use App\Model\Mysql\HelpdeskDashboardModel;

class DashboardService
{
    /** Períodos aceitos em ?dias=. */
    public const PERIODOS = [7, 30, 90];

    public const PERIODO_PADRAO = 30;

    /**
     * Monta todo o payload do dashboard de Helpdesk.
     *
     * @return array<string,mixed>
     */
    public static function helpdesk(int $dias): array
    {
        $dias = self::normalizarPeriodo($dias);

        $fim = new \DateTimeImmutable('now');
        $inicio = $fim->modify('-' . ($dias - 1) . ' days')->setTime(0, 0);

        $de = $inicio->format('Y-m-d H:i:s');
        $ate = $fim->format('Y-m-d H:i:s');

        $kpis = HelpdeskDashboardModel::kpis($de, $ate);
        $buckets = HelpdeskDashboardModel::bucketsPeriodo($de, $ate);

        return [
            'periodo' => [
                'dias' => $dias,
                'de' => $inicio->format('d/m/Y'),
                'ate' => $fim->format('d/m/Y'),
            ],
            'kpis' => [
                'abertos' => $kpis['abertos'],
                'nao_iniciados' => $kpis['nao_iniciados'],
                'em_atendimento' => $kpis['em_atendimento'],
                'aguardando_terceiros' => $kpis['aguardando_terceiros'],
                'envelhecidos' => $kpis['envelhecidos'],
                'resolvidos' => $kpis['resolvidos'],
                'cancelados' => $kpis['cancelados'],
                'tempo_medio' => self::formatarDuracao($kpis['tempo_medio_min']),
                'tempo_medio_min' => $kpis['tempo_medio_min'],
            ],
            'buckets' => $buckets,
            'serie' => HelpdeskDashboardModel::serieDiaria($de, $ate),
            'por_grupo' => HelpdeskDashboardModel::porGrupo(),
            'por_responsavel' => HelpdeskDashboardModel::porResponsavel(),
            'por_status' => HelpdeskDashboardModel::porStatus(),
            'mais_antigos' => HelpdeskDashboardModel::maisAntigos(),
            'dias_envelhecido' => HelpdeskDashboardModel::DIAS_ENVELHECIDO,
        ];
    }

    public static function normalizarPeriodo(int $dias): int
    {
        return in_array($dias, self::PERIODOS, true) ? $dias : self::PERIODO_PADRAO;
    }

    /** Minutos -> "3h 12min", omite a parte zero. */
    private static function formatarDuracao(?float $minutos): string
    {
        if ($minutos === null || $minutos <= 0) {
            return '—';
        }

        $total = (int) round($minutos);
        $horas = intdiv($total, 60);
        $min = $total % 60;

        if ($horas === 0) {
            return "{$min}min";
        }

        return $min === 0 ? "{$horas}h" : "{$horas}h {$min}min";
    }
}

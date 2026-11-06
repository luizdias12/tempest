<?php

namespace App\Service;

use App\Model\Mysql\SlaBaseModel;
use App\Model\Mysql\SlaCalendarioModel;
use App\Model\Mysql\SlaRegraModel;
use App\Model\Mysql\SlaStatusModel;
use RuntimeException;

class SlaService
{
    public static function listarBases(): array
    {
        return SlaBaseModel::listar();
    }

    public static function listarBasesAtivas(): array
    {
        return SlaBaseModel::listarAtivas();
    }

    public static function listarCalendarios(): array
    {
        return SlaCalendarioModel::listar();
    }

    public static function listarCalendariosAtivos(): array
    {
        return SlaCalendarioModel::listarAtivos();
    }

    public static function listarRegras(): array
    {
        $rows = SlaRegraModel::listar();

        return array_map(static function (array $r): array {
            $r['grupo_txt'] = ($r['grupo_descricao'] ?? '') !== '' ? $r['grupo_descricao'] : 'Geral';
            $r['subgrupo_txt'] = ($r['subgrupo_descricao'] ?? '') !== '' ? $r['subgrupo_descricao'] : 'Geral';
            $r['calendario_txt'] = ($r['calendario_nome'] ?? '') !== '' ? $r['calendario_nome'] : '—';
            $r['prazo_resp_txt'] = self::formatarMin($r['prazo_primeira_resposta_min'] ?? 0);
            $r['prazo_resol_txt'] = self::formatarMin($r['prazo_resolucao_min'] ?? 0);

            return $r;
        }, $rows);
    }

    public static function listarStatus(): array
    {
        return SlaStatusModel::listarTodos();
    }

    public static function salvarBase(?int $id, string $nome, string $descricao, bool $ativo): string
    {
        $nome = trim($nome);
        $descricao = trim($descricao);

        if ($nome === '') {
            throw new RuntimeException('Informe o nome da base de SLA.');
        }

        if (mb_strlen($nome) > 100) {
            throw new RuntimeException('O nome da base deve ter no máximo 100 caracteres.');
        }

        if (mb_strlen($descricao) > 255) {
            throw new RuntimeException('A descrição deve ter no máximo 255 caracteres.');
        }

        $dados = [
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'ativo' => $ativo ? 1 : 0,
        ];

        if ($id !== null && $id > 0) {
            if (SlaBaseModel::buscar($id) === null) {
                throw new RuntimeException('Base de SLA não encontrada.');
            }

            SlaBaseModel::atualizar($id, $dados);

            return 'Base de SLA atualizada com sucesso.';
        }

        if (SlaBaseModel::inserir($dados) === null) {
            throw new RuntimeException('Falha ao cadastrar a base de SLA.');
        }

        return 'Base de SLA cadastrada com sucesso.';
    }

    public static function excluirBase(int $id): string
    {
        if (SlaBaseModel::buscar($id) === null) {
            throw new RuntimeException('Base de SLA não encontrada.');
        }

        if (SlaBaseModel::countRegras($id) > 0) {
            throw new RuntimeException('Não é possível excluir uma base que possui regras de SLA. Exclua as regras primeiro.');
        }

        SlaBaseModel::excluir($id);

        return 'Base de SLA excluída.';
    }

    public static function salvarCalendario(
        ?int $id,
        string $nome,
        array $dias,
        string $horaInicio,
        string $horaFim,
        ?string $intervaloInicio,
        ?string $intervaloFim,
        bool $ativo
    ): string {
        $nome = trim($nome);

        if ($nome === '') {
            throw new RuntimeException('Informe o nome do calendário.');
        }

        if (mb_strlen($nome) > 100) {
            throw new RuntimeException('O nome do calendário deve ter no máximo 100 caracteres.');
        }

        $diasFixos = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];

        $valores = [];

        foreach ($diasFixos as $dia) {
            $valores[$dia] = !empty($dias[$dia]) ? 1 : 0;
        }

        if (array_sum($valores) === 0) {
            throw new RuntimeException('Marque ao menos um dia de funcionamento.');
        }

        if (!self::tempoValido($horaInicio) || !self::tempoValido($horaFim)) {
            throw new RuntimeException('Informe horários de funcionamento válidos.');
        }

        $horaInicio = self::normalizarHora($horaInicio);
        $horaFim = self::normalizarHora($horaFim);

        if ($horaInicio >= $horaFim) {
            throw new RuntimeException('O horário de início deve ser anterior ao horário de fim.');
        }

        $intervaloInicio = trim((string) $intervaloInicio) !== '' ? $intervaloInicio : null;
        $intervaloFim = trim((string) $intervaloFim) !== '' ? $intervaloFim : null;

        if ($intervaloInicio !== null || $intervaloFim !== null) {
            if ($intervaloInicio === null || $intervaloFim === null) {
                throw new RuntimeException('Informe o início e o fim do intervalo de pausa.');
            }

            if (!self::tempoValido($intervaloInicio) || !self::tempoValido($intervaloFim)) {
                throw new RuntimeException('Informe horários de intervalo válidos.');
            }

            $intervaloInicio = self::normalizarHora($intervaloInicio);
            $intervaloFim = self::normalizarHora($intervaloFim);

            if ($intervaloInicio >= $intervaloFim) {
                throw new RuntimeException('O início do intervalo deve ser anterior ao fim.');
            }

            if ($intervaloInicio <= $horaInicio || $intervaloFim >= $horaFim) {
                throw new RuntimeException('O intervalo deve estar dentro do horário de funcionamento.');
            }
        }

        $dados = array_merge($valores, [
            'nome' => $nome,
            'hora_inicio' => $horaInicio,
            'hora_fim' => $horaFim,
            'intervalo_inicio' => $intervaloInicio,
            'intervalo_fim' => $intervaloFim,
            'ativo' => $ativo ? 1 : 0,
        ]);

        if ($id !== null && $id > 0) {
            if (SlaCalendarioModel::buscar($id) === null) {
                throw new RuntimeException('Calendário não encontrado.');
            }

            SlaCalendarioModel::atualizar($id, $dados);

            return 'Calendário atualizado com sucesso.';
        }

        if (SlaCalendarioModel::inserir($dados) === null) {
            throw new RuntimeException('Falha ao cadastrar o calendário.');
        }

        return 'Calendário cadastrado com sucesso.';
    }

    public static function excluirCalendario(int $id): string
    {
        if (SlaCalendarioModel::buscar($id) === null) {
            throw new RuntimeException('Calendário não encontrado.');
        }

        SlaCalendarioModel::excluir($id);

        return 'Calendário excluído.';
    }

    public static function salvarRegra(
        ?int $id,
        int $slaId,
        ?int $grupoId,
        ?int $subgrupoId,
        ?int $calendarioId,
        ?int $prazoPrimeiraResposta,
        ?int $prazoResolucao,
        int $ordem,
        bool $ativo
    ): string {
        if ($slaId <= 0 || SlaBaseModel::buscar($slaId) === null) {
            throw new RuntimeException('Selecione uma base de SLA válida.');
        }

        $prazoPrimeiraResposta = $prazoPrimeiraResposta !== null && $prazoPrimeiraResposta > 0 ? $prazoPrimeiraResposta : null;
        $prazoResolucao = $prazoResolucao !== null && $prazoResolucao > 0 ? $prazoResolucao : null;

        if ($prazoPrimeiraResposta === null && $prazoResolucao === null) {
            throw new RuntimeException('Informe ao menos um prazo (primeira resposta ou resolução).');
        }

        if ($ordem < 0) {
            throw new RuntimeException('A ordem deve ser um número maior ou igual a zero.');
        }

        $dados = [
            'sla_id' => $slaId,
            'grupo_id' => $grupoId !== null && $grupoId > 0 ? $grupoId : null,
            'subgrupo_id' => $subgrupoId !== null && $subgrupoId > 0 ? $subgrupoId : null,
            'calendario_id' => $calendarioId !== null && $calendarioId > 0 ? $calendarioId : null,
            'prazo_primeira_resposta_min' => $prazoPrimeiraResposta,
            'prazo_resolucao_min' => $prazoResolucao,
            'ordem' => $ordem,
            'ativo' => $ativo ? 1 : 0,
        ];

        if ($id !== null && $id > 0) {
            if (SlaRegraModel::buscar($id) === null) {
                throw new RuntimeException('Regra de SLA não encontrada.');
            }

            SlaRegraModel::atualizar($id, $dados);

            return 'Regra de SLA atualizada com sucesso.';
        }

        if (SlaRegraModel::inserir($dados) === null) {
            throw new RuntimeException('Falha ao cadastrar a regra de SLA.');
        }

        return 'Regra de SLA cadastrada com sucesso.';
    }

    public static function excluirRegra(int $id): string
    {
        if (SlaRegraModel::buscar($id) === null) {
            throw new RuntimeException('Regra de SLA não encontrada.');
        }

        SlaRegraModel::excluir($id);

        return 'Regra de SLA excluída.';
    }

    public static function salvarStatus(?int $id, int $slaId, int $statusId, bool $contabiliza): string
    {
        if ($slaId <= 0 || SlaBaseModel::buscar($slaId) === null) {
            throw new RuntimeException('Selecione uma base de SLA válida.');
        }

        if ($statusId <= 0) {
            throw new RuntimeException('Informe um código de status válido.');
        }

        $dados = [
            'sla_id' => $slaId,
            'status_id' => $statusId,
            'contabiliza_tempo' => $contabiliza ? 1 : 0,
        ];

        if ($id !== null && $id > 0) {
            if (SlaStatusModel::buscar($id) === null) {
                throw new RuntimeException('Status de SLA não encontrado.');
            }

            SlaStatusModel::atualizar($id, $dados);

            return 'Status do SLA atualizado com sucesso.';
        }

        $existente = SlaStatusModel::buscarPorSlaStatus($slaId, $statusId);

        if ($existente !== null) {
            SlaStatusModel::atualizar((int) $existente['id'], $dados);

            return 'Status do SLA atualizado com sucesso.';
        }

        if (SlaStatusModel::inserir($dados) === null) {
            throw new RuntimeException('Falha ao configurar o status do SLA.');
        }

        return 'Status do SLA configurado com sucesso.';
    }

    public static function excluirStatus(int $id): string
    {
        if (SlaStatusModel::buscar($id) === null) {
            throw new RuntimeException('Status de SLA não encontrado.');
        }

        SlaStatusModel::excluir($id);

        return 'Status removido do SLA.';
    }

    private static function tempoValido(string $valor): bool
    {
        return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', trim($valor));
    }

    private static function normalizarHora(string $valor): string
    {
        $ts = strtotime(trim($valor));

        return $ts !== false ? date('H:i:s', $ts) : $valor;
    }

    private static function formatarMin($min): string
    {
        $min = (int) $min;

        if ($min <= 0) {
            return '—';
        }

        $horas = intdiv($min, 60);
        $minutos = $min % 60;

        if ($horas === 0) {
            return $minutos . ' min';
        }

        return $minutos > 0 ? "{$horas} h {$minutos} min" : "{$horas} h";
    }
}
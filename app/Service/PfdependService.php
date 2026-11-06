<?php

namespace App\Service;

use App\Core\DB;
use App\Model\Oracle\PfdependModel;
use RuntimeException;
use Throwable;

class PfdependService
{
    public static function previa(): array
    {
        $competencia = self::competencia();

        return [
            'competencia' => $competencia,
            'contadores'  => PfdependModel::conferencia($competencia['referencia']),
        ];
    }

    public static function atualizar(): array
    {
        $competencia = self::competencia();
        $contadores = PfdependModel::conferencia($competencia['referencia']);

        DB::beginTransaction();

        try {
            $incsalfam = PfdependModel::zerarIncsalfam();
            $marcados = PfdependModel::marcarVacinaEscolar($competencia['referencia']);

            DB::commit();
        } catch (Throwable $e) {
            if (DB::connect()->inTransaction()) {
                DB::rollBack();
            }

            throw $e;
        }

        return [
            'competencia' => $competencia,
            'contadores'  => $contadores,
            'alterados'   => [
                'incsalfam'    => $incsalfam,
                'cartaovacina' => $marcados,
            ],
        ];
    }

    private static function competencia(): array
    {
        $competencia = PfdependModel::competencia();

        if ($competencia === null) {
            throw new RuntimeException('Não foi possível ler a competência (anocomp/mescomp) na tabela pparam.');
        }

        return $competencia;
    }
}

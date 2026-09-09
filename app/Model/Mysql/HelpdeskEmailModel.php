<?php

namespace App\Model\Mysql;

use App\Core\DB;

class HelpdeskEmailModel
{
    public static function jaImportado(string $messageId): bool
    {
        return DB::first(
            "SELECT 1 FROM helpdesk_email_importado WHERE message_id = :id LIMIT 1",
            ['id' => $messageId],
            'mysql'
        ) !== null;
    }

    public static function reservarImportacao(string $messageId): bool
    {
        try {
            DB::deleteWhere('helpdesk_email_importado', [
                'message_id' => $messageId,
                'help_id' => 0,
            ]);

            DB::insert('helpdesk_email_importado', [
                'message_id'   => $messageId,
                'help_id'      => 0,
                'importado_em' => date('Y-m-d H:i:s'),
            ], 'mysql');

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function confirmarImportacao(string $messageId, int $helpId): bool
    {
        DB::update('helpdesk_email_importado', 'message_id', $messageId, [
            'help_id' => $helpId,
        ], 'mysql');

        return true;
    }

    public static function liberarReserva(string $messageId): bool
    {
        DB::deleteWhere('helpdesk_email_importado', [
            'message_id' => $messageId,
        ]);

        return true;
    }

    public static function registrar(string $messageId, int $helpId): bool
    {
        DB::insert('helpdesk_email_importado', [
            'message_id'   => $messageId,
            'help_id'      => $helpId,
            'importado_em' => date('Y-m-d H:i:s'),
        ], 'mysql');

        return true;
    }

    public static function buscarUsuarioPorEmail(string $email): ?array
    {
        return DB::first(
            "SELECT u.cpf, u.email
             FROM usuarios u
             WHERE u.email = :email
             LIMIT 1",
            ['email' => $email],
            'mysql'
        );
    }

    public static function dadosFuncionario(string $cpf): ?array
    {
        return DB::first(
            "SELECT f.cpf, f.nome, f.chapa
             FROM func f
             WHERE f.cpf = :cpf
             UNION ALL
             SELECT fe.cpf, fe.nome, fe.chapa
             FROM func_externo fe
             WHERE fe.cpf = :cpf2
             LIMIT 1",
            ['cpf' => $cpf, 'cpf2' => $cpf],
            'mysql'
        );
    }

    public static function criarTabelaSeNecessario(): void
    {
        DB::connect('mysql')->exec("
            CREATE TABLE IF NOT EXISTS helpdesk_email_importado (
                message_id VARCHAR(200) NOT NULL PRIMARY KEY,
                help_id INT NOT NULL,
                importado_em DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}

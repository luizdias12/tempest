<?php

namespace App\Model\Mysql;

use App\Core\DB;
use App\Core\Logger;

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

    public static function reservarImportacao(string $messageId, string $conversationId = ''): bool
    {
        try {
            $stmt = DB::connect('mysql')->prepare(
                "DELETE FROM helpdesk_email_importado WHERE message_id = :id AND help_id = 0"
            );
            $stmt->execute(['id' => $messageId]);

            DB::insert('helpdesk_email_importado', [
                'message_id'      => $messageId,
                'help_id'         => 0,
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'importado_em'    => date('Y-m-d H:i:s'),
            ], 'mysql');

            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                Logger::error("HelpdeskEmailModel: duplicidade ao reservar mensagem {$messageId}");
                return false;
            }

            Logger::exception($e, ['message_id' => $messageId]);
            throw $e;
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
        $stmt = DB::connect('mysql')->prepare(
            "DELETE FROM helpdesk_email_importado WHERE message_id = :id AND help_id = 0"
        );
        $stmt->execute(['id' => $messageId]);

        return true;
    }

    public static function buscarPorConversa(string $conversationId): ?int
    {
        if ($conversationId === '') {
            return null;
        }

        $row = DB::first(
            "SELECT help_id FROM helpdesk_email_importado
             WHERE conversation_id = :id AND help_id > 0
             LIMIT 1",
            ['id' => $conversationId],
            'mysql'
        );

        return ($row['help_id'] ?? 0) > 0 ? (int) $row['help_id'] : null;
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
        $pdo = DB::connect('mysql');

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS helpdesk_email_importado (
                message_id VARCHAR(200) NOT NULL PRIMARY KEY,
                help_id INT NOT NULL,
                conversation_id VARCHAR(200) NULL,
                importado_em DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $coluna = $pdo->query("SHOW COLUMNS FROM helpdesk_email_importado LIKE 'conversation_id'")->fetch();

        if (!$coluna) {
            $pdo->exec("ALTER TABLE helpdesk_email_importado ADD COLUMN conversation_id VARCHAR(200) NULL AFTER help_id");
        }
    }
}

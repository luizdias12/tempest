<?php

namespace App\Model\Mysql;

use App\Core\DB;
use PDO;

class ChatModel
{
    public static function conversas(string $cpf): array
    {
        $sql = "
            SELECT c.id, c.tipo, c.titulo, c.criado_por, c.criado_em,
                (SELECT mx.texto FROM chat_mensagens mx
                    WHERE mx.conversa_id = c.id AND mx.id > COALESCE(p.limpo_em, 0)
                    ORDER BY mx.id DESC LIMIT 1) AS ultima_msg,
                (SELECT mx.criado_em FROM chat_mensagens mx
                    WHERE mx.conversa_id = c.id AND mx.id > COALESCE(p.limpo_em, 0)
                    ORDER BY mx.id DESC LIMIT 1) AS ultima_em,
                (SELECT mx.id FROM chat_mensagens mx
                    WHERE mx.conversa_id = c.id AND mx.id > COALESCE(p.limpo_em, 0)
                    ORDER BY mx.id DESC LIMIT 1) AS ultima_id,
                (SELECT COUNT(*) FROM chat_mensagens mx
                    WHERE mx.conversa_id = c.id
                      AND mx.id > GREATEST(COALESCE(p.ultimo_lido, 0), COALESCE(p.limpo_em, 0))
                      AND mx.cpf <> :cpf2) AS nao_lidas
            FROM chat_conversas c
            INNER JOIN chat_participantes p ON p.conversa_id = c.id
            WHERE p.cpf = :cpf AND p.apagado_em IS NULL
            ORDER BY COALESCE((SELECT mx.id FROM chat_mensagens mx
                    WHERE mx.conversa_id = c.id AND mx.id > COALESCE(p.limpo_em, 0)
                    ORDER BY mx.id DESC LIMIT 1), -1) DESC";

        return DB::select($sql, ['cpf' => $cpf, 'cpf2' => $cpf], 'mysql');
    }

    public static function participantes(int $conversaId): array
    {
        return DB::select("SELECT p.cpf, f.nome
            FROM chat_participantes p
            LEFT JOIN func f ON f.cpf = p.cpf
            WHERE p.conversa_id = :conversa_id",
            ['conversa_id' => $conversaId], 'mysql');
    }

    public static function ehParticipante(int $conversaId, string $cpf): bool
    {
        return DB::first("SELECT id FROM chat_participantes WHERE conversa_id = :conversa_id AND cpf = :cpf AND apagado_em IS NULL",
            ['conversa_id' => $conversaId, 'cpf' => $cpf], 'mysql') !== null;
    }

    public static function mensagens(int $conversaId, int $apos = 0, int $limite = 100, ?string $cpf = null): array
    {
        $sql = "SELECT m.id, m.conversa_id, m.cpf AS remetente, m.texto, m.tipo, m.anexo, m.anexo_nome,
                    m.editado_em, m.apagado, m.criado_em,
                    f.nome AS remetente_nome
                FROM chat_mensagens m
                INNER JOIN chat_participantes p ON p.conversa_id = m.conversa_id
                LEFT JOIN func f ON f.cpf = m.cpf
                WHERE m.conversa_id = :conversa_id AND m.id > :apos";

        $params = ['conversa_id' => $conversaId, 'apos' => $apos];

        if ($cpf !== null) {
            $sql .= ' AND p.cpf = :cpf AND m.id > COALESCE(p.limpo_em, 0)';
            $params['cpf'] = $cpf;
        } else {
            $sql .= ' AND p.id = (SELECT MIN(id) FROM chat_participantes WHERE conversa_id = :conversa_id2)';
            $params['conversa_id2'] = $conversaId;
        }

        $sql .= " ORDER BY m.id ASC";

        if ($limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite;
        }

        $rows = DB::select($sql, $params, 'mysql');

        if (!$rows) {
            return [];
        }

        return self::enriquecer($rows, (int) $conversaId);
    }

    public static function enviar(int $conversaId, string $cpf, string $texto, string $tipo = 'texto', ?string $anexo = null, ?string $anexoNome = null): ?int
    {
        return DB::insert('chat_mensagens', [
            'conversa_id' => $conversaId,
            'cpf' => $cpf,
            'texto' => $texto,
            'tipo' => $tipo,
            'anexo' => $anexo,
            'anexo_nome' => $anexoNome,
        ], 'mysql');
    }

    public static function mensagem(int $id): ?array
    {
        $row = DB::first("SELECT m.id, m.conversa_id, m.cpf AS remetente, m.texto, m.tipo,
                    m.anexo, m.anexo_nome, m.editado_em, m.apagado, m.criado_em,
                    f.nome AS remetente_nome
                FROM chat_mensagens m
                LEFT JOIN func f ON f.cpf = m.cpf
                WHERE m.id = :id",
            ['id' => $id], 'mysql');

        if (!$row) {
            return null;
        }

        return self::enriquecer([$row], (int) $row['conversa_id'])[0];
    }

    public static function editar(int $mensagemId, string $cpf, string $texto): bool
    {
        $conn = DB::connect('mysql');
        $stmt = $conn->prepare("UPDATE chat_mensagens
            SET texto = :texto, editado_em = NOW()
            WHERE id = :id AND cpf = :cpf AND apagado = 0");
        $check = $conn->prepare("SELECT texto FROM chat_mensagens WHERE id = :id");
        $params = ['texto' => $texto, 'id' => $mensagemId, 'cpf' => $cpf];

        for ($i = 0; $i < 3; $i++) {
            $stmt->execute($params);

            $check->execute(['id' => $mensagemId]);
            $row = $check->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['texto'] === $texto) {
                return true;
            }
        }

        return false;
    }

    public static function excluir(int $mensagemId, string $cpf): bool
    {
        $conn = DB::connect('mysql');
        $stmt = $conn->prepare("UPDATE chat_mensagens SET apagado = 1
            WHERE id = :id AND cpf = :cpf AND apagado = 0");
        $check = $conn->prepare("SELECT COALESCE(apagado, 0) AS apagado FROM chat_mensagens WHERE id = :id");

        for ($i = 0; $i < 3; $i++) {
            $stmt->execute(['id' => $mensagemId, 'cpf' => $cpf]);

            $check->execute(['id' => $mensagemId]);
            if ((int) ($check->fetchColumn() ?: 0) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function temReacao(int $mensagemId, string $cpf, string $emoji): bool
    {
        return DB::first("SELECT id FROM chat_reacoes
            WHERE mensagem_id = :m AND cpf = :c AND emoji = :e",
            ['m' => $mensagemId, 'c' => $cpf, 'e' => $emoji], 'mysql') !== null;
    }

    public static function reacaoAdicionar(int $mensagemId, string $cpf, string $emoji): void
    {
        $conn = DB::connect('mysql');
        $stmt = $conn->prepare("INSERT INTO chat_reacoes (mensagem_id, cpf, emoji)
            VALUES (:m, :c, :e) ON DUPLICATE KEY UPDATE emoji = emoji");
        $stmt->execute(['m' => $mensagemId, 'c' => $cpf, 'e' => $emoji]);
    }

    public static function reacaoRemover(int $mensagemId, string $cpf, string $emoji): void
    {
        $conn = DB::connect('mysql');
        $stmt = $conn->prepare("DELETE FROM chat_reacoes
            WHERE mensagem_id = :m AND cpf = :c AND emoji = :e");
        $stmt->execute(['m' => $mensagemId, 'c' => $cpf, 'e' => $emoji]);
    }

    public static function reacoes(array $mensagemIds): array
    {
        if (!$mensagemIds) {
            return [];
        }

        $in = implode(',', array_fill(0, count($mensagemIds), '?'));

        return DB::select("SELECT mensagem_id, emoji, cpf FROM chat_reacoes
            WHERE mensagem_id IN ({$in}) ORDER BY mensagem_id, emoji",
            array_values($mensagemIds), 'mysql');
    }

    public static function ultimoLido(int $conversaId, string $cpf): int
    {
        $row = DB::first("SELECT COALESCE(ultimo_lido, 0) AS ultimo
            FROM chat_participantes WHERE conversa_id = :c AND cpf = :p",
            ['c' => $conversaId, 'p' => $cpf], 'mysql');

        return (int) ($row['ultimo'] ?? 0);
    }

    public static function novoEvento(int $conversaId, string $evento, array $dados): int
    {
        $conn = DB::connect('mysql');
        $stmt = $conn->prepare("INSERT INTO chat_eventos (conversa_id, evento, dados)
            VALUES (:c, :e, :d)");
        $stmt->execute([
            'c' => $conversaId,
            'e' => $evento,
            'd' => json_encode($dados, JSON_UNESCAPED_UNICODE),
        ]);

        return (int) $conn->lastInsertId();
    }

    public static function eventos(int $conversaId, int $apos): array
    {
        return DB::select("SELECT id, evento, dados, criado_em FROM chat_eventos
            WHERE conversa_id = :c AND id > :apos ORDER BY id ASC",
            ['c' => $conversaId, 'apos' => $apos], 'mysql');
    }

    public static function ultimoEventoId(int $conversaId): int
    {
        $row = DB::first("SELECT COALESCE(MAX(id), 0) AS ultimo
            FROM chat_eventos WHERE conversa_id = :c",
            ['c' => $conversaId], 'mysql');

        return (int) ($row['ultimo'] ?? 0);
    }

    private static function enriquecer(array $rows, int $conversaId): array
    {
        $ids = array_values(array_unique(array_map('intval', array_column($rows, 'id'))));
        $lidos = self::ultimosLidos($conversaId);
        $reacoes = $ids ? self::reacoes($ids) : [];

        foreach ($rows as &$m) {
            $mid = (int) $m['id'];
            $m['id'] = $mid;
            $m['tipo'] = (string) ($m['tipo'] ?? 'texto');
            $m['anexo'] = $m['anexo'] ?? null;
            $m['anexo_nome'] = $m['anexo_nome'] ?? null;
            $m['editado_em'] = $m['editado_em'] ?? null;
            $m['apagado'] = (int) ($m['apagado'] ?? 0);

            if ($m['apagado']) {
                $m['texto'] = '';
            }

            $m['lida'] = self::mensagemLida($mid, (string) $m['remetente'], $lidos);
            $m['reacoes'] = self::reacoesDaMensagem($mid, $reacoes);
        }
        unset($m);

        return $rows;
    }

    private static function ultimosLidos(int $conversaId): array
    {
        $rows = DB::select("SELECT cpf, COALESCE(ultimo_lido, 0) AS ultimo_lido
            FROM chat_participantes WHERE conversa_id = :conversa_id",
            ['conversa_id' => $conversaId], 'mysql');

        $map = [];
        foreach ($rows as $r) {
            $map[$r['cpf']] = (int) $r['ultimo_lido'];
        }

        return $map;
    }

    private static function mensagemLida(int $id, string $remetente, array $lidos): bool
    {
        foreach ($lidos as $cpf => $ultimo) {
            if ((string) $cpf === (string) $remetente) {
                continue;
            }
            if ($ultimo < $id) {
                return false;
            }
        }

        return true;
    }

    private static function reacoesDaMensagem(int $id, array $reacoes): array
    {
        $map = [];

        foreach ($reacoes as $r) {
            if ((int) $r['mensagem_id'] !== $id) {
                continue;
            }

            $emoji = (string) $r['emoji'];

            if (!isset($map[$emoji])) {
                $map[$emoji] = ['emoji' => $emoji, 'total' => 0, 'cpfs' => []];
            }

            $map[$emoji]['total']++;
            $map[$emoji]['cpfs'][] = $r['cpf'];
        }

        return array_values($map);
    }

    public static function criarConversa(string $tipo, ?string $titulo, string $criadoPor): ?int
    {
        return DB::insert('chat_conversas', [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'criado_por' => $criadoPor,
        ], 'mysql');
    }

    public static function adicionarParticipante(int $conversaId, string $cpf): void
    {
        $conn = DB::connect('mysql');
        $stmt = $conn->prepare("INSERT INTO chat_participantes (conversa_id, cpf) VALUES (:conversa_id, :cpf)
            ON DUPLICATE KEY UPDATE conversa_id = conversa_id");
        $stmt->execute(['conversa_id' => $conversaId, 'cpf' => $cpf]);
    }

    public static function conversaDireta(string $cpfA, string $cpfB): ?int
    {
        $row = DB::first("SELECT c.id
            FROM chat_conversas c
            INNER JOIN chat_participantes pa ON pa.conversa_id = c.id AND pa.cpf = :a
            INNER JOIN chat_participantes pb ON pb.conversa_id = c.id AND pb.cpf = :b
            WHERE c.tipo = 'direta'
            LIMIT 1",
            ['a' => $cpfA, 'b' => $cpfB], 'mysql');

        return $row ? (int) $row['id'] : null;
    }

    public static function marcarLido(int $conversaId, string $cpf, int $mensagemId): void
    {
        $conn = DB::connect('mysql');
        $update = $conn->prepare("UPDATE chat_participantes
            SET ultimo_lido = GREATEST(COALESCE(ultimo_lido, 0), CAST(:mensagem_id AS UNSIGNED))
            WHERE conversa_id = :conversa_id AND cpf = :cpf");
        $check = $conn->prepare("SELECT COALESCE(ultimo_lido, 0) AS ultimo
            FROM chat_participantes WHERE conversa_id = :conversa_id AND cpf = :cpf");

        for ($i = 0; $i < 3; $i++) {
            $update->execute(['mensagem_id' => $mensagemId, 'conversa_id' => $conversaId, 'cpf' => $cpf]);

            $check->execute(['conversa_id' => $conversaId, 'cpf' => $cpf]);
            $ultimo = (int) ($check->fetch(PDO::FETCH_ASSOC)['ultimo'] ?? 0);

            if ($ultimo >= $mensagemId) {
                return;
            }
        }
    }

    public static function limpar(int $conversaId, string $cpf): void
    {
        $conn = DB::connect('mysql');
        $update = $conn->prepare("UPDATE chat_participantes
            SET limpo_em = (SELECT COALESCE(MAX(id), 0) FROM chat_mensagens WHERE conversa_id = :c),
                ultimo_lido = GREATEST(COALESCE(ultimo_lido, 0), COALESCE(limpo_em, 0))
            WHERE conversa_id = :conversa_id AND cpf = :cpf");
        $check = $conn->prepare("SELECT COALESCE(limpo_em, 0) AS limpo
            FROM chat_participantes WHERE conversa_id = :conversa_id AND cpf = :cpf");

        for ($i = 0; $i < 3; $i++) {
            $update->execute(['c' => $conversaId, 'conversa_id' => $conversaId, 'cpf' => $cpf]);

            $check->execute(['conversa_id' => $conversaId, 'cpf' => $cpf]);
            if ((int) ($check->fetch(PDO::FETCH_ASSOC)['limpo'] ?? 0) > 0) {
                return;
            }
        }
    }

    public static function removeParticipante(int $conversaId, string $cpf): void
    {
        $conn = DB::connect('mysql');
        $stmt = $conn->prepare("UPDATE chat_participantes
            SET apagado_em = NOW()
            WHERE conversa_id = :conversa_id AND cpf = :cpf AND apagado_em IS NULL");
        $stmt->execute(['conversa_id' => $conversaId, 'cpf' => $cpf]);
    }

    public static function reativarParticipante(int $conversaId, string $cpf): void
    {
        $conn = DB::connect('mysql');
        $stmt = $conn->prepare("UPDATE chat_participantes
            SET apagado_em = NULL
            WHERE conversa_id = :conversa_id AND cpf = :cpf AND apagado_em IS NOT NULL");
        $stmt->execute(['conversa_id' => $conversaId, 'cpf' => $cpf]);
    }

    public static function reativarParticipantes(int $conversaId): void
    {
        $conn = DB::connect('mysql');
        $stmt = $conn->prepare("UPDATE chat_participantes
            SET apagado_em = NULL
            WHERE conversa_id = :conversa_id AND apagado_em IS NOT NULL");
        $stmt->execute(['conversa_id' => $conversaId]);
    }

    public static function tipoConversa(int $conversaId): ?string
    {
        $row = DB::first("SELECT tipo FROM chat_conversas WHERE id = :id",
            ['id' => $conversaId], 'mysql');

        return $row ? (string) $row['tipo'] : null;
    }

    public static function contaParticipantes(int $conversaId): int
    {
        $row = DB::first("SELECT COUNT(*) AS total
            FROM chat_participantes WHERE conversa_id = :conversa_id AND apagado_em IS NULL",
            ['conversa_id' => $conversaId], 'mysql');

        return (int) ($row['total'] ?? 0);
    }

    public static function excluirConversa(int $conversaId): void
    {
        $conn = DB::connect('mysql');

        $stmt = $conn->prepare("SELECT id FROM chat_mensagens WHERE conversa_id = :c");
        $stmt->execute(['c' => $conversaId]);
        $mids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($mids) {
            $in = implode(',', $mids);
            $conn->exec("DELETE FROM chat_reacoes WHERE mensagem_id IN ({$in})");
        }

        $conn->exec("DELETE FROM chat_mensagens WHERE conversa_id = {$conversaId}");
        $conn->exec("DELETE FROM chat_eventos WHERE conversa_id = {$conversaId}");
        $conn->exec("DELETE FROM chat_participantes WHERE conversa_id = {$conversaId}");
        $conn->exec("DELETE FROM chat_conversas WHERE id = {$conversaId}");
    }

    public static function ultimaMensagemId(int $conversaId): int
    {
        $row = DB::first("SELECT COALESCE(MAX(id), 0) AS ultimo
            FROM chat_mensagens WHERE conversa_id = :conversa_id",
            ['conversa_id' => $conversaId], 'mysql');

        return (int) ($row['ultimo'] ?? 0);
    }

    public static function totalNaoLidas(string $cpf): int
    {
        $row = DB::first("SELECT COUNT(*) AS total
            FROM chat_mensagens m
            INNER JOIN chat_participantes p ON p.conversa_id = m.conversa_id AND p.cpf = :cpf
            WHERE m.id > GREATEST(COALESCE(p.ultimo_lido, 0), COALESCE(p.limpo_em, 0)) AND m.cpf <> :cpf2 AND p.apagado_em IS NULL",
            ['cpf' => $cpf, 'cpf2' => $cpf], 'mysql');

        return (int) ($row['total'] ?? 0);
    }

    public static function ultimaNaoLida(string $cpf): ?array
    {
        $row = DB::first("
            SELECT m.conversa_id, m.cpf AS remetente, m.texto, m.tipo, m.anexo, m.criado_em,
                f.nome AS remetente_nome
            FROM chat_mensagens m
            INNER JOIN chat_participantes p ON p.conversa_id = m.conversa_id AND p.cpf = :cpf
            LEFT JOIN func f ON f.cpf = m.cpf
            WHERE m.id > GREATEST(COALESCE(p.ultimo_lido, 0), COALESCE(p.limpo_em, 0)) AND m.cpf <> :cpf2 AND p.apagado_em IS NULL
            ORDER BY m.id DESC LIMIT 1",
            ['cpf' => $cpf, 'cpf2' => $cpf], 'mysql');

        return $row ?: null;
    }

    public static function contatos(string $termo, string $cpfExcluir, int $limite = 20): array
    {
        $sql = "SELECT f.cpf, f.nome, f.codfilial, COALESCE(fi.nome, '') AS filial, COALESCE(s.setor, '') AS setor
            FROM func f
            LEFT JOIN filial fi ON fi.codgfilial = f.codfilial
            LEFT JOIN usuarios u ON u.cpf = f.cpf
            LEFT JOIN setor s ON s.id = u.id_setor
            WHERE f.codsituacao = 'A' AND f.cpf <> :cpf AND f.nome LIKE :termo
            ORDER BY f.nome ASC";

        if ($limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite;
        }

        return DB::select($sql, ['cpf' => $cpfExcluir, 'termo' => '%' . str_replace(['%', '_'], ['\%', '\_'], $termo) . '%'], 'mysql');
    }
}
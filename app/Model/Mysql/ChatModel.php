<?php

namespace App\Model\Mysql;

use App\Core\DB;
use PDO;

class ChatModel
{
    public static function conversas(string $cpf): array
    {
        $rows = DB::select(
            "SELECT c.id, c.tipo, c.titulo, c.criado_por, c.criado_em,
                    c.ultima_mensagem_id, COALESCE(p.limpo_em, 0) AS limpo_em
             FROM chat_conversas c
             INNER JOIN chat_participantes p ON p.conversa_id = c.id
             WHERE p.cpf = :cpf AND p.apagado_em IS NULL
             ORDER BY c.ultima_mensagem_id IS NULL, c.ultima_mensagem_id DESC",
            ['cpf' => $cpf],
            'mysql'
        );

        if (!$rows) {
            return [];
        }

        $idsMensagem = [];
        foreach ($rows as $r) {
            $ultima = (int) ($r['ultima_mensagem_id'] ?? 0);

            if ($ultima > (int) $r['limpo_em']) {
                $idsMensagem[$ultima] = $ultima;
            }
        }

        $mensagens = [];
        if ($idsMensagem) {
            $in = implode(',', array_fill(0, count($idsMensagem), '?'));
            $ultimas = DB::select(
                "SELECT id, texto, apagado, criado_em, tipo, anexo_nome FROM chat_mensagens WHERE id IN ({$in})",
                array_values($idsMensagem),
                'mysql'
            );

            foreach ($ultimas as $m) {
                $mensagens[(int) $m['id']] = $m;
            }
        }

        $naoLidas = [];
        $contagens = DB::select(
            "SELECT m.conversa_id, COUNT(*) AS total
             FROM chat_mensagens m
             INNER JOIN chat_participantes p ON p.conversa_id = m.conversa_id AND p.cpf = :cpf
             WHERE m.id > GREATEST(COALESCE(p.ultimo_lido, 0), COALESCE(p.limpo_em, 0))
               AND m.cpf <> :cpf2
               AND p.apagado_em IS NULL
             GROUP BY m.conversa_id",
            ['cpf' => $cpf, 'cpf2' => $cpf],
            'mysql'
        );

        foreach ($contagens as $n) {
            $naoLidas[(int) $n['conversa_id']] = (int) $n['total'];
        }

        foreach ($rows as &$r) {
            $ultima = (int) ($r['ultima_mensagem_id'] ?? 0);
            $msg = $ultima > (int) $r['limpo_em'] ? ($mensagens[$ultima] ?? null) : null;

            $r['ultima_id'] = $msg ? (int) $msg['id'] : null;
            $r['ultima_em'] = $msg ? $msg['criado_em'] : null;
            $r['ultima_msg'] = $msg ? (string) $msg['texto'] : '';
            $r['ultima_tipo'] = $msg ? (string) $msg['tipo'] : 'texto';
            $r['ultima_anexo_nome'] = $msg ? (string) ($msg['anexo_nome'] ?? '') : '';
            $r['ultima_apagada'] = $msg ? (int) $msg['apagado'] : 0;
            $r['nao_lidas'] = $naoLidas[(int) $r['id']] ?? 0;
        }
        unset($r);

        return $rows;
    }

    public static function idsConversas(string $cpf): array
    {
        $rows = DB::select(
            "SELECT c.id
             FROM chat_conversas c
             INNER JOIN chat_participantes p ON p.conversa_id = c.id
             WHERE p.cpf = :cpf AND p.apagado_em IS NULL",
            ['cpf' => $cpf],
            'mysql'
        );

        return array_map(static fn($r) => (int) $r['id'], $rows);
    }

    public static function participantes(int $conversaId): array
    {
        return DB::select("SELECT p.cpf, f.nome
            FROM chat_participantes p
            LEFT JOIN func f ON f.cpf = p.cpf
            WHERE p.conversa_id = :conversa_id",
            ['conversa_id' => $conversaId], 'mysql');
    }

    /**
     * CPFs dos parceiros de conversa do usuário, agrupados por conversa.
     * Usado pelo chat para resolver quem é o "outro" participante na
     * verificação de presença.
     *
     * @return array<int, list<string>> mapa [conversa_id => [cpf, ...]]
     */
    public static function parceirosPorConversa(string $cpf): array
    {
        $rows = DB::select(
            "SELECT p.conversa_id, p.cpf
             FROM chat_participantes meu
             INNER JOIN chat_participantes p
                 ON p.conversa_id = meu.conversa_id
                 AND p.cpf <> :cpf
                 AND p.apagado_em IS NULL
             WHERE meu.cpf = :cpf2
             AND meu.apagado_em IS NULL",
            ['cpf' => $cpf, 'cpf2' => $cpf],
            'mysql'
        );

        $porConversa = [];

        foreach ($rows as $row) {
            $porConversa[(int) $row['conversa_id']][] = (string) $row['cpf'];
        }

        return $porConversa;
    }

    public static function ehParticipante(int $conversaId, string $cpf): bool
    {
        return DB::first("SELECT id FROM chat_participantes WHERE conversa_id = :conversa_id AND cpf = :cpf AND apagado_em IS NULL",
            ['conversa_id' => $conversaId, 'cpf' => $cpf], 'mysql') !== null;
    }

    /**
     * Página de mensagens da conversa.
     *
     * - sem `apos`/`antes`: devolve as últimas `$limite` mensagens (ordem cronológica)
     * - `apos > 0`: mensagens mais novas que `apos`
     * - `antes > 0`: página anterior, mais antigas que `antes`
     *
     * @return array{mensagens: list<array>, tem_mais: bool}
     */
    public static function mensagens(int $conversaId, int $apos = 0, int $limite = 100, ?string $cpf = null, int $antes = 0): array
    {
        $limite = max(1, $limite);

        $sql = "SELECT m.id, m.conversa_id, m.cpf AS remetente, m.texto, m.tipo, m.anexo, m.anexo_nome,
                    m.editado_em, m.apagado, m.criado_em,
                    f.nome AS remetente_nome
                FROM chat_mensagens m
                INNER JOIN chat_participantes p ON p.conversa_id = m.conversa_id
                LEFT JOIN func f ON f.cpf = m.cpf
                WHERE m.conversa_id = :conversa_id";

        $params = ['conversa_id' => $conversaId];

        if ($antes > 0) {
            $sql .= ' AND m.id < :antes';
            $params['antes'] = $antes;
            $ordem = 'DESC';
        } elseif ($apos > 0) {
            $sql .= ' AND m.id > :apos';
            $params['apos'] = $apos;
            $ordem = 'ASC';
        } else {
            $ordem = 'DESC';
        }

        if ($cpf !== null) {
            $sql .= ' AND p.cpf = :cpf AND m.id > COALESCE(p.limpo_em, 0)';
            $params['cpf'] = $cpf;
        } else {
            $sql .= ' AND p.id = (SELECT MIN(id) FROM chat_participantes WHERE conversa_id = :conversa_id2)';
            $params['conversa_id2'] = $conversaId;
        }

        $sql .= " ORDER BY m.id {$ordem} LIMIT " . ($limite + 1);

        $rows = DB::select($sql, $params, 'mysql');
        $temMais = count($rows) > $limite;

        if ($temMais) {
            array_pop($rows);
        }

        if ($ordem === 'DESC') {
            $rows = array_reverse($rows);
        }

        return [
            'mensagens' => $rows ? self::enriquecer($rows, (int) $conversaId) : [],
            'tem_mais' => $temMais,
        ];
    }

    public static function enviar(int $conversaId, string $cpf, string $texto, string $tipo = 'texto', ?string $anexo = null, ?string $anexoNome = null): ?int
    {
        $id = DB::insert('chat_mensagens', [
            'conversa_id' => $conversaId,
            'cpf' => $cpf,
            'texto' => $texto,
            'tipo' => $tipo,
            'anexo' => $anexo,
            'anexo_nome' => $anexoNome,
        ], 'mysql');

        if ($id !== null) {
            $conn = DB::connect('mysql');
            $stmt = $conn->prepare(
                "UPDATE chat_conversas
                 SET ultima_mensagem_id = GREATEST(COALESCE(ultima_mensagem_id, 0), :mensagem_id)
                 WHERE id = :conversa_id"
            );
            $stmt->execute(['mensagem_id' => $id, 'conversa_id' => $conversaId]);
        }

        return $id;
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
            WHERE conversa_id = :c AND id > :apos ORDER BY id ASC LIMIT 200",
            ['c' => $conversaId, 'apos' => $apos], 'mysql');
    }

    public static function ultimoEventoId(int $conversaId): int
    {
        $row = DB::first("SELECT COALESCE(MAX(id), 0) AS ultimo
            FROM chat_eventos WHERE conversa_id = :c",
            ['c' => $conversaId], 'mysql');

        return (int) ($row['ultimo'] ?? 0);
    }

    public static function digitandoAtualizar(int $conversaId, string $cpf): void
    {
        $conn = DB::connect('mysql');
        $stmt = $conn->prepare(
            "INSERT INTO chat_digitando (conversa_id, cpf, atualizado_em)
             VALUES (:conversa_id, :cpf, NOW())
             ON DUPLICATE KEY UPDATE atualizado_em = NOW()"
        );
        $stmt->execute(['conversa_id' => $conversaId, 'cpf' => $cpf]);
    }

    /**
     * Participantes digitando nos últimos 5 segundos (nunca inclui o próprio usuário).
     *
     * @return list<array{cpf: string, nome: string}>
     */
    public static function digitandoAtivos(int $conversaId, string $cpf): array
    {
        $rows = DB::select(
            "SELECT d.cpf, COALESCE(NULLIF(TRIM(f.nomesocial), ''), f.nome, '') AS nome
             FROM chat_digitando d
             LEFT JOIN func f ON f.cpf = d.cpf
             WHERE d.conversa_id = :conversa_id
               AND d.cpf <> :cpf
               AND d.atualizado_em > (NOW() - INTERVAL 5 SECOND)",
            ['conversa_id' => $conversaId, 'cpf' => $cpf],
            'mysql'
        );

        foreach ($rows as &$r) {
            $r['cpf'] = (string) $r['cpf'];
            $r['nome'] = (string) $r['nome'];
        }
        unset($r);

        return $rows;
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
                f.nome AS remetente_nome, c.titulo AS conversa_titulo, c.tipo AS conversa_tipo
            FROM chat_mensagens m
            INNER JOIN chat_participantes p ON p.conversa_id = m.conversa_id AND p.cpf = :cpf
            INNER JOIN chat_conversas c ON c.id = m.conversa_id
            LEFT JOIN func f ON f.cpf = m.cpf
            WHERE m.id > GREATEST(COALESCE(p.ultimo_lido, 0), COALESCE(p.limpo_em, 0)) AND m.cpf <> :cpf2 AND p.apagado_em IS NULL
            ORDER BY m.id DESC LIMIT 1",
            ['cpf' => $cpf, 'cpf2' => $cpf], 'mysql');

        return $row ?: null;
    }

    public static function contatos(string $termo, string $cpfExcluir, int $limite = 20): array
    {
        $sql = "SELECT f.cpf, COALESCE(NULLIF(TRIM(f.nomesocial), ''), f.nome) AS nome, f.codfilial, COALESCE(fi.nome, '') AS filial, COALESCE(s.setor, '') AS setor, COALESCE(u.foto, '') AS foto
            FROM func f
            LEFT JOIN filial fi ON fi.codgfilial = f.codfilial
            LEFT JOIN usuarios u ON u.cpf = f.cpf
            LEFT JOIN setor s ON s.id = u.id_setor
            WHERE f.codsituacao = 'A' AND f.cpf <> :cpf AND (f.nome LIKE :termo OR f.nomesocial LIKE :termo)
            ORDER BY COALESCE(NULLIF(TRIM(f.nomesocial), ''), f.nome) ASC";

        if ($limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite;
        }

        return DB::select($sql, ['cpf' => $cpfExcluir, 'termo' => '%' . str_replace(['%', '_'], ['\%', '\_'], $termo) . '%'], 'mysql');
    }
}
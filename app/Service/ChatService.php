<?php

namespace App\Service;

use App\Core\DB;
use App\Model\Mysql\ChatModel;
use App\Service\AuthService;

class ChatService
{
    private const MAX_ANEXO = 5242880;

    private const EXTENSOES = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'zip', 'rar', 'mp3', 'wav', 'mp4',
    ];

    public const EMOJIS_REACAO = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

    public static function conversas(string $cpf): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null) {
            return [];
        }

        $rows = ChatModel::conversas($cpf);

        $ids = array_column($rows, 'id');
        $participantes = [];

        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $participantes = DB::select(
                "SELECT p.conversa_id, p.cpf, p.apagado_em, f.nome, COALESCE(fi.nome, '') AS filial, COALESCE(s.setor, '') AS setor
                 FROM chat_participantes p
                 LEFT JOIN func f ON f.cpf = p.cpf
                 LEFT JOIN filial fi ON fi.codgfilial = f.codfilial
                 LEFT JOIN usuarios u ON u.cpf = p.cpf
                 LEFT JOIN setor s ON s.id = u.id_setor
                 WHERE p.conversa_id IN ({$in})
                 ORDER BY p.conversa_id",
                $ids, 'mysql'
            );
        }

        $porConversa = [];
        foreach ($participantes as $p) {
            $porConversa[(int) $p['conversa_id']][] = [
                'cpf' => $p['cpf'],
                'nome' => $p['nome'] ?? 'Sem nome',
                'filial' => $p['filial'] ?? '',
                'setor' => $p['setor'] ?? '',
                'apagado' => !empty($p['apagado_em']),
            ];
        }

        $lista = [];

        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $pessoas = $porConversa[$id] ?? [];
            [$titulo, $sub] = self::montaIdentificacao($r, $pessoas, $cpf);

            $lista[] = [
                'id' => $id,
                'tipo' => $r['tipo'],
                'titulo' => $titulo,
                'subtitulo' => $sub,
                'ultima_msg' => (string) ($r['ultima_msg'] ?? ''),
                'ultima_em' => $r['ultima_em'] ?? null,
                'nao_lidas' => (int) ($r['nao_lidas'] ?? 0),
                'participantes' => $pessoas,
            ];
        }

        return $lista;
    }

    public static function mensagens(int $conversaId, string $cpf, int $apos = 0): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $conversaId <= 0 || !self::participa($conversaId, $cpf)) {
            throw new \RuntimeException('Conversa não encontrada.', 404);
        }

        return ChatModel::mensagens((int) $conversaId, max(0, $apos), 100, $cpf);
    }

    public static function enviar(int $conversaId, string $cpf, string $texto, string $tipo = 'texto', ?string $anexo = null, ?string $anexoNome = null): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $conversaId <= 0) {
            throw new \RuntimeException('Conversa não encontrada.', 404);
        }

        if (!self::participa($conversaId, $cpf)) {
            throw new \RuntimeException('Você não participa desta conversa.', 403);
        }

        $tipo = in_array($tipo, ['texto', 'imagem', 'arquivo'], true) ? $tipo : 'texto';
        $texto = trim($texto);

        if ($tipo === 'texto' && $texto === '') {
            throw new \RuntimeException('Digite a mensagem para enviar.', 400);
        }

        if ($tipo !== 'texto' && ($anexo === null || $anexo === '')) {
            throw new \RuntimeException('Nenhum anexo foi fornecido.', 400);
        }

        if (mb_strlen($texto) > 2000) {
            $texto = mb_substr($texto, 0, 2000);
        }

        $id = ChatModel::enviar((int) $conversaId, $cpf, $texto, $tipo, $anexo !== null ? (string) $anexo : null, $anexoNome);

        if ($id === null) {
            throw new \RuntimeException('Não foi possível gravar a mensagem.', 500);
        }

        $msg = ChatModel::mensagem($id);

        ChatModel::novoEvento((int) $conversaId, 'novidade', [
            'conversa_id' => (int) $conversaId,
            'apos' => $id,
            'mensagens' => [$msg],
        ]);

        if (ChatModel::tipoConversa((int) $conversaId) === 'direta') {
            ChatModel::reativarParticipantes((int) $conversaId);
        }

        return $msg;
    }

    public static function enviarAnexo(int $conversaId, string $cpf, array $file, string $texto = ''): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $conversaId <= 0 || !self::participa($conversaId, $cpf)) {
            throw new \RuntimeException('Conversa não encontrada.', 404);
        }

        if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Selecione um arquivo para enviar.', 400);
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_ANEXO) {
            throw new \RuntimeException('O arquivo excede o limite de 5 MB.', 400);
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, self::EXTENSOES, true)) {
            throw new \RuntimeException('Tipo de arquivo não permitido.', 400);
        }

        $mime = mime_content_type((string) $file['tmp_name']);
        $safe = substr(sha1((string) $file['name'] . microtime() . random_bytes(16)), 7, 22);
        $relativo = 'files/chat/' . $safe . '.' . $ext;
        $destino = basePath('public/' . $relativo);

        $dir = dirname($destino);

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException('Falha ao criar a pasta de arquivos.', 500);
        }

        $tmp = (string) $file['tmp_name'];
        $gravado = is_uploaded_file($tmp) ? move_uploaded_file($tmp, $destino) : @rename($tmp, $destino);

        if (!$gravado) {
            throw new \RuntimeException('Falha ao salvar o arquivo.', 500);
        }

        $tipo = $mime !== false && str_starts_with($mime, 'image/') ? 'imagem' : 'arquivo';

        try {
            return self::enviar($conversaId, $cpf, trim($texto), $tipo, '/' . $relativo, $file['name']);
        } catch (\Throwable $e) {
            @unlink($destino);
            throw $e;
        }
    }

    public static function editar(int $conversaId, int $mensagemId, string $cpf, string $texto): array
    {
        $cpf = self::cpf($cpf);
        self::validaPropria($conversaId, $mensagemId, $cpf);

        $texto = trim($texto);

        if ($texto === '') {
            throw new \RuntimeException('A mensagem não pode ficar vazia.', 400);
        }

        if (mb_strlen($texto) > 2000) {
            $texto = mb_substr($texto, 0, 2000);
        }

        if (!ChatModel::editar($mensagemId, $cpf, $texto)) {
            throw new \RuntimeException('Não foi possível editar a mensagem.', 500);
        }

        $nova = ChatModel::mensagem($mensagemId);

        ChatModel::novoEvento((int) $conversaId, 'edicao', [
            'conversa_id' => (int) $conversaId,
            'mensagem_id' => $mensagemId,
            'texto' => $nova['texto'],
            'editado_em' => $nova['editado_em'],
        ]);

        return $nova;
    }

    public static function excluir(int $conversaId, int $mensagemId, string $cpf): array
    {
        $cpf = self::cpf($cpf);
        self::validaPropria($conversaId, $mensagemId, $cpf);

        if (!ChatModel::excluir($mensagemId, $cpf)) {
            throw new \RuntimeException('Não foi possível apagar a mensagem.', 500);
        }

        $nova = ChatModel::mensagem($mensagemId);

        ChatModel::novoEvento((int) $conversaId, 'exclusao', [
            'conversa_id' => (int) $conversaId,
            'mensagem_id' => $mensagemId,
        ]);

        return $nova;
    }

    public static function reagir(int $conversaId, int $mensagemId, string $cpf, string $emoji): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $conversaId <= 0 || !self::participa($conversaId, $cpf)) {
            throw new \RuntimeException('Conversa não encontrada.', 404);
        }

        if (!in_array($emoji, self::EMOJIS_REACAO, true)) {
            throw new \RuntimeException('Reação inválida.', 400);
        }

        $msg = ChatModel::mensagem($mensagemId);

        if (!$msg || (int) $msg['conversa_id'] !== $conversaId) {
            throw new \RuntimeException('Mensagem não encontrada.', 404);
        }

        if (ChatModel::temReacao($mensagemId, $cpf, $emoji)) {
            ChatModel::reacaoRemover($mensagemId, $cpf, $emoji);
            $ativo = false;
        } else {
            ChatModel::reacaoAdicionar($mensagemId, $cpf, $emoji);
            $ativo = true;
        }

        ChatModel::novoEvento((int) $conversaId, 'reacao', [
            'conversa_id' => (int) $conversaId,
            'mensagem_id' => $mensagemId,
            'cpf' => $cpf,
            'emoji' => $emoji,
            'ativo' => $ativo,
        ]);

        return [
            'mensagem_id' => $mensagemId,
            'emoji' => $emoji,
            'ativo' => $ativo,
        ];
    }

    public static function digitando(int $conversaId, string $cpf): void
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $conversaId <= 0 || !self::participa($conversaId, $cpf)) {
            return;
        }

        $row = DB::first("SELECT id FROM chat_eventos
            WHERE conversa_id = :c AND evento = 'digitando'
            ORDER BY id DESC LIMIT 1",
            ['c' => $conversaId], 'mysql');

        if ($row) {
            $dt = DB::first("SELECT criado_em FROM chat_eventos WHERE id = :id", ['id' => $row['id']], 'mysql');

            if ($dt && (time() - strtotime((string) $dt['criado_em'])) < 1) {
                return;
            }
        }

        $nome = null;
        $u = DB::first("SELECT f.nome FROM func f WHERE f.cpf = :cpf", ['cpf' => $cpf], 'mysql');

        if ($u) {
            $nome = $u['nome'];
        }

        ChatModel::novoEvento((int) $conversaId, 'digitando', [
            'conversa_id' => (int) $conversaId,
            'cpf' => $cpf,
            'nome' => $nome,
        ]);
    }

    public static function novaConversa(string $cpfLogado, string $cpfContato): int
    {
        $cpfLogado = self::cpf($cpfLogado);

        if ($cpfLogado === null) {
            throw new \RuntimeException('CPF de usuário inválido.', 400);
        }

        $cpfContato = self::cpf($cpfContato);

        if ($cpfContato === null) {
            throw new \RuntimeException('Contato inválido.', 400);
        }

        if ($cpfContato === $cpfLogado) {
            throw new \RuntimeException('Você não pode conversar consigo mesmo.', 400);
        }

        $existente = ChatModel::conversaDireta($cpfLogado, $cpfContato);

        if ($existente !== null) {
            ChatModel::reativarParticipante($existente, $cpfLogado);
            return $existente;
        }

        $conn = DB::connect('mysql');
        $conn->beginTransaction();

        try {
            $id = ChatModel::criarConversa('direta', null, $cpfLogado);

            if ($id === null) {
                throw new \RuntimeException('Não foi possível criar a conversa.', 500);
            }

            ChatModel::adicionarParticipante($id, $cpfLogado);
            ChatModel::adicionarParticipante($id, $cpfContato);

            $conn->commit();

            return $id;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public static function limparConversa(int $conversaId, string $cpf): void
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $conversaId <= 0 || !self::participa($conversaId, $cpf)) {
            throw new \RuntimeException('Conversa não encontrada.', 404);
        }

        ChatModel::limpar((int) $conversaId, $cpf);
    }

    public static function apagarConversa(int $conversaId, string $cpf): void
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $conversaId <= 0 || !self::participa($conversaId, $cpf)) {
            throw new \RuntimeException('Conversa não encontrada.', 404);
        }

        ChatModel::removeParticipante((int) $conversaId, $cpf);

        if (ChatModel::contaParticipantes((int) $conversaId) === 0) {
            ChatModel::excluirConversa((int) $conversaId);
        }
    }

    public static function streamCursor(int $conversaId): int
    {
        return ChatModel::ultimoEventoId((int) $conversaId);
    }

    public static function eventos(int $conversaId, int $apos): array
    {
        $rows = ChatModel::eventos((int) $conversaId, max(0, $apos));

        foreach ($rows as &$r) {
            $r['dados'] = json_decode((string) ($r['dados'] ?? '{}'), true) ?: [];
        }
        unset($r);

        return $rows;
    }

    public static function marcarLido(int $conversaId, string $cpf, int $mensagemId): void
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $conversaId <= 0 || $mensagemId <= 0 || !self::participa($conversaId, $cpf)) {
            return;
        }

        $antes = ChatModel::ultimoLido((int) $conversaId, $cpf);

        ChatModel::marcarLido((int) $conversaId, $cpf, $mensagemId);

        $depois = ChatModel::ultimoLido((int) $conversaId, $cpf);
        $maxId = ChatModel::ultimaMensagemId((int) $conversaId);

        if ($depois > $antes && $depois >= $maxId) {
            ChatModel::novoEvento((int) $conversaId, 'leitura', [
                'conversa_id' => (int) $conversaId,
                'cpf' => $cpf,
                'apos' => $depois,
            ]);
        }
    }

    public static function totalNaoLidas(string $cpf): int
    {
        $cpf = self::cpf($cpf);

        return $cpf ? ChatModel::totalNaoLidas($cpf) : 0;
    }

    public static function notificar(string $cpf): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null) {
            return ['total' => 0, 'ultima' => null];
        }

        $total = ChatModel::totalNaoLidas($cpf);

        if ($total === 0) {
            return ['total' => 0, 'ultima' => null];
        }

        $ultima = ChatModel::ultimaNaoLida($cpf);

        if ($ultima === null) {
            return ['total' => $total, 'ultima' => null];
        }

        $titulo = 'Nova mensagem';

        if ((int) $ultima['conversa_id'] > 0) {
            foreach (self::conversas($cpf) as $c) {
                if ((int) $c['id'] === (int) $ultima['conversa_id']) {
                    $titulo = $c['titulo'] ?: $titulo;
                    break;
                }
            }
        }

        return [
            'total' => $total,
            'ultima' => [
                'conversa_id' => (int) $ultima['conversa_id'],
                'titulo' => $titulo,
                'remetente' => $ultima['remetente_nome'] ?? 'Funcionário',
                'texto' => self::resumoTexto((string) ($ultima['texto'] ?? ''), (string) ($ultima['tipo'] ?? 'texto')),
                'tipo' => $ultima['tipo'] ?? 'texto',
                'criado_em' => $ultima['criado_em'] ?? null,
            ],
        ];
    }

    private static function resumoTexto(string $texto, string $tipo): string
    {
        if ($tipo === 'imagem' || $tipo === 'arquivo') {
            return $tipo === 'imagem' ? '[Imagem]' : '[Arquivo]';
        }

        $texto = trim($texto);
        $texto = preg_replace('/\s+/u', ' ', $texto);
        $texto = (string) $texto;

        return mb_strlen($texto) > 120 ? mb_substr($texto, 0, 120) . '…' : $texto;
    }

    public static function contatos(string $termo, string $cpf): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null) {
            return [];
        }

        $termo = trim($termo);

        if (mb_strlen($termo) < 2) {
            return [];
        }

        return ChatModel::contatos(mb_substr($termo, 0, 60), $cpf);
    }

    private static function participa(int $conversaId, string $cpf): bool
    {
        return ChatModel::ehParticipante($conversaId, $cpf);
    }

    public static function participaPub(int $conversaId, string $cpf): bool
    {
        return self::participa($conversaId, $cpf);
    }

    private static function validaPropria(int $conversaId, int $mensagemId, string $cpf): array
    {
        if ($cpf === null || $conversaId <= 0 || $mensagemId <= 0 || !self::participa($conversaId, $cpf)) {
            throw new \RuntimeException('Conversa não encontrada.', 404);
        }

        $msg = ChatModel::mensagem($mensagemId);

        if (!$msg || (int) $msg['conversa_id'] !== $conversaId) {
            throw new \RuntimeException('Mensagem não encontrada.', 404);
        }

        if ((string) $msg['remetente'] !== $cpf) {
            throw new \RuntimeException('Você só pode alterar as suas próprias mensagens.', 403);
        }

        return $msg;
    }

    private static function montaIdentificacao(array $conversa, array $pessoas, string $cpf): array
    {
        if ($conversa['tipo'] === 'grupo') {
            $ativos = array_values(array_filter($pessoas, function ($p) {
                return empty($p['apagado']);
            }));

            return [
                $conversa['titulo'] ?: 'Grupo',
                count($ativos) . ' participante' . (count($ativos) !== 1 ? 's' : ''),
            ];
        }

        $outro = null;

        foreach ($pessoas as $p) {
            if ($p['cpf'] !== $cpf) {
                $outro = $p;
                break;
            }
        }

        if ($outro === null) {
            $outro = ['cpf' => $cpf, 'nome' => 'Conversa direta', 'setor' => '', 'filial' => ''];
        }

        return [
            $outro['nome'],
            implode(' · ', array_filter([$outro['setor'] ?? '', $outro['filial'] ?? ''])),
        ];
    }

    private static function cpf(?string $cpf): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $cpf);

        if (strlen($digits) !== 11) {
            $user = AuthService::getUser() ?? [];
            $digits = preg_replace('/\D/', '', (string) ($user['cpf'] ?? ''));
        }

        return strlen($digits) === 11 ? $digits : null;
    }
}
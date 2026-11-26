<?php

namespace App\Service;

use App\Core\DB;
use App\Model\Mysql\ChatModel;
use App\Model\Mysql\OnlineModel;
use App\Service\AuthService;

class ChatService
{
    private const MAX_ANEXO = 5242880;

    private const DIR_ANEXO = 'storage/chat';

    private const EXTENSOES = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'zip', 'rar', 'mp3', 'wav', 'mp4',
    ];

    private const MIMES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain; charset=utf-8',
        'csv' => 'text/csv; charset=utf-8',
        'zip' => 'application/zip',
        'rar' => 'application/vnd.rar',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'mp4' => 'video/mp4',
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
            $janela = OnlineModel::JANELA_ATIVIDADE_MIN;
            $participantes = DB::select(
                "SELECT p.conversa_id, p.cpf, p.apagado_em,
                COALESCE(NULLIF(TRIM(f.nomesocial), ''), f.nome) AS nome,
                COALESCE(fi.nome, '') AS filial, COALESCE(s.setor, '') AS setor, COALESCE(u.foto, '') AS foto,
                    CASE WHEN EXISTS (SELECT 1 FROM online o
                        WHERE o.cpf = p.cpf AND o.status = '1'
                        AND o.dt_login > (NOW() - INTERVAL {$janela} MINUTE))
                    THEN 1 ELSE 0 END AS online
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
                'foto' => $p['foto'] ?? '',
                'apagado' => !empty($p['apagado_em']),
                'online' => !empty($p['online']),
            ];
        }

        $lista = [];

        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $pessoas = $porConversa[$id] ?? [];
            [$titulo, $sub, $foto] = self::montaIdentificacao($r, $pessoas, $cpf);

            $lista[] = [
                'id' => $id,
                'tipo' => $r['tipo'],
                'titulo' => $titulo,
                'subtitulo' => $sub,
                'foto' => $foto,
                'ultima_msg' => self::previewUltimaMensagem($r),
                'ultima_em' => $r['ultima_em'] ?? null,
                'nao_lidas' => (int) ($r['nao_lidas'] ?? 0),
                'online' => self::conversaOnline($r['tipo'], $pessoas, $cpf),
                'participantes' => $pessoas,
            ];
        }

        return $lista;
    }

    /**
     * Texto exibido na lista de conversas para a última mensagem.
     * Anexos sem legenda não têm texto, então recebem um rótulo próprio.
     */
    private static function previewUltimaMensagem(array $r): string
    {
        if (!empty($r['ultima_apagada'])) {
            return '[Mensagem apagada]';
        }

        $texto = trim((string) CryptoService::descriptografar($r['ultima_msg'] ?? ''));

        if ($texto !== '') {
            return $texto;
        }

        return match ((string) ($r['ultima_tipo'] ?? 'texto')) {
            'imagem' => '[Imagem]',
            'arquivo' => '[Arquivo]' . (($r['ultima_anexo_nome'] ?? '') !== '' ? ' ' . $r['ultima_anexo_nome'] : ''),
            default => '',
        };
    }

    /**
     * Presença dos parceiros de conversa, chaveada por `conversa_id`.
     * Consumida pelo poll de status do chat, que renova a atividade do próprio
     * usuário através do header `X-Usuario-Ativo`.
     *
     * @return array{conversas: array<int, bool>}
     */
    public static function status(string $cpf): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null) {
            return ['conversas' => []];
        }

        $porConversa = ChatModel::parceirosPorConversa($cpf);

        $cpfs = [];
        foreach ($porConversa as $parceiros) {
            foreach ($parceiros as $parceiro) {
                $cpfs[$parceiro] = $parceiro;
            }
        }

        $online = OnlineService::cpfsOnline(array_values($cpfs));
        $mapa = [];

        foreach (ChatModel::idsConversas($cpf) as $id) {
            $mapa[$id] = false;

            foreach ($porConversa[$id] ?? [] as $parceiro) {
                if (isset($online[$parceiro])) {
                    $mapa[$id] = true;
                    break;
                }
            }
        }

        return ['conversas' => $mapa];
    }

    /**
     * @return array{mensagens: list<array>, tem_mais: bool, cursor: int}
     */
    public static function mensagens(int $conversaId, string $cpf, int $apos = 0, int $antes = 0): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $conversaId <= 0 || !self::participa($conversaId, $cpf)) {
            throw new \RuntimeException('Conversa não encontrada.', 404);
        }

        // Cursor antes da leitura: eventos criados durante a consulta são
        // reentregues no próximo poll e descartados no cliente por id.
        $cursor = ChatModel::ultimoEventoId((int) $conversaId);

        $dados = ChatModel::mensagens((int) $conversaId, max(0, $apos), 100, $cpf, max(0, $antes));

        foreach ($dados['mensagens'] as &$m) {
            $m['texto'] = CryptoService::descriptografar((string) ($m['texto'] ?? ''));
        }
        unset($m);

        $dados['cursor'] = $cursor;

        return $dados;
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

        $id = ChatModel::enviar((int) $conversaId, $cpf, CryptoService::criptografar($texto), $tipo, $anexo !== null ? (string) $anexo : null, $anexoNome);

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

        return self::descriptografaMensagem($msg);
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
        $relativo = 'chat/' . $safe . '.' . $ext;
        $destino = basePath(self::DIR_ANEXO . '/' . $safe . '.' . $ext);

        $dir = dirname($destino);

        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new \RuntimeException('Falha ao criar a pasta de arquivos.', 500);
        }

        $tmp = (string) $file['tmp_name'];
        $gravado = is_uploaded_file($tmp) ? move_uploaded_file($tmp, $destino) : @rename($tmp, $destino);

        if (!$gravado) {
            throw new \RuntimeException('Falha ao salvar o arquivo.', 500);
        }

        $tipo = $mime !== false && str_starts_with($mime, 'image/') ? 'imagem' : 'arquivo';

        try {
            return self::enviar($conversaId, $cpf, trim($texto), $tipo, $relativo, $file['name']);
        } catch (\Throwable $e) {
            @unlink($destino);
            throw $e;
        }
    }

    /**
     * Resolve o arquivo de um anexo somente para participantes da conversa.
     *
     * @return array{caminho: string, nome: string, mime: string, tamanho: int, inline: bool}
     */
    public static function anexo(int $mensagemId, string $cpf): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $mensagemId <= 0) {
            throw new \RuntimeException('Anexo não encontrado.', 404);
        }

        $msg = ChatModel::mensagem($mensagemId);

        if (!$msg || (int) ($msg['apagado'] ?? 0) === 1 || empty($msg['anexo'])) {
            throw new \RuntimeException('Anexo não encontrado.', 404);
        }

        if (!self::participa((int) $msg['conversa_id'], $cpf)) {
            throw new \RuntimeException('Anexo não encontrado.', 404);
        }

        $caminho = self::caminhoAnexo((string) $msg['anexo']);

        if ($caminho === null) {
            throw new \RuntimeException('Anexo não encontrado.', 404);
        }

        $mime = self::MIMES[strtolower(pathinfo($caminho, PATHINFO_EXTENSION))] ?? 'application/octet-stream';

        return [
            'caminho' => $caminho,
            'nome' => (string) ($msg['anexo_nome'] ?: basename($caminho)),
            'mime' => $mime,
            'tamanho' => (int) filesize($caminho),
            'inline' => str_starts_with($mime, 'image/'),
        ];
    }

    private static function caminhoAnexo(string $anexo): ?string
    {
        $anexo = str_replace('\\', '/', $anexo);

        if (preg_match('#^/files/chat/[a-f0-9]{22}\.[a-z0-9]{1,10}$#i', $anexo)) {
            return self::arquivoDentro(basePath('public/files/chat'), basename($anexo));
        }

        if (preg_match('#^chat/[a-f0-9]{22}\.[a-z0-9]{1,10}$#i', $anexo)) {
            return self::arquivoDentro(basePath(self::DIR_ANEXO), basename($anexo));
        }

        return null;
    }

    private static function arquivoDentro(string $dir, string $arquivo): ?string
    {
        $raiz = realpath($dir);

        if ($raiz === false) {
            return null;
        }

        $caminho = realpath($raiz . DIRECTORY_SEPARATOR . $arquivo);

        if ($caminho === false || !str_starts_with($caminho, $raiz . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($caminho) ? $caminho : null;
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

        if (!ChatModel::editar($mensagemId, $cpf, CryptoService::criptografar($texto))) {
            throw new \RuntimeException('Não foi possível editar a mensagem.', 500);
        }

        $nova = ChatModel::mensagem($mensagemId);

        ChatModel::novoEvento((int) $conversaId, 'edicao', [
            'conversa_id' => (int) $conversaId,
            'mensagem_id' => $mensagemId,
            'texto' => $nova['texto'],
            'editado_em' => $nova['editado_em'],
        ]);

        return self::descriptografaMensagem($nova);
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

        ChatModel::digitandoAtualizar((int) $conversaId, $cpf);
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

    public static function eventos(int $conversaId, string $cpf, int $apos): array
    {
        $cpf = self::cpf($cpf);

        if ($cpf === null || $conversaId <= 0 || !self::participa($conversaId, $cpf)) {
            throw new \RuntimeException('Conversa não encontrada.', 404);
        }

        $rows = ChatModel::eventos((int) $conversaId, max(0, $apos));
        $cursor = max(0, $apos);

        foreach ($rows as &$r) {
            $cursor = (int) $r['id'];
            $r['dados'] = json_decode((string) ($r['dados'] ?? '{}'), true) ?: [];

            if (($r['evento'] ?? '') === 'novidade') {
                foreach (($r['dados']['mensagens'] ?? []) as &$m) {
                    $m['texto'] = CryptoService::descriptografar((string) ($m['texto'] ?? ''));
                }
                unset($m);
            }

            if (($r['evento'] ?? '') === 'edicao' && isset($r['dados']['texto'])) {
                $r['dados']['texto'] = CryptoService::descriptografar((string) $r['dados']['texto']);
            }
        }
        unset($r);

        return [
            'eventos' => $rows,
            'cursor' => $cursor,
            'digitando' => ChatModel::digitandoAtivos((int) $conversaId, $cpf),
        ];
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

        if (($ultima['conversa_tipo'] ?? '') === 'grupo' && !empty($ultima['conversa_titulo'])) {
            $titulo = (string) $ultima['conversa_titulo'];
        } elseif (!empty($ultima['remetente_nome'])) {
            $titulo = (string) $ultima['remetente_nome'];
        }

        return [
            'total' => $total,
            'ultima' => [
                'conversa_id' => (int) $ultima['conversa_id'],
                'titulo' => $titulo,
                'remetente' => $ultima['remetente_nome'] ?? 'Funcionário',
                'texto' => self::resumoTexto((string) CryptoService::descriptografar($ultima['texto'] ?? ''), (string) ($ultima['tipo'] ?? 'texto')),
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

    private static function descriptografaMensagem(array $m): array
    {
        $m['texto'] = CryptoService::descriptografar((string) ($m['texto'] ?? ''));

        return $m;
    }

    /**
     * Conversa direta: segue o outro participante. Grupo: acende se algum
     * participante ativo estiver online.
     */
    private static function conversaOnline(string $tipo, array $pessoas, string $cpf): bool
    {
        $outro = null;

        foreach ($pessoas as $p) {
            if ($p['cpf'] === $cpf || !empty($p['apagado'])) {
                continue;
            }

            if ($tipo === 'grupo') {
                if (!empty($p['online'])) {
                    return true;
                }

                continue;
            }

            $outro = $p;
            break;
        }

        return $tipo !== 'grupo' && !empty($outro['online']);
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
                '',
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
            $outro = ['cpf' => $cpf, 'nome' => 'Conversa direta', 'setor' => '', 'filial' => '', 'foto' => ''];
        }

        return [
            $outro['nome'],
            implode(' · ', array_filter([$outro['setor'] ?? '', $outro['filial'] ?? ''])),
            $outro['foto'] ?? '',
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
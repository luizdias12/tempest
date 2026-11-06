<?php

namespace App\Service;

use App\Core\Logger;
use App\Model\Mysql\HelpdeskEmailModel;
use App\Model\Mysql\HelpdeskModel;
use RuntimeException;
use Throwable;

class EmailHelpdeskService
{
    private const ID_GRUPO    = 13;
    private const ID_SUBGRUPO = 165;
    private const SLA         = 176;

    public static function importarEmails(int $limite = 50): array
    {
        HelpdeskEmailModel::criarTabelaSeNecessario();

        $resumo = ['importados' => 0, 'ignorados' => 0, 'erros' => [], 'respostas' => 0];

        $mailbox = self::env('SUPPORT_MAILBOX', 'helpdesk@villefort.com.br');

        if ($mailbox === '') {
            return $resumo;
        }

        $pastaId = self::pastaDestinoId($mailbox);

        $mensagens = GraphService::mensagensNaoLidas($mailbox, $limite);

        $resumo['total'] = count($mensagens);

        foreach ($mensagens as $m) {
            $messageId = $m['id'] ?? '';
            if ($messageId === '') {
                continue;
            }

            $conversationId = (string) ($m['conversationId'] ?? '');
            $subject        = trim($m['subject'] ?? '');

            if (!HelpdeskEmailModel::reservarImportacao($messageId, $conversationId)) {
                $resumo['ignorados']++;
                continue;
            }

            try {
                $chamadoExistente = self::chamadoExistente($conversationId, $subject);

                if ($chamadoExistente !== null) {
                    self::registrarResposta($chamadoExistente, $m, $mailbox);
                    $helpId = $chamadoExistente;
                    $resumo['respostas']++;
                } else {
                    $helpId = self::criarChamado($m, $mailbox);
                    $resumo['importados']++;
                }

                HelpdeskEmailModel::confirmarImportacao($messageId, $helpId);

                GraphService::marcarLida($mailbox, $messageId);

                if ($pastaId !== null) {
                    GraphService::mover($mailbox, $messageId, $pastaId);
                }
            } catch (Throwable $e) {
                HelpdeskEmailModel::liberarReserva($messageId);

                Logger::exception($e, [
                    'message_id' => $messageId,
                    'subject'    => $subject,
                ]);

                $resumo['erros'][] = [
                    'subject' => $subject !== '' ? $subject : '(sem assunto)',
                    'erro'    => $e->getMessage(),
                ];
            }
        }

        return $resumo;
    }

    private static function chamadoExistente(string $conversationId, string $subject): ?int
    {
        if ($conversationId !== '') {
            $helpId = HelpdeskEmailModel::buscarPorConversa($conversationId);

            if ($helpId !== null) {
                return $helpId;
            }
        }

        if (preg_match('/N[ÂºÂ°]?\s*(\d+)/iu', $subject, $m) && isset($m[1])) {
            $helpId = (int) $m[1];

            if ($helpId > 0 && HelpdeskService::obterStatus($helpId) !== null) {
                return $helpId;
            }
        }

        return null;
    }

    private static function notificarChamadoEncerrado(int $helpId, string $status, array $m): void
    {
        $statusMap = [
            'R' => 'já foi resolvido',
            'C' => 'já foi cancelado',
        ];

        $frase = $statusMap[$status] ?? 'já se encontra encerrado';

        [$fromEmail] = self::remetenteMensagem($m);

        if ($fromEmail === '') {
            return;
        }

        $dados = HelpdeskModel::obterDadosNotificacao($helpId);

        $assunto = trim((string) ($dados['cab_problema'] ?? ''));
        if ($assunto === '') {
            $assunto = '(sem assunto)';
        }

        $corpo = '<p>Olá!</p>'
            . '<p>Não foi possível registrar a sua resposta, pois o chamado <strong>Nº ' . $helpId . '</strong> ' . $frase . '.</p>'
            . '<ul>'
            . '<li><strong>Assunto:</strong> ' . htmlspecialchars($assunto, ENT_QUOTES, 'UTF-8') . '</li>'
            . '<li><strong>Número:</strong> ' . $helpId . '</li>'
            . '<li><strong>Situação:</strong> Encerrado</li>'
            . '</ul>'
            . '<p>Caso necessário, abra um novo chamado.</p>';

        MailService::enviar(
            $fromEmail,
            'Chamado Nº ' . $helpId . ' já encerrado - ' . $assunto,
            $corpo
        );
    }

    private static function registrarResposta(int $helpId, array $m, string $mailbox): void
    {
        $statusAtual = HelpdeskService::obterStatus($helpId);

        if (in_array($statusAtual, ['R', 'C'], true)) {
            self::notificarChamadoEncerrado($helpId, $statusAtual, $m);
            return;
        }

        [$fromEmail, $fromName] = self::remetenteMensagem($m);
        $corpo = self::corpoMensagem($m);
        $messageId = $m['id'] ?? '';

        $desc = "Resposta de {$fromName} <{$fromEmail}>:\n\n{$corpo}";

        $cpfAb = '';
        $usuario = HelpdeskEmailModel::buscarUsuarioPorEmail($fromEmail);

        if ($usuario && ($usuario['cpf'] ?? '') !== '') {
            $cpfAb = $usuario['cpf'];
        }

        $ehAbertura = $cpfAb !== '' && HelpdeskService::obterCpfAbertura($helpId) === $cpfAb;
        $status = $ehAbertura ? 'PS' : 'PU';

        $idHist = HelpHistoricoService::registrarInteracao(
            $helpId,
            '[EMAIL] ' . $desc,
            $cpfAb !== '' ? $cpfAb : '0',
            $status
        );

        HelpdeskService::atualizaStatusChamado($helpId, $status);
        HelpdeskService::upsertHelpStatus($helpId, $status);

        LogService::store([
            'nivel'    => 'INFO',
            'tipo'     => 'UPDATE',
            'modulo'   => 'helpdesk',
            'acao'     => 'resposta_email',
            'mensagem' => "chamado {$helpId} recebeu resposta via e-mail de {$fromEmail}",
            'contexto' => [
                'help_id'   => $helpId,
                'from_email' => $fromEmail,
            ],
        ]);

        if ($idHist !== null) {
            self::salvarAnexos($helpId, (int) $idHist, $messageId, $mailbox, (string) ($m['body']['content'] ?? ''));
        }
    }

    private static function criarChamado(array $m, string $mailbox): int
    {
        [$fromEmail, $fromName] = self::remetenteMensagem($m);
        $corpo = self::corpoMensagem($m);

        $cab = trim($m['subject'] ?? '');
        if ($cab === '') {
            $cab = '(sem assunto)';
        }

        $desc = "Enviado por: {$fromName} <{$fromEmail}>\n\n{$corpo}";

        $cpfAb = '';
        $chapa = 0;
        $nomeRemetente = $fromName;

        if ($fromEmail !== '') {
            $usuario = HelpdeskEmailModel::buscarUsuarioPorEmail($fromEmail);

            if ($usuario && ($usuario['cpf'] ?? '') !== '') {
                $cpfAb = $usuario['cpf'];

                $dados = HelpdeskEmailModel::dadosFuncionario($cpfAb);

                if ($dados) {
                    $chapa         = (int) ($dados['chapa'] ?? 0);
                    $nomeRemetente = $dados['nome'] ?? $fromName;
                }
            }
        }

        $dtRecebimento = date('Y-m-d H:i:s', strtotime((string) ($m['receivedDateTime'] ?? 'now')));

        $helpId = HelpdeskService::criar([
            'cpf_ab'        => $cpfAb,
            'chapa'         => $chapa,
            'dt_abertura'   => $dtRecebimento,
            'status'        => 'A',
            'idgrupo'       => self::ID_GRUPO,
            'idsubgrupo'    => self::ID_SUBGRUPO,
            'sla'           => self::SLA,
            'cab_problema'  => $cab,
            'desc_problema' => $desc,
            'id_resp'       => '',
            'cpf'           => $cpfAb,
        ]);

        if ($helpId === null) {
            throw new RuntimeException('Falha ao criar chamado no banco.');
        }

        $idHist = HelpHistoricoService::registrarInteracao(
            $helpId,
            "[EMAIL] Chamado aberto via e-mail por {$nomeRemetente} <{$fromEmail}>",
            $cpfAb !== '' ? $cpfAb : '0',
            'A'
        );

        HelpdeskService::upsertHelpStatus($helpId, 'A');

        LogService::store([
            'nivel'    => 'INFO',
            'tipo'     => 'INSERT',
            'modulo'   => 'helpdesk',
            'acao'     => 'abrir_chamado_email',
            'mensagem' => "chamado {$helpId} aberto via e-mail de {$fromEmail}",
            'contexto' => [
                'help_id'   => $helpId,
                'from_email' => $fromEmail,
            ],
        ]);

        if ($idHist !== null) {
            self::salvarAnexos($helpId, (int) $idHist, $m['id'] ?? '', $mailbox, (string) ($m['body']['content'] ?? ''));
        }

        HelpdeskService::notificarAbertura($helpId, $cab, $fromEmail);

        return $helpId;
    }

    private static function salvarAnexos(int $helpId, int $idHist, string $messageId, string $mailbox, string $bodyHtml = ''): void
    {
        if ($messageId === '' && trim($bodyHtml) === '') {
            return;
        }

        $anexos = [];

        if ($messageId !== '') {
            $anexos = GraphService::anexos($mailbox, $messageId);

            Logger::info('GraphService::anexos devolveu', [
                'qtd'     => count($anexos),
                'mailbox' => $mailbox,
                'anexos'  => array_map(static function (array $a): array {
                    return [
                        'nome'        => (string) ($a['name'] ?? ''),
                        'isInline'    => !empty($a['isInline']),
                        'contentType' => (string) ($a['contentType'] ?? ''),
                        'contentId'   => (string) ($a['contentId'] ?? ''),
                        'size'        => (int) ($a['size'] ?? 0),
                        'id'          => (string) ($a['id'] ?? ''),
                    ];
                }, $anexos),
            ]);

            Logger::info('GraphService::anexos do Graph', [
                'count'     => count($anexos),
                'mailbox'   => $mailbox,
                'messageId' => $messageId,
            ]);
        }

        $candidatos = array_merge($anexos, self::extrairImagensDataUri($bodyHtml));

        if ($candidatos === []) {
            return;
        }

        $cids = self::extrairCids($bodyHtml);

        usort($candidatos, static function (array $a, array $b) use ($cids): int {
            $posA = self::posNoCorpo($a, $cids);
            $posB = self::posNoCorpo($b, $cids);

            if ($posA === $posB) {
                return (int) ($b['size'] ?? 0) <=> (int) ($a['size'] ?? 0);
            }

            return $posA <=> $posB;
        });

        $salvos    = 0;
        $ignorados = 0;

        foreach ($candidatos as $anexo) {
            $ok = self::salvarAnexoNoHelpFiles(
                $helpId,
                $idHist,
                $anexo,
                $messageId !== '' ? $mailbox : '',
                $messageId
            );

            if ($ok) {
                $salvos++;
            } else {
                $ignorados++;
            }
        }

        Logger::info('salvarAnexos concluído', [
            'help_id'   => $helpId,
            'id_hist'   => $idHist,
            'salvos'    => $salvos,
            'ignorados' => $ignorados,
        ]);
    }

    private static function salvarAnexoNoHelpFiles(
        int $helpId,
        int $idHist,
        array $anexo,
        string $mailbox,
        string $messageId
    ): bool {
        $inline = !empty($anexo['isInline']);
        $nome   = trim((string) ($anexo['name'] ?? ''));
        $tipo   = strtolower((string) ($anexo['contentType'] ?? ''));
        $idAnexo = (string) ($anexo['id'] ?? '');

        if ($nome === '') {
            if (!$inline) {
                Logger::warning('salvarAnexos: anexo sem nome ignorado', [
                    'help_id'   => $helpId,
                    'id_hist'   => $idHist,
                    'contentType' => $tipo,
                ]);
                return false;
            }

            $ext = self::extensaoImagem($tipo, (string) ($anexo['contentId'] ?? ''));

            if ($ext === '') {
                Logger::warning('salvarAnexos: anexo inline sem nome e com extensão desconhecida ignorado', [
                    'help_id'     => $helpId,
                    'id_hist'     => $idHist,
                    'contentType' => $tipo,
                ]);
                return false;
            }

            $nome = 'imagem_inline_' . substr(hash('md5', $idAnexo !== '' ? $idAnexo : $tipo), 0, 6) . '.' . $ext;
        }

        if ($inline && !self::eImagem($tipo, $nome)) {
            Logger::warning('salvarAnexos: anexo inline não-imagem ignorado', [
                'help_id'     => $helpId,
                'id_hist'     => $idHist,
                'nome'        => $nome,
                'contentType' => $tipo,
            ]);
            return false;
        }

        $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

        if (in_array($ext, ['exe', 'bat', 'cmd', 'ps1', 'vbs', 'js'], true)) {
            Logger::warning('salvarAnexos: extensão perigosa bloqueada', [
                'help_id' => $helpId,
                'id_hist' => $idHist,
                'nome'    => $nome,
            ]);
            return false;
        }

        if (!in_array($ext, FileService::extensoesPermitidas(), true)) {
            $extTipo = [
                'image/png'  => 'png',
                'image/jpeg' => 'jpg',
                'image/bmp'  => 'bmp',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];

            if (isset($extTipo[$tipo])) {
                $nome = preg_replace('/\.[^.]*$/', '', $nome) . '.' . $extTipo[$tipo];
                $ext  = $extTipo[$tipo];
            }
        }

        if (isset($anexo['conteudo']) && is_string($anexo['conteudo'])) {
            $conteudo = $anexo['conteudo'];
        } else {
            $conteudo = GraphService::baixarAnexo($mailbox, $messageId, $idAnexo);

            if ($conteudo === null) {
                Logger::warning('salvarAnexos: falha ao baixar anexo do Graph', [
                    'help_id'   => $helpId,
                    'id_hist'   => $idHist,
                    'nome'      => $nome,
                    'id_anexo'  => $idAnexo,
                ]);
                return false;
            }
        }

        try {
            $upload     = FileService::salvarConteudo($nome, $conteudo);
            $idArquivo  = HelpFilesService::registrar($helpId, $idHist, $nome);

            if ($idArquivo === null) {
                throw new RuntimeException('Falha ao registrar o arquivo em help_files.');
            }

            HelpFilesService::atualizarFileStr($idArquivo, FileService::finalize($upload, $idArquivo));

            return true;
        } catch (Throwable $e) {
            Logger::exception($e, ['help_id' => $helpId, 'anexo' => $nome]);

            return false;
        }
    }

    /**
     * Extrai imagens embutidas no corpo HTML como data:URI (caso em que o
     * Graph não devolve anexo algum — comum em apps de celular).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function extrairImagensDataUri(string $html): array
    {
        if ($html === '' || stripos($html, 'data:image') === false) {
            return [];
        }

        $padrao = '/<img[^>]*\ssrc\s*=\s*["\']data:(image\/[a-z0-9.+-]+);base64,([a-z0-9+\/=\s]+)["\']/i';

        if (!preg_match_all($padrao, $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $extMap = [
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/bmp'  => 'bmp',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        $imagens  = [];
        $vistos   = [];

        foreach ($matches as $m) {
            $tipo = strtolower($m[1]);

            if (!isset($extMap[$tipo])) {
                Logger::warning('extrairImagensDataUri: tipo de imagem data-URI não suportado ignorado', [
                    'contentType' => $tipo,
                ]);
                continue;
            }

            $base64   = (string) preg_replace('/\s+/', '', $m[2]);
            $conteudo = base64_decode($base64, true);

            if ($conteudo === false || $conteudo === '') {
                Logger::warning('extrairImagensDataUri: base64 inválido ignorado', ['contentType' => $tipo]);
                continue;
            }

            $hash = md5($conteudo);

            if (isset($vistos[$hash])) {
                continue;
            }

            $vistos[$hash] = true;

            $imagens[] = [
                'id'          => '',
                'name'        => 'imagem_corpo_' . (count($imagens) + 1) . '.' . $extMap[$tipo],
                'contentType' => $tipo,
                'isInline'    => true,
                'contentId'   => '',
                'size'        => strlen($conteudo),
                'conteudo'    => $conteudo,
            ];
        }

        return $imagens;
    }

    private static function extrairCids(string $html): array
    {
        $cids = [];

        if ($html !== '' && preg_match_all('/cid\s*:\s*([^"\'<>\s]+)/i', $html, $m)) {
            foreach ($m[1] as $cid) {
                $cids[] = strtolower(trim($cid, " <>\"'"));
            }
        }

        return $cids;
    }

    private static function posNoCorpo(array $anexo, array $cids): int
    {
        $contentId = strtolower(trim((string) ($anexo['contentId'] ?? ''), " <>\""));

        if ($contentId === '') {
            return PHP_INT_MAX;
        }

        foreach ($cids as $i => $cid) {
            if ($contentId === $cid || str_ends_with($contentId, $cid) || str_ends_with($cid, $contentId)) {
                return $i;
            }
        }

        return PHP_INT_MAX;
    }

    private static function eImagem(string $tipo, string $nome): bool
    {
        if (strpos($tipo, 'image/') === 0) {
            return true;
        }

        $base = strtolower((string) preg_replace('/@.*$/i', '', $nome));

        return in_array(
            pathinfo($base, PATHINFO_EXTENSION),
            ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp', 'tif', 'tiff'],
            true
        );
    }

    private static function extensaoImagem(string $tipo, string $nome): string
    {
        $base = strtolower((string) preg_replace('/@.*$/i', '', $nome));
        $ext  = strtolower(pathinfo($base, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp'], true)) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        $map = [
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/bmp'  => 'bmp',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        return $map[$tipo] ?? '';
    }

    private static function remetenteMensagem(array $m): array
    {
        $fromEmail = strtolower(trim($m['from']['emailAddress']['address'] ?? ''));
        $fromName  = trim($m['from']['emailAddress']['name'] ?? '');

        return [$fromEmail, $fromName];
    }

    private static function corpoMensagem(array $m): string
    {
        if (($m['body']['contentType'] ?? '') === 'text/plain') {
            return self::limparCorpoTexto(trim($m['body']['content'] ?? ''));
        }

        $html = (string) ($m['body']['content'] ?? '');

        if ($html !== '') {
            $comQuebras = preg_replace('#</(div|p|li|tr|blockquote|br)\s*/?>#i', "\n", $html);

            if ($comQuebras !== null) {
                $html = $comQuebras;
            }

            $html = self::removerCitacaoHtml($html);
        }

        return self::limparCorpoTexto(trim(strip_tags(html_entity_decode($html))));
    }

    private static function removerCitacaoHtml(string $html): string
    {
        if (!extension_loaded('dom') || $html === '') {
            return $html;
        }

        $dom = new \DOMDocument();
        $anterior = libxml_use_internal_errors(true);

        $dom->loadHTML(
            '<html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        $blocos = [];

        foreach ($dom->getElementsByTagName('blockquote') as $n) {
            $blocos[] = $n;
        }

        foreach ($dom->getElementsByTagName('div') as $d) {
            $classe = strtolower((string) $d->getAttribute('class'));
            $style  = strtolower((string) $d->getAttribute('style'));

            if (
                strpos($classe, 'gmail_quote') !== false ||
                strpos($classe, 'quote') !== false ||
                strpos($classe, 'citacao') !== false ||
                strpos($classe, 'cmpq') !== false ||
                (strpos($style, 'border-left') !== false && strpos($style, 'solid') !== false) ||
                (strpos($style, 'border:none') !== false && strpos($style, 'border-top') !== false && strpos($style, 'solid') !== false)
            ) {
                $blocos[] = $d;
            }
        }

        foreach ($blocos as $n) {
            if ($n->parentNode) {
                $n->parentNode->removeChild($n);
            }
        }

        $html = $dom->saveHTML();

        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        return $html;
    }

    private static function limparCorpoTexto(string $corpo): string
    {
        if ($corpo === '') {
            return '';
        }

        $linhas = preg_split('/\R/', $corpo);

        $marcadoresCitacao = [
            '/^-{5,}/',                                       // separador "-----Original Message-----"
            '/^_{10,}/',                                      // sublinhado longo
            '/^(?:On\b.+?)wrote:\s*$/i',                      // Gmail/Apple
            '/^(?:Em\b.+?)escreveu:\s*$/i',                   // Gmail pt-BR
            '/^[>*\-_ ]{0,4}(?:De|From|Sent|Enviad[oa]|Enviada em|Para|To|Cc|Assunto|Subject):/i',
        ];

        $mantidas = [];

        foreach ($linhas as $linha) {
            $l = trim($linha);

            if ($l !== '') {
                $ehCitacao = false;

                foreach ($marcadoresCitacao as $rx) {
                    if (preg_match($rx, $l)) {
                        $ehCitacao = true;
                        break;
                    }
                }

                if ($ehCitacao) {
                    break;
                }

                if (strpos($l, '>') === 0) {
                    continue;
                }
            }

            $mantidas[] = $linha;
        }

        return trim(implode("\n", $mantidas));
    }

    private static function env(string $key, string $default = ''): string
    {
        return getenv($key) ?: ($_ENV[$key] ?? $default);
    }

    private static function pastaDestinoId(string $mailbox): ?string
    {
        $nome = self::env('GRAPH_PASTA', 'Chamados');

        $id = GraphService::pastaId($mailbox, $nome);

        if ($id !== null) {
            return $id;
        }

        return GraphService::criarPasta($mailbox, $nome);
    }
}
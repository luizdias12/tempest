<?php

namespace App\Service;

use App\Core\Logger;
use App\Model\Mysql\HelpdeskEmailModel;
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

        $resumo = ['importados' => 0, 'ignorados' => 0, 'erros' => []];

        $mailbox = self::env('SUPPORT_MAILBOX', 'helpdesk@villefort.com.br');

        if ($mailbox === '') {
            return $resumo;
        }

        $pastaId = self::pastaDestinoId($mailbox);

        $mensagens = GraphService::mensagensNaoLidas($mailbox, $limite);

        foreach ($mensagens as $m) {
            $messageId = $m['id'] ?? '';
            if ($messageId === '') {
                continue;
            }

            if (!HelpdeskEmailModel::reservarImportacao($messageId)) {
                $resumo['ignorados']++;
                continue;
            }

            try {
                $helpId = self::criarChamado($m, $mailbox);

                HelpdeskEmailModel::confirmarImportacao($messageId, $helpId);

                GraphService::marcarLida($mailbox, $messageId);

                if ($pastaId !== null) {
                    GraphService::mover($mailbox, $messageId, $pastaId);
                }

                $resumo['importados']++;
            } catch (Throwable $e) {
                HelpdeskEmailModel::liberarReserva($messageId);

                Logger::exception($e, [
                    'message_id' => $messageId,
                    'subject'    => $m['subject'] ?? '',
                ]);

                $resumo['erros'][] = [
                    'subject' => $m['subject'] ?? '(sem assunto)',
                    'erro'    => $e->getMessage(),
                ];
            }
        }

        return $resumo;
    }

    private static function criarChamado(array $m, string $mailbox): int
    {
        $fromEmail = strtolower(trim($m['from']['emailAddress']['address'] ?? ''));
        $fromName  = trim($m['from']['emailAddress']['name'] ?? '');

        $cab = trim($m['subject'] ?? '');
        if ($cab === '') {
            $cab = '(sem assunto)';
        }

        $corpo = '';
        if (($m['body']['contentType'] ?? '') === 'text/plain') {
            $corpo = trim($m['body']['content'] ?? '');
        } else {
            $corpo = trim(strip_tags(html_entity_decode((string) ($m['body']['content'] ?? ''))));
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

        HelpHistoricoService::registrarInteracao(
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

        self::salvarAnexos($helpId, $m['id'] ?? '', $mailbox, $fromEmail);

        return $helpId;
    }

    private static function salvarAnexos(int $helpId, string $messageId, string $mailbox, string $fromEmail): void
    {
        if ($messageId === '') {
            return;
        }

        $anexos = GraphService::anexos($mailbox, $messageId);

        foreach ($anexos as $anexo) {
            $nome = $anexo['name'] ?? '';
            if ($nome === '') {
                continue;
            }

            $conteudo = GraphService::baixarAnexo($mailbox, $messageId, $anexo['id'] ?? '');
            if ($conteudo === null) {
                continue;
            }

            $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

            if (in_array($ext, ['exe', 'bat', 'cmd', 'ps1', 'vbs', 'js'], true)) {
                continue;
            }

            try {
                $upload = FileService::salvarConteudo($nome, $conteudo);

                $idHist = HelpHistoricoService::registrarInteracao(
                    $helpId,
                    "Anexo recebido por e-mail: {$nome}",
                    $fromEmail !== '' ? $fromEmail : '0',
                    'A'
                );

                if ($idHist !== null) {
                    HelpHistoricoService::atualizarFileStr(
                        $idHist,
                        FileService::finalize($upload, $idHist)
                    );
                }
            } catch (Throwable $e) {
                Logger::exception($e, ['help_id' => $helpId, 'anexo' => $nome]);
            }
        }
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

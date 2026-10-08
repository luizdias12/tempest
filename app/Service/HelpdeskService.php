<?php

namespace App\Service;

use App\Model\Mysql\HelpdeskModel;

class HelpdeskService
{
    public static function chamadosAbertos(
            int $page = 1,
            int $limit = 10,
            ?int $id = null,
            ?string $emitente = null,
            ?string $status = null,
            ?string $local = null,
            ?string $idResp = null,
            ?string $idMeu = null,
            ?string $tecnico = null,
            ?bool $isSuporte = false,
            ?bool $isExterno = false
        ): array|null
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        
        return HelpdeskModel::chamadosAbertos(
            $page,
            $limit,
            $id,
            $emitente,
            $status,
            $local,
            $idResp,
            $idMeu,
            $tecnico,
            $isSuporte,
            $isExterno
        );
    }

    public static function obterSla(int $idgrupo, int $idsubgrupo): ?int
    {
        return HelpdeskModel::obterSla($idgrupo, $idsubgrupo);
    }

    public static function criar(array $data): ?int
    {
        return HelpdeskModel::criar($data);
    }

    public static function atualizar(int $id, array $data): bool
    {
        return HelpdeskModel::atualizar($id, $data);
    }

    public static function atualizaStatusChamado(int $helpId, string $status): bool
    {
        return HelpdeskModel::atualizaStatusChamado($helpId, $status);
    }

    public static function obterCpfAbertura(int $id): ?string
    {
        return HelpdeskModel::obterCpfAbertura($id);
    }

    public static function obterStatus(int $id): ?string
    {
        return HelpdeskModel::obterStatus($id);
    }

    public static function registrarCancelamento(int $idHelp, int $codmotivo, string $idCanc, string $ip, string $acao): ?int
    {
        return HelpdeskModel::registrarCancelamento($idHelp, $codmotivo, $idCanc, $ip, $acao);
    }

    public static function upsertHelpStatus(int $idHelp, string $statusValue): bool
    {
        return HelpdeskModel::upsertHelpStatus($idHelp, $statusValue);
    }

    public static function updateHelpResp(int $idHelp, string $idResp): bool
    {
        return HelpdeskModel::updateHelpResp($idHelp, $idResp);
    }

    public static function obterEmailsNotificacao(int $id, ?string $idUsuExcluir = null): array
    {
        return HelpdeskModel::obterEmailsNotificacao($id, $idUsuExcluir);
    }

    public static function listarGrupos(): array
    {
        return HelpdeskModel::listarGrupos();
    }

    public static function listarSubgrupos(): array
    {
        return HelpdeskModel::listarSubgrupos();
    }

    public static function listarResponsaveis(): array
    {
        return HelpdeskModel::listarResponsaveis();
    }

    public static function cancelaChamado(int $idHelp): bool
    {
        return HelpdeskModel::cancelaChamado($idHelp);
    }

    public static function listarFuncionarios(): array
    {
        return HelpdeskModel::listarFuncionarios();
    }

    public static function obterChapaPorCpf(string $cpf): ?string
    {
        return HelpdeskModel::obterChapaPorCpf($cpf);
    }

    public static function chamadosPendentesUsuario(): array|null
    {
        return HelpdeskModel::chamadosPendentesUsuario();
    }

    public static function notificarAbertura(int $id, string $assunto, string $paraEmail = ''): void
    {
        if ($paraEmail === '') {
            $dados = HelpdeskModel::obterDadosNotificacao($id);
            $paraEmail = $dados['email_abertura'] ?? '';
        }

        if ($paraEmail === '') {
            return;
        }

        $assunto = trim($assunto);
        if ($assunto === '') {
            $assunto = '(sem assunto)';
        }

        $corpo = '<p>Olá!</p>'
            . '<p>O seu chamado <strong>Nº ' . $id . '</strong> foi aberto.</p>'
            . '<ul>'
            . '<li><strong>Assunto:</strong> ' . htmlspecialchars($assunto, ENT_QUOTES, 'UTF-8') . '</li>'
            . '<li><strong>Situação:</strong> Em atendimento pela equipe de TI</li>'
            . '</ul>'
            . '<p>Em caso de dúvidas, responda a este e-mail informando o número do chamado.</p>';

        MailService::enviar(
            $paraEmail,
            'Chamado Nº ' . $id . ' aberto - ' . $assunto,
            $corpo
        );
    }

    public static function notificarFinalizacao(int $id, string $statusValue): void
    {
        $map = [
            'R' => 'foi resolvido',
            'C' => 'foi cancelado',
        ];

        $frase = $map[$statusValue] ?? null;

        if ($frase === null) {
            return;
        }

        $dados = HelpdeskModel::obterDadosNotificacao($id);

        if (!$dados || empty($dados['email_abertura'])) {
            return;
        }

        $assunto = trim((string) $dados['cab_problema']);
        if ($assunto === '') {
            $assunto = '(sem assunto)';
        }

        $corpo = '<p>Olá!</p>'
            . '<p>O chamado <strong>Nº ' . $id . '</strong> ' . $frase . '.</p>'
            . '<ul>'
            . '<li><strong>Assunto:</strong> ' . htmlspecialchars($assunto, ENT_QUOTES, 'UTF-8') . '</li>'
            . '<li><strong>Número:</strong> ' . $id . '</li>'
            . '</ul>'
            . '<p>Em caso de dúvidas, responda a este e-mail informando o número do chamado.</p>';

        MailService::enviar(
            $dados['email_abertura'],
            'Chamado Nº ' . $id . ' - ' . $assunto,
            $corpo
        );
    }

    public static function notificarFilaSuporte(int $horasUteis = 8, int $limite = 50, bool $enviar = true): array
    {
        $limite = max(1, $limite);

        $rotulos = [];
        foreach (StatusService::all() ?? [] as $status) {
            $rotulos[(string) $status['status']] = (string) ($status['descricao'] ?? '');
        }

        $porTecnico = [];
        $ignorados = 0;

        foreach (HelpdeskModel::chamadosFilaSuporte() as $chamado) {
            $horas = businessHoursBetween((string) $chamado['desde_status']);

            if ($horas < $horasUteis) {
                $ignorados++;
                continue;
            }

            $porTecnico[(string) $chamado['id_resp']][] = $chamado;
        }

        $emailsOk = 0;
        $emailsErro = 0;
        $elegiveis = 0;

        foreach ($porTecnico as $idResp => $fila) {
            $total = count($fila);
            $elegiveis += $total;

            $email = (string) ($fila[0]['email'] ?? '');
            $nome = trim((string) ($fila[0]['nome'] ?? ''));

            if ($email === '') {
                continue;
            }

            $linhas = '';
            foreach (array_slice($fila, 0, $limite) as $item) {
                $rotulo = $rotulos[(string) $item['status']] ?? (string) $item['status'];
                $assunto = trim((string) ($item['cab_problema'] ?? ''));
                $desde = date('d/m/Y H:i', strtotime((string) $item['desde_status']));
                $decorrido = formatBusinessHours(businessHoursBetween((string) $item['desde_status']));

                $linhas .= '<tr>'
                    . '<td style="padding:6px 10px;border:1px solid #ddd;">' . htmlspecialchars((string) $item['id']) . '</td>'
                    . '<td style="padding:6px 10px;border:1px solid #ddd;">' . htmlspecialchars($assunto !== '' ? $assunto : '(sem assunto)') . '</td>'
                    . '<td style="padding:6px 10px;border:1px solid #ddd;">' . htmlspecialchars($rotulo) . '</td>'
                    . '<td style="padding:6px 10px;border:1px solid #ddd;">' . $desde . ' (há ' . $decorrido . ')</td>'
                    . '</tr>';
            }

            $rodape = '';
            if ($total > $limite) {
                $rodape = '<p><em>E mais ' . ($total - $limite) . ' chamado(s) — refiltre na intranet para ver a lista completa.</em></p>';
            }

            $saudacao = $nome !== '' ? explode(' ', $nome)[0] : 'Olá';

            $corpo = '<p>Olá, ' . htmlspecialchars($saudacao) . '!</p>'
                . '<p>Você tem <strong>' . $total . ' chamado(s)</strong> na sua fila aguardando ação'
                . ' (há ' . $horasUteis . ' horas úteis ou mais):</p>'
                . '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'
                . '<tr>'
                . '<th style="padding:6px 10px;border:1px solid #ddd;background:#f5f5f5;text-align:left;">Nº</th>'
                . '<th style="padding:6px 10px;border:1px solid #ddd;background:#f5f5f5;text-align:left;">Assunto</th>'
                . '<th style="padding:6px 10px;border:1px solid #ddd;background:#f5f5f5;text-align:left;">Situação</th>'
                . '<th style="padding:6px 10px;border:1px solid #ddd;background:#f5f5f5;text-align:left;">Status desde</th>'
                . '</tr>'
                . $linhas
                . '</table>'
                . $rodape
                . '<p>Acesse a intranet &gt; Helpdesk para atender.</p>';

            if (!$enviar) {
                continue;
            }

            $enviou = MailService::enviar($email, 'Fila de chamados - ' . $total . ' chamado(s) aguardando ação', $corpo);

            if ($enviou) {
                $emailsOk++;
            } else {
                $emailsErro++;
            }

            LogService::store([
                'nivel' => $enviou ? 'INFO' : 'ERROR',
                'tipo' => 'AUTO',
                'modulo' => 'helpdesk',
                'acao' => 'notificar_fila_suporte',
                'mensagem' => "fila de suporte notificada para {$email} ({$total} chamado(s))",
                'contexto' => [
                    'id_resp' => $idResp,
                    'email' => $email,
                    'qtde' => $total,
                    'limite' => $limite,
                    'horas_uteis' => $horasUteis,
                    'enviado' => $enviou,
                ],
            ]);
        }

        return [
            'chamados' => $elegiveis,
            'ignorados' => $ignorados,
            'emails_ok' => $emailsOk,
            'emails_erro' => $emailsErro,
        ];
    }
}
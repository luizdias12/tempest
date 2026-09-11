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
        ?bool $isSuporte = false,
        ?bool $isExterno = false
        ): array|null
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        
        return HelpdeskModel::chamadosAbertos($page, $limit, $id, $emitente, $status, $local, $idResp, $idMeu, $isSuporte, $isExterno);
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
}
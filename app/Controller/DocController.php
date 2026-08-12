<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;
use App\Service\DocService;
use App\Service\LogService;
use Throwable;

class DocController extends BaseController
{
    public function indexView(Request $request): void
    {
        try {
            view('documentos/index', [
                'arvore' => DocService::arvore(),
                'diretorios' => DocService::listarDiretorios(),
                'subdiretorios' => DocService::listarSubdiretorios(),
                'funcoes' => DocService::listarFuncoes(),
                'isSuporte' => AuthService::hasPermission('ti'),
                'title' => 'Documentos'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function abrir(Request $request, int $idDoc): void
    {
        try {
            if ($idDoc <= 0) {
                AlertManager::add('error', 'Documento inválido.');
                redirect('/documentos');
                return;
            }

            $arquivo = DocService::abrir($idDoc);

            if ($arquivo === null) {
                AlertManager::add('warning', 'Documento não encontrado ou sem permissão de acesso.');
                redirect('/documentos');
                return;
            }

            $this->streamArquivo($arquivo);
        } catch (Throwable $e) {
            Logger::exception($e);

            AlertManager::add('error', 'Erro ao abrir o documento.');
            redirect('/documentos');
        }
    }

    public function visualizar(Request $request, int $idDoc): void
    {
        try {
            if ($idDoc <= 0) {
                AlertManager::add('error', 'Documento inválido.');
                redirect('/documentos/gestao');
                return;
            }

            $arquivo = DocService::abrirAdmin($idDoc);

            if ($arquivo === null) {
                AlertManager::add('warning', 'Arquivo não encontrado.');
                redirect('/documentos/gestao');
                return;
            }

            $this->streamArquivo($arquivo);
        } catch (Throwable $e) {
            Logger::exception($e);

            AlertManager::add('error', 'Erro ao abrir o documento.');
            redirect('/documentos/gestao');
        }
    }

    private function streamArquivo(array $arquivo): void
    {
        header('Content-Type: ' . DocService::mimeTipo($arquivo['tipo']));
        header('Content-Length: ' . $arquivo['tamanho']);
        header(
            "Content-Disposition: " . ($arquivo['inline'] ? 'inline' : 'attachment')
            . '; filename="' . rawurlencode($arquivo['basename'])
            . '"; filename*=UTF-8\'\'' . rawurlencode($arquivo['basename'])
        );

        readfile($arquivo['caminho_absoluto']);
    }

    public function gestaoView(Request $request): void
    {
        try {
            $idDir = max(0, (int) $request->query('id_dir', 0));
            $idSubdir = max(0, (int) $request->query('id_subdir', 0));
            $nome = trim((string) $request->query('nome', ''));

            view('documentos/gestao', [
                'documentos' => DocService::listarDocumentosAdmin(
                    $idDir > 0 ? $idDir : null,
                    $idSubdir > 0 ? $idSubdir : null,
                    $nome !== '' ? $nome : null
                ),
                'permissoes' => DocService::permissoesPorDocumento(),
                'versoes' => DocService::versoesPorDocumento(),
                'diretorios' => DocService::listarDiretorios(),
                'subdiretorios' => DocService::listarSubdiretorios(),
                'funcoes' => DocService::listarFuncoes(),
                'filtroDir' => $idDir,
                'filtroSubdir' => $idSubdir,
                'filtroNome' => $nome,
                'title' => 'Gestão de Documentos'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function diretorio(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return DocService::criarDiretorio((string) $request->post('nome', ''));
        }, 'criar_diretorio');
    }

    public function subdiretorio(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return DocService::criarSubdiretorio(
                (int) $request->post('id_dir', 0),
                (string) $request->post('nome', '')
            );
        }, 'criar_subdiretorio');
    }

    public function excluirDiretorio(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return DocService::excluirDiretorio((int) $request->post('id', 0));
        }, 'excluir_diretorio');
    }

    public function excluirSubdiretorio(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return DocService::excluirSubdiretorio((int) $request->post('id', 0));
        }, 'excluir_subdiretorio');
    }

    public function adicionarPermissao(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return DocService::adicionarPermissao(
                (int) $request->post('id_doc', 0),
                (string) $request->post('codfuncao', '')
            );
        }, 'adicionar_permissao', true);
    }

    public function removerPermissao(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return DocService::removerPermissao(
                (int) $request->post('id_doc', 0),
                (string) $request->post('codfuncao', '')
            );
        }, 'remover_permissao', true);
    }

    public function geral(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return DocService::alternarGeral(
                (int) $request->post('id_doc', 0),
                $request->post('geral', '') === 'S'
            );
        }, 'alterar_acesso_geral', true);
    }

    public function copiarPermissoes(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return DocService::copiarPermissoes(
                (int) $request->post('funcao_origem', 0),
                (int) $request->post('funcao_destino', 0)
            );
        }, 'copiar_permissoes', true);
    }

    public function novaVersao(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            $idDoc = (int) $request->post('id_doc', 0);

            if ($idDoc <= 0) {
                throw new \RuntimeException('Documento inválido.');
            }

            $resultado = DocService::processarNovaVersao($idDoc, $request->file('docArquivo') ?? []);

            return $resultado['mensagem'];
        }, 'nova_versao_documento');
    }

    public function versao(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            $idDoc = (int) $request->post('id_doc', 0);
            $idVersao = (int) $request->post('id_versao', 0);

            if (!DocService::restaurarVersao($idDoc, $idVersao)) {
                throw new \RuntimeException('Versão não encontrada para este documento.');
            }

            return "Versão {$idVersao} restaurada como atual.";
        }, 'restaurar_versao');
    }

    public function excluirVersao(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            return DocService::excluirVersao(
                (int) $request->post('id_doc', 0),
                (int) $request->post('id_versao', 0)
            );
        }, 'excluir_versao');
    }

    public function excluirDocumento(Request $request): void
    {
        $this->acaoGestao($request, function () use ($request): string {
            $idDoc = (int) $request->post('id_doc', 0);

            if (!DocService::excluirDocumento($idDoc)) {
                throw new \RuntimeException('Documento não encontrado.');
            }

            return "Documento {$idDoc} excluído.";
        }, 'excluir_documento');
    }

    public function upload(Request $request): void
    {
        try {
            if (!AuthService::hasPermission('ti')) {
                AlertManager::add('error', 'Você não tem permissão para enviar documentos.');
                redirect('/documentos/gestao');
                return;
            }

            $idDir = (int) $request->post('id_dir', 0);
            $idSubdir = (int) $request->post('id_subdir', 0);
            $geral = $request->post('geral', '') === 'S';
            $funcoes = $request->post('funcoes', []);
            $funcoes = is_array($funcoes) ? $funcoes : [];

            if ($idDir <= 0 || $idSubdir <= 0) {
                AlertManager::add('error', 'Selecione o diretório e o subdiretório do documento.');
                redirect('/documentos/gestao');
                return;
            }

            if (!$geral && empty($funcoes)) {
                AlertManager::add('error', 'Selecione ao menos uma função para o documento.');
                redirect('/documentos/gestao');
                return;
            }

            $resultado = DocService::processarUpload(
                $request->file('docArquivo'),
                $idDir,
                $idSubdir,
                $geral,
                $funcoes
            );

            $this->logAcao($request, 'enviar_documento', $resultado['mensagem'] ?? 'upload de documento', [
                'id_dir' => $idDir,
                'id_subdir' => $idSubdir,
                'geral' => $geral ? 'S' : 'N',
            ]);

            AlertManager::add('success', $resultado['mensagem'] ?? 'Documento enviado com sucesso.');
            redirect('/documentos/gestao');
        } catch (Throwable $e) {
            Logger::exception($e);

            AlertManager::add('error', $e->getMessage());
            redirect('/documentos/gestao');
        }
    }

    private function acaoGestao(Request $request, callable $acao, string $acaoNome, bool $json = false): void
    {
        try {
            if (!AuthService::hasPermission('ti')) {
                if ($json) {
                    Response::json(['success' => false, 'message' => 'Acesso não permitido.'], 403);
                    return;
                }

                AlertManager::add('error', 'Acesso não permitido.');
                redirect('/documentos');
                return;
            }

            $mensagem = $acao();

            $this->logAcao($request, $acaoNome, $mensagem, $request->post());

            if ($json) {
                Response::json(['success' => true, 'message' => $mensagem]);
                return;
            }

            AlertManager::add('success', $mensagem);
        } catch (Throwable $e) {
            Logger::exception($e);

            if ($json) {
                Response::json(['success' => false, 'message' => $e->getMessage()], 400);
                return;
            }

            AlertManager::add('error', $e->getMessage());
        }

        redirect('/documentos/gestao');
    }

    private function logAcao(Request $request, string $acao, string $mensagem, array $contexto = []): void
    {
        $user = AuthService::getUser() ?? [];

        LogService::store([
            'nivel' => 'INFO',
            'tipo' => 'INSERT',
            'modulo' => 'documentos',
            'acao' => $acao,
            'usuario_id' => $user['id'] ?? null,
            'chapa' => $user['chapa'] ?? null,
            'usuario_nome' => $user['name'] ?? $user['username'] ?? null,
            'metodo_http' => $request->method(),
            'rota' => $request->uri(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'mensagem' => $mensagem,
            'contexto' => $contexto,
        ]);
    }
}

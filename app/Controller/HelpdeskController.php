<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;
use App\Service\FileService;
use App\Service\FuncionarioService;
use App\Service\HelpdeskService;
use App\Service\HelpHistoricoService;
use App\Service\MailService;
use Throwable;

class HelpdeskController extends BaseController
{
    public function indexView(Request $request): void
    {
        try {
            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 10)));
            $id = $request->input('id', $request->query('id', ''));
            $emitente = $request->input('emitente', $request->query('emitente', ''));
            $status = $request->input('status', $request->query('status', ''));
            $local = $request->input('local', $request->query('local', ''));
            $meus = $request->input('meus', $request->query('meus', ''));
            $open = max(0, (int) $request->query('open', 0));

            $idResp = !empty($meus) ? $this->cpfUsuarioAtual() : null;

            $isSuporte = AuthService::hasPermission('ti');
            //$isSuporte = false; // Temporarily disable support check for testing purposes
            $result = HelpdeskService::chamadosAbertos($page, $limit, $id ?: null, $emitente ?: null, $status ?: null, $local ?: null, $idResp, $isSuporte);

            $grupos = HelpdeskService::listarGrupos();
            $subgrupos = HelpdeskService::listarSubgrupos();
            $responsaveis = HelpdeskService::listarResponsaveis();
            $historico = $open > 0 ? HelpHistoricoService::obterHistoricoHelpdesk($open) : [];
            $contagemHistoricos = HelpHistoricoService::contagemHistoricos(array_column($result['data'], 'id'));
            $openCpf = $open > 0 ? HelpdeskService::obterCpfAbertura($open) : null;
            $anexosAbertura = HelpHistoricoService::anexosAbertura(array_column($result['data'], 'id'));

            view('helpdesk/index', [
                'chamados' => $result['data'],
                'meta' => $result['meta'],
                'id' => $id,
                'emitente' => $emitente,
                'status' => $status,
                'local' => $local,
                'meus' => $meus,
                'open' => $open,
                'grupos' => $grupos,
                'subgrupos' => $subgrupos,
                'responsaveis' => $responsaveis,
                'hist' => $historico,
                'contagemHistoricos' => $contagemHistoricos,
                'openCpf' => $openCpf,
                'anexosAbertura' => $anexosAbertura,
                'title' => 'Helpdesk'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function update(Request $request): void
    {
        try {
            $id = (int) $request->post('id', 0);

            if ($id <= 0) {
                AlertManager::add('error', 'Chamado inválido.');
                redirect('/helpdesk/index');
                return;
            }

            $data = [];
            foreach (['status', 'idgrupo', 'idsubgrupo', 'id_resp'] as $campo) {
                $valor = $request->post($campo);
                if ($valor !== null && $valor !== '') {
                    $data[$campo] = $valor;
                }
            }

            if (empty($data)) {
                AlertManager::add('error', 'Nenhum campo para atualizar.');
                redirect($this->redirectBack($request, $id));
                return;
            }

            HelpdeskService::atualizar($id, $data);
            AlertManager::add('success', "Chamado Nº {$id} atualizado.");

            redirect($this->redirectBack($request, $id));
        } catch (Throwable $e) {
            Logger::exception($e);

            AlertManager::add('error', 'Erro ao atualizar o chamado.');
            redirect('/helpdesk/index');
        }
    }

    public function store(Request $request): void
    {
        try {
            $idgrupo = (int) $request->post('idgrupo', 0);
            $idsubgrupo = (int) $request->post('idsubgrupo', 0);
            $cabProblema = trim((string) $request->post('cab_problema', ''));
            $descProblema = trim((string) $request->post('desc_problema', ''));

            if ($idgrupo <= 0 || $idsubgrupo <= 0) {
                AlertManager::add('error', 'Selecione o grupo e o sub-grupo do chamado.');
                redirect('/helpdesk/index');
                return;
            }

            if ($cabProblema === '' || $descProblema === '') {
                AlertManager::add('error', 'Informe o tópico e a descrição do problema.');
                redirect('/helpdesk/index');
                return;
            }

            $upload = null;

            if (!empty($request->file('helpAttach')['name'])) {
                try {
                    $upload = FileService::upload($request->file('helpAttach'));
                } catch (Throwable $e) {
                    Logger::exception($e);

                    AlertManager::add('error', $e->getMessage());
                    redirect('/helpdesk/index');
                    return;
                }
            }

            $sla = HelpdeskService::obterSla($idgrupo, $idsubgrupo);

            if ($sla === null) {
                AlertManager::add('error', 'Nenhum SLA configurado para o grupo/sub-grupo selecionado.');
                redirect('/helpdesk/index');
                return;
            }

            $cpfAb = $this->cpfUsuarioAtual();
            $chapa = (int) (AuthService::getUser()['chapa'] ?? 0);

            $id = HelpdeskService::criar([
                'cpf_ab' => $cpfAb,
                'chapa' => $chapa,
                'dt_abertura' => date('Y-m-d H:i:s'),
                'status' => 'A',
                'idgrupo' => $idgrupo,
                'idsubgrupo' => $idsubgrupo,
                'sla' => $sla,
                'cab_problema' => $cabProblema,
                'desc_problema' => $descProblema,
                'id_resp' => '',
                'cpf' => $cpfAb,
            ]);

            if ($id === null) {
                AlertManager::add('error', 'Erro ao abrir o chamado.');
                redirect('/helpdesk/index');
                return;
            }

            $idHist = HelpHistoricoService::registrarInteracao($id, 'Abertura do chamado', $cpfAb, 'A');

            if ($upload !== null && $idHist !== null) {
                try {
                    HelpHistoricoService::atualizarFileStr($idHist, FileService::finalize($upload, $idHist));
                } catch (Throwable $e) {
                    Logger::exception($e);

                    AlertManager::add('warning', 'Chamado aberto, mas o anexo não pôde ser salvo.');
                }
            }

            AlertManager::add('success', "Chamado Nº {$id} aberto com sucesso.");

            redirect('/helpdesk/index?open=' . $id);
        } catch (Throwable $e) {
            Logger::exception($e);

            AlertManager::add('error', 'Erro ao abrir o chamado.');
            redirect('/helpdesk/index');
        }
    }

    public function interacao(Request $request): void
    {
        try {
            $id = (int) $request->post('id', 0);
            $mensagem = trim((string) $request->post('mensagem', ''));

            if ($id <= 0) {
                AlertManager::add('error', 'Chamado inválido.');
                redirect('/helpdesk/index');
                return;
            }

            if ($mensagem === '') {
                AlertManager::add('error', 'Escreva uma mensagem para registrar a interação.');
                redirect($this->redirectBack($request, $id));
                return;
            }

            $idUsu = $this->cpfUsuarioAtual();

            $cpfAb = HelpdeskService::obterCpfAbertura($id);
            $status = !empty($cpfAb) && $idUsu === $cpfAb ? 'PS' : 'PU';

            $upload = null;

            if (!empty($request->file('helpAttach')['name'])) {
                try {
                    $upload = FileService::upload($request->file('helpAttach'));
                } catch (Throwable $e) {
                    Logger::exception($e);

                    AlertManager::add('error', $e->getMessage());
                    redirect($this->redirectBack($request, $id));
                    return;
                }
            }

            $idHist = HelpHistoricoService::registrarInteracao($id, $mensagem, $idUsu, $status);

            if ($upload !== null && $idHist !== null) {
                try {
                    HelpHistoricoService::atualizarFileStr($idHist, FileService::finalize($upload, $idHist));
                } catch (Throwable $e) {
                    Logger::exception($e);

                    AlertManager::add('warning', 'Interação registrada, mas o anexo não pôde ser salvo.');
                }
            }

            HelpdeskService::atualizaStatusChamado($id, $status);

            $this->notificarInteracao($id, $mensagem);

            AlertManager::add('success', "Interação registrada no chamado Nº {$id}.");

            redirect($this->redirectBack($request, $id));
        } catch (Throwable $e) {
            Logger::exception($e);

            AlertManager::add('error', 'Erro ao registrar a interação.');
            redirect('/helpdesk/index');
        }
    }

    public function historicoJson(Request $request, int $id): void
    {
        try {
            if ($id <= 0) {
                Response::json(['error' => 'Chamado inválido.'], 400);
                return;
            }

            $hist = HelpHistoricoService::obterHistoricoHelpdesk($id);

            $items = array_map(static fn(array $item): array => [
                'nome' => $item['nome'] ?? 'Sistema',
                'data_hist' => !empty($item['data_hist']) ? date('d-m-Y H:i', strtotime($item['data_hist'])) : '',
                'status' => $item['status'] ?? '',
                'historico' => $item['historico'] ?? '',
                'id_usu' => $item['id_usu'] ?? '',
                'file_str' => $item['file_str'] ?? '',
            ], $hist);

            Response::json(['id' => $id, 'hist' => $items]);
        } catch (Throwable $e) {
            Logger::exception($e);

            Response::json(['error' => 'Erro ao carregar o histórico.'], 500);
        }
    }

    private function notificarInteracao(int $id, string $mensagem): void
    {
        try {
            $emails = HelpdeskService::obterEmailsNotificacao($id);

            if (empty($emails)) {
                return;
            }

            $user = AuthService::getUser();
            $autor = $user['name'] ?? ($user['username'] ?? 'Sistema');

            $assunto = "Chamado Nº {$id} - Nova interação";
            $corpo = "
                <h3>Nova interação no Chamado Nº {$id}</h3>
                <p><strong>Autor:</strong> " . htmlspecialchars($autor) . "</p>
                <p><strong>Data:</strong> " . date('d-m-Y H:i:s') . "</p>
                <hr>
                <p>" . nl2br(htmlspecialchars($mensagem)) . "</p>
            ";

            foreach ($emails as $email) {
                MailService::enviar($email, $assunto, $corpo);
            }
        } catch (Throwable $e) {
            Logger::exception($e);
        }
    }

    private function redirectBack(Request $request, int $id): string
    {
        $query = $request->query();
        $query['open'] = $id;

        return '/helpdesk/index?' . http_build_query($query);
    }

    private function cpfUsuarioAtual(): string
    {
        try {
            $user = AuthService::getUser();

            if (!empty($user['name'])) {
                $func = FuncionarioService::findByNome($user['name']);

                if (!empty($func['cpf'])) {
                    return $func['cpf'];
                }
            }

            return $user['username'] ?? 'sistema';
        } catch (Throwable $e) {
            return $_SESSION['auth']['username'] ?? 'sistema';
        }
    }
}

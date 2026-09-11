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
use App\Service\GenericService;
use App\Service\EmailHelpdeskService;
use App\Service\HelpdeskService;
use App\Service\HelpHistoricoService;
use App\Service\MailService;
use App\Service\LogService;
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
            $openbyme = $request->input('openbyme', $request->query('openbyme', ''));
            $open = max(0, (int) $request->query('open', 0));

            $idResp = !empty($meus) ? $this->cpfUsuarioAtual() : null;
            $idMeu = !empty($openbyme) ? $this->cpfUsuarioAtual() : null;

            $isSuporte = AuthService::hasPermission('ti');
            $isExterno = AuthService::isExterno();
            $result = HelpdeskService::chamadosAbertos($page, $limit, $id ?: null, $emitente ?: null, $status ?: null, $local ?: null, $idResp, $idMeu, $isSuporte, $isExterno);

            $grupos = HelpdeskService::listarGrupos();
            $subgrupos = HelpdeskService::listarSubgrupos();
            $responsaveis = HelpdeskService::listarResponsaveis();
            $funcionarios = HelpdeskService::listarFuncionarios();
            $historico = $open > 0 ? HelpHistoricoService::obterHistoricoHelpdesk($open) : [];
            $contagemHistoricos = HelpHistoricoService::contagemHistoricos(array_column($result['data'], 'id'));
            $openCpf = $open > 0 ? HelpdeskService::obterCpfAbertura($open) : null;
            $anexosAbertura = HelpHistoricoService::anexosAbertura(array_column($result['data'], 'id'));
            $motivosCancelamento = GenericService::listaMotivosCancelamento();

            $andamentos = [];
            foreach ($result['data'] as $chamado) {
                $dataRef = $chamado['data_status'] ?? $chamado['data_hist'] ?? '';

                if (empty($dataRef)) {
                    continue;
                }

                if (in_array($chamado['status'], ['R', 'C', 'EA', 'D'])) {
                    continue;
                }

                try {
                    $slaHoras = (float) preg_replace('/\D/', '', $chamado['sla'] ?? '');
                    $slaHoras = $slaHoras > 0 ? $slaHoras : 24;

                    $decorrido = businessHoursBetween($dataRef);
                    $pct = (int) round($decorrido / $slaHoras * 100);

                    $andamentos[$chamado['id']] = [
                        'horas' => $decorrido,
                        'sla' => $slaHoras,
                        'pct' => min(100, $pct),
                        'label' => formatBusinessHours($decorrido) . ' / ' . formatBusinessHours($slaHoras),
                    ];
                } catch (Throwable $e) {
                    continue;
                }
            }

            view('helpdesk/index', [
                'chamados' => $result['data'],
                'meta' => $result['meta'],
                'id' => $id,
                'emitente' => $emitente,
                'status' => $status,
                'local' => $local,
                'meus' => $meus,
                'openbyme' => $openbyme,
                'open' => $open,
                'grupos' => $grupos,
                'subgrupos' => $subgrupos,
                'responsaveis' => $responsaveis,
                'funcionarios' => $funcionarios,
                'hist' => $historico,
                'contagemHistoricos' => $contagemHistoricos,
                'openCpf' => $openCpf,
                'anexosAbertura' => $anexosAbertura,
                'motivosCancelamento' => $motivosCancelamento,
                'andamentos' => $andamentos,
                'isSuporte' => $isSuporte,
                'title' => 'Helpdesk'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function update(Request $request): void
    {
        $json = $this->isFetch($request);
        $responder = function (bool $sucesso, string $mensagem, ?string $url = null, int $status = 200) use ($request, $json) {
            if ($json) {
                Response::json(['success' => $sucesso, 'message' => $mensagem], $sucesso ? 200 : $status);
                return;
            }

            AlertManager::add($sucesso ? 'success' : 'error', $mensagem);
            redirect($url ?? '/helpdesk/index');
        };

        try {
            $id = (int) $request->post('id', 0);

            if ($id <= 0) {
                $responder(false, 'Chamado inválido.');
                return;
            }

            $data = [];

            $respValue = $request->post('id_resp');
            if ($respValue !== null) {
                if ($respValue === '') {
                    $respValue = AuthService::getUserCpf() ?? '';
                }
                $data['id_resp'] = $respValue;
            }

            $cpfAb = trim((string) $request->post('cpf_ab', ''));
            if ($cpfAb !== '') {
                $chapa = HelpdeskService::obterChapaPorCpf($cpfAb);
                $data['cpf_ab'] = $cpfAb;
                $data['chapa'] = $chapa ?? '';
            }

            $statusValue = $request->post('status');
            if ($statusValue !== null && $statusValue !== '') {
                $data['status'] = $statusValue;
                $data['dt_solucao'] = in_array($statusValue, ['R', 'C'], true) ? date('Y-m-d H:i:s') : null;
            }

            $motivo = trim((string) $request->post('motivo', ''));
            $statusAtual = HelpdeskService::obterStatus($id);
            $cancelando = $statusValue === 'C' && $statusAtual !== 'C';

            if ($cancelando && ($motivo === '' || !ctype_digit($motivo))) {
                $responder(false, 'Selecione o motivo do cancelamento.');
                return;
            }

            foreach (['idgrupo', 'idsubgrupo'] as $campo) {
                $valor = $request->post($campo);
                if ($valor !== null && $valor !== '') {
                    $data[$campo] = $valor;
                }
            }

            if (empty($data)) {
                $responder(false, 'Nenhum campo para atualizar.');
                return;
            }

            if ($statusValue !== null && $statusValue !== '' && $statusAtual !== $statusValue) {
                HelpdeskService::upsertHelpStatus($id, $statusValue);
            }

            HelpdeskService::atualizar($id, $data);

            $user = AuthService::getUser() ?? [];
            LogService::store([
                'nivel' => 'INFO',
                'tipo' => 'UPDATE',
                'modulo' => 'helpdesk',
                'acao' => 'alterar_chamado',
                'mensagem' => "chamado {$id} " . (in_array($statusValue, ['R'], true) ? 'finalizado' : 'alterado'),
                'contexto' => $data
            ]);

            if ($statusValue !== null && $statusValue !== '' && $statusAtual !== $statusValue) {
                HelpdeskService::notificarFinalizacao($id, $statusValue);
            }

            if ($cancelando) {
                HelpdeskService::registrarCancelamento($id, (int) $motivo, $this->cpfUsuarioAtual(), $_SERVER['REMOTE_ADDR'] ?? '', 'U');
                HelpHistoricoService::registrarInteracao(
                    $id,
                    GenericService::obterTextoCancelamento((int) $motivo),
                    $this->cpfUsuarioAtual(),
                    $statusValue
                );
                LogService::store([
                    'nivel' => 'INFO',
                    'tipo' => 'UPDATE',
                    'modulo' => 'helpdesk',
                    'acao' => 'cancelar_chamado',
                    'mensagem' => "chamado {$id} cancelado",
                    'contexto' => $data
                ]);
                $responder(true, "Chamado Nº {$id} cancelado.", $this->redirectBack($request, $id));
                return;
            }

            $responder(true, "Chamado Nº {$id} atualizado.", $this->redirectBack($request, $id));
        } catch (Throwable $e) {
            Logger::exception($e);

            $responder(false, 'Erro ao atualizar o chamado.');
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

            $isSuporte = AuthService::hasPermission('ti');

            $cpfAb = trim((string) $request->post('cpf_ab', ''));

            if (!empty($cpfAb) && $isSuporte) {
                $chapa = (int) (HelpdeskService::obterChapaPorCpf($cpfAb) ?? 0);
            } else {
                $cpfAb = $this->cpfUsuarioAtual();
                $chapa = (int) (AuthService::getUser()['chapa'] ?? 0);
            }

            $dadosAbertura = [
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
            ];

            $id = HelpdeskService::criar($dadosAbertura);

            if ($id === null) {
                AlertManager::add('error', 'Erro ao abrir o chamado.');
                redirect('/helpdesk/index');
                return;
            }

            LogService::store([
                'nivel' => 'INFO',
                'tipo' => 'INSERT',
                'modulo' => 'helpdesk',
                'acao' => 'abrir_chamado',
                'mensagem' => "chamado {$id} aberto",
                'contexto' => $dadosAbertura
            ]);

            $idHist = HelpHistoricoService::registrarInteracao($id, 'Abertura do chamado', $cpfAb, 'A');
            HelpdeskService::upsertHelpStatus($id, 'A');

            HelpdeskService::notificarAbertura($id, $cabProblema);

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
        $json = $this->isFetch($request);
        $responder = function (bool $sucesso, string $mensagem, ?string $url = null, int $status = 200) use ($request, $json) {
            if ($json) {
                Response::json(['success' => $sucesso, 'message' => $mensagem], $sucesso ? 200 : $status);
                return;
            }

            AlertManager::add($sucesso ? 'success' : 'error', $mensagem);
            redirect($url ?? '/helpdesk/index');
        };

        try {
            $id = (int) $request->post('id', 0);
            $mensagem = trim((string) $request->post('mensagem', ''));

            if ($id <= 0) {
                $responder(false, 'Chamado inválido.');
                return;
            }

            if ($mensagem === '') {
                $responder(false, 'Escreva uma mensagem para registrar a interação.');
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

                    $responder(false, $e->getMessage());
                    return;
                }
            }

            $idHist = HelpHistoricoService::registrarInteracao($id, $mensagem, $idUsu, $status);

            if ($upload !== null && $idHist !== null) {
                try {
                    HelpHistoricoService::atualizarFileStr($idHist, FileService::finalize($upload, $idHist));
                } catch (Throwable $e) {
                    Logger::exception($e);

                    $responder(true, 'Interação registrada, mas o anexo não pôde ser salvo.', $this->redirectBack($request, $id));
                    return;
                }
            }

            HelpdeskService::atualizaStatusChamado($id, $status);
            HelpdeskService::upsertHelpStatus($id, $status);

            $this->notificarInteracao($id, $mensagem, $idUsu);

            $responder(true, "Interação registrada no chamado Nº {$id}.", $this->redirectBack($request, $id));
        } catch (Throwable $e) {
            LogService::store([
                'nivel' => 'ERROR',
                'tipo' => 'INSERT',
                'modulo' => 'helpdesk',
                'acao' => 'registro_interacao',
                'mensagem' => $e->getMessage(),
                'contexto' => [
                    'id' => $id,
                    'idUsu' => $idUsu,
                    'status' => $status
                ]
            ]);
            Logger::exception($e);

            $responder(false, 'Erro ao registrar a interação.');
        }
    }

    public function historicoJson(Request $request, int $id): void
    {
        try {
            if ($id <= 0) {
                Response::json(['error' => 'Chamado inválido.'], 400);
                return;
            }

            HelpHistoricoService::marcarVisualizado($id, $this->cpfUsuarioAtual());

            $hist = HelpHistoricoService::obterHistoricoHelpdesk($id);

            $items = array_map(static fn(array $item): array => [
                'nome' => $item['nome'] ?? 'Sistema',
                'data_hist' => !empty($item['data_hist']) ? date('d-m-Y H:i', strtotime($item['data_hist'])) : '',
                'status' => $item['status'] ?? '',
                'historico' => $item['historico'] ?? '',
                'id_usu' => $item['id_usu'] ?? '',
                'file_str' => handleAttach($item['file_str'] ?? '', $id),
                'dtview' => !empty($item['dtview']) ? date('d-m-Y H:i', strtotime($item['dtview'])) : '',
            ], $hist);

            Response::json(['id' => $id, 'hist' => $items]);
        } catch (Throwable $e) {
            Logger::exception($e);

            Response::json(['error' => 'Erro ao carregar o histórico.'], 500);
        }
    }

    private function notificarInteracao(int $id, string $mensagem, string $idUsu): void
    {
        try {
            $emails = HelpdeskService::obterEmailsNotificacao($id, $idUsu);

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

    private function isFetch(Request $request): bool
    {
        return $request->header('X-Requested-With') === 'fetch';
    }

    public function importarEmails(Request $request): void
    {
        try {
            $resumo = EmailHelpdeskService::importarEmails();
            Response::json($resumo);
        } catch (Throwable $e) {
            Logger::exception($e);
            Response::json(['error' => 'Erro ao importar e-mails.'], 500);
        }
    }

    private function cpfUsuarioAtual(): string
    {
        try {
            $user = AuthService::getUser();

            if (!empty($user['name'])) {
                $func = FuncionarioService::findByNome($user['name']);

                if (!empty($func['cpf'])) {
                    return $func['cpf'];
                } else {
                    $func = GenericService::buscaFuncExterno($user['name']);
                    return $func['cpf'];
                }
            }

            return $user['username'] ?? 'sistema';
        } catch (Throwable $e) {
            return $_SESSION['auth']['username'] ?? 'sistema';
        }
    }
}

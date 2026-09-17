<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Request;
use App\Service\AuthService;
use App\Service\ChatService;

class ChatController extends BaseController
{
    public function indexView(Request $request): void
    {
        $user = AuthService::getUser() ?? [];

        view('chat/index', [
            'title' => 'Chat',
            'chatUser' => [
                'cpf' => $user['cpf'] ?? '',
                'secao' => $user['secao'] ?? '',
                'nome' => $user['name'] ?? '',
            ],
        ]);
    }

    public function conversas(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        return $this->handle(fn() => $this->success(ChatService::conversas($cpf)));
    }

    public function mensagens(Request $request, int $id): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $apos = (int) $request->query('apos', 0);

        return $this->handle(fn() => $this->success(ChatService::mensagens($id, $cpf, $apos)));
    }

    public function contatos(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $q = trim((string) $request->query('q', ''));

        return $this->handle(fn() => $this->success(ChatService::contatos($q, $cpf)));
    }

    public function enviar(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $conversaId = (int) $request->input('conversa_id', 0);
        $texto = trim((string) $request->input('texto', ''));

        return $this->handle(function () use ($conversaId, $cpf, $texto) {
            $msg = ChatService::enviar($conversaId, $cpf, $texto);

            return $this->success($msg, [], 'Mensagem enviada.');
        });
    }

    public function anexo(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $conversaId = (int) $request->input('conversa_id', 0);
        $texto = trim((string) $request->input('texto', ''));
        $arquivo = $request->file('anexo');

        return $this->handle(function () use ($conversaId, $cpf, $texto, $arquivo) {
            $msg = ChatService::enviarAnexo($conversaId, $cpf, is_array($arquivo) ? $arquivo : [], $texto);

            return $this->success($msg, [], 'Anexo enviado.');
        });
    }

    public function editar(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $conversaId = (int) $request->input('conversa_id', 0);
        $mensagemId = (int) $request->input('mensagem_id', 0);
        $texto = trim((string) $request->input('texto', ''));

        return $this->handle(function () use ($conversaId, $mensagemId, $cpf, $texto) {
            $msg = ChatService::editar($conversaId, $mensagemId, $cpf, $texto);

            return $this->success($msg, [], 'Mensagem editada.');
        });
    }

    public function excluir(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $conversaId = (int) $request->input('conversa_id', 0);
        $mensagemId = (int) $request->input('mensagem_id', 0);

        return $this->handle(function () use ($conversaId, $mensagemId, $cpf) {
            $msg = ChatService::excluir($conversaId, $mensagemId, $cpf);

            return $this->success($msg, [], 'Mensagem apagada.');
        });
    }

    public function reacao(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $conversaId = (int) $request->input('conversa_id', 0);
        $mensagemId = (int) $request->input('mensagem_id', 0);
        $emoji = trim((string) $request->input('emoji', ''));

        return $this->handle(function () use ($conversaId, $mensagemId, $cpf, $emoji) {
            $data = ChatService::reagir($conversaId, $mensagemId, $cpf, $emoji);

            return $this->success($data, [], 'Reação atualizada.');
        });
    }

    public function digitando(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $conversaId = (int) $request->input('conversa_id', 0);

        ChatService::digitando($conversaId, $cpf);

        return $this->success(null, [], 'OK.');
    }

    public function nova(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $contato = preg_replace('/\D/', '', (string) $request->input('contato_cpf', ''));

        if ($contato === '') {
            return $this->error('Selecione o contato.', 400);
        }

        return $this->handle(function () use ($cpf, $contato) {
            $id = ChatService::novaConversa($cpf, $contato);

            return $this->success(['conversa_id' => $id], [], 'Conversa criada.');
        });
    }

    public function naoLidas(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        return $this->handle(fn() => $this->success(['total' => ChatService::totalNaoLidas($cpf)]));
    }

    public function notificar(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        return $this->handle(fn() => $this->success(ChatService::notificar($cpf)));
    }

    public function marcar(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $conversaId = (int) $request->input('conversa_id', 0);
        $mensagemId = (int) $request->input('mensagem_id', 0);

        return $this->handle(function () use ($cpf, $conversaId, $mensagemId) {
            ChatService::marcarLido($conversaId, $cpf, $mensagemId);

            return $this->success(null, [], 'Mensagens marcadas como lidas.');
        });
    }

    public function limpar(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $conversaId = (int) $request->input('conversa_id', 0);

        return $this->handle(function () use ($conversaId, $cpf) {
            ChatService::limparConversa($conversaId, $cpf);

            return $this->success(null, [], 'Histórico limpo.');
        });
    }

    public function apagar(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $conversaId = (int) $request->input('conversa_id', 0);

        return $this->handle(function () use ($conversaId, $cpf) {
            ChatService::apagarConversa($conversaId, $cpf);

            return $this->success(null, [], 'Conversa removida.');
        });
    }

    public function stream(Request $request, int $id): void
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null || !ChatService::participaPub($id, $cpf)) {
            http_response_code(403);
            return;
        }

        $apos = ChatService::streamCursor($id);

        $lastEventId = $request->header('Last-Event-ID');
        if ($lastEventId !== null && $lastEventId !== '') {
            $apos = max($apos, (int) $lastEventId);
        }

        $qAp = $request->query('apos');
        if ($qAp !== null) {
            $apos = max($apos, (int) $qAp);
        }

        $deadline = time() + 25;
        $pingInterval = 15;
        $lastPing = time();

        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        @ini_set('output_buffering', '0');
        while (@ob_get_level() > 0) {
            @ob_end_flush();
        }

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        session_write_close();

        while (time() < $deadline) {
            usleep(1000000);

            if (connection_aborted()) {
                break;
            }

            $eventos = ChatService::eventos($id, $apos);

            if ($eventos) {
                foreach ($eventos as $ev) {
                    $this->emit((string) $ev['evento'], array_merge([
                        'conversa_id' => $id,
                    ], (array) ($ev['dados'] ?? [])), (int) $ev['id']);
                }

                $apos = (int) end($eventos)['id'];
                $lastPing = time();
            }

            if (time() - $lastPing >= $pingInterval) {
                $this->emit('ping', ['ts' => time()], $apos);
                $lastPing = time();
            }

            if (connection_aborted()) {
                break;
            }
        }

        $this->emit('ping', ['ts' => time()], $apos);
    }

    private function emit(string $event, array $data, int $idEvent): void
    {
        echo "id: {$idEvent}\n";
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        @flush();
    }
}
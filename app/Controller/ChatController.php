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

        if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['csrf_chat'])) {
            $_SESSION['csrf_chat'] = bin2hex(random_bytes(32));
        }

        view('chat/index', [
            'title' => 'Chat',
            'chatUser' => [
                'cpf' => $user['cpf'] ?? '',
                'secao' => $user['secao'] ?? '',
                'nome' => ($user['nomesocial'] ?? $user['name']) ?? '',
                'csrf' => $_SESSION['csrf_chat'] ?? '',
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
        $antes = (int) $request->query('antes', 0);

        return $this->handle(function () use ($id, $cpf, $apos, $antes) {
            $dados = ChatService::mensagens($id, $cpf, $apos, $antes);

            return $this->success($dados['mensagens'], [
                'tem_mais' => $dados['tem_mais'],
                'cursor' => $dados['cursor'],
            ]);
        });
    }

    public function eventos(Request $request, int $id): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        $apos = (int) $request->query('apos', 0);

        return $this->handle(fn() => $this->success(ChatService::eventos($id, $cpf, $apos)));
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

        $erro = $this->csrf($request);

        if ($erro !== null) {
            return $erro;
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

        $erro = $this->csrf($request);

        if ($erro !== null) {
            return $erro;
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

        $erro = $this->csrf($request);

        if ($erro !== null) {
            return $erro;
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

        $erro = $this->csrf($request);

        if ($erro !== null) {
            return $erro;
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

        $erro = $this->csrf($request);

        if ($erro !== null) {
            return $erro;
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

        $erro = $this->csrf($request);

        if ($erro !== null) {
            return $erro;
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

        $erro = $this->csrf($request);

        if ($erro !== null) {
            return $erro;
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

    public function status(Request $request): array
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            return $this->error('CPF do usuário inválido.', 401);
        }

        return $this->handle(fn() => $this->success(ChatService::status($cpf)));
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

        $erro = $this->csrf($request);

        if ($erro !== null) {
            return $erro;
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

        $erro = $this->csrf($request);

        if ($erro !== null) {
            return $erro;
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

        $erro = $this->csrf($request);

        if ($erro !== null) {
            return $erro;
        }

        $conversaId = (int) $request->input('conversa_id', 0);

        return $this->handle(function () use ($conversaId, $cpf) {
            ChatService::apagarConversa($conversaId, $cpf);

            return $this->success(null, [], 'Conversa removida.');
        });
    }

    public function baixarAnexo(Request $request, int $id): void
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            http_response_code(401);
            return;
        }

        try {
            $anexo = ChatService::anexo($id, $cpf);
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 404);
            return;
        }

        session_write_close();

        header('Content-Type: ' . $anexo['mime']);
        header('Content-Length: ' . $anexo['tamanho']);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, no-store');
        header('Content-Disposition: ' . ($anexo['inline'] ? 'inline' : 'attachment')
            . "; filename*=UTF-8''" . rawurlencode($anexo['nome']));

        readfile($anexo['caminho']);
    }

    private function csrf(Request $request): ?array
    {
        $token = (string) $request->header('X-CSRF-Token', '');

        if ($token !== '' && hash_equals((string) ($_SESSION['csrf_chat'] ?? ''), $token)) {
            return null;
        }

        return $this->error('Sessão expirada. Recarregue a página.', 403);
    }
}
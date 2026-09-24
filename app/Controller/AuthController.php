<?php

namespace App\Controller;

use App\Core\Alerts\AlertManager;
use App\Core\BaseController;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Model\Mysql\ContatoModel;
use App\Service\AuthService;
use App\Service\FuncionarioService;
use App\Service\GenericService;
use App\Service\LogService;
use App\Service\MessageService;
use App\Service\OnlineService;
use App\Service\UsuarioService;

class AuthController extends BaseController
{
    public function loginView(Request $request): void
    {
        if (AuthService::isAuthenticated()) {
            redirect('/');
            return;
        }

        $error = $request->query('error', '');
        view(
            'auth/login',
            [
                'error' => $error,
                'title' => 'Login',
            ]
        );
    }

    public function login(Request $request): void
    {
        $username = $request->input('username', '');
        $password = $request->input('password', '');

        if (AuthService::login($username, $password)) {

            AlertManager::add('success', 'Login com sucesso.');
            $user = AuthService::getUser() ?? [];

            LogService::store([
                'nivel' => 'INFO',
                'tipo' => 'LOGIN',
                'modulo' => 'auth',
                'acao' => 'autenticar',
                'usuario_id' => $user['id'] ?? null,
                'chapa' => $user['chapa'] ?? null,
                'usuario_nome' => $user['name'] ?? $user['username'] ?? null,
                'metodo_http' => $request->method(),
                'rota' => $request->uri(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'mensagem' => "usuário autenticado com sucesso ({$username})",
            ]);

            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            OnlineService::registrarLogin((string) ($user['cpf'] ?? ''), session_id(), $ip, localPorIp($ip));

            $this->mensagemDoDia($username);

            redirect('/');
        } else {

            LogService::store([
                'nivel' => 'ERROR',
                'tipo' => 'LOGIN',
                'modulo' => 'auth',
                'acao' => 'autenticar',
                'usuario_id' => $user['id'] ?? null,
                'chapa' => $user['chapa'] ?? null,
                'usuario_nome' => $user['name'] ?? $user['username'] ?? null,
                'metodo_http' => $request->method(),
                'rota' => $request->uri(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'mensagem' => "falha na autenticaçao do usuário ({$username})",
            ]);

            AlertManager::add('error', 'Credenciais inválidas.');

            redirect('/login');
        }
    }

    private function mensagemDoDia(string $username): void
    {
        $hoje = date('Y-m-d');
        $cookie = 'msg_vista_' . md5($username);

        if (($_COOKIE[$cookie] ?? '') === $hoje) {
            return;
        }

        $msg = MessageService::aleatoria();

        if ($msg === null) {
            return;
        }

        $_SESSION['msg_dia'] = $msg['mensagem'];
        setcookie($cookie, $hoje, [
            'expires' => strtotime('tomorrow') - 1,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public function cadastroView(Request $request): void
    {
        if (AuthService::isAuthenticated()) {
            redirect('/');
            return;
        }

        view(
            'auth/cadastro',
            [
                'filtroCpf' => (string) ($_SESSION['cadastro_cpf'] ?? ''),
                'nomeEncontrado' => (string) ($_SESSION['cadastro_nome'] ?? ''),
                'setores' => ContatoModel::listarSetores(),
                'title' => 'Cadastro',
            ]
        );
    }

    public function cadastro(Request $request): void
    {
        if (AuthService::isAuthenticated()) {
            redirect('/');
            return;
        }

        $cpf = preg_replace('/\D/', '', (string) $request->post('cpf', ''));

        // Etapa 2: criação da conta
        if (($request->post('usuario') ?? null) !== null && !empty($_SESSION['cadastro_cpf'])) {
            $cpf = (string) $_SESSION['cadastro_cpf'];
            $usuario = trim((string) $request->post('usuario', ''));
            $email = trim((string) $request->post('email', ''));
            $ramal = trim((string) $request->post('ramal', ''));
            $corporativo = trim((string) $request->post('corporativo', ''));
            $idSetor = max(0, (int) $request->post('id_setor', 0));
            $senha = (string) $request->post('senha', '');
            $confirmar = (string) $request->post('confirmar', '');

            if (!validarCpf($cpf)) {
                AlertManager::add('error', 'CPF inválido.');
                redirect('/login/cadastro');
                return;
            }

            if (UsuarioService::existe($cpf)) {
                $usuarioInfo = UsuarioService::getUsuario($cpf);
                AlertManager::add('warning', 'Usuario ja cadastrado: ' . ($usuarioInfo['usuario'] ?? ''));
                redirect('/login');
                return;
            }

            if (UsuarioService::existeLogin($usuario)) {
                AlertManager::add('error', 'Usuário já cadastrado, escolha outro nome de usuário.');
                redirect('/login/cadastro');
                return;
            }

            if (mb_strlen($usuario) > 20) {
                AlertManager::add('error', 'O usuário deve ter no máximo 20 caracteres.');
                redirect('/login/cadastro');
                return;
            }

            if ($email !== '' && !str_ends_with(mb_strtolower($email), '@villefort.com.br')) {
                AlertManager::add('error', 'Informe um e-mail corporativo válido (@villefort.com.br).');
                redirect('/login/cadastro');
                return;
            }

            if (mb_strlen($senha) < 6) {
                AlertManager::add('error', 'A senha deve ter no mínimo 6 caracteres.');
                redirect('/login/cadastro');
                return;
            }

            if ($senha !== $confirmar) {
                AlertManager::add('error', 'As senhas informadas não conferem.');
                redirect('/login/cadastro');
                return;
            }

            if (UsuarioService::criarConta(
                $cpf,
                $usuario,
                $email,
                $senha,
                (int) ($_SESSION['cadastro_filial'] ?? 0),
                $ramal,
                $corporativo,
                $idSetor
            )) {
                unset($_SESSION['cadastro_cpf'], $_SESSION['cadastro_nome'], $_SESSION['cadastro_filial']);

                AlertManager::add('success', 'Cadastro realizado com sucesso. Faça login.');
                redirect('/login');
                return;
            }

            AlertManager::add('error', 'Não foi possível realizar o cadastro.');
            redirect('/login/cadastro');
            return;
        }

        // Etapa 1: validação do CPF (não possui acesso + existe como funcionário ativo)
        if (!validarCpf($cpf)) {
            AlertManager::add('error', 'CPF inválido.');
            redirect('/login/cadastro');
            return;
        }

        if (UsuarioService::existe($cpf)) {
            $usuarioInfo = UsuarioService::getUsuario($cpf);
            AlertManager::add('warning', 'Usuario ja cadastrado: ' . ($usuarioInfo['usuario'] ?? ''));
            redirect('/login');
            return;
        }

        // if (UsuarioService::existe($cpf)) {
        //     AlertManager::add('error', 'Este CPF já possui acesso à Intranet.');
        //     redirect('/login');
        //     return;
        // }

        $funcionario = FuncionarioService::findByCpfDados($cpf);

        if (empty($funcionario) || empty($funcionario['nome'])) {
            try {
                $externo = GenericService::buscaFuncExternoPorCpf($cpf);
            } catch (\Throwable $e) {
                Logger::exception($e);
                $externo = [];
            }

            if (empty($externo) || empty($externo['nome'])) {
                AlertManager::add('error', 'CPF não encontrado como funcionário ativo.');
                redirect('/login/cadastro');
                return;
            }

            $_SESSION['cadastro_cpf'] = $cpf;
            $_SESSION['cadastro_nome'] = (string) $externo['nome'];
        } else {
            $_SESSION['cadastro_cpf'] = $cpf;
            $_SESSION['cadastro_nome'] = (string) $funcionario['nome'];
            $_SESSION['cadastro_filial'] = (int) ($funcionario['codfilial'] ?? 0);
        }

        redirect('/login/cadastro');
    }

    public function redefinirView(Request $request): void
    {
        if (AuthService::isAuthenticated()) {
            redirect('/');
            return;
        }

        $nome = GenericService::buscaFuncPorCpf((string) ($_SESSION['redefinir_cpf']))['nome'] ?? 'Nome não encontrado';

        view(
            'auth/redefinir',
            [
                'filtroCpf' => (string) ($_SESSION['redefinir_cpf'] ?? ''),
                'nomeEncontrado' => $nome,
                'title' => 'Redefinir Senha',
            ]
        );
    }

    public function redefinir(Request $request): void
    {
        if (AuthService::isAuthenticated()) {
            redirect('/');
            return;
        }

        $cpf = preg_replace('/\D/', '', (string) $request->post('cpf', ''));

        // Etapa 2: gravação da nova senha
        if (($request->post('senha') ?? null) !== null && !empty($_SESSION['redefinir_cpf'])) {
            $cpf = (string) $_SESSION['redefinir_cpf'];
            $senha = (string) $request->post('senha', '');
            $confirmar = (string) $request->post('confirmar', '');

            if (mb_strlen($senha) < 6) {
                AlertManager::add('error', 'A senha deve ter no mínimo 6 caracteres.');
                redirect('/login/redefinir');
                return;
            }

            if ($senha !== $confirmar) {
                AlertManager::add('error', 'As senhas informadas não conferem.');
                redirect('/login/redefinir');
                return;
            }

            if (UsuarioService::redefinirSenha($cpf, $senha)) {
                unset($_SESSION['redefinir_cpf']);

                AlertManager::add('success', 'Senha redefinida com sucesso. Faça login.');
                redirect('/login');
                return;
            }

            AlertManager::add('error', 'Não foi possível redefinir a senha.');
            redirect('/login/redefinir');
            return;
        }

        // Etapa 1: validação do CPF
        if (!validarCpf($cpf)) {
            AlertManager::add('error', 'CPF inválido.');
            redirect('/login/redefinir');
            return;
        }

        if (!UsuarioService::existe($cpf)) {
            AlertManager::add('error', 'CPF não cadastrado na Intranet.');
            redirect('/login/redefinir');
            return;
        }

        $_SESSION['redefinir_cpf'] = $cpf;

        redirect('/login/redefinir');
    }

    public function perfilView(Request $request): void
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            redirect('/login');
            return;
        }

        $perfil = UsuarioService::perfil($cpf) ?? [];

        view(
            'perfil/index',
            [
                'perfil' => $perfil,
                'setores' => ContatoModel::listarSetores(),
                'title' => 'Meu Perfil',
            ]
        );
    }

    public function perfil(Request $request): void
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            redirect('/login');
            return;
        }

        $email = trim((string) $request->post('email', ''));
        $ramal = trim((string) $request->post('ramal', ''));
        $idSetor = max(0, (int) $request->post('id_setor', 0));

        if ($email !== '' && !str_ends_with(mb_strtolower($email), '@villefort.com.br')) {
            AlertManager::add('error', 'Informe um e-mail corporativo válido (@villefort.com.br).');
            redirect('/perfil');
            return;
        }

        if (UsuarioService::atualizarPerfil($cpf, $email, $ramal, $idSetor)) {
            AlertManager::add('success', 'Perfil atualizado com sucesso.');
        } else {
            AlertManager::add('error', 'Não foi possível atualizar o perfil.');
        }

        redirect('/perfil');
    }

    public function senha(Request $request): void
    {
        $cpf = AuthService::getUserCpf();

        if ($cpf === null) {
            redirect('/login');
            return;
        }

        $senhaAtual = (string) $request->post('senha_atual', '');
        $novaSenha = (string) $request->post('nova_senha', '');
        $confirmar = (string) $request->post('confirmar_senha', '');

        if ($senhaAtual === '' || mb_strlen($novaSenha) < 6) {
            AlertManager::add('error', 'A nova senha deve ter no mínimo 6 caracteres.');
            redirect('/perfil');
            return;
        }

        if ($novaSenha !== $confirmar) {
            AlertManager::add('error', 'As senhas informadas não conferem.');
            redirect('/perfil');
            return;
        }

        if (UsuarioService::alterarSenha($cpf, $senhaAtual, $novaSenha)) {
            AlertManager::add('success', 'Senha alterada com sucesso.');
        } else {
            AlertManager::add('error', 'Senha atual incorreta ou não foi possível alterar.');
        }

        redirect('/perfil');
    }

    public function logout(Request $request): void
    {
        $cpf = AuthService::getUserCpf();

        AuthService::logout();

        if ($cpf !== null) {
            OnlineService::registrarLogout($cpf);
        }

        redirect('/login');
    }

    public function apiLogin(Request $request): array
    {
        return $this->handle(function () use ($request) {
            $username = $request->input('username', '');
            $password = $request->input('password', '');

            $token = AuthService::loginJwt($username, $password);

            if ($token === null) {
                return $this->error('Credenciais inválidas', 401);
            }

            return $this->success([
                'token' => $token,
                'type' => 'Bearer',
            ]);
        });
    }

    public function me(Request $request): array
    {
        return $this->handle(function () use ($request) {
            $user = $request->getAttribute('user');

            if (!$user) {
                return $this->error('Não autenticado', 401);
            }

            return $this->success($user->all());
        });
    }
}

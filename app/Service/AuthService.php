<?php

namespace App\Service;

use App\Core\Facades\JWT;
use App\Service\FuncionarioService;
use App\Service\GenericService;
use Throwable;
use App\Core\Logger;
use App\Core\QueryBuilder;

class AuthService
{
    public static function login(string $username, string $password): bool
    {
        if (empty($username) || empty($password)) {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (self::loginLdap($username, $password)) {
            return true;
        }

        return self::loginFallback($username, $password);
    }

    private static function loginLdap(string $username, string $password): bool
    {
        $externo = false;

        $ldapUrl = $_ENV['LDAP_URL'] ?? '';
        $domain = $_ENV['LDAP_DOMAIN'] ?? '';
        $searchBase = $_ENV['LDAP_SEARCHBASE'] ?? '';
        $nameAttr = $_ENV['LDAP_NAMEATTR'] ?? 'sAMAccountName';

        if (!function_exists('ldap_connect') || empty($ldapUrl) || empty($domain)) {
            return false;
        }

        $ldapConn = @ldap_connect($ldapUrl);
        if (!$ldapConn) {
            return false;
        }

        ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

        $userDn = "$username@$domain";
        if (!@ldap_bind($ldapConn, $userDn, $password)) {
            ldap_close($ldapConn);
            return false;
        }

        $search = @ldap_search($ldapConn, $searchBase, "($nameAttr=$username)", array("*"));
        if ($search) {
            $entries = ldap_get_entries($ldapConn, $search);

            if ($entries['count'] > 0) {
                $func = FuncionarioService::findByNome(removeAccents($entries[0]['cn'][0]));
                if (!$func) {
                    try {
                        $func = GenericService::buscaFuncExterno(removeAccents($entries[0]['cn'][0]));
                        $externo = true;
                    } catch (Throwable $e) {
                        Logger::exception($e);

                        ldap_close($ldapConn);
                        return false;
                    }
                }

                $permissoes = self::extrairGrupos($entries[0] ?? []);

                $_SESSION['auth'] = [
                    'username' => $username,
                    'name' => $entries[0]['cn'][0] ?? $username,
                    'email' => $entries[0]['mail'][0] ?? '',
                    'logged_in_at' => date('Y-m-d H:i:s'),
                    'chapa' => $func['chapa'] ?? null,
                    'cpf' => $func['cpf'] ?? null,
                    'codfuncao' => $func['codfuncao'] ?? null,
                    'funcao' => $func['funcao'] ?? null,
                    'secao' => $func['secao'] ?? null,
                    'permissoes' => $permissoes,
                    'suporte' => in_array('ti', $permissoes, true),
                    'externo' => $externo,
                ];

                return true;
            }
        }

        ldap_close($ldapConn);
        return false;
    }

    private static function loginFallback(string $username, string $password): bool
    {
        $data = self::dadosFallback($username, $password);

        if ($data === null) {
            return false;
        }

        $_SESSION['auth'] = $data;

        return true;
    }

    private static function dadosFallback(string $username, string $password): ?array
    {
        try {
            $row = QueryBuilder::table('usuarios', 'mysql')
                ->select('cpf', 'email', 'usuario', 'senha', 'ativo', 'bloqueado', 'admin', 'suporte')
                ->where('usuario', $username)
                ->first();
        } catch (Throwable $e) {
            Logger::exception($e);
            return null;
        }

        if ($row === null) {
            return null;
        }

        if (!hash_equals((string) ($row['senha'] ?? ''), md5($password))) {
            return null;
        }

        if (($row['ativo'] ?? '') !== 'S') {
            return null;
        }

        if (($row['bloqueado'] ?? 'N') === 'S') {
            return null;
        }

        $cpf = (string) ($row['cpf'] ?? '');
        $externo = false;

        try {
            $func = $cpf !== '' ? FuncionarioService::findByCpfDados($cpf) : null;
        } catch (Throwable $e) {
            Logger::exception($e);
            $func = null;
        }

        if (empty($func) || empty($func['nome'])) {
            try {
                $func = $cpf !== '' ? GenericService::buscaFuncExternoPorCpf($cpf) : [];
                $externo = !empty($func);
            } catch (Throwable $e) {
                Logger::exception($e);
                $func = [];
            }
        }

        $permissoes = [];
        if (in_array($row['suporte'] ?? '', ['S', 's'], true) || in_array($row['admin'] ?? '', ['S', 's'], true)) {
            $permissoes[] = 'ti';
        }

        return [
            'username' => $username,
            'sub' => $username,
            'name' => $func['nome'] ?? $username,
            'email' => (string) ($row['email'] ?? ''),
            'logged_in_at' => date('Y-m-d H:i:s'),
            'chapa' => $func['chapa'] ?? null,
            'cpf' => $func['cpf'] ?? ($cpf !== '' ? $cpf : null),
            'codfuncao' => $func['codfuncao'] ?? null,
            'funcao' => $func['funcao'] ?? null,
            'secao' => $func['secao'] ?? null,
            'permissoes' => $permissoes,
            'suporte' => in_array('ti', $permissoes, true),
            'externo' => $externo,
        ];
    }

    public static function loginJwt(string $username, string $password): ?string
    {
        $userData = self::authenticate($username, $password);

        if ($userData === null) {
            return null;
        }

        return JWT::encode($userData);
    }

    protected static function authenticate(string $username, string $password): ?array
    {
        if (empty($username) || empty($password)) {
            return null;
        }

        $ldapUrl = $_ENV['LDAP_URL'] ?? '';
        $domain = $_ENV['LDAP_DOMAIN'] ?? '';
        $searchBase = $_ENV['LDAP_SEARCHBASE'] ?? '';
        $nameAttr = $_ENV['LDAP_NAMEATTR'] ?? 'sAMAccountName';

        if (empty($ldapUrl) || empty($domain)) {
            return null;
        }

        $ldapConn = @ldap_connect($ldapUrl);
        if (!$ldapConn) {
            return null;
        }

        ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

        $userDn = "$username@$domain";
        if (!@ldap_bind($ldapConn, $userDn, $password)) {
            ldap_close($ldapConn);
            return null;
        }

        // $search = @ldap_search($ldapConn, $searchBase, "($nameAttr=$username)", ['cn', 'mail', $nameAttr]);
        $search = @ldap_search($ldapConn, $searchBase, "($nameAttr=$username)", array("*"));
        $userData = null;

        if ($search) {
            $entries = ldap_get_entries($ldapConn, $search);
            if ($entries['count'] > 0) {
                try {
                    $func = FuncionarioService::findByNome(removeAccents($entries[0]['cn'][0]));
                } catch (Throwable $e) {
                    Logger::exception($e);
                    ldap_close($ldapConn);
                    return null;
                }

                $permissoes = self::extrairGrupos($entries[0] ?? []);

                $userData = [
                    'sub' => $username,
                    'name' => $entries[0]['cn'][0] ?? $username,
                    'email' => $entries[0]['mail'][0] ?? '',
                    'chapa' => $func['chapa'] ?? null,
                    'cpf' => $func['cpf'] ?? null,
                    'codfuncao' => $func['codfuncao'] ?? null,
                    'secao' => $func['secao'] ?? null,
                    'permissoes' => self::extrairGrupos($entries[0] ?? []),
                ];
            }
        }

        ldap_close($ldapConn);

        if ($userData !== null) {
            return $userData;
        }

        return self::dadosFallback($username, $password);
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['auth']);
        session_destroy();
    }

    public static function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['auth']);
    }

    public static function getUser(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['auth'] ?? null;
    }

    public static function getUserName(): ?string
    {
        $name = self::getUser()['name'] ?? null;

        return $name !== null ? strtoupper($name) : null;
    }

    public static function getUserCpf(): ?string
    {
        return self::getUser()['cpf'] ?? null;
    }

    public static function getUserSecao(): ?string
    {
        return self::getUser()['secao'] ?? null;
    }

    public static function hasAnyRole(array $codFuncoes): bool
    {
        $codFuncao = self::getUser()['codfuncao'] ?? null;

        return in_array($codFuncao, $codFuncoes, true);
    }

    public static function hasPermission(string $permissao): bool
    {
        $permissoes = self::getUser()['permissoes'] ?? [];

        return in_array($permissao, $permissoes, true);
    }

    public static function isExterno(): bool
    {
        return self::getUser()['externo'] ?? false;
    }

    private static function extrairGrupos(array $entry): array
    {
        if (empty($entry['memberof']) || !is_array($entry['memberof'])) {
            return [];
        }

        $grupos = [];

        foreach ($entry['memberof'] as $dn) {
            if (!is_string($dn)) {
                continue;
            }

            $cn = '';

            foreach (explode(',', $dn) as $rdn) {
                if (stripos($rdn, 'CN=') === 0) {
                    $cn = substr($rdn, 3);
                    break;
                }
            }

            if ($cn !== '') {
                $grupos[] = removeAccents(strtolower($cn));
            }
        }

        return array_values(array_unique($grupos));
    }
}

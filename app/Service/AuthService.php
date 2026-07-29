<?php

namespace App\Service;

use App\Service\FuncionarioService;
use Throwable;
use App\Core\Logger;

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

        $ldapUrl = $_ENV['LDAP_URL'] ?? '';
        $domain = $_ENV['LDAP_DOMAIN'] ?? '';
        $searchBase = $_ENV['LDAP_SEARCHBASE'] ?? '';
        $nameAttr = $_ENV['LDAP_NAMEATTR'] ?? 'sAMAccountName';

        if (empty($ldapUrl) || empty($domain)) {
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

        $search = @ldap_search($ldapConn, $searchBase, "($nameAttr=$username)", ['cn', 'mail', $nameAttr]);
        if ($search) {
            $entries = ldap_get_entries($ldapConn, $search);
            if ($entries['count'] > 0) {
                try {
                    $func = FuncionarioService::findByNome($entries[0]['cn'][0]);
                } catch (Throwable $e) {
                    Logger::exception($e);

                    ldap_close($ldapConn);
                    return false;
                }

                $_SESSION['auth'] = [
                    'username' => $username,
                    'name' => $entries[0]['cn'][0] ?? $username,
                    'email' => $entries[0]['mail'][0] ?? '',
                    'logged_in_at' => date('Y-m-d H:i:s'),
                    'codfuncao' => $func['codfuncao'] ?? null,
                ];
            }
        }

        ldap_close($ldapConn);
        return true;
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return strtoupper($_SESSION['auth']['name']) ?? null;
    }

    public static function hasAnyRole(array $codFuncoes): bool
    {
        $codFuncao = $_SESSION['auth']['codfuncao'] ?? null;

        return in_array($codFuncao, $codFuncoes, true);
    }
}

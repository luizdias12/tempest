<?php

namespace App\Service;

use App\Core\Logger;
use GuzzleHttp\Client;
use Throwable;

class GraphService
{
    private static ?string $token = null;

    private static function env(string $key, string $default = ''): string
    {
        return getenv($key) ?: ($_ENV[$key] ?? $default);
    }

    public static function token(): ?string
    {
        if (self::$token !== null) {
            return self::$token;
        }

        $tenant   = self::env('AZURE_TENANT_ID');
        $clientId = self::env('AZURE_CLIENT_ID');
        $secret   = self::env('AZURE_CLIENT_SECRET');

        if ($tenant === '' || $clientId === '' || $secret === '') {
            Logger::error('GraphService: credenciais Azure não configuradas');
            return null;
        }

        try {
            $client   = new Client(['timeout' => 30]);
            $response = $client->post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
                'form_params' => [
                    'client_id'     => $clientId,
                    'client_secret' => $secret,
                    'scope'         => 'https://graph.microsoft.com/.default',
                    'grant_type'    => 'client_credentials',
                ],
            ]);

            $data               = json_decode((string) $response->getBody(), true);
            self::$token        = $data['access_token'] ?? null;

            return self::$token;
        } catch (Throwable $e) {
            Logger::exception($e);
            return null;
        }
    }

    private static function client(): Client
    {
        return new Client([
            'base_uri' => 'https://graph.microsoft.com/v1.0/',
            'headers'  => [
                'Authorization' => 'Bearer ' . self::token(),
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    public static function mensagensNaoLidas(string $mailbox, int $top = 50): array
    {
        try {
            $response = self::client()->get("users/{$mailbox}/mailFolders/Inbox/messages", [
                'query' => [
                    '$filter'   => 'isRead eq false',
                    '$select'   => 'id,subject,from,receivedDateTime,body,hasAttachments,bodyPreview,conversationId',
                    '$top'      => $top,
                    '$orderby'  => 'receivedDateTime desc',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            $itens = $data['value'] ?? [];
            return $itens;
        } catch (Throwable $e) {
            Logger::exception($e);
            return [];
        }
    }

    public static function anexos(string $mailbox, string $messageId): array
    {
        try {
            $response = self::client()->get("users/{$mailbox}/messages/{$messageId}/attachments", [
                'query' => [
                    '$select' => 'id,name,contentType,size,isInline,contentId',
                ],
            ]);
            $data     = json_decode((string) $response->getBody(), true);

            return $data['value'] ?? [];
        } catch (Throwable $e) {
            Logger::exception($e);
            return [];
        }
    }

    public static function baixarAnexo(string $mailbox, string $messageId, string $attachmentId): ?string
    {
        try {
            $response = self::client()->get("users/{$mailbox}/messages/{$messageId}/attachments/{$attachmentId}");
            $data     = json_decode((string) $response->getBody(), true);

            if (!isset($data['contentBytes'])) {
                return null;
            }

            return base64_decode($data['contentBytes']);
        } catch (Throwable $e) {
            Logger::exception($e);
            return null;
        }
    }

    public static function marcarLida(string $mailbox, string $messageId): bool
    {
        try {
            self::client()->patch("users/{$mailbox}/messages/{$messageId}", [
                'json' => ['isRead' => true],
            ]);

            return true;
        } catch (Throwable $e) {
            Logger::exception($e);
            return false;
        }
    }

    public static function pastaId(string $mailbox, string $nome): ?string
    {
        try {
            $response = self::client()->get("users/{$mailbox}/mailFolders", [
                'query' => [
                    '$filter' => "displayName eq '{$nome}'",
                    '$select' => 'id',
                    '$top'    => 1,
                ],
            ]);

            $data    = json_decode((string) $response->getBody(), true);
            $folders = $data['value'] ?? [];

            return $folders[0]['id'] ?? null;
        } catch (Throwable $e) {
            Logger::exception($e);
            return null;
        }
    }

    public static function criarPasta(string $mailbox, string $nome): ?string
    {
        try {
            $response = self::client()->post("users/{$mailbox}/mailFolders", [
                'json' => ['displayName' => $nome],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            return $data['id'] ?? null;
        } catch (Throwable $e) {
            Logger::exception($e);
            return null;
        }
    }

    public static function mover(string $mailbox, string $messageId, string $folderId): bool
    {
        try {
            self::client()->post("users/{$mailbox}/messages/{$messageId}/move", [
                'json' => ['destinationId' => $folderId],
            ]);

            return true;
        } catch (Throwable $e) {
            Logger::exception($e);
            return false;
        }
    }
}

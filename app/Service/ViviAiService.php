<?php

namespace App\Service;

use App\Core\ApiException;
use App\Core\Logger;
use GuzzleHttp\Client;
use Throwable;

/**
 * Cliente para provedores compatíveis com a API OpenAI
 * (Ollama, Groq, OpenRouter, DeepSeek e similares).
 */
class ViviAiService
{
    private static function env(string $chave, string $padrao = ''): string
    {
        return trim((string) (getenv($chave) ?: ($_ENV[$chave] ?? $padrao)));
    }

    public static function configurado(): bool
    {
        $base = self::env('VIVI_BASE_URL');
        $modelo = self::env('VIVI_MODEL');

        if ($base === '' || $modelo === '') {
            return false;
        }

        if (self::env('VIVI_API_KEY') !== '') {
            return true;
        }

        $host = strtolower((string) parse_url($base, PHP_URL_HOST));

        if ($host === 'localhost' || $host === '::1') {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return str_ends_with($host, '.local') || str_ends_with($host, '.internal');
    }

    public static function modelo(): string
    {
        return self::env('VIVI_MODEL');
    }

    public static function modeloEmbedding(): string
    {
        return self::env('VIVI_EMBED_MODEL');
    }

    /**
     * @param array<int, array{role: string, content: string}> $mensagens
     */
    public static function chat(array $mensagens, float $temperatura = 0.2, int $maxTokens = 700): string
    {
        $dados = self::post('/chat/completions', [
            'model' => self::modelo(),
            'messages' => $mensagens,
            'temperature' => $temperatura,
            'max_tokens' => max(50, $maxTokens),
            'stream' => false,
        ]);

        $texto = $dados['choices'][0]['message']['content'] ?? null;

        if (!is_string($texto) || trim($texto) === '') {
            throw new ApiException('A Vivi não conseguiu gerar uma resposta agora. Tente novamente.', 502);
        }

        return trim($texto);
    }

    /**
     * @param string[] $textos
     * @return array<int, float[]>
     */
    public static function embeddings(array $textos): array
    {
        if (self::modeloEmbedding() === '' || empty($textos)) {
            return [];
        }

        $dados = self::post('/embeddings', [
            'model' => self::modeloEmbedding(),
            'input' => array_values($textos),
        ], 20);

        $vetores = [];

        foreach (($dados['data'] ?? []) as $item) {
            $vetor = $item['embedding'] ?? null;

            if (!is_array($vetor) || empty($vetor)) {
                continue;
            }

            $indice = (int) ($item['index'] ?? count($vetores));
            $vetores[$indice] = array_map('floatval', $vetor);
        }

        ksort($vetores);

        return $vetores;
    }

    /**
     * @return array<string, mixed>
     */
    private static function post(string $caminho, array $payload, ?int $timeoutSegundos = null): array
    {
        if (!self::configurado()) {
            throw new ApiException('A Vivi ainda não foi configurada. Avise o suporte de TI.', 503);
        }

        $base = rtrim(self::env('VIVI_BASE_URL'), '/');
        $chave = self::env('VIVI_API_KEY');
        $timeout = $timeoutSegundos ?? max(5, (int) self::env('VIVI_TIMEOUT', '60'));

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($chave !== '') {
            $headers['Authorization'] = 'Bearer ' . $chave;
        }

        try {
            $client = new Client([
                'timeout' => $timeout,
                'connect_timeout' => 5,
            ]);

            $resposta = $client->post($base . $caminho, [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false,
            ]);

            $status = $resposta->getStatusCode();
            $corpo = json_decode((string) $resposta->getBody(), true);

            if ($status >= 400) {
                Logger::warning('Vivi: provedor de IA retornou erro', [
                    'status' => $status,
                    'corpo' => is_array($corpo) ? ($corpo['error']['message'] ?? null) : null,
                ]);

                if ($status === 429) {
                    $retry = max(1, (int) ($resposta->getHeaderLine('retry-after') ?: 30));

                    throw new ApiException(
                        'A Vivi atingiu o limite de uso do provedor de IA. Aguarde alguns instantes e tente novamente.',
                        429,
                        ['retry_after' => $retry]
                    );
                }

                throw new ApiException('A Vivi está temporariamente indisponível. Tente novamente em instantes.', 503);
            }

            if (!is_array($corpo)) {
                throw new ApiException('Resposta inválida do provedor de IA.', 502);
            }

            return $corpo;
        } catch (ApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            Logger::exception($e, ['servico' => 'ViviAiService', 'caminho' => $caminho]);

            throw new ApiException('Não foi possível falar com a Vivi agora. Tente novamente em instantes.', 503);
        }
    }
}

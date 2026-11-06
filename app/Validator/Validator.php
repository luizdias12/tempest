<?php

namespace App\Validator;

use App\Core\ApiException;
use LogicException;

class Validator
{
    public static function make(array $data, array $rules, array $labels = []): void
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $fieldName = $labels[$field] ?? $field;

            foreach (explode('|', $ruleString) as $rule) {
                $param = null;

                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                if ($rule === 'nullable' && self::isEmpty($value)) {
                    continue 2;
                }

                $erro = match ($rule) {
                    'nullable' => null,
                    'required' => self::regraRequired($value, $fieldName),
                    'string' => self::regraString($value, $fieldName),
                    'email' => self::regraEmail($value, $fieldName),
                    'min' => self::regraMin($value, $param, $fieldName),
                    'max' => self::regraMax($value, $param, $fieldName),
                    'numeric' => self::regraNumeric($value, $fieldName),
                    'integer' => self::regraInteger($value, $fieldName),
                    'boolean' => self::regraBoolean($value, $fieldName),
                    'in' => self::regraIn($value, $param, $fieldName),
                    'date' => self::regraDate($value, $fieldName),
                    'url' => self::regraUrl($value, $fieldName),
                    'regex' => self::regraRegex($value, $param, $fieldName),
                    'cpf' => self::regraCpf($value, $fieldName),
                    default => throw new LogicException("Regra de validação desconhecida: {$rule}."),
                };

                if ($erro !== null) {
                    $errors[$field][] = $erro;
                }
            }
        }

        if (!empty($errors)) {
            throw new ApiException('Dados inválidos', 422, $errors);
        }
    }

    private static function regraRequired($value, string $fieldName): ?string
    {
        return self::isEmpty($value) ? "O campo {$fieldName} é obrigatório." : null;
    }

    private static function regraString($value, string $fieldName): ?string
    {
        if (!self::isEmpty($value) && !is_string($value)) {
            return "O campo {$fieldName} deve ser um texto.";
        }

        return null;
    }

    private static function regraEmail($value, string $fieldName): ?string
    {
        if (!self::isEmpty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "O campo {$fieldName} deve ser um e-mail válido.";
        }

        return null;
    }

    private static function regraMin($value, ?string $param, string $fieldName): ?string
    {
        $min = self::parametroInteiro($param, 'min');

        if (self::isEmpty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value < $min
                ? "O campo {$fieldName} deve ser no mínimo {$min}."
                : null;
        }

        return mb_strlen((string) $value) < $min
            ? "O campo {$fieldName} deve ter no mínimo {$min} caracteres."
            : null;
    }

    private static function regraMax($value, ?string $param, string $fieldName): ?string
    {
        $max = self::parametroInteiro($param, 'max');

        if (self::isEmpty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value > $max
                ? "O campo {$fieldName} deve ser no máximo {$max}."
                : null;
        }

        return mb_strlen((string) $value) > $max
            ? "O campo {$fieldName} deve ter no máximo {$max} caracteres."
            : null;
    }

    private static function regraNumeric($value, string $fieldName): ?string
    {
        if (!self::isEmpty($value) && !is_numeric($value)) {
            return "O campo {$fieldName} deve ser numérico.";
        }

        return null;
    }

    private static function regraInteger($value, string $fieldName): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return "O campo {$fieldName} deve ser um número inteiro.";
        }

        return null;
    }

    private static function regraBoolean($value, string $fieldName): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        $validos = [true, false, 0, 1, '0', '1'];

        if (!in_array($value, $validos, true)) {
            return "O campo {$fieldName} deve ser um valor booleano.";
        }

        return null;
    }

    private static function regraIn($value, ?string $param, string $fieldName): ?string
    {
        if ($param === null || $param === '') {
            throw new LogicException("A regra 'in' exige valores separados por vírgula.");
        }

        if (self::isEmpty($value)) {
            return null;
        }

        $opcoes = explode(',', $param);

        if (!in_array((string) $value, $opcoes, false)) {
            $lista = implode(', ', $opcoes);

            return "O campo {$fieldName} deve ser um dos seguintes valores: {$lista}.";
        }

        return null;
    }

    private static function regraDate($value, string $fieldName): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!is_string($value)) {
            return "O campo {$fieldName} deve ser uma data válida no formato Y-m-d.";
        }

        $data = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $erros = \DateTimeImmutable::getLastErrors();

        if ($data === false || ($erros !== false && ($erros['warning_count'] > 0 || $erros['error_count'] > 0)) || $data->format('Y-m-d') !== $value) {
            return "O campo {$fieldName} deve ser uma data válida no formato Y-m-d.";
        }

        return null;
    }

    private static function regraUrl($value, string $fieldName): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_URL) || !str_starts_with($value, 'https://')) {
            return "O campo {$fieldName} deve ser uma URL válida começando com https://.";
        }

        return null;
    }

    private static function regraRegex($value, ?string $param, string $fieldName): ?string
    {
        if ($param === null || $param === '') {
            throw new LogicException("A regra 'regex' exige um padrão, no formato regex:/padrão/.");
        }

        if (self::isEmpty($value)) {
            return null;
        }

        $padrao = str_starts_with($param, '/') ? $param : "/{$param}/";

        $resultado = @preg_match($padrao, (string) $value);

        if ($resultado === false) {
            throw new LogicException("Padrão de regex inválido: {$padrao}.");
        }

        if ($resultado !== 1) {
            return "O campo {$fieldName} não possui um formato válido.";
        }

        return null;
    }

    private static function regraCpf($value, string $fieldName): ?string
    {
        if (!self::isEmpty($value) && !self::isValidCpf((string) $value)) {
            return "O campo {$fieldName} deve conter um CPF válido.";
        }

        return null;
    }

    private static function parametroInteiro(?string $param, string $regra): int
    {
        if ($param === null || filter_var($param, FILTER_VALIDATE_INT) === false) {
            throw new LogicException("A regra '{$regra}' exige um número inteiro como parâmetro.");
        }

        return (int) $param;
    }

    private static function isEmpty($value): bool
    {
        return $value === null || $value === '';
    }

    public static function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += (int) $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf[$c] !== $d) {
                return false;
            }
        }

        return true;
    }
}

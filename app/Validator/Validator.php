<?php

namespace App\Validator;

use App\Core\ApiException;

class Validator
{
    public static function make(array $data, array $rules, array $labels = []): void
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $fieldName = $labels[$field] ?? $field;
            $ruleList = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                $param = null;

                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                switch ($rule) {
                    case 'nullable':
                        if(self::isEmpty($value)) {
                            continue 2; // Pula para a próxima regra do campo
                        }
                        break;
                    case 'required':
                        if (self::isEmpty($value)) {
                            $errors[$field][] = "O campo {$fieldName} é obrigatório.";
                        }
                        break;

                    case 'string':
                        if (!self::isEmpty($value) && !is_string($value)) {
                            $errors[$field][] = "O campo {$fieldName} deve ser um texto.";
                        }
                        break;

                    case 'email':
                        if (!self::isEmpty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "O campo {$fieldName} deve ser um e-mail válido.";
                        }
                        break;

                    case 'min':
                        if (!self::isEmpty($value) && mb_strlen((string) $value) < (int) $param) {
                            $errors[$field][] = "O campo {$fieldName} deve ter no mínimo {$param} caracteres.";
                        }
                        break;

                    case 'max':
                        if (!self::isEmpty($value) && mb_strlen((string) $value) > (int) $param) {
                            $errors[$field][] = "O campo {$fieldName} deve ter no máximo {$param} caracteres.";
                        }
                        break;

                    case 'numeric':
                        if (!self::isEmpty($value) && !is_numeric($value)) {
                            $errors[$field][] = "O campo {$fieldName} deve ser numérico.";
                        }
                        break;
                    case 'cpf':
                        if (!self::isEmpty($value) && !self::isValidCpf($value)) {
                            $errors[$field][] = "O campo {$fieldName} deve conter um CPF válido.";
                        }
                        break;
                }
            }
        }

        if (!empty($errors)) {
            throw new ApiException('Dados inválidos', 422, $errors);
        }
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

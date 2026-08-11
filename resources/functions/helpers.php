<?php

if (!function_exists('dd')) {
    function dd(mixed ...$values): never
    {
        echo '<pre style="
            background: #1e1e1e;
            color: #08CB00;
            padding: 16px;
            border-radius: 8px;
            font-family: Consolas, monospace;
            font-size: 14px;
            line-height: 1.5;
            overflow:auto;
        ">';

        foreach ($values as $value) {
            print_r($value);
            echo "\n\n";
        }

        echo '</pre>';
        die();
    }
}

if (!function_exists('dump')) {
    function dump(mixed ...$values): void
    {
        echo '<pre style="
            background: #1e1e1e;
            color: #8CE4FF;
            padding: 16px;
            border-radius: 8px;
            font-family: Consolas, monospace;
            font-size: 14px;
            line-height: 1.5;
            overflow:auto;
        ">';

        foreach ($values as $value) {
            var_dump($value);
            echo "\n";
        }

        echo '</pre>';
    }
}

if (!function_exists('dumper')) {
    function dumper(mixed $value): void
    {
        echo '<pre>';
        print_r($value);
        echo '</pre>';
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): never
    {
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('basePath')) {
    function basePath(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('view')) {
    function view(string $view, array $params = [], string $layout = 'layouts/main'): void
    {
        extract($params, EXTR_SKIP);

        $viewPath = basePath("app/View/{$view}.php");
        $layoutPath = basePath("app/View/{$layout}.php");

        if (!file_exists($viewPath)) {
            die("View '{$view}' não encontrada.");
        }

        if (!file_exists($layoutPath)) {
            die("Layout '{$layout}' não encontrado.");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require $layoutPath;
    }
}

if (!function_exists('component')) {
    function component(string $component, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require basePath("app/View/components/{$component}.php");
    }
}

if (!function_exists('renderComponent')) {
    function renderComponent(string $component, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require basePath("app/View/components/{$component}.php");
        return ob_get_clean();
    }
}

if (!function_exists('partial')) {
    function partial(string $partial, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require basePath("app/View/partials/{$partial}.php");
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $file = basePath("public/" . ltrim($path, '/'));
        $v = file_exists($file) ? '?v=' . filemtime($file) : '';
        return '/' . ltrim($path, '/') . $v;
    }
}


if (!function_exists('jsonResponse')) {
    function jsonResponse(array $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        die();
    }
}

if (!function_exists('buildPaginationUrl')) {
    function buildPaginationUrl(int $page, ?string $baseUrl = null, array $extraQuery = []): string
    {
        // pega URL base sem query
        $url = $baseUrl ?: strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

        // pega query atual
        parse_str($_SERVER['QUERY_STRING'] ?? '', $currentQuery);

        // remove page antigo
        unset($currentQuery['page']);
        unset($currentQuery['open']);

        // monta nova query
        $query = array_merge($currentQuery, $extraQuery, [
            'page' => $page
        ]);

        return $url . '?' . http_build_query($query);
    }
}

if (!function_exists('pagination')) {
    function pagination(array $meta, ?string $baseUrl = null, array $extraQuery = []): string
    {
        $baseUrl = $baseUrl ?? ($_SERVER['REQUEST_URI'] ?? '');
        $baseUrl = strtok($baseUrl, '?');
        ob_start();
        component('pagination', [
            'meta' => $meta,
            'baseUrl' => $baseUrl,
            'extraQuery' => $extraQuery
        ]);
        return ob_get_clean();
    }
}

if (!function_exists('maskCpf')) {
    function maskCpf(string $cpf): string
    {
        $digits = preg_replace('/\D/', '', $cpf);

        if (strlen($digits) !== 11) {
            return $cpf;
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);
    }
}

if (!function_exists('businessHoursBetween')) {
    function businessHoursBetween(string|DateTimeInterface $start, string|DateTimeInterface|null $end = null): float
    {
        $inicio = $start instanceof DateTimeInterface ? (clone $start) : new DateTime($start);
        $fim = $end ? ($end instanceof DateTimeInterface ? (clone $end) : new DateTime($end)) : new DateTime();

        if ($fim <= $inicio) {
            return 0.0;
        }

        $abreDia = 8;
        $fechaDia = 18;
        $almocoInicio = 12;
        $almocoFim = 13;

        $total = 0.0;
        $dia = clone $inicio;
        $dia->setTime(0, 0);
        $ultimoDia = clone $fim;
        $ultimoDia->setTime(0, 0);

        while ($dia <= $ultimoDia) {
            if ((int) $dia->format('N') < 6) {
                $abre = (clone $dia)->setTime($abreDia, 0);
                $fecha = (clone $dia)->setTime($fechaDia, 0);
                $almIni = (clone $dia)->setTime($almocoInicio, 0);
                $almFim = (clone $dia)->setTime($almocoFim, 0);

                $inicioDia = $inicio > $abre ? $inicio : $abre;
                $fimDia = $fim < $fecha ? $fim : $fecha;

                if ($fimDia > $inicioDia) {
                    $total += $fimDia->getTimestamp() - $inicioDia->getTimestamp();

                    $iniAlmoco = $inicioDia > $almIni ? $inicioDia : $almIni;
                    $fimAlmoco = $fimDia < $almFim ? $fimDia : $almFim;

                    if ($fimAlmoco > $iniAlmoco) {
                        $total -= $fimAlmoco->getTimestamp() - $iniAlmoco->getTimestamp();
                    }
                }
            }

            $dia->modify('+1 day');
        }

        return round($total / 3600, 2);
    }
}

if (!function_exists('formatBusinessHours')) {
    function formatBusinessHours(float $hours): string
    {
        $totalMin = (int) round($hours * 60);
        $h = intdiv($totalMin, 60);
        $m = $totalMin % 60;

        if ($h > 0 && $m > 0) {
            return "{$h}h {$m}min";
        }

        return $h > 0 ? "{$h}h" : "{$m}min";
    }
}

if (!function_exists('initcap')) {
    function initcap(string $value): string
    {
        $words = explode(' ', $value);
        $lowercase = ['de', 'da', 'do', 'das', 'dos', 'e'];

        foreach ($words as $i => $word) {
            $lower = mb_strtolower($word);
            if ($i === 0 || $i === count($words) - 1 || !in_array($lower, $lowercase)) {
                $words[$i] = mb_convert_case($lower, MB_CASE_TITLE);
            } else {
                $words[$i] = $lower;
            }
        }

        return implode(' ', $words);
    }
}

if (!function_exists('removeAccents')) {
    function removeAccents(string $string): string
    {
        $unwantedArray = [
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c',
        ];

        return str_replace(array_keys($unwantedArray), array_values($unwantedArray), $string);
    }
}

if (!function_exists('localPorIp')) {
    function localPorIp(string $ip): string
    {
        $locals = [
            '0' => 'Dulce Sarmento',
            '1' => 'Dulce Sarmento',
            '2' => 'CD',
            '3' => 'CD',
            '4' => 'Tito Fulgencio',
            '5' => 'Juiz de Fora',
            '6' => 'Ceanorte',
            '7' => 'Coronel Fabriciano',
            '8' => 'Vaz de Melo',
            '9' => 'Brigadeiro',
            '10' => 'Justinópolis',
            '11' => 'Divinópolis',
            '12' => 'Pedro II',
            '13' => 'Joaquim Jose',
            '14' => 'Tropical',
            '15' => 'Jaraguá',
            '16' => 'Humberto de Moro',
            '17' => 'BR-040',
            '18' => 'Serrano',
            '19' => 'Santa Luzia',
            '20' => 'Ceasa',
            '21' => 'Ceasa',
            '22' => 'Jataí',
            '23' => 'Itabira',
            '24' => 'Cula Managabeira',
            '25' => 'Ipatinga',
            '26' => 'Betania',
            '27' => 'Sion',
            '28' => 'Américo Vespúcio',
            '29' => 'Uberaba',
            '30' => 'Sabará',
            '31' => 'Bosque - Escritorio',
            '44' => 'Jacuí',
            '45' => 'Bosque',
            '32' => 'Poços de Caldas',
            '33' => 'Lavras',
            '34' => 'Paracatu',
            '35' => 'Patrocínio',
            '36' => 'Anápolis',
            '37' => 'Uberlândia',
            '38' => 'Vila Jaraguá',
            '39' => 'Uberville',
            '40' => 'Anápolis 2',
            '100' => 'Arvoredo - VM',
            '101' => 'Arvoredo - VM',
            '112' => 'Pedro II - Callcenter',
            '200' => 'Arvoredo - Bloco Verde',
            '201' => 'Arvoredo - Bloco Amarelo',
            '202' => 'Arvoredo - Bloco Vermelho',
            '203' => 'Arvoredo - Central',
            '204' => 'Arvoredo - Galpao',
            '210' => 'Arvoredo - Presidencia',
        ];

        $octetos = explode('.', $ip);

        return $locals[$octetos[2] ?? ''] ?? 'Nao Identificado';
    }
}

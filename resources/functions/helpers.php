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

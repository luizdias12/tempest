<?php

namespace App\Core;

use Closure;
use Throwable;
use App\Core\Request;
use App\Core\Response;

class Router
{
    private array $routes = [];
    private array $middlewareAliases = [];
    private string $currentPrefix = '';
    private array $currentMiddlewares = [];
    private bool $currentIsApi = false;

    public function get(
        string $path,
        callable|array $action,
        array $middlewares = []
    ): void {
        $this->addRoute('GET', $path, $action, $middlewares);
    }

    public function post(
        string $path,
        callable|array $action,
        array $middlewares = []
    ): void {
        $this->addRoute('POST', $path, $action, $middlewares);
    }

    public function put(
        string $path,
        callable|array $action,
        array $middlewares = []
    ): void {
        $this->addRoute('PUT', $path, $action, $middlewares);
    }

    public function delete(
        string $path,
        callable|array $action,
        array $middlewares = []
    ): void {
        $this->addRoute('DELETE', $path, $action, $middlewares);
    }

    private function addRoute(
        string $method,
        string $path,
        callable|array $action,
        array $middlewares = []
    ): void {
        $fullPath = $this->normalizePath($this->currentPrefix . $path);

        $middlewares = array_merge($this->currentMiddlewares, $middlewares);

        $this->routes[$method][$fullPath] = [
            'action' => $action,
            'middlewares' => $middlewares,
            'isApi' => $this->currentIsApi
        ];
    }

    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $previousPrefix = $this->currentPrefix;
        $previousMiddlewares = $this->currentMiddlewares ?? [];
        $previousIsApi = $this->currentIsApi;

        $this->currentPrefix .= $prefix;
        $this->currentMiddlewares = array_merge($previousMiddlewares, $middlewares);

        if (str_starts_with($this->normalizePath($this->currentPrefix), '/api')) {
            $this->currentIsApi = true;
        }

        $callback($this);

        $this->currentPrefix = $previousPrefix;
        $this->currentMiddlewares = $previousMiddlewares;
        $this->currentIsApi = $previousIsApi;
    }

    public function dispatch(string $uri, string $httpMethod): void
    {
        try {
            $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?? '/');
            $routes = $this->routes[$httpMethod] ?? [];

            foreach ($routes as $route => $config) {
                $pattern = $this->convertRouteToRegex($route);

                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches);

                    $matches = array_map('rawurldecode', $matches);

                    $request = new Request();
                    $request->setIsApi($config['isApi'] ?? false);

                    $this->runMiddlewares($config['middlewares'] ?? [], $request);
                    $this->execute($config['action'], $request, $matches);

                    return;
                }
            }

            ErrorHandler::handle(
                404,
                'Página não encontrada',
                str_starts_with($path, '/api')
            );
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(
                500,
                'Erro interno do servidor',
                str_starts_with($path ?? '', '/api')
            );
        }
    }

    public function aliasMiddleware(string $alias, string $class): void
    {
        $this->middlewareAliases[$alias] = $class;
    }

    private function runMiddlewares(
        array $middlewares,
        Request $request
    ): void {
        foreach ($middlewares as $middleware) {
            if (isset($this->middlewareAliases[$middleware])) {
                $middleware = $this->middlewareAliases[$middleware];
            }

            if (!class_exists($middleware)) {
                throw new \Exception("Middleware não encontrado: {$middleware}");
            }

            $instance = new $middleware();

            if (!method_exists($instance, 'handle')) {
                throw new \Exception("Middleware inválido: {$middleware}");
            }

            $result = $instance->handle($request);

            if ($result === false) {
                exit;
            }
        }
    }

    private function execute(
        callable|array $action,
        Request $request,
        array $params = []
    ): void {
        if (is_array($action)) {
            [$controller, $controllerMethod] = $action;

            if (!class_exists($controller)) {
                ErrorHandler::handle(500, 'Controller não encontrado', $request->isApi());
                return;
            }

            $controllerInstance = new $controller();

            if (!method_exists($controllerInstance, $controllerMethod)) {
                ErrorHandler::handle(500, 'Método do controller não encontrado', $request->isApi());
                return;
            }

            $response = $controllerInstance->$controllerMethod($request, ...$params);

            if (is_array($response)) {
                Response::json($response);
            }

            return;
        }

        if ($action instanceof Closure || is_callable($action)) {
            $response = $action(...$params);

            if (is_array($response)) {
                Response::json($response);
                return;
            }

            if ($response !== null) {
                echo $response;
            }

            return;
        }

        ErrorHandler::handle(500, 'Ação de rota inválida', $request->isApi());
        return;
    }

    private function convertRouteToRegex(string $route): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $route);
        return '#^' . $pattern . '$#';
    }

    private function normalizePath(string $path): string
    {
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path ?: '/';
    }
}

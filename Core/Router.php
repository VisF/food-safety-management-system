<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(
        string $uri,
        array $action,
        array $middlewares = []
    ): void
    {
        $this->routes['GET'][$uri] = [
            'action' => $action,
            'middlewares' => $middlewares
        ];
    }

    public function post(
        string $uri,
        array $action,
        array $middlewares = []
    ): void
    {
        $this->routes['POST'][$uri] = [
            'action' => $action,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch(
        string $uri,
        string $method
    ): void {

        if (
            !isset($this->routes[$method][$uri])
        ) {
            http_response_code(404);
            exit('Ruta no encontrada');
        }

        $route =
            $this->routes[$method][$uri];

        foreach (
            $route['middlewares']
            as $middleware
        ) {

            $class =
                "App\\Middleware\\{$middleware}";

            $class::handle();
        }

        [$controller, $action]
            = $route['action'];

        $instance =
            new $controller();

        $instance->$action();
    }
}
<?php

namespace App\Core;

class Middleware
{
    public static function run(
        string $middleware,
        array $params = []
    ): void {

        $class =
            "App\\Middleware\\{$middleware}";

        if (!class_exists($class)) {
            throw new \Exception(
                "Middleware {$middleware} inexistente"
            );
        }

        call_user_func_array(
            [$class, 'handle'],
            $params
        );
    }
}
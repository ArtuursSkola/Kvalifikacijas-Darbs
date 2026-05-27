<?php

require_once __DIR__ . '/main.php';

function admin_routes(): array
{
    static $routes = [
        'dashboard' => 'Admin/index.php',
        'users' => 'Admin/lietotaji.php',
        'users_legacy' => 'Admin/lietotaji_new.php',
        'listings' => 'Admin/sludinajumi.php',
        'listings_legacy' => 'Admin/sludinajumi_new.php',
        'moderators' => 'Admin/moderatori.php',
        'palidziba' => 'Admin/palidziba.php',
        'subscription_dashboard' => 'Admin/subscription_dashboard.php',
        'statistics' => 'Admin/statistics.php',
        'login' => 'Admin/login.php',
    ];

    return $routes;
}

function admin_route(string $name, array $query = []): string
{
    $routes = admin_routes();
    if (!isset($routes[$name])) {
        throw new InvalidArgumentException("Unknown admin route: {$name}");
    }

    return app_url($routes[$name], $query);
}

function admin_redirect(string $name, array $query = []): never
{
    header('Location: ' . admin_route($name, $query));
    exit;
}

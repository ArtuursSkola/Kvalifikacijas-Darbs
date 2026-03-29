<?php

function app_relative_url(string $targetPath): string
{
    $normalizedTarget = trim(str_replace('\\', '/', $targetPath), '/');
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = trim(dirname($scriptName), '/.');

    $fromParts = $scriptDir === '' ? [] : explode('/', $scriptDir);
    $toParts = $normalizedTarget === '' ? [] : explode('/', $normalizedTarget);

    while (!empty($fromParts) && !empty($toParts) && $fromParts[0] === $toParts[0]) {
        array_shift($fromParts);
        array_shift($toParts);
    }

    $relativeParts = array_merge(array_fill(0, count($fromParts), '..'), $toParts);
    $relativePath = implode('/', $relativeParts);

    return $relativePath === '' ? './' : $relativePath;
}

function app_url(string $path, array $query = []): string
{
    $url = app_relative_url($path);
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function app_absolute_url(string $path, array $query = []): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $projectRoot = realpath(dirname(__DIR__));
    $scriptFilename = realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = '/';

    if ($projectRoot && $scriptFilename && str_starts_with($scriptFilename, $projectRoot)) {
        $relativeScript = trim(str_replace('\\', '/', substr($scriptFilename, strlen($projectRoot))), '/');
        if ($relativeScript !== '' && str_ends_with($scriptName, $relativeScript)) {
            $basePath = substr($scriptName, 0, -strlen($relativeScript));
        }
    }

    $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    $url = $scheme . '://' . $host . ($basePath === '' ? '' : $basePath) . '/' . ltrim($path, '/');

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function asset_path(string $path): string
{
    return app_url($path);
}

function main_routes(): array
{
    static $routes = [
        'home' => 'index.php',
        'about' => 'pages/about.php',
        'owner' => 'pages/owner.php',
        'property.list' => 'pages/property/homes.php',
        'property.show' => 'pages/property/home.php',
        'property.create' => 'pages/property/newhome.php',
        'property.myhomes' => 'pages/property/myhomes.php',
        'login' => 'login/login.php',
        'register' => 'login/register.php',
        'logout' => 'login/logout.php',
        'account.settings_page' => 'pages/settings.php',
        'account.settings' => 'account/update_profile.php',
        'account.password' => 'account/change_password.php',
        'payment.checkout' => 'payments/checkout.php',
        'payment.checkout_offer' => 'payments/checkout_offer.php',
        'payment.success' => 'payments/success.php',
        'api.homes' => 'api/get_homes.php',
    ];

    return $routes;
}

function main_route(string $name, array $query = []): string
{
    $routes = main_routes();
    if (!isset($routes[$name])) {
        throw new InvalidArgumentException("Unknown main route: {$name}");
    }

    return app_url($routes[$name], $query);
}

function main_route_absolute(string $name, array $query = []): string
{
    $routes = main_routes();
    if (!isset($routes[$name])) {
        throw new InvalidArgumentException("Unknown main route: {$name}");
    }

    return app_absolute_url($routes[$name], $query);
}

function main_redirect(string $name, array $query = []): never
{
    header('Location: ' . main_route($name, $query));
    exit;
}

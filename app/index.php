<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Controller\AuthController;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Support\Database;

try {
    // Wiring (manual DI): create each dependency once and pass it in
    $pdo  = Database::connect();
    $auth = new AuthController(
        new AuthService(new UserRepository($pdo), new TokenRepository($pdo))
    );

    $router = new Router();
    /** To verify quickly if the app is running */
    $router->add('GET', '/health', fn () => Response::json(['status' => 'ok']));
    $router->add('POST', '/register', [$auth, 'register']);
    $router->add('POST', '/login', [$auth, 'login']);
    $router->add('POST', '/logout', [$auth, 'logout']);

    $response = $router->dispatch(Request::fromGlobals());

} catch (HttpException $e) {
    $response = Response::json(['error' => $e->getMessage()], $e->status);
} catch (Throwable $e) {
    error_log((string) $e);                                   // details go to the log only
    $response = Response::json(['error' => 'Internal server error'], 500);
}

$response->send();
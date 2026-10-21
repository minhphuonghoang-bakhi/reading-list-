<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

try {
    $router = new Router();
    /** To verify quickly if the app is running */
    $router->add('GET', '/health', fn () => Response::json(['status' => 'ok']));

    $response = $router->dispatch(Request::fromGlobals());
} catch (HttpException $e) {
    $response = Response::json(['error' => $e->getMessage()], $e->status);
} catch (Throwable $e) {
    error_log((string) $e);                                   // details go to the log only
    $response = Response::json(['error' => 'Internal server error'], 500);
}

$response->send();
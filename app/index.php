<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

// Never print PHP errors into the response; they go to the log instead
ini_set('display_errors', '0');

// Turn warnings and notices into exceptions, so they are handled like any other error (500 + log)
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

use App\Controller\AuthController;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Support\Database;
use App\Controller\BookController;
use App\Repository\BookRepository;
use App\Repository\TagRepository;
use App\Service\Authenticator;
use App\Service\BookService;

try {
    // Wiring (manual DI): create each dependency once and pass it in
    $pdo  = Database::connect();
    $tokens = new TokenRepository($pdo); // used for AuthService + Authenticator
    $auth = new AuthController(
        new AuthService(new UserRepository($pdo), new TokenRepository($pdo))
    );
    $books = new BookController(
        new BookService($pdo, new BookRepository($pdo), new TagRepository($pdo)),
        new Authenticator($tokens),
    );
    $router = new Router();
    /** To verify quickly if the app is running */
    $router->add('GET', '/health', fn () => Response::json(['status' => 'ok']));
    $router->add('POST', '/register', [$auth, 'register']);
    $router->add('POST', '/login', [$auth, 'login']);
    $router->add('POST', '/logout', [$auth, 'logout']);
    $router->add('POST', '/books', [$books, 'create']);
    $router->add('GET', '/books', [$books, 'list']);
    $router->add('PUT', '/books/{id}', [$books, 'update']);
    $router->add('PATCH', '/books/{id}/status', [$books, 'status']);
    $router->add('DELETE', '/books/{id}', [$books, 'delete']);

    $response = $router->dispatch(Request::fromGlobals());

} catch (HttpException $e) {
    $response = Response::json(['error' => $e->getMessage()], $e->status);
} catch (Throwable $e) {
    error_log((string) $e);                                   // details go to the log only
    $response = Response::json(['error' => 'Internal server error'], 500);
}

$response->send();
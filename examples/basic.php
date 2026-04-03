<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Xpress\XRouter;
use Xpress\XRequest;
use Xpress\XResponse;
use Xpress\Attributes\{XRoute, XMiddleware, XGroup};

#[XGroup('/api/v1')]
class UserController
{
    #[XRoute('/users', 'GET', name: 'user.index')]
    public function index(XRequest $request): ResponseInterface
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Doe', 'email' => 'jane@example.com'],
        ];

        return (new XResponse())->json([
            'data' => $users,
            'total' => count($users)
        ]);
    }

    #[XRoute('/users/{id}', 'GET', name: 'user.show')]
    public function show(XRequest $request, array $params): ResponseInterface
    {
        $id = $params['id'];

        if ($id > 100) {
            return (new XResponse())->json([
                'error' => 'User not found'
            ], 404);
        }

        return (new XResponse())->json([
            'data' => [
                'id' => (int) $id,
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]
        ]);
    }

    #[XRoute('/users', 'POST', name: 'user.store')]
    #[XMiddleware(AuthMiddleware::class)]
    public function store(XRequest $request): ResponseInterface
    {
        $data = $request->getJson();

        if (empty($data['name']) || empty($data['email'])) {
            return (new XResponse())->json([
                'error' => 'Name and email are required'
            ], 422);
        }

        return (new XResponse())->json([
            'data' => [
                'id' => rand(100, 1000),
                'name' => $data['name'],
                'email' => $data['email']
            ]
        ], 201);
    }

    #[XRoute('/users/{id}', 'PUT', name: 'user.update')]
    #[XMiddleware([AuthMiddleware::class, RateLimitMiddleware::class])]
    public function update(XRequest $request, array $params): ResponseInterface
    {
        $id = $params['id'];
        $data = $request->getJson();

        return (new XResponse())->json([
            'data' => [
                'id' => (int) $id,
                'name' => $data['name'] ?? 'Updated Name',
                'email' => $data['email'] ?? 'updated@example.com'
            ],
            'message' => 'User updated successfully'
        ]);
    }

    #[XRoute('/users/{id}', 'DELETE', name: 'user.destroy')]
    #[XMiddleware(AuthMiddleware::class)]
    public function destroy(XRequest $request, array $params): ResponseInterface
    {
        return (new XResponse())->json([
            'message' => 'User deleted successfully'
        ], 200);
    }
}

#[XGroup('/api/v1/posts')]
class PostController
{
    #[XRoute('/posts', 'GET', name: 'post.index')]
    public function index(XRequest $request): ResponseInterface
    {
        return (new XResponse())->json([
            'data' => [
                ['id' => 1, 'title' => 'Hello World', 'author' => 'John'],
                ['id' => 2, 'title' => 'Laravel Tips', 'author' => 'Jane'],
            ]
        ]);
    }

    #[XRoute('/posts/{postId}/comments/{commentId}', 'GET', name: 'post.comment')]
    public function getComment(XRequest $request, array $params): ResponseInterface
    {
        return (new XResponse())->json([
            'data' => [
                'post_id' => $params['postId'],
                'comment_id' => $params['commentId'],
                'text' => 'This is a comment'
            ]
        ]);
    }
}

class AuthMiddleware
{
    public function handle(XRequest $request, callable $next): ResponseInterface
    {
        $token = $request->getHeader('Authorization');

        if (empty($token)) {
            return (new XResponse())->json([
                'error' => 'Authentication required',
                'message' => 'Please provide an Authorization header'
            ], 401);
        }

        if (!str_starts_with($token, 'Bearer ')) {
            return (new XResponse())->json([
                'error' => 'Invalid token format'
            ], 401);
        }

        return $next($request);
    }
}

class RateLimitMiddleware
{
    private static array $requests = [];

    public function handle(XRequest $request, callable $next): ResponseInterface
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $key = $ip . ':' . date('Y-m-d:H:i');

        if (!isset(self::$requests[$key])) {
            self::$requests[$key] = 0;
        }

        self::$requests[$key]++;

        if (self::$requests[$key] > 10) {
            return (new XResponse())->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => 60
            ], 429);
        }

        $response = $next($request);

        return $response->withHeader('X-RateLimit-Limit', '10')
                        ->withHeader('X-RateLimit-Remaining', (string) (10 - self::$requests[$key]));
    }
}

class CorsMiddleware
{
    public function handle(XRequest $request, callable $next): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            return (new XResponse())
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->withHeader('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }
}

$router = new XRouter();
$router->use(CorsMiddleware::class);
$router->registerControllers([UserController::class, PostController::class]);

$router->get('/hello', function (XRequest $req) {
    $name = $req->getQuery('name', 'World');
    return (new XResponse())->json([
        'message' => "Hello, {$name}!",
        'path' => $req->getPath(),
        'method' => $req->getMethod()
    ]);
});

$router->post('/echo', function (XRequest $req) {
    return (new XResponse())->json([
        'echo' => $req->getJson() ?? $req->getBody()
    ]);
});

try {
    $response = $router->dispatch();
    $response->send();
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

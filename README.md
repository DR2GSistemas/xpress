# Xpress - Router REST API para PHP 8.4

Xpress es un router ligero para crear APIs REST con PHP 8.4, inspirado en la sintaxis de Express.js para Node.js. Utiliza atributos PHP para definir rutas de forma limpia y declarativa.

## Características

- **Sintaxis estilo Express.js** - API familiar para desarrolladores de Node.js
- **Atributos PHP 8.4** - Definiciones de rutas limpias y declarativas
- **Sistema de middlewares encadenables** - Auth, logging, CORS, rate limiting, etc.
- **Compatible con PSR-7** - Totalmente compatible con el estándar de mensajes HTTP
- **Mínimo de dependencias** - Solo requiere `psr/http-message` y `guzzlehttp/psr7`
- **Helpers para respuestas rápidas** - Funciones globales para json(), text(), html(), etc.

## Requisitos

- PHP 8.4 o superior
- `psr/http-message: ^2.0`
- `guzzlehttp/psr7: ^2.0`

## Instalación

```bash
composer require dr2gsistemas/xpress
```

## CLI - Herramienta de Línea de Comandos

Xpress incluye un CLI para ayudarte con la configuración y desarrollo.

### Comandos Disponibles

| Comando | Descripción |
|---------|-------------|
| `xpress htaccess` | Genera archivo .htaccess para Apache |
| `xpress serve` | Inicia servidor de desarrollo PHP |
| `xpress init` | Inicializa estructura de proyecto |
| `xpress route:list` | Lista rutas registradas |
| `xpress help` | Muestra ayuda |

### Generar .htaccess

```bash
# Generar .htaccess básico en public/.htaccess
xpress htaccess

# Con opciones
xpress htaccess --public=htdocs        # Directorio público personalizado
xpress htaccess --base=/api/v1         # Base path
xpress htaccess --entry=app.php        # Archivo de entrada
xpress htaccess --stdout               # Ver sin crear archivo
```

**Archivo .htaccess generado:**

```apache
# Xpress Router - Apache Configuration
RewriteEngine On

# Redirigir requests al index.php
RewriteCond %{REQUEST_URI} -f [OR]
RewriteCond %{REQUEST_URI} -d
RewriteRule ^ - [L]

RewriteRule ^ index.php [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# Prevenir listing de directorios
Options -Indexes

# Cache estáticos
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Iniciar Servidor de Desarrollo

```bash
# Servidor básico
xpress serve

# Con opciones
xpress serve --port=3000          # Puerto personalizado
xpress serve --host=0.0.0.0       # Escuchar en todas las interfaces
xpress serve --public=htdocs      # Directorio público
```

Salida:
```
╔══════════════════════════════════════════════════════════╗
║                    Xpress Development Server              ║
╠══════════════════════════════════════════════════════════╣
║  URL:      http://localhost:8080                          ║
║  Document: /path/to/project/public                        ║
║  Entry:    index.php                                      ║
╠══════════════════════════════════════════════════════════╣
║  Presiona Ctrl+C para detener                           ║
╚══════════════════════════════════════════════════════════╝
```

### Inicializar Proyecto

```bash
# Crear estructura básica
xpress init

# Sobrescribir archivos existentes
xpress init --force
```

Crea:
```
├── public/
│   ├── .htaccess
│   └── index.php
├── src/
│   ├── controllers/
│   └── middleware/
├── bootstrap.php
├── .env.example
└── routes/
    └── web.php
```

### Listar Rutas

```bash
# Ver rutas en formato tabla
xpress route:list

# Salida JSON
xpress route:list --json
```

## Uso Básico

### 1. Instalación con Composer y estructura del proyecto

```bash
mkdir mi-api && cd mi-api
composer require dr2gsistemas/xpress
```

```
mi-api/
├── composer.json
├── public/
│   └── index.php          # Entry point
├── src/
│   ├── Controllers/
│   │   └── UserController.php
│   └── Middlewares/
│       └── AuthMiddleware.php
└── vendor/
```

### 2. Punto de entrada (public/index.php)

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Xpress\XRouter;

$router = new XRouter();
$router->setBasePath('/api/v1');

require_once __DIR__ . '/../src/routes.php';

$response = $router->dispatch();
$response->send();
```

### 3. Definir rutas (src/routes.php)

```php
<?php

use Xpress\XRouter;
use Xpress\XRequest;
use Xpress\XResponse;
use Xpress\Attributes\{XRoute, XMiddleware, XGroup};

$router = xpress();

// Rutas simples con closures
$router->get('/hello', function (XRequest $req) {
    return json(['message' => '¡Hola Mundo!']);
});

// Rutas con parámetros
$router->get('/users/{id}', function (XRequest $req, array $params) {
    return json(['user_id' => $params['id']]);
});

// Rutas con query parameters
$router->get('/search', function (XRequest $req) {
    $query = $req->getQuery('q', '');
    $page = $req->getQuery('page', 1);
    return json(['query' => $query, 'page' => (int)$page]);
});

// Rutas POST con body JSON
$router->post('/users', function (XRequest $req) {
    $data = $req->getJson();
    return json(['created' => $data], 201);
});

// Middleware inline
$router->get('/admin', function () {
    return json(['secret' => 'data']);
}, [function (XRequest $req, callable $next) {
    if ($req->getHeader('X-Admin-Token') !== 'secret123') {
        return json(['error' => 'Unauthorized'], 401);
    }
    return $next($req);
}]);
```

## Controladores con Atributos

### Estructura de controlador

```php
<?php
// src/Controllers/UserController.php

namespace App\Controllers;

use Xpress\XRouter;
use Xpress\XRequest;
use Xpress\XResponse;
use Xpress\Attributes\{XRoute, XMiddleware, XGroup};

#[XGroup('/users')]
class UserController
{
    #[XRoute('', 'GET')]
    public function index(XRequest $request): XResponse
    {
        $users = [
            ['id' => 1, 'name' => 'Juan Pérez', 'email' => 'juan@ejemplo.com'],
            ['id' => 2, 'name' => 'María García', 'email' => 'maria@ejemplo.com'],
        ];

        return (new XResponse())->json([
            'data' => $users,
            'total' => count($users)
        ]);
    }

    #[XRoute('/{id}', 'GET')]
    public function show(XRequest $request, array $params): XResponse
    {
        $id = (int) $params['id'];

        $user = $this->findUser($id);

        if (!$user) {
            return (new XResponse())->json([
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        return (new XResponse())->json([
            'data' => $user
        ]);
    }

    #[XRoute('', 'POST')]
    #[XMiddleware(AuthMiddleware::class)]
    public function store(XRequest $request): XResponse
    {
        $data = $request->getJson();

        if (empty($data['name']) || empty($data['email'])) {
            return (new XResponse())->json([
                'error' => 'Nombre y email son requeridos'
            ], 422);
        }

        $user = [
            'id' => random_int(100, 9999),
            'name' => $data['name'],
            'email' => $data['email'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return (new XResponse())->json([
            'data' => $user,
            'message' => 'Usuario creado exitosamente'
        ], 201);
    }

    #[XRoute('/{id}', 'PUT')]
    #[XMiddleware([AuthMiddleware::class, ValidateMiddleware::class])]
    public function update(XRequest $request, array $params): XResponse
    {
        $id = (int) $params['id'];
        $data = $request->getJson();

        return (new XResponse())->json([
            'data' => [
                'id' => $id,
                'name' => $data['name'] ?? 'Nombre actualizado',
                'email' => $data['email'] ?? 'actualizado@ejemplo.com'
            ],
            'message' => 'Usuario actualizado'
        ]);
    }

    #[XRoute('/{id}', 'DELETE')]
    #[XMiddleware(AuthMiddleware::class)]
    public function destroy(XRequest $request, array $params): XResponse
    {
        $id = (int) $params['id'];

        return (new XResponse())->json([
            'message' => "Usuario {$id} eliminado"
        ]);
    }

    private function findUser(int $id): ?array
    {
        $users = [
            1 => ['id' => 1, 'name' => 'Juan Pérez', 'email' => 'juan@ejemplo.com'],
            2 => ['id' => 2, 'name' => 'María García', 'email' => 'maria@ejemplo.com'],
        ];

        return $users[$id] ?? null;
    }
}
```

### Registrar controladores

```php
<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

use Xpress\XRouter;
use App\Controllers\UserController;
use App\Controllers\PostController;
use App\Middleware\CorsMiddleware;

$router = new XRouter();

// Middleware global (se ejecuta en todas las rutas)
$router->use(CorsMiddleware::class);

// Rutas con parámetros (orden importante - específico primero)
$router->get('/users/{id}/posts/{postId}', [PostController::class, 'getComment']);

// Rutas normales
$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);

// O simplemente registrar todos los controladores
$router->registerControllers([
    UserController::class,
    PostController::class
]);

$response = $router->dispatch();
$response->send();
```

## Middlewares

### Middleware de autenticación

```php
<?php
// src/Middleware/AuthMiddleware.php

namespace App\Middleware;

use Xpress\XRequest;
use Xpress\XResponse;

class AuthMiddleware
{
    public function handle(XRequest $request, callable $next): XResponse
    {
        $authHeader = $request->getHeader('Authorization');

        if (empty($authHeader)) {
            return (new XResponse())->json([
                'error' => 'Autenticación requerida',
                'message' => 'Por favor proporcione el header Authorization'
            ], 401);
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return (new XResponse())->json([
                'error' => 'Token inválido',
                'message' => 'El token debe comenzar con "Bearer "'
            ], 401);
        }

        $token = substr($authHeader, 7);

        if (!$this->validateToken($token)) {
            return (new XResponse())->json([
                'error' => 'Token expirado o inválido'
            ], 401);
        }

        $request = $request->withAttribute('user_id', $this->getUserId($token));

        return $next($request);
    }

    private function validateToken(string $token): bool
    {
        // Lógica de validación de token
        return strlen($token) > 10;
    }

    private function getUserId(string $token): ?int
    {
        return 1; // Retornar ID del usuario del token
    }
}
```

### Middleware de logging

```php
<?php
// src/Middleware/LoggerMiddleware.php

namespace App\Middleware;

use Xpress\XRequest;
use Xpress\XResponse;

class LoggerMiddleware
{
    public function handle(XRequest $request, callable $next): XResponse
    {
        $startTime = microtime(true);
        $method = $request->getMethod();
        $path = $request->getPath();

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $log = sprintf(
            "[%s] %s %s - %d (%sms)",
            date('Y-m-d H:i:s'),
            $method,
            $path,
            $response->getStatusCode(),
            $duration
        );

        error_log($log);

        return $response->withHeader('X-Response-Time', "{$duration}ms");
    }
}
```

### Middleware de rate limiting

```php
<?php
// src/Middleware/RateLimitMiddleware.php

namespace App\Middleware;

use Xpress\XRequest;
use Xpress\XResponse;

class RateLimitMiddleware
{
    private static array $requests = [];

    public function handle(XRequest $request, callable $next): XResponse
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $key = $ip . ':' . date('Y-m-d:H:i');

        if (!isset(self::$requests[$key])) {
            self::$requests[$key] = 0;
        }

        self::$requests[$key]++;
        $limit = 60;
        $remaining = max(0, $limit - self::$requests[$key]);

        if (self::$requests[$key] > $limit) {
            return (new XResponse())->json([
                'error' => 'Límite de requests excedido',
                'retry_after' => 60
            ], 429)
            ->withHeader('X-RateLimit-Limit', (string) $limit)
            ->withHeader('X-RateLimit-Remaining', '0');
        }

        $response = $next($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $limit)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
    }
}
```

### Middleware CORS

```php
<?php
// src/Middleware/CorsMiddleware.php

namespace App\Middleware;

use Xpress\XRequest;
use Xpress\XResponse;

class CorsMiddleware
{
    public function handle(XRequest $request, callable $next): XResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return (new XResponse(204))
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
                ->withHeader('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
    }
}
```

### Usar middlewares

```php
<?php
// Uso global
$router->use(LoggerMiddleware::class);
$router->use(CorsMiddleware::class);

// Uso en ruta específica
$router->get('/profile', function () {
    return json(['user' => ['name' => 'Juan']]);
}, [AuthMiddleware::class]);

// Múltiples middlewares en ruta
$router->post('/admin/users', function () {
    return json(['created' => true]);
}, [
    AuthMiddleware::class,
    RateLimitMiddleware::class,
    AdminMiddleware::class
]);

// Middleware inline
$router->delete('/items/{id}', function (XRequest $req, array $params) {
    return json(['deleted' => $params['id']]);
}, [function (XRequest $req, callable $next) {
    if ($req->getRouteParam('id') === '0') {
        return json(['error' => 'ID inválido'], 400);
    }
    return $next($req);
}]);
```

## Grupos de Rutas

```php
<?php
// src/Controllers/Api/V1/ProductController.php

namespace App\Controllers\Api\V1;

use Xpress\XRequest;
use Xpress\XResponse;
use Xpress\Attributes\{XRoute, XMiddleware, XGroup};

#[XGroup('/products')]
class ProductController
{
    #[XRoute('', 'GET')]
    public function index(XRequest $request): XResponse
    {
        return (new XResponse())->json([
            'products' => [
                ['id' => 1, 'name' => 'Producto A', 'price' => 29.99],
                ['id' => 2, 'name' => 'Producto B', 'price' => 49.99],
            ]
        ]);
    }

    #[XRoute('/{id}', 'GET')]
    public function show(XRequest $request, array $params): XResponse
    {
        return (new XResponse())->json([
            'product' => [
                'id' => (int) $params['id'],
                'name' => 'Producto Demo',
                'price' => 99.99,
                'stock' => 100
            ]
        ]);
    }

    #[XRoute('', 'POST')]
    #[XMiddleware(AuthMiddleware::class)]
    public function store(XRequest $request): XResponse
    {
        $data = $request->getJson();

        return (new XResponse())->json([
            'product' => [
                'id' => random_int(1000, 9999),
                'name' => $data['name'] ?? 'Nuevo Producto',
                'price' => $data['price'] ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ], 201);
    }

    #[XRoute('/search', 'GET')]
    public function search(XRequest $request): XResponse
    {
        $q = $request->getQuery('q', '');
        $category = $request->getQuery('category', 'all');
        $minPrice = $request->getQuery('min_price', 0);
        $maxPrice = $request->getQuery('max_price', PHP_INT_MAX);

        return (new XResponse())->json([
            'query' => $q,
            'filters' => [
                'category' => $category,
                'min_price' => (float) $minPrice,
                'max_price' => (float) $maxPrice
            ],
            'results' => [
                ['id' => 1, 'name' => 'Resultado 1', 'price' => 25.00],
                ['id' => 2, 'name' => 'Resultado 2', 'price' => 35.00],
            ],
            'total' => 2
        ]);
    }
}
```

### Grupo con middlewares compartidos

```php
<?php
// src/Controllers/AdminController.php

namespace App\Controllers;

use Xpress\XRequest;
use Xpress\XResponse;
use Xpress\Attributes\{XRoute, XMiddleware, XGroup};

#[XGroup('/admin', [AuthMiddleware::class, AdminMiddleware::class])]
class AdminController
{
    #[XRoute('/dashboard', 'GET')]
    public function dashboard(): XResponse
    {
        return (new XResponse())->json([
            'stats' => [
                'users' => 1250,
                'orders' => 342,
                'revenue' => 45890.50
            ]
        ]);
    }

    #[XRoute('/settings', 'GET')]
    public function settings(): XResponse
    {
        return (new XResponse())->json([
            'settings' => [
                'site_name' => 'Mi Tienda',
                'timezone' => 'America/Mexico_City',
                'currency' => 'MXN'
            ]
        ]);
    }
}
```

## API Reference

### XRouter

```php
$router = new XRouter();

// Configuración
$router->setBasePath('/api/v1');  // Prefijo base para todas las rutas

// Middleware global
$router->use(CorsMiddleware::class);
$router->use(LoggerMiddleware::class);

// Métodos HTTP
$router->get('/ruta', $handler);
$router->post('/ruta', $handler);
$router->put('/ruta', $handler);
$router->patch('/ruta', $handler);
$router->delete('/ruta', $handler);
$router->options('/ruta', $handler);
$router->head('/ruta', $handler);

// Múltiples métodos
$router->any(['GET', 'POST'], '/ruta', $handler);

// Con middlewares
$router->get('/protegida', $handler, [AuthMiddleware::class]);
$router->post('/recurso', $handler, [AuthMiddleware::class, ValidateMiddleware::class]);

// Registrar controladores con atributos
$router->registerControllers([UserController::class, ProductController::class]);

// Dispatch
$response = $router->dispatch();              // Desde globals
$response = $router->dispatch($request);     // Con request custom
```

### XRequest

```php
// Crear desde globals
$request = XRequest::fromGlobals();

// Crear desde URI (útil para testing)
$request = new XRequest(new ServerRequest('GET', new Uri('/users/123')));

// Métodos HTTP
$request->getMethod();                           // 'GET', 'POST', etc.
$request->getPath();                             // '/users/123'
$request->getUri();                              // UriInterface

// Query parameters
$request->getQueryParams();                      // ['page' => 1, 'limit' => 10]
$request->getQuery('page');                      // '1'
$request->getQuery('page', 1);                   // 1 (default)
$request->getQuery('q', 'default');              // 'default'

// Body y JSON
$request->getBody();                             // string raw
$request->getParsedBody();                        // parsed form data
$request->getJson();                              // ['key' => 'value'] o null
$request->isJson();                              // true/false

// Headers
$request->getHeader('Authorization');            // 'Bearer token' o null
$request->getHeader('Content-Type');              // 'application/json' o null
$request->getHeaders();                          // todos los headers
$request->hasHeader('X-Custom');                 // true/false

// Parámetros de ruta (capturados de {param})
$request->getRouteParams();                       // ['id' => '123', 'action' => 'edit']
$request->getRouteParam('id');                    // '123'
$request->getRouteParam('id', 0);                // 0 (default)
$request->setRouteParams(['id' => '123']);       // setear params

// Attributes (para pasar datos entre middlewares)
$request->getAttribute('user_id');               // valor o null
$request->withAttribute('user_id', 123);         // nuevo request con attribute

// Cookies
$request->getCookies();                          // ['session' => 'abc123']
$request->getCookie('session');                  // 'abc123'

// Server params
$request->getServerParams();                     // $_SERVER
$request->getProtocolVersion();                   // '1.1'

// Files upload
$request->getUploadedFiles();                    // uploaded files array
```

### XResponse

```php
// Constructor básico
$response = new XResponse();
$response = new XResponse(200);
$response = new XResponse(404, ['Content-Type' => 'text/plain'], 'Not Found');

// Crear con contenido
(new XResponse())->json(['key' => 'value']);
(new XResponse())->text('Hello World');
(new XResponse())->html('<h1>Título</h1>');
(new XResponse())->xml('<root><item>valor</item></root>');

// Con código de estado
(new XResponse())->json(['error' => 'Not found'], 404);
(new XResponse())->text('Created', 201);

// Helpers estáticos
XResponse::ok();                                 // 200
XResponse::created(['id' => 1]);                  // 201
XResponse::noContent();                          // 204
XResponse::notFound();                           // 404
XResponse::unauthorized();                       // 401
XResponse::forbidden();                         // 403
XResponse::badRequest();                        // 400
XResponse::error('Server error', 500);           // 500

// Métodos fluidos
$response
    ->withHeader('X-Custom-Header', 'value')
    ->withHeader('X-Another', 'another')
    ->withStatus(200, 'OK');

// Redirección
$response->redirect('/new-location');
$response->redirect('/new-location', 301);       // permanent
$response->redirect('/new-location', 307);       // temporary

// CORS
$response->cors([
    'origin' => '*',                              // o dominio específico
    'methods' => 'GET, POST, PUT',
    'headers' => 'Content-Type, Authorization',
    'expose_headers' => 'X-Custom',
    'max_age' => 86400,
    'credentials' => false
]);

// Cookie
$response->cookie('session', 'abc123');
$response->cookie('token', 'xyz', [
    'expires' => time() + 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Enviar respuesta
$response->send();                               // headers + body

// Getters
$response->getStatusCode();                       // 200
$response->getReasonPhrase();                     // 'OK'
$response->getHeaders();                         // todos los headers
$response->getHeaderLine('Content-Type');         // 'application/json'
$response->getBody();                            // StreamInterface
$response->getProtocolVersion();                  // '1.1'
```

## Funciones Helper

```php
// Instancia global del router
xpress();

// Respuestas rápidas
json(['key' => 'value']);
json(['error' => 'msg'], 400);
text('Hello');
html('<h1>Title</h1>');

// Redirecciones y errores
redirect('/new-path');
redirect('/login', 302);
notFound();
unauthorized();
badRequest();
error('Server error');
error('Custom error', 500);

// Helpers con XResponse
json()    → (new XResponse())->json()
text()    → (new XResponse())->text()
html()    → (new XResponse())->html()
redirect() → (new XResponse())->redirect()
notFound() → XResponse::notFound()->text()
```

## Ejemplo Completo de API

```php
<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

use Xpress\XRouter;
use Xpress\XRequest;
use Xpress\XResponse;
use Xpress\Attributes\{XRoute, XMiddleware, XGroup};

class Database
{
    private static array $users = [];
    private static int $lastId = 0;

    public static function users(): array { return self::$users; }

    public static function findUser(int $id): ?array
    {
        return self::$users[$id] ?? null;
    }

    public static function createUser(array $data): array
    {
        self::$users[++self::$lastId] = [
            'id' => self::$lastId,
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        return self::$users[self::$lastId];
    }

    public static function updateUser(int $id, array $data): ?array
    {
        if (!isset(self::$users[$id])) return null;
        self::$users[$id] = array_merge(self::$users[$id], $data);
        return self::$users[$id];
    }

    public static function deleteUser(int $id): bool
    {
        if (!isset(self::$users[$id])) return false;
        unset(self::$users[$id]);
        return true;
    }
}

#[XGroup('/api')]
class ApiController
{
    #[XRoute('/health', 'GET')]
    public function health(): XResponse
    {
        return (new XResponse())->json([
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ]);
    }
}

#[XGroup('/api/users')]
class UserController
{
    #[XRoute('', 'GET')]
    public function index(XRequest $request): XResponse
    {
        $page = max(1, (int) $request->getQuery('page', 1));
        $limit = min(100, max(1, (int) $request->getQuery('limit', 10)));
        $search = $request->getQuery('search', '');

        $users = array_values(Database::users());

        if ($search) {
            $users = array_filter($users, fn($u) =>
                stripos($u['name'], $search) !== false ||
                stripos($u['email'], $search) !== false
            );
        }

        $total = count($users);
        $offset = ($page - 1) * $limit;
        $users = array_slice($users, $offset, $limit);

        return (new XResponse())->json([
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    #[XRoute('/{id}', 'GET')]
    public function show(XRequest $request, array $params): XResponse
    {
        $id = (int) $params['id'];
        $user = Database::findUser($id);

        if (!$user) {
            return (new XResponse())->json([
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        return (new XResponse())->json(['data' => $user]);
    }

    #[XRoute('', 'POST')]
    public function store(XRequest $request): XResponse
    {
        $data = $request->getJson();

        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }

        if (!empty($errors)) {
            return (new XResponse())->json([
                'error' => 'Datos de validación fallidos',
                'errors' => $errors
            ], 422);
        }

        $user = Database::createUser($data);

        return (new XResponse())->json([
            'data' => $user,
            'message' => 'Usuario creado exitosamente'
        ], 201);
    }

    #[XRoute('/{id}', 'PUT')]
    public function update(XRequest $request, array $params): XResponse
    {
        $id = (int) $params['id'];
        $data = $request->getJson();

        $user = Database::updateUser($id, $data);

        if (!$user) {
            return (new XResponse())->json([
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        return (new XResponse())->json([
            'data' => $user,
            'message' => 'Usuario actualizado'
        ]);
    }

    #[XRoute('/{id}', 'DELETE')]
    public function destroy(XRequest $request, array $params): XResponse
    {
        $id = (int) $params['id'];

        if (!Database::deleteUser($id)) {
            return (new XResponse())->json([
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        return (new XResponse())->json([
            'message' => 'Usuario eliminado'
        ]);
    }
}

// Configurar router
$router = new XRouter();
$router->use(function (XRequest $req, callable $next) {
    $response = $next($req);
    return $response->withHeader('X-API-Version', '1.0');
});
$router->registerControllers([ApiController::class, UserController::class]);

// Ejecutar
$response = $router->dispatch();
$response->send();
```

## Probar la API

```bash
# Health check
curl http://localhost:8000/api/health

# Listar usuarios
curl http://localhost:8000/api/users

# Crear usuario
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Juan Pérez","email":"juan@ejemplo.com"}'

# Ver usuario
curl http://localhost:8000/api/users/1

# Actualizar usuario
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"Juan Actualizado"}'

# Eliminar usuario
curl -X DELETE http://localhost:8000/api/users/1

# Con búsqueda
curl "http://localhost:8000/api/users?search=Juan&page=1&limit=5"
```

## Testing

```bash
# Ejecutar tests con Pest (recomendado)
./vendor/bin/pest

# O con Composer
composer test
```

## Patrón Result

Xpress incluye un patrón Result para manejar respuestas de forma funcional, inspirado en Rust y Haskell. Permite retornar éxito o error de manera uniforme y encadenable.

### XResult

```php
use Xpress\Result\XResult;
use Xpress\Result\XError;

// Retornar éxito
$result = XResult::ok(['user' => ['id' => 1, 'name' => 'Juan']]);

// Retornar error
$result = XResult::fail('Usuario no encontrado', 404);

// Con datos adicionales
$result = XResult::fail('Validación fallida', 422, ['errors' => ['email' => 'inválido']]);
```

### XError - Helpers de Códigos HTTP

```php
use Xpress\Result\XError;

XError::badRequest('Datos inválidos');          // 400
XError::unauthorized('No autenticado');         // 401
XError::forbidden('Sin permisos');              // 403
XError::notFound('Recurso no encontrado');     // 404
XError::conflict('Conflicto de datos');         // 409
XError::unprocessable('Entidad no procesable'); // 422
XError::validation(['email' => 'inválido']);     // 422 con errores
XError::internal('Error interno');              // 500
```

### Métodos de XResult

```php
$result = XResult::ok(['data' => 'value']);

// Verificación
$result->isSuccess();      // true
$result->isFailure();      // false
$result->isNotFound();     // false
$result->isUnauthorized(); // false

// Extracción de valores
$result->getValue();                // ['data' => 'value']
$result->getValueOr('default');    // 'default' si es error
$result->unwrap();                  // Lanza excepción si es error
$result->unwrapOr('default');       // 'default' si es error

// Manejo de errores
$result->getError();          // null (éxito) o XError
$result->getErrorMessage();   // string del error
$result->getErrorCode();      // código HTTP del error
$result->getErrorData();      // datos adicionales del error

// Modificación
$result->withCode(201);       // Cambiar código HTTP a 201
$result->withHttpCode(203);  // Similar, más semántico
```

### Encadenamiento Funcional

```php
$result = XResult::ok(['user_id' => 1])
    ->andThen(fn($data) => findUserById($data['user_id']))
    ->andThen(fn($user) => $user->isActive() 
        ? XResult::ok($user) 
        : XResult::fail('Usuario inactivo', 403))
    ->map(fn($user) => $user->toArray());

// map: transforma el valor si es éxito
// mapError: transforma el error si es fallo
// andThen: encadena operaciones que retornan Result
// orElse: maneja errores y puede recuperarlos
```

### Convertir a Response

```php
$result = XResult::ok(['user' => $user]);

// Directamente a Response
$result->toResponse()->send();

// O usando send()
$result->send();

// Convertir a array
$result->toArray();
// [
//     'success' => true,
//     'data' => ['user' => ...],
//     'error' => null
// ]
```

### Trait XResultController

Usa el trait en controladores para helpers integrados:

```php
<?php
use Xpress\XRequest;
use Xpress\Result\XResult;
use Xpress\Result\XResultController;

class UserController
{
    use XResultController;

    public function show(XRequest $request, array $params): XResult
    {
        return $this->try(function() use ($params) {
            $id = (int) $params['id'];
            $user = $this->findUser($id);
            
            if (!$user) {
                return $this->notFound('Usuario no encontrado');
            }
            
            return $this->ok($user->toArray());
        });
    }

    public function store(XRequest $request): XResult
    {
        return $this->try(function() use ($request) {
            $data = $request->getJson();
            
            $this->validate($data, [
                'name' => 'required|string|max:255',
                'email' => 'required|email'
            ]);
            
            return $this->created($this->createUser($data));
        });
    }
}
```

### Helpers de Result

```php
// En helpers.php
result_ok(['data' => 'value']);              // XResult::ok()
result_fail('Error', 500, ['key' => 'val']); // XResult::fail()
result_error(XError::notFound());            // XResult::error()
```

### Ejemplo Completo con Result

```php
<?php
use Xpress\XRouter;
use Xpress\XRequest;
use Xpress\Result\XResult;
use Xpress\Result\XResultController;

class ProductController
{
    use XResultController;

    #[XRoute('/products', 'GET')]
    public function index(XRequest $request): XResult
    {
        return $this->try(function() use ($request) {
            $page = (int) $request->getQuery('page', 1);
            $limit = min(100, (int) $request->getQuery('limit', 10));
            
            $products = $this->getProductRepository()->paginate($page, $limit);
            
            return $this->ok([
                'products' => $products['data'],
                'pagination' => $products['pagination']
            ]);
        });
    }

    #[XRoute('/products/{id}', 'GET')]
    public function show(XRequest $request, array $params): XResult
    {
        return $this->try(function() use ($params) {
            $product = $this->getProductRepository()->find((int) $params['id']);
            
            if (!$product) {
                return $this->notFound('Producto no encontrado');
            }
            
            return $this->ok($product->toArray());
        });
    }

    #[XRoute('/products', 'POST')]
    public function store(XRequest $request): XResult
    {
        return $this->try(function() use ($request) {
            $data = $request->getJson();
            
            $errors = $this->validate($data, [
                'name' => 'required|string|min:3',
                'price' => 'required|numeric|min:0'
            ]);
            
            if (!empty($errors)) {
                return $this->validationError($errors);
            }
            
            $product = $this->createProduct($data);
            
            return $this->created($product->toArray());
        });
    }
}
```

### Compatibilidad hacia atrás

Los controladores pueden retornar `XResponse` directamente si no usan el patrón Result:

```php
public function index(): XResponse  // XResponse directo
{
    return (new XResponse())->json(['data' => []]);
}

public function show(): XResult  // Con Result pattern
{
    return $this->ok(['data' => []]);
}

// Ambos funcionan en el router
```

## Códigos de Estado HTTP

| Código | Nombre | Uso común |
|--------|--------|----------|
| 200 | OK | Respuesta exitosa |
| 201 | Created | Recurso creado |
| 204 | No Content | Eliminado sin contenido |
| 400 | Bad Request | Datos inválidos del cliente |
| 401 | Unauthorized | Sin autenticación |
| 403 | Forbidden | Sin permisos |
| 404 | Not Found | Recurso no existe |
| 409 | Conflict | Conflicto de datos |
| 422 | Unprocessable Entity | Validación fallida |
| 429 | Too Many Requests | Rate limit excedido |
| 500 | Internal Server Error | Error del servidor |

## Licencia

MIT License - ver archivo [LICENSE](LICENSE) para más detalles.

## Contributing

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcion`)
3. Commit tus cambios (`git commit -am 'Agregar nueva función'`)
4. Push a la rama (`git push origin feature/nueva-funcion`)
5. Crea un Pull Request

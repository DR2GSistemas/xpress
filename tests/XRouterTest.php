<?php

declare(strict_types=1);

namespace Xpress\Tests;

use PHPUnit\Framework\TestCase;
use Xpress\XRouter;
use Xpress\XRequest;
use Xpress\XResponse;
use Xpress\Attributes\{XRoute, XGroup, XMiddleware};
use Psr\Http\Message\ResponseInterface;

class XRouterTest extends TestCase
{
    private XRouter $router;

    protected function setUp(): void
    {
        XRouter::resetInstance();
        $this->router = new XRouter();
    }

    public function testBasicRoute(): void
    {
        $this->router->get('/hello', function (XRequest $req) {
            return (new XResponse())->json(['message' => 'Hello World']);
        });

        $response = $this->router->dispatchFromUri('/hello');

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Hello World', $body['message']);
    }

    public function testRouteWithParams(): void
    {
        $this->router->get('/users/{id}', function (XRequest $req, array $params) {
            return (new XResponse())->json(['user_id' => $params['id']]);
        });

        $response = $this->router->dispatchFromUri('/users/123');

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('123', $body['user_id']);
    }

    public function testMultipleParams(): void
    {
        $this->router->get('/posts/{postId}/comments/{commentId}', function (XRequest $req, array $params) {
            return (new XResponse())->json($params);
        });

        $response = $this->router->dispatchFromUri('/posts/1/comments/42');

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('1', $body['postId']);
        $this->assertEquals('42', $body['commentId']);
    }

    public function testPostRoute(): void
    {
        $this->router->post('/data', function (XRequest $req) {
            $data = $req->getJson();
            return (new XResponse())->json(['received' => $data]);
        });

        $response = $this->router->dispatchFromUri('/data', 'POST');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNotFoundRoute(): void
    {
        $this->router->get('/exists', function () {
            return (new XResponse())->ok();
        });

        $response = $this->router->dispatchFromUri('/not-exists');

        $this->assertEquals(404, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Not Found', $body['error']);
    }

    public function testMethodNotAllowed(): void
    {
        $this->router->get('/resource', function () {
            return (new XResponse())->ok();
        });

        $response = $this->router->dispatchFromUri('/resource', 'POST');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFluentInterface(): void
    {
        $this->router
            ->get('/get', fn() => (new XResponse())->ok())
            ->post('/post', fn() => (new XResponse())->ok())
            ->put('/put', fn() => (new XResponse())->ok())
            ->delete('/delete', fn() => (new XResponse())->ok());

        $this->assertCount(4, $this->router->getRoutes());
    }

    public function testBasePath(): void
    {
        $this->router->setBasePath('/api/v1');
        
        $this->router->get('/users', function () {
            return (new XResponse())->json(['users' => []]);
        });

        $response = $this->router->dispatchFromUri('/api/v1/users');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGlobalMiddleware(): void
    {
        $called = false;

        $this->router->use(function (XRequest $req, callable $next) use (&$called) {
            $called = true;
            return $next($req);
        });

        $this->router->get('/test', function () {
            return (new XResponse())->ok();
        });

        $this->router->dispatchFromUri('/test');

        $this->assertTrue($called);
    }

    public function testRouteMiddleware(): void
    {
        $this->router->get(
            '/protected',
            fn() => (new XResponse())->ok(),
            [function (XRequest $req, callable $next) {
                return $next($req);
            }]
        );

        $response = $this->router->dispatchFromUri('/protected');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testResponseHelpers(): void
    {
        $response = new XResponse();

        $jsonResponse = $response->json(['key' => 'value']);
        $this->assertEquals('application/json; charset=utf-8', $jsonResponse->getHeaderLine('Content-Type'));

        $textResponse = $response->text('Hello');
        $this->assertEquals('text/plain; charset=utf-8', $textResponse->getHeaderLine('Content-Type'));

        $htmlResponse = $response->html('<h1>Title</h1>');
        $this->assertEquals('text/html; charset=utf-8', $htmlResponse->getHeaderLine('Content-Type'));
    }

    public function testResponseStatusCodes(): void
    {
        $response = new XResponse(404);
        $this->assertEquals(404, $response->getStatusCode());

        $created = XResponse::created(['id' => 1]);
        $this->assertEquals(201, $created->getStatusCode());

        $noContent = XResponse::noContent();
        $this->assertEquals(204, $noContent->getStatusCode());

        $notFound = XResponse::notFound();
        $this->assertEquals(404, $notFound->getStatusCode());
    }

    public function testRedirect(): void
    {
        $response = (new XResponse())->redirect('/new-location', 301);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/new-location', $response->getHeaderLine('Location'));
    }

    public function testCORS(): void
    {
        $response = (new XResponse())->cors([
            'origin' => 'https://example.com',
            'credentials' => true
        ]);

        $this->assertEquals('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testRequestQueryParams(): void
    {
        $response = $this->router->dispatchFromUri('/search?name=john&age=30');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testStaticMethodHandler(): void
    {
        $this->router->get('/static', [StaticController::class, 'handle']);

        $response = $this->router->dispatchFromUri('/static');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testInstanceMethodHandler(): void
    {
        $this->router->get('/instance', [new InstanceController(), 'handle']);

        $response = $this->router->dispatchFromUri('/instance');

        $this->assertEquals(200, $response->getStatusCode());
    }
}

class StaticController
{
    public static function handle(XRequest $req, array $params): XResponse
    {
        return (new XResponse())->json(['static' => true]);
    }
}

class InstanceController
{
    public function handle(XRequest $req, array $params): XResponse
    {
        return (new XResponse())->json(['instance' => true]);
    }
}

<?php

declare(strict_types=1);

namespace Xpress\Tests;

use PHPUnit\Framework\TestCase;
use Xpress\Result\XResult;
use Xpress\Result\XError;

class XResultTest extends TestCase
{
    public function testOk(): void
    {
        $result = XResult::ok(['id' => 1]);
        
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertEquals(['id' => 1], $result->getValue());
        $this->assertEquals(200, $result->getCode());
        $this->assertNull($result->getError());
    }

    public function testFail(): void
    {
        $result = XResult::fail('Not found', 404);
        
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertEquals('Not found', $result->getErrorMessage());
        $this->assertEquals(404, $result->getErrorCode());
        $this->assertEquals(404, $result->getCode());
    }

    public function testFailWithData(): void
    {
        $result = XResult::fail('Validation failed', 422, ['errors' => ['email' => 'invalid']]);
        
        $this->assertEquals(['errors' => ['email' => 'invalid']], $result->getErrorData());
    }

    public function testUnwrap(): void
    {
        $result = XResult::ok('value');
        $this->assertEquals('value', $result->unwrap());
    }

    public function testUnwrapThrowsOnFailure(): void
    {
        $result = XResult::fail('Error');
        
        $this->expectException(\RuntimeException::class);
        $result->unwrap();
    }

    public function testUnwrapOr(): void
    {
        $success = XResult::ok('value');
        $failure = XResult::fail('Error');
        
        $this->assertEquals('value', $success->unwrapOr('default'));
        $this->assertEquals('default', $failure->unwrapOr('default'));
    }

    public function testMap(): void
    {
        $result = XResult::ok(5)
            ->map(fn($v) => $v * 2);
        
        $this->assertEquals(10, $result->getValue());
    }

    public function testMapDoesNotTransformOnFailure(): void
    {
        $result = XResult::fail('Error')
            ->map(fn($v) => $v * 2);
        
        $this->assertTrue($result->isFailure());
    }

    public function testAndThen(): void
    {
        $result = XResult::ok(5)
            ->andThen(fn($v) => XResult::ok($v * 2));
        
        $this->assertEquals(10, $result->getValue());
    }

    public function testAndThenShortCircuitsOnFailure(): void
    {
        $result = XResult::fail('Error')
            ->andThen(fn($v) => XResult::ok($v * 2));
        
        $this->assertTrue($result->isFailure());
        $this->assertEquals('Error', $result->getErrorMessage());
    }

    public function testMapError(): void
    {
        $result = XResult::fail('Original error')
            ->mapError(fn($e) => XError::badRequest('Mapped error'));
        
        $this->assertTrue($result->isFailure());
        $this->assertEquals(400, $result->getErrorCode());
        $this->assertEquals('Mapped error', $result->getErrorMessage());
    }

    public function testToArrayOnSuccess(): void
    {
        $result = XResult::ok(['user' => ['id' => 1]]);
        $arr = $result->toArray();
        
        $this->assertTrue($arr['success']);
        $this->assertEquals(['user' => ['id' => 1]], $arr['data']);
        $this->assertNull($arr['error']);
    }

    public function testToArrayOnFailure(): void
    {
        $result = XResult::fail('Error message', 404, ['key' => 'val']);
        $arr = $result->toArray();
        
        $this->assertFalse($arr['success']);
        $this->assertNull($arr['data']);
        $this->assertEquals('Error message', $arr['error']['message']);
        $this->assertEquals(404, $arr['error']['code']);
        $this->assertEquals(['key' => 'val'], $arr['error']['data']);
    }

    public function testWithCode(): void
    {
        $result = XResult::ok(['data' => 'value'])->withCode(201);
        $this->assertEquals(201, $result->getCode());
        
        $errorResult = XResult::fail('Error', 500)->withCode(404);
        $this->assertEquals(404, $errorResult->getErrorCode());
    }

    public function testIsNotFound(): void
    {
        $notFound = XResult::fail('Not found', 404);
        $other = XResult::fail('Error', 500);
        
        $this->assertTrue($notFound->isNotFound());
        $this->assertFalse($other->isNotFound());
    }

    public function testIsUnauthorized(): void
    {
        $unauthorized = XResult::fail('Unauthorized', 401);
        $other = XResult::fail('Error', 500);
        
        $this->assertTrue($unauthorized->isUnauthorized());
        $this->assertFalse($other->isUnauthorized());
    }

    public function testIsBadRequest(): void
    {
        $badRequest = XResult::fail('Bad request', 400);
        $other = XResult::fail('Error', 500);
        
        $this->assertTrue($badRequest->isBadRequest());
        $this->assertFalse($other->isBadRequest());
    }

    public function testIsServerError(): void
    {
        $serverError = XResult::fail('Internal error', 500);
        $clientError = XResult::fail('Bad request', 400);
        
        $this->assertTrue($serverError->isServerError());
        $this->assertFalse($clientError->isServerError());
    }

    public function testOrElse(): void
    {
        $fallback = XResult::ok('fallback');
        $result = XResult::fail('Error')->orElse(fn($e) => $fallback);
        
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('fallback', $result->getValue());
    }

    public function testCountable(): void
    {
        $success = XResult::ok('value');
        $failure = XResult::fail('Error');
        
        $this->assertCount(1, $success);
        $this->assertCount(0, $failure);
    }

    public function testFromThrowable(): void
    {
        $exception = new \RuntimeException('Something went wrong', 500);
        $result = XResult::fromThrowable($exception, false);
        
        $this->assertTrue($result->isFailure());
        $this->assertEquals(500, $result->getErrorCode());
    }

    public function testFromThrowableHidesInternal(): void
    {
        $exception = new \RuntimeException('Internal message');
        $result = XResult::fromThrowable($exception, true);
        
        $this->assertEquals('An error occurred', $result->getErrorMessage());
    }

    public function testToResponse(): void
    {
        $result = XResult::ok(['user' => ['id' => 1]]);
        $response = $result->toResponse();
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testToResponseError(): void
    {
        $result = XResult::fail('Not found', 404);
        $response = $result->toResponse();
        
        $this->assertEquals(404, $response->getStatusCode());
    }
}

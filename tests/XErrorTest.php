<?php

declare(strict_types=1);

namespace Xpress\Tests;

use PHPUnit\Framework\TestCase;
use Xpress\Result\XError;

class XErrorTest extends TestCase
{
    public function testConstructor(): void
    {
        $error = new XError('Test error', 400, ['key' => 'value']);
        
        $this->assertEquals('Test error', $error->getMessage());
        $this->assertEquals(400, $error->getCode());
        $this->assertEquals(['key' => 'value'], $error->getData());
    }

    public function testDefaultValues(): void
    {
        $error = new XError('Error');
        
        $this->assertEquals('Error', $error->getMessage());
        $this->assertEquals(500, $error->getCode());
        $this->assertEquals([], $error->getData());
    }

    public function testGet(): void
    {
        $error = new XError('Error', 400, ['user_id' => 123]);
        
        $this->assertEquals(123, $error->get('user_id'));
        $this->assertEquals('default', $error->get('missing', 'default'));
    }

    public function testHas(): void
    {
        $error = new XError('Error', 400, ['key' => 'value']);
        
        $this->assertTrue($error->has('key'));
        $this->assertFalse($error->has('missing'));
    }

    public function testToArray(): void
    {
        $error = new XError('Error', 400, ['key' => 'value']);
        $arr = $error->toArray();
        
        $this->assertEquals([
            'message' => 'Error',
            'code' => 400,
            'data' => ['key' => 'value']
        ], $arr);
    }

    public function testWithCode(): void
    {
        $error = new XError('Error', 400);
        $newError = $error->withCode(500);
        
        $this->assertEquals(400, $error->getCode());
        $this->assertEquals(500, $newError->getCode());
        $this->assertEquals('Error', $newError->getMessage());
    }

    public function testWithMessage(): void
    {
        $error = new XError('Error', 400);
        $newError = $error->withMessage('New message');
        
        $this->assertEquals('Error', $error->getMessage());
        $this->assertEquals('New message', $newError->getMessage());
        $this->assertEquals(400, $newError->getCode());
    }

    public function testWithData(): void
    {
        $error = new XError('Error', 400, ['existing' => true]);
        $newError = $error->withData(['new' => 'value']);
        
        $this->assertEquals(['existing' => true], $error->getData());
        $this->assertEquals(['existing' => true, 'new' => 'value'], $newError->getData());
    }

    public function testBadRequest(): void
    {
        $error = XError::badRequest('Invalid data');
        
        $this->assertEquals('Invalid data', $error->getMessage());
        $this->assertEquals(400, $error->getCode());
    }

    public function testUnauthorized(): void
    {
        $error = XError::unauthorized('Token expired');
        
        $this->assertEquals('Token expired', $error->getMessage());
        $this->assertEquals(401, $error->getCode());
    }

    public function testForbidden(): void
    {
        $error = XError::forbidden('Access denied');
        
        $this->assertEquals('Access denied', $error->getMessage());
        $this->assertEquals(403, $error->getCode());
    }

    public function testNotFound(): void
    {
        $error = XError::notFound('User not found');
        
        $this->assertEquals('User not found', $error->getMessage());
        $this->assertEquals(404, $error->getCode());
    }

    public function testConflict(): void
    {
        $error = XError::conflict('Email already exists');
        
        $this->assertEquals('Email already exists', $error->getMessage());
        $this->assertEquals(409, $error->getCode());
    }

    public function testUnprocessable(): void
    {
        $error = XError::unprocessable('Invalid entity');
        
        $this->assertEquals('Invalid entity', $error->getMessage());
        $this->assertEquals(422, $error->getCode());
    }

    public function testValidation(): void
    {
        $errors = ['email' => 'invalid', 'name' => 'required'];
        $error = XError::validation($errors);
        
        $this->assertEquals('Validation failed', $error->getMessage());
        $this->assertEquals(422, $error->getCode());
        $this->assertEquals($errors, $error->get('errors'));
    }

    public function testInternal(): void
    {
        $error = XError::internal('Database error');
        
        $this->assertEquals('Database error', $error->getMessage());
        $this->assertEquals(500, $error->getCode());
    }

    public function testToString(): void
    {
        $error = new XError('Error message', 400);
        $str = (string) $error;
        
        $this->assertEquals('XError(400, "Error message")', $str);
    }

    public function testJsonSerialize(): void
    {
        $error = new XError('Error', 400, ['key' => 'value']);
        $json = json_encode($error);
        
        $this->assertEquals('{"message":"Error","code":400,"data":{"key":"value"}}', $json);
    }
}

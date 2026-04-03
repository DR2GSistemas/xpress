<?php
/**
 * Manual test script for Result pattern
 * Run with: php tests/manual-test.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Result/XError.php';
require_once __DIR__ . '/../src/Result/XResult.php';

use Xpress\Result\XResult;
use Xpress\Result\XError;

echo "=== XError Tests ===\n\n";

$error = new XError('Test error', 400, ['key' => 'value']);
echo "XError constructor: ";
echo ($error->getMessage() === 'Test error' && $error->getCode() === 400) ? "PASS\n" : "FAIL\n";

echo "XError::get(): ";
echo ($error->get('key') === 'value' && $error->get('missing', 'default') === 'default') ? "PASS\n" : "FAIL\n";

echo "XError::has(): ";
echo ($error->has('key') === true && $error->has('missing') === false) ? "PASS\n" : "FAIL\n";

echo "XError::withCode(): ";
$newError = $error->withCode(500);
echo ($newError->getCode() === 500 && $error->getCode() === 400) ? "PASS\n" : "FAIL\n";

echo "XError::withMessage(): ";
$msgError = $error->withMessage('New message');
echo ($msgError->getMessage() === 'New message' && $error->getMessage() === 'Test error') ? "PASS\n" : "FAIL\n";

echo "XError::withData(): ";
$mergedError = $error->withData(['new' => 'value']);
echo ($mergedError->getData() === ['key' => 'value', 'new' => 'value']) ? "PASS\n" : "FAIL\n";

echo "XError::badRequest(): ";
$br = XError::badRequest('Invalid');
echo ($br->getCode() === 400) ? "PASS\n" : "FAIL\n";

echo "XError::unauthorized(): ";
$un = XError::unauthorized('Auth required');
echo ($un->getCode() === 401) ? "PASS\n" : "FAIL\n";

echo "XError::forbidden(): ";
$fb = XError::forbidden('No access');
echo ($fb->getCode() === 403) ? "PASS\n" : "FAIL\n";

echo "XError::notFound(): ";
$nf = XError::notFound('User not found');
echo ($nf->getCode() === 404) ? "PASS\n" : "FAIL\n";

echo "XError::conflict(): ";
$cf = XError::conflict('Already exists');
echo ($cf->getCode() === 409) ? "PASS\n" : "FAIL\n";

echo "XError::unprocessable(): ";
$up = XError::unprocessable('Invalid entity');
echo ($up->getCode() === 422) ? "PASS\n" : "FAIL\n";

echo "XError::validation(): ";
$v = XError::validation(['email' => 'invalid']);
echo ($v->getCode() === 422 && $v->get('errors') === ['email' => 'invalid']) ? "PASS\n" : "FAIL\n";

echo "XError::internal(): ";
$int = XError::internal('Server error');
echo ($int->getCode() === 500) ? "PASS\n" : "FAIL\n";

echo "\n=== XResult Tests ===\n\n";

echo "XResult::ok(): ";
$result = XResult::ok(['id' => 1]);
echo ($result->isSuccess() && !$result->isFailure() && $result->getValue() === ['id' => 1]) ? "PASS\n" : "FAIL\n";

echo "XResult::ok() with code: ";
$okWithCode = $result->withCode(201);
echo ($okWithCode->getCode() === 201) ? "PASS\n" : "FAIL\n";

echo "XResult::fail(): ";
$fail = XResult::fail('Not found', 404);
echo ($fail->isFailure() && !$fail->isSuccess() && $fail->getErrorMessage() === 'Not found' && $fail->getErrorCode() === 404) ? "PASS\n" : "FAIL\n";

echo "XResult::fail() with data: ";
$failData = XResult::fail('Error', 422, ['errors' => ['email' => 'invalid']]);
echo ($failData->getErrorData() === ['errors' => ['email' => 'invalid']]) ? "PASS\n" : "FAIL\n";

echo "XResult::unwrap(): ";
try {
    $unwrapOk = XResult::ok('value')->unwrap();
    echo ($unwrapOk === 'value') ? "PASS\n" : "FAIL\n";
} catch (Exception $e) {
    echo "FAIL\n";
}

echo "XResult::unwrap() throws on failure: ";
try {
    XResult::fail('Error')->unwrap();
    echo "FAIL\n";
} catch (RuntimeException $e) {
    echo "PASS\n";
}

echo "XResult::unwrapOr(): ";
echo (XResult::ok('value')->unwrapOr('default') === 'value' && XResult::fail('Error')->unwrapOr('default') === 'default') ? "PASS\n" : "FAIL\n";

echo "XResult::map(): ";
$mapped = XResult::ok(5)->map(fn($v) => $v * 2);
echo ($mapped->getValue() === 10) ? "PASS\n" : "FAIL\n";

echo "XResult::map() does not transform on failure: ";
$mappedFail = XResult::fail('Error')->map(fn($v) => $v * 2);
echo ($mappedFail->isFailure()) ? "PASS\n" : "FAIL\n";

echo "XResult::andThen(): ";
$chained = XResult::ok(5)->andThen(fn($v) => XResult::ok($v * 2));
echo ($chained->getValue() === 10) ? "PASS\n" : "FAIL\n";

echo "XResult::andThen() short-circuits on failure: ";
$chainedFail = XResult::fail('Error')->andThen(fn($v) => XResult::ok($v * 2));
echo ($chainedFail->isFailure() && $chainedFail->getErrorMessage() === 'Error') ? "PASS\n" : "FAIL\n";

echo "XResult::mapError(): ";
$mappedError = XResult::fail('Original')->mapError(fn($e) => XError::badRequest('Mapped'));
echo ($mappedError->getErrorCode() === 400 && $mappedError->getErrorMessage() === 'Mapped') ? "PASS\n" : "FAIL\n";

echo "XResult::toArray() on success: ";
$arrSuccess = XResult::ok(['user' => ['id' => 1]])->toArray();
echo ($arrSuccess['success'] === true && $arrSuccess['data'] === ['user' => ['id' => 1]] && $arrSuccess['error'] === null) ? "PASS\n" : "FAIL\n";

echo "XResult::toArray() on failure: ";
$arrFail = XResult::fail('Error', 404)->toArray();
echo ($arrFail['success'] === false && $arrFail['data'] === null && $arrFail['error']['message'] === 'Error') ? "PASS\n" : "FAIL\n";

echo "XResult::isNotFound(): ";
echo (XResult::fail('Not found', 404)->isNotFound() === true && XResult::fail('Error', 500)->isNotFound() === false) ? "PASS\n" : "FAIL\n";

echo "XResult::isUnauthorized(): ";
echo (XResult::fail('Auth', 401)->isUnauthorized() === true && XResult::fail('Error', 500)->isUnauthorized() === false) ? "PASS\n" : "FAIL\n";

echo "XResult::isBadRequest(): ";
echo (XResult::fail('Bad', 400)->isBadRequest() === true && XResult::fail('Error', 500)->isBadRequest() === false) ? "PASS\n" : "FAIL\n";

echo "XResult::isServerError(): ";
echo (XResult::fail('Error', 500)->isServerError() === true && XResult::fail('Error', 400)->isServerError() === false) ? "PASS\n" : "FAIL\n";

echo "XResult::orElse(): ";
$fallback = XResult::ok('fallback');
$orElse = XResult::fail('Error')->orElse(fn($e) => $fallback);
echo ($orElse->isSuccess() && $orElse->getValue() === 'fallback') ? "PASS\n" : "FAIL\n";

echo "XResult::count() success: ";
echo (count(XResult::ok('value')) === 1) ? "PASS\n" : "FAIL\n";

echo "XResult::count() failure: ";
echo (count(XResult::fail('Error')) === 0) ? "PASS\n" : "FAIL\n";

echo "XResult::fromThrowable(): ";
$ex = new RuntimeException('Test', 500);
$fromEx = XResult::fromThrowable($ex, false);
echo ($fromEx->isFailure() && $fromEx->getErrorCode() === 500) ? "PASS\n" : "FAIL\n";

echo "XResult::fromThrowable() hides internal: ";
$fromExHidden = XResult::fromThrowable($ex, true);
echo ($fromExHidden->getErrorMessage() === 'An error occurred') ? "PASS\n" : "FAIL\n";

echo "XResult::getValueOr(): ";
echo (XResult::ok('value')->getValueOr('default') === 'value' && XResult::fail('Error')->getValueOr('default') === 'default') ? "PASS\n" : "FAIL\n";

echo "XResult::error() factory: ";
$errFactory = XResult::error(XError::notFound('User'));
echo ($errFactory->isFailure() && $errFactory->getErrorCode() === 404) ? "PASS\n" : "FAIL\n";

echo "\n=== All tests completed ===\n";

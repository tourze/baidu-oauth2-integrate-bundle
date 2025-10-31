<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2Exception::class)]
final class BaiduOAuth2ExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionExtendsException(): void
    {
        $exception = new BaiduOAuth2Exception('test');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionCanBeInstantiatedWithMessage(): void
    {
        $message = 'Test exception message';
        $exception = new BaiduOAuth2Exception($message);

        $this->assertInstanceOf(BaiduOAuth2Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionCanBeInstantiatedWithMessageAndCode(): void
    {
        $message = 'OAuth authentication failed';
        $code = 401;
        $exception = new BaiduOAuth2Exception($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionCanBeInstantiatedWithPreviousException(): void
    {
        $previousException = new \RuntimeException('Previous error');
        $message = 'Baidu OAuth2 error';
        $code = 500;

        $exception = new BaiduOAuth2Exception($message, $code, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionWithEmptyMessage(): void
    {
        $exception = new BaiduOAuth2Exception('');

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testExceptionWithSpecialCharactersInMessage(): void
    {
        $message = 'OAuth错误: 特殊字符 éñ中文 & symbols @#$%^&*()';
        $exception = new BaiduOAuth2Exception($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionWithLongMessage(): void
    {
        $message = str_repeat('Long error message. ', 100);
        $exception = new BaiduOAuth2Exception($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertGreaterThan(1000, strlen($exception->getMessage()));
    }

    public function testExceptionWithNegativeCode(): void
    {
        $message = 'Error with negative code';
        $code = -1;
        $exception = new BaiduOAuth2Exception($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionWithLargeCode(): void
    {
        $message = 'Error with large code';
        $code = PHP_INT_MAX;
        $exception = new BaiduOAuth2Exception($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionCanBeThrownAndCaught(): void
    {
        $message = 'Test throwing exception';
        $code = 123;

        $this->expectException(BaiduOAuth2Exception::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($code);

        throw new BaiduOAuth2Exception($message, $code);
    }

    public function testExceptionStackTrace(): void
    {
        $exception = new BaiduOAuth2Exception('Stack trace test');

        $this->assertIsString($exception->getTraceAsString());
        $this->assertNotEmpty($exception->getTrace());
        $this->assertIsArray($exception->getTrace());
    }

    public function testExceptionFile(): void
    {
        $exception = new BaiduOAuth2Exception('File test');

        $this->assertIsString($exception->getFile());
        $this->assertStringEndsWith('.php', $exception->getFile());
    }

    public function testExceptionLine(): void
    {
        $exception = new BaiduOAuth2Exception('Line test');

        $this->assertIsInt($exception->getLine());
        $this->assertGreaterThan(0, $exception->getLine());
    }

    public function testExceptionToString(): void
    {
        $message = 'ToString test';
        $exception = new BaiduOAuth2Exception($message);

        $stringRepresentation = (string) $exception;

        $this->assertIsString($stringRepresentation);
        $this->assertStringContainsString($message, $stringRepresentation);
        $this->assertStringContainsString('BaiduOAuth2Exception', $stringRepresentation);
    }

    public function testExceptionWithMultipleLevelsOfPreviousExceptions(): void
    {
        $rootException = new \InvalidArgumentException('Root cause');
        $middleException = new \RuntimeException('Middle layer', 0, $rootException);
        $topException = new BaiduOAuth2Exception('Top level OAuth error', 500, $middleException);

        $this->assertSame($middleException, $topException->getPrevious());
        $previous = $topException->getPrevious();
        $this->assertInstanceOf(\RuntimeException::class, $previous);
        $this->assertSame($rootException, $previous->getPrevious());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new BaiduOAuth2Exception('Inheritance test');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(BaiduOAuth2Exception::class, $exception);
    }

    public function testExceptionWithCommonOAuth2ErrorMessages(): void
    {
        $commonMessages = [
            'Invalid client credentials',
            'Authorization code expired',
            'Invalid redirect URI',
            'Access token expired',
            'Insufficient permissions',
            'Rate limit exceeded',
            'Server temporarily unavailable',
        ];

        foreach ($commonMessages as $message) {
            $exception = new BaiduOAuth2Exception($message);
            $this->assertEquals($message, $exception->getMessage());
            $this->assertInstanceOf(BaiduOAuth2Exception::class, $exception);
        }
    }

    public function testExceptionWithJsonEncodedMessage(): void
    {
        $errorData = [
            'error' => 'invalid_grant',
            'error_description' => 'The authorization code is invalid or expired',
            'error_uri' => 'https://developers.baidu.com/docs/oauth/error-codes',
        ];
        $message = json_encode($errorData);
        $this->assertNotFalse($message); // 确保json_encode成功
        $exception = new BaiduOAuth2Exception($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertJson($exception->getMessage());
    }
}

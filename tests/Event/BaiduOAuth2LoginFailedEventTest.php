<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\BaiduOauth2IntegrateBundle\Event\BaiduOAuth2LoginFailedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2LoginFailedEvent::class)]
final class BaiduOAuth2LoginFailedEventTest extends AbstractEventTestCase
{
    public function testEventConstructionWithReason(): void
    {
        $reason = 'Invalid credentials';
        $event = new BaiduOAuth2LoginFailedEvent($reason);

        $this->assertEquals($reason, $event->getReason());
        $this->assertEmpty($event->getContext());
    }

    public function testEventConstructionWithReasonAndContext(): void
    {
        $reason = 'OAuth state mismatch';
        $context = [
            'expected_state' => 'abc123',
            'received_state' => 'xyz789',
            'client_ip' => '192.168.1.1',
        ];

        $event = new BaiduOAuth2LoginFailedEvent($reason, $context);

        $this->assertEquals($reason, $event->getReason());
        $this->assertEquals($context, $event->getContext());
    }

    public function testEventConstructionWithEmptyContext(): void
    {
        $reason = 'Token exchange failed';
        $context = [];

        $event = new BaiduOAuth2LoginFailedEvent($reason, $context);

        $this->assertEquals($reason, $event->getReason());
        $this->assertEmpty($event->getContext());
    }

    public function testGetReasonReturnsCorrectValue(): void
    {
        $reason = 'User profile fetch failed';
        $event = new BaiduOAuth2LoginFailedEvent($reason);

        $result = $event->getReason();

        $this->assertIsString($result);
        $this->assertEquals($reason, $result);
    }

    public function testGetContextReturnsCorrectValue(): void
    {
        $reason = 'API rate limit exceeded';
        $context = [
            'error_code' => 429,
            'retry_after' => 3600,
            'endpoint' => '/api/user/info',
        ];

        $event = new BaiduOAuth2LoginFailedEvent($reason, $context);

        $result = $event->getContext();

        $this->assertIsArray($result);
        $this->assertEquals($context, $result);
    }

    public function testEventWithComplexContextData(): void
    {
        $reason = 'Network error during authentication';
        $context = [
            'error' => [
                'type' => 'NetworkException',
                'message' => 'Connection timeout',
                'code' => 504,
            ],
            'request_data' => [
                'client_id' => 'test_client_123',
                'redirect_uri' => 'https://example.com/callback',
            ],
            'timestamp' => '2023-12-01T10:30:00Z',
            'user_agent' => 'Mozilla/5.0 (compatible; TestBot/1.0)',
        ];

        $event = new BaiduOAuth2LoginFailedEvent($reason, $context);

        $this->assertEquals($reason, $event->getReason());
        $eventContext = $event->getContext();
        $this->assertEquals($context, $eventContext);
        $this->assertArrayHasKey('error', $eventContext);
        $this->assertArrayHasKey('request_data', $eventContext);
        $this->assertIsArray($eventContext['error']);
        $this->assertEquals('NetworkException', $eventContext['error']['type']);
    }

    public function testEventImmutability(): void
    {
        $reason = 'Immutability test';
        $context = ['key' => 'value'];

        $event = new BaiduOAuth2LoginFailedEvent($reason, $context);

        // Event properties should be readonly
        $this->assertEquals($reason, $event->getReason());
        $this->assertEquals($context, $event->getContext());

        // Modifying the context array externally should not affect the event
        $context['new_key'] = 'new_value';
        $this->assertArrayNotHasKey('new_key', $event->getContext());
    }

    public function testEventWithSpecialCharactersInReason(): void
    {
        $reason = 'Error with special chars: éñ中文 & symbols @#$%^&*()';
        $event = new BaiduOAuth2LoginFailedEvent($reason);

        $this->assertEquals($reason, $event->getReason());
    }

    public function testEventWithNumericContext(): void
    {
        $reason = 'Numeric context test';
        $context = [
            'attempt_count' => 3,
            'max_attempts' => 5,
            'timeout_seconds' => 30.5,
            'user_id' => 12345,
        ];

        $event = new BaiduOAuth2LoginFailedEvent($reason, $context);

        $this->assertEquals($context, $event->getContext());
        $this->assertIsInt($event->getContext()['attempt_count']);
        $this->assertIsFloat($event->getContext()['timeout_seconds']);
    }

    public function testEventWithNullValuesInContext(): void
    {
        $reason = 'Context with null values';
        $context = [
            'session_id' => null,
            'user_id' => null,
            'error_details' => 'Some error occurred',
        ];

        $event = new BaiduOAuth2LoginFailedEvent($reason, $context);

        $this->assertEquals($context, $event->getContext());
        $this->assertNull($event->getContext()['session_id']);
        $this->assertNull($event->getContext()['user_id']);
        $this->assertIsString($event->getContext()['error_details']);
    }
}

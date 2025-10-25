<?php

namespace Noty\Laravel\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Noty\Laravel\Support\Event;
use Noty\Laravel\Support\Transports\HttpTransport;
use Noty\Laravel\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class HttpTransportTest extends TestCase
{
    /** @test */
    public function itConstructsWithCorrectEndpoint(): void
    {
        $transport = new HttpTransport(
            'http://localhost:3020',
            ['path' => '/api/v1/events'],
            'token123'
        );

        $this->assertInstanceOf(HttpTransport::class, $transport);
    }

    /** @test */
    public function itSendsEventAsynchronously(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"success": true}'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handlerStack]);

        $transport = new HttpTransport(
            'http://localhost:3020',
            ['path' => '/api/v1/events'],
            'token123'
        );

        // Use reflection to inject mock client
        $reflection = new \ReflectionClass($transport);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($transport, $httpClient);

        $event = new Event(
            channel: 'channel_123',
            title: 'Test Event'
        );

        $result = $transport->send($event);

        $this->assertEquals('channel_123', $result);
    }

    /** @test */
    public function itIncludesAuthorizationHeaderWhenTokenProvided(): void
    {
        $requestHeaders = null;

        $mock = new MockHandler([
            function ($request) use (&$requestHeaders) {
                $requestHeaders = $request->getHeaders();

                return new Response(200);
            },
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handlerStack]);

        $transport = new HttpTransport(
            'http://localhost:3020',
            ['path' => '/api/v1/events'],
            'test_token'
        );

        $reflection = new \ReflectionClass($transport);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($transport, $httpClient);

        $event = new Event('channel_123', 'Test');
        $transport->send($event);
        $transport->flush();

        $this->assertArrayHasKey('Authorization', $requestHeaders);
        $this->assertStringContainsString('Bearer test_token', $requestHeaders['Authorization'][0]);
    }

    /** @test */
    public function itFlushesPendingPromises(): void
    {
        $mock = new MockHandler([
            new Response(200),
            new Response(200),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handlerStack]);

        $transport = new HttpTransport(
            'http://localhost:3020',
            ['path' => '/api/v1/events']
        );

        $reflection = new \ReflectionClass($transport);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($transport, $httpClient);

        $transport->send(new Event('ch1', 'Test 1'));
        $transport->send(new Event('ch2', 'Test 2'));

        $transport->flush(1.0);

        // If we get here without exception, flush worked
        $this->assertTrue(true);
    }
}

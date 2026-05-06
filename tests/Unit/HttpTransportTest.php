<?php

namespace Noty\Laravel\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
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

        // If we get here without exception, flush worked.
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function itDoesNotIncludeAuthorizationHeaderWhenTokenIsNull(): void
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
            ['path' => '/api/v1/events']
            // no token
        );

        $this->injectMockHttpClient($transport, $httpClient);

        $transport->send(new Event('ch', 'Test'));
        $transport->flush();

        $this->assertNotNull($requestHeaders);
        $this->assertArrayNotHasKey('Authorization', $requestHeaders);
        $this->assertArrayHasKey('Content-Type', $requestHeaders);
        $this->assertSame('application/json', $requestHeaders['Content-Type'][0]);
    }

    /** @test */
    public function itPostsToConfiguredCustomPath(): void
    {
        $requestUri = null;

        $mock = new MockHandler([
            function ($request) use (&$requestUri) {
                $requestUri = (string) $request->getUri();

                return new Response(200);
            },
        ]);

        $httpClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);

        $transport = new HttpTransport(
            'http://localhost:3020/',
            ['path' => '/custom/events/path'],
            'tk'
        );

        $this->injectMockHttpClient($transport, $httpClient);

        $transport->send(new Event('ch', 'Test'));
        $transport->flush();

        $this->assertSame('http://localhost:3020/custom/events/path', $requestUri);
    }

    /** @test */
    public function flushIsNoopWhenNothingPending(): void
    {
        $mock = new MockHandler([]); // no responses queued
        $httpClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);

        $transport = new HttpTransport('http://localhost:3020', ['path' => '/api/v1/events']);
        $this->injectMockHttpClient($transport, $httpClient);

        // Calling flush on an empty queue must not throw or attempt any HTTP call.
        $transport->flush(1.0);

        $this->assertSame(0, $mock->count(), 'No mock requests should have been consumed');
    }

    /** @test */
    public function flushSwallowsRejectedPromisesSilently(): void
    {
        $mock = new MockHandler([
            new ConnectException(
                'mock connect failure',
                new Request('POST', 'http://localhost:3020/api/v1/events')
            ),
            new Response(200),
        ]);

        $httpClient = new GuzzleClient(['handler' => HandlerStack::create($mock)]);

        $transport = new HttpTransport('http://localhost:3020', ['path' => '/api/v1/events']);
        $this->injectMockHttpClient($transport, $httpClient);

        $transport->send(new Event('ch1', 'fails'));
        $transport->send(new Event('ch2', 'succeeds'));

        // Must not throw — Utils::settle resolves both promises and the rejection is swallowed.
        $transport->flush();

        $this->assertSame(0, $mock->count(), 'Both mock entries should have been consumed');
    }

    /** @test */
    public function sendReturnsNullWhenPromiseCreationThrows(): void
    {
        // Simulate a rare path: the transport's http client throws synchronously on postAsync.
        $brokenClient = new class extends GuzzleClient {
            public function postAsync($uri, array $options = []): PromiseInterface
            {
                throw new \RuntimeException('cannot create promise');
            }
        };

        $transport = new HttpTransport('http://localhost:3020', ['path' => '/api/v1/events'], 'tk');
        $this->injectMockHttpClient($transport, $brokenClient);

        $result = $transport->send(new Event('ch', 'x'));

        $this->assertNull($result);
    }

    private function injectMockHttpClient(HttpTransport $transport, GuzzleClient $client): void
    {
        $reflection = new \ReflectionClass($transport);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($transport, $client);
    }
}

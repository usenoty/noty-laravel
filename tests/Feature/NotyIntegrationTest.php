<?php

namespace Noty\Laravel\Tests\Feature;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Noty\Laravel\Facades\Noty;
use Noty\Laravel\Support\Transports\HttpTransport;
use Noty\Laravel\Support\Transports\TransportInterface;
use Noty\Laravel\Tests\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class NotyIntegrationTest extends TestCase
{
    /** @var RequestInterface[] */
    private array $capturedRequests = [];

    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->capturedRequests = [];

        // Pre-fill enough successful responses for any test in this class.
        $this->mockHandler = new MockHandler(array_fill(0, 16, new Response(200, [], '{"ok":true}')));

        $stack = HandlerStack::create($this->mockHandler);
        $stack->push(Middleware::history($this->capturedRequests));

        $client = new GuzzleClient(['handler' => $stack]);

        // Inject mock client into the singleton HttpTransport.
        /** @var HttpTransport $transport */
        $transport = $this->app->make(TransportInterface::class);
        $reflection = new \ReflectionClass($transport);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($transport, $client);
    }

    /** @test */
    public function captureEventViaFacadeIssuesPostWithExpectedPayload(): void
    {
        Noty::captureEvent([
            'title' => 'Test Title',
            'message' => 'Test Message',
        ]);

        Noty::flush();

        $this->assertCount(1, $this->capturedRequests);
        $request = $this->capturedRequests[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('http://localhost:3020/api/v1/events', (string) $request->getUri());

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame('Test Title', $payload['title']);
        $this->assertSame('Test Message', $payload['message']);
        $this->assertSame('channel_general', $payload['channel']);
    }

    /** @test */
    public function namedChannelIsResolvedToConfiguredId(): void
    {
        Noty::captureEvent([
            'title' => 'Payment received',
            'channel' => 'payments',
        ]);
        Noty::flush();

        $payload = json_decode((string) $this->capturedRequests[0]['request']->getBody(), true);
        $this->assertSame('channel_payments', $payload['channel']);
    }

    /** @test */
    public function fullFeaturedEventSerializesAllFields(): void
    {
        Noty::captureEvent([
            'title' => 'Full event',
            'message' => 'detail',
            'channel' => 'general',
            'priority' => 'HIGH',
            'actions' => [['name' => 'View', 'url' => 'https://x', 'browser' => true]],
            'tags' => ['user_id' => 7],
        ]);
        Noty::flush();

        $payload = json_decode((string) $this->capturedRequests[0]['request']->getBody(), true);
        $this->assertSame('HIGH', $payload['priority']);
        $this->assertSame([['name' => 'View', 'url' => 'https://x', 'browser' => true]], $payload['actions']);
        $this->assertSame(['user_id' => 7], $payload['tags']);
    }

    /** @test */
    public function bearerTokenIsSentAsAuthorizationHeader(): void
    {
        Noty::captureEvent(['title' => 'auth test']);
        Noty::flush();

        $authHeader = $this->capturedRequests[0]['request']->getHeader('Authorization');
        $this->assertSame(['Bearer test_token_123'], $authHeader);
    }

    /** @test */
    public function helperFunctionDispatchesThroughSameTransport(): void
    {
        noty()->captureEvent(['title' => 'from helper']);
        noty()->flush();

        $this->assertCount(1, $this->capturedRequests);
        $payload = json_decode((string) $this->capturedRequests[0]['request']->getBody(), true);
        $this->assertSame('from helper', $payload['title']);
    }

    /** @test */
    public function multipleCapturedEventsAreFlushedTogether(): void
    {
        Noty::captureEvent(['title' => 'Event 1']);
        Noty::captureEvent(['title' => 'Event 2']);
        Noty::captureEvent(['title' => 'Event 3']);

        $this->assertCount(0, $this->capturedRequests, 'No requests should have completed before flush');

        Noty::flush();

        $this->assertCount(3, $this->capturedRequests);
        $titles = array_map(
            fn ($r) => json_decode((string) $r['request']->getBody(), true)['title'],
            $this->capturedRequests
        );
        $this->assertSame(['Event 1', 'Event 2', 'Event 3'], $titles);
    }
}

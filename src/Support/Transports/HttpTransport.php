<?php

namespace Noty\Laravel\Support\Transports;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Noty\Laravel\Support\Event;

class HttpTransport implements TransportInterface
{
    protected string $endpoint;
    protected array $options;
    protected GuzzleClient $http;
    protected ?string $token;

    /** @var PromiseInterface[] */
    protected array $pending = [];

    public function __construct(string $dsn, array $httpOptions = [], ?string $token = null)
    {
        $this->endpoint = self::buildEndpoint($dsn, $httpOptions);
        $this->options = $httpOptions;
        $this->token = $token;
        $this->http = new GuzzleClient([
            'timeout' => $httpOptions['timeout'] ?? 0.5,
            'connect_timeout' => $httpOptions['connect_timeout'] ?? 0.25,
        ]);
    }

    /**
     * Build the full POST endpoint from a DSN and the http options array.
     *
     * Single source of truth for endpoint construction; reused by callers
     * (e.g. the service provider) that need the resolved URL without
     * instantiating a transport.
     */
    public static function buildEndpoint(string $dsn, array $httpOptions = []): string
    {
        $path = $httpOptions['path'] ?? '/api/v1/events';

        return rtrim($dsn, '/') . $path;
    }

    public function send(Event $event): ?string
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        try {
            $promise = $this->http->postAsync($this->endpoint, [
                'headers' => $headers,
                'json' => $event->toArray(),
            ]);

            $this->pending[] = $promise;
        } catch (\Throwable $e) {
            // Failed to create promise
            return null;
        }

        return $event->channel; // Return channel as ID
    }

    public function flush(float $timeoutSeconds = 1.0): void
    {
        // $timeoutSeconds is intentionally unused: Guzzle promises do not expose
        // a wait-with-timeout primitive, so the actual upper bound on this call
        // is the per-request 'timeout' / 'connect_timeout' configured on the
        // underlying client. The parameter remains in the signature to satisfy
        // TransportInterface and to leave room for a future timeout-aware impl.
        if (empty($this->pending)) {
            return;
        }

        try {
            Utils::settle($this->pending)->wait(true);
        } catch (\Throwable $e) {
            // fail-silent: settled promises shouldn't throw, but we belt-and-brace
            // here because a misbehaving handler could still raise.
        } finally {
            $this->pending = [];
        }
    }
}

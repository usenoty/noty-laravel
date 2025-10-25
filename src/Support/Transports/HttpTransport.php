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
        $path = $httpOptions['path'] ?? '/api/v1/events';
        $this->endpoint = rtrim($dsn, '/') . $path;
        $this->options = $httpOptions;
        $this->token = $token;
        $this->http = new GuzzleClient([
            'timeout'         => $httpOptions['timeout'] ?? 0.5,
            'connect_timeout' => $httpOptions['connect_timeout'] ?? 0.25,
        ]);
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
                'json'    => $event->toArray(),
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
        if (empty($this->pending)) {
            return;
        }

        try {
            Utils::settle($this->pending)->wait($timeoutSeconds);
        } catch (\Throwable $e) {
            // yut
        } finally {
            $this->pending = [];
        }
    }
}

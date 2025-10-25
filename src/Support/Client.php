<?php

namespace Noty\Laravel\Support;

use Noty\Laravel\Support\Transports\TransportInterface;

class Client
{
    public function __construct(
        protected TransportInterface $transport
    ) {}

    /**
     * Capture and send an event (similar to Sentry's captureException/captureMessage)
     * 
     * @param array $data Event data containing: title, message?, channel?, priority?, actions?, attachments?, tags?
     * @return ?string Event ID if sent
     */
    public function captureEvent(array $data): ?string
    {
        $event = new Event(
            channel: $this->resolveChannel($data['channel'] ?? null),
            title: $data['title'],
            message: $data['message'] ?? null,
            priority: $data['priority'] ?? config('noty.default_priority', 'MEDIUM'),
            actions: $data['actions'] ?? [],
            attachments: $data['attachments'] ?? [],
            tags: $data['tags'] ?? []
        );

        return $this->transport->send($event);
    }

    /**
     * Resolve channel name to channel ID
     */
    protected function resolveChannel(?string $channelNameOrId): string
    {
        if ($channelNameOrId === null) {
            $channelNameOrId = config('noty.channels.default', 'general');
        }

        $channels = config('noty.channels.list', []);

        // If it's a named channel, resolve it from config
        if (isset($channels[$channelNameOrId])) {
            return $channels[$channelNameOrId];
        }

        // Otherwise, treat it as a direct channel ID
        return $channelNameOrId;
    }

    public function flush(float $timeoutSeconds = 1.0): void
    {
        $this->transport->flush($timeoutSeconds);
    }
}

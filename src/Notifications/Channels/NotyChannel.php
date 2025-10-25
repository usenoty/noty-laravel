<?php

namespace Noty\Laravel\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Noty\Laravel\Support\Client;

class NotyChannel
{
    public function __construct(
        protected Client $client
    ) {}

    /**
     * Send notification via Noty channel
     * 
     * Expects toNoty($notifiable): array in Notification class with:
     * [
     *   'title' => 'User registered',
     *   'message' => 'A new user has registered',
     *   'channel' => 'auth', // Optional: channel name or ID
     *   'priority' => 'HIGH', // Optional: HIGH, NORMAL, LOW
     *   'actions' => [...],
     *   'tags' => [...]
     * ]
     */
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toNoty')) {
            return; // fail-silent
        }

        try {
            $data = $notification->toNoty($notifiable);

            // Add notifiable context to tags
            $tags = $data['tags'] ?? [];
            if (is_object($notifiable) && method_exists($notifiable, 'getKey')) {
                $tags['notifiable_type'] = get_class($notifiable);
                $tags['notifiable_id'] = (string) $notifiable->getKey();
            }

            $this->client->captureEvent([
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? null,
                'channel' => $data['channel'] ?? null,
                'priority' => $data['priority'] ?? null,
                'actions' => $data['actions'] ?? [],
                'attachments' => $data['attachments'] ?? [],
                'tags' => $tags,
            ]);
        } catch (\Throwable $e) {
            // fail-silent
        }
    }
}

<?php

namespace Noty\Laravel\Tests\Unit;

use Illuminate\Notifications\Notification;
use Mockery;
use Noty\Laravel\Notifications\Channels\NotyChannel;
use Noty\Laravel\Support\Client;
use Noty\Laravel\Tests\TestCase;

class NotyChannelTest extends TestCase
{
    protected Client $client;
    protected NotyChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(Client::class);
        $this->channel = new NotyChannel($this->client);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_sends_notification_via_channel(): void
    {
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();

        $this->client->shouldReceive('captureEvent')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn('channel_123');

        $this->channel->send($notifiable, $notification);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_adds_notifiable_context_to_tags(): void
    {
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();
        $notifiable->id = 456;

        $this->client->shouldReceive('captureEvent')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['title'])
                    && $data['title'] === 'Test Notification'
                    && isset($data['tags']['notifiable_type'])
                    && $data['tags']['notifiable_type'] === TestNotifiable::class
                    && $data['tags']['notifiable_id'] === '456'
                    && $data['tags']['custom'] === 'value';
            }))
            ->andReturn('channel_123');

        $this->channel->send($notifiable, $notification);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_ignores_notifications_without_to_noty_method(): void
    {
        $notification = new NotificationWithoutToNoty();
        $notifiable = new TestNotifiable();

        $this->client->shouldNotReceive('captureEvent');

        $this->channel->send($notifiable, $notification);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_send_failures_gracefully(): void
    {
        $notification = new TestNotificationWithException();
        $notifiable = new TestNotifiable();

        // Should not throw exception
        $this->channel->send($notifiable, $notification);

        $this->assertTrue(true);
    }
}

class TestNotifiable
{
    public $id = 123;

    public function getKey()
    {
        return $this->id;
    }
}

class TestNotification extends Notification
{
    public function toNoty($notifiable): array
    {
        return [
            'title' => 'Test Notification',
            'message' => 'Test message',
            'priority' => 'HIGH',
            'tags' => ['custom' => 'value']
        ];
    }
}

class NotificationWithoutToNoty extends Notification
{
    // No toNoty method
}

class TestNotificationWithException extends Notification
{
    public function toNoty($notifiable): array
    {
        throw new \Exception('Test exception');
    }
}


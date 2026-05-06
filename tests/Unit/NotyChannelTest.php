<?php

namespace Noty\Laravel\Tests\Unit;

use Illuminate\Notifications\Notification;
use Noty\Laravel\Notifications\Channels\NotyChannel;
use Noty\Laravel\Support\Client;
use Noty\Laravel\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class NotyChannelTest extends TestCase
{
    protected Client $client;
    protected NotyChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = \Mockery::mock(Client::class);
        $this->channel = new NotyChannel($this->client);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function itSendsNotificationViaChannel(): void
    {
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();

        $this->client->shouldReceive('captureEvent')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturn('channel_123')
        ;

        $this->channel->send($notifiable, $notification);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function itAddsNotifiableContextToTags(): void
    {
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();
        $notifiable->id = 456;

        $this->client->shouldReceive('captureEvent')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return isset($data['title'])
                    && $data['title'] === 'Test Notification'
                    && isset($data['tags']['notifiable_type'])
                    && $data['tags']['notifiable_type'] === TestNotifiable::class
                    && $data['tags']['notifiable_id'] === '456'
                    && $data['tags']['custom'] === 'value';
            }))
            ->andReturn('channel_123')
        ;

        $this->channel->send($notifiable, $notification);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function itIgnoresNotificationsWithoutToNotyMethod(): void
    {
        $notification = new NotificationWithoutToNoty();
        $notifiable = new TestNotifiable();

        $this->client->shouldNotReceive('captureEvent');

        $this->channel->send($notifiable, $notification);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function itHandlesSendFailuresGracefully(): void
    {
        $notification = new TestNotificationWithException();
        $notifiable = new TestNotifiable();

        // Should not throw exception
        $this->channel->send($notifiable, $notification);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function itDoesNotAddNotifiableTagsWhenNotifiableHasNoGetKey(): void
    {
        $notification = new TestNotification();
        $notifiable = new \stdClass(); // plain object, no getKey()

        $this->client->shouldReceive('captureEvent')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return $data['tags']['custom'] === 'value'
                    && !isset($data['tags']['notifiable_type'])
                    && !isset($data['tags']['notifiable_id']);
            }))
            ->andReturn('channel_x')
        ;

        $this->channel->send($notifiable, $notification);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function itDoesNotAddNotifiableTagsWhenNotifiableIsScalar(): void
    {
        // Routing notifications (Notification::route('noty', 'foo')) can result in
        // a non-object notifiable. The channel should treat that gracefully.
        $notification = new TestNotification();

        $this->client->shouldReceive('captureEvent')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return !isset($data['tags']['notifiable_type'])
                    && !isset($data['tags']['notifiable_id']);
            }))
            ->andReturn('channel_x')
        ;

        $this->channel->send('not-an-object', $notification);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function itAppliesDefaultTitleAndEmptyDefaultsWhenToNotyReturnsEmpty(): void
    {
        $notification = new MinimalNotification();
        $notifiable = new TestNotifiable();

        $this->client->shouldReceive('captureEvent')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return $data['title'] === 'Notification'
                    && $data['message'] === null
                    && $data['channel'] === null
                    && $data['priority'] === null
                    && $data['actions'] === []
                    && $data['attachments'] === []
                    // notifiable_* still added because TestNotifiable has getKey()
                    && $data['tags']['notifiable_type'] === TestNotifiable::class
                    && $data['tags']['notifiable_id'] === '123';
            }))
            ->andReturn('channel_x')
        ;

        $this->channel->send($notifiable, $notification);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function itStringifiesNotifiableIdEvenWhenIntegerKey(): void
    {
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();
        $notifiable->id = 999; // int

        $this->client->shouldReceive('captureEvent')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return $data['tags']['notifiable_id'] === '999'
                    && is_string($data['tags']['notifiable_id']);
            }))
            ->andReturn('channel_x')
        ;

        $this->channel->send($notifiable, $notification);

        $this->addToAssertionCount(1);
    }
}

class MinimalNotification extends Notification
{
    public function toNoty($notifiable): array
    {
        return []; // explicitly empty — exercises every default branch
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
            'tags' => ['custom' => 'value'],
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

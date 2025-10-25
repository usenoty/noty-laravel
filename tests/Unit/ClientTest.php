<?php

namespace Noty\Laravel\Tests\Unit;

use Noty\Laravel\Support\Client;
use Noty\Laravel\Support\Event;
use Noty\Laravel\Support\Transports\TransportInterface;
use Noty\Laravel\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ClientTest extends TestCase
{
    protected TransportInterface $transport;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = \Mockery::mock(TransportInterface::class);
        $this->client = new Client($this->transport);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function itCapturesEventWithDefaultChannel(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function (Event $event) {
                return $event->channel === 'channel_general'
                    && $event->title === 'Test Title'
                    && $event->message === 'Test Message';
            }))
            ->andReturn('channel_general')
        ;

        $result = $this->client->captureEvent([
            'title' => 'Test Title',
            'message' => 'Test Message',
        ]);

        $this->assertEquals('channel_general', $result);
    }

    /** @test */
    public function itCapturesEventWithSpecificChannel(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function (Event $event) {
                return $event->channel === 'channel_auth';
            }))
            ->andReturn('channel_auth')
        ;

        $result = $this->client->captureEvent([
            'title' => 'Test',
            'channel' => 'auth',
        ]);

        $this->assertEquals('channel_auth', $result);
    }

    /** @test */
    public function itCapturesFullFeaturedEvent(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function (Event $event) {
                return $event->title === 'Test'
                    && $event->priority === 'HIGH'
                    && count($event->actions) === 1
                    && $event->tags['user_id'] === '123';
            }))
            ->andReturn('channel_general')
        ;

        $result = $this->client->captureEvent([
            'title' => 'Test',
            'channel' => 'general',
            'priority' => 'HIGH',
            'actions' => [['name' => 'Action']],
            'tags' => ['user_id' => '123'],
        ]);

        $this->assertEquals('channel_general', $result);
    }

    /** @test */
    public function itResolvesNamedChannelsToIds(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function (Event $event) {
                return $event->channel === 'channel_auth';
            }))
            ->andReturn('channel_auth')
        ;

        $this->client->captureEvent([
            'title' => 'Test',
            'channel' => 'auth',
        ]);

        $this->assertTrue(true); // Assertion done in mock
    }

    /** @test */
    public function itAcceptsDirectChannelIds(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function (Event $event) {
                return $event->channel === 'channel_direct_id_xyz';
            }))
            ->andReturn('channel_direct_id_xyz')
        ;

        $this->client->captureEvent([
            'title' => 'Test',
            'channel' => 'channel_direct_id_xyz',
        ]);

        $this->assertTrue(true);
    }

    /** @test */
    public function itFlushesTransport(): void
    {
        $this->transport->shouldReceive('flush')
            ->once()
            ->with(1.0)
        ;

        $this->client->flush(1.0);

        $this->assertTrue(true);
    }

    /** @test */
    public function itPassesTagsAsIs(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function (Event $event) {
                // Ensure tags are passed as-is without normalization
                return is_array($event->tags)
                    && $event->tags['user_id'] === 123
                    && $event->tags['count'] === 5;
            }))
            ->andReturn('channel_general')
        ;

        $this->client->captureEvent([
            'title' => 'Test',
            'tags' => [
                'user_id' => 123,
                'count' => 5,
            ],
        ]);

        $this->assertTrue(true); // Assertion done in mock
    }
}

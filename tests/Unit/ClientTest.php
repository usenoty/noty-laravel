<?php

namespace Noty\Laravel\Tests\Unit;

use Mockery;
use Noty\Laravel\Support\Client;
use Noty\Laravel\Support\Event;
use Noty\Laravel\Support\Transports\TransportInterface;
use Noty\Laravel\Tests\TestCase;

class ClientTest extends TestCase
{
    protected TransportInterface $transport;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = Mockery::mock(TransportInterface::class);
        $this->client = new Client($this->transport);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_captures_event_with_default_channel(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function (Event $event) {
                return $event->channel === 'channel_general'
                    && $event->title === 'Test Title'
                    && $event->message === 'Test Message';
            }))
            ->andReturn('channel_general');

        $result = $this->client->captureEvent([
            'title' => 'Test Title',
            'message' => 'Test Message',
        ]);

        $this->assertEquals('channel_general', $result);
    }

    /** @test */
    public function it_captures_event_with_specific_channel(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function (Event $event) {
                return $event->channel === 'channel_auth';
            }))
            ->andReturn('channel_auth');

        $result = $this->client->captureEvent([
            'title' => 'Test',
            'channel' => 'auth',
        ]);

        $this->assertEquals('channel_auth', $result);
    }

    /** @test */
    public function it_captures_full_featured_event(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function (Event $event) {
                return $event->title === 'Test'
                    && $event->priority === 'HIGH'
                    && count($event->actions) === 1
                    && $event->tags['user_id'] === '123';
            }))
            ->andReturn('channel_general');

        $result = $this->client->captureEvent([
            'title' => 'Test',
            'channel' => 'general',
            'priority' => 'HIGH',
            'actions' => [['name' => 'Action']],
            'tags' => ['user_id' => '123']
        ]);

        $this->assertEquals('channel_general', $result);
    }

    /** @test */
    public function it_resolves_named_channels_to_ids(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function (Event $event) {
                return $event->channel === 'channel_auth';
            }))
            ->andReturn('channel_auth');

        $this->client->captureEvent([
            'title' => 'Test',
            'channel' => 'auth',
        ]);

        $this->assertTrue(true); // Assertion done in mock
    }

    /** @test */
    public function it_accepts_direct_channel_ids(): void
    {
        $this->transport->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function (Event $event) {
                return $event->channel === 'channel_direct_id_xyz';
            }))
            ->andReturn('channel_direct_id_xyz');

        $this->client->captureEvent([
            'title' => 'Test',
            'channel' => 'channel_direct_id_xyz',
        ]);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_flushes_transport(): void
    {
        $this->transport->shouldReceive('flush')
            ->once()
            ->with(1.0);

        $this->client->flush(1.0);

        $this->assertTrue(true);
    }


}


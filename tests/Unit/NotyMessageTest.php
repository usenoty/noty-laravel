<?php

namespace Noty\Laravel\Tests\Unit;

use Mockery;
use Noty\Laravel\NotyMessage;
use Noty\Laravel\Tests\TestCase;

class NotyMessageTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_a_message_with_title(): void
    {
        $message = NotyMessage::create('Test Title');

        $this->assertInstanceOf(NotyMessage::class, $message);
    }

    /** @test */
    public function it_builds_array_with_required_fields(): void
    {
        $data = NotyMessage::create('Test Title')->toArray();

        $this->assertEquals('Test Title', $data['title']);
        // Optional fields should not be included when empty/default
        $this->assertArrayNotHasKey('priority', $data);
        $this->assertArrayNotHasKey('actions', $data);
        $this->assertArrayNotHasKey('attachments', $data);
        $this->assertArrayNotHasKey('tags', $data);
    }

    /** @test */
    public function it_sets_message_and_channel(): void
    {
        $data = NotyMessage::create('Test')
            ->message('Test message')
            ->channel('auth')
            ->toArray();

        $this->assertEquals('Test message', $data['message']);
        $this->assertEquals('auth', $data['channel']);
    }

    /** @test */
    public function it_sets_priority(): void
    {
        $high = NotyMessage::create('Test')->priority(NotyMessage::PRIORITY_HIGH)->toArray();
        $low = NotyMessage::create('Test')->priority(NotyMessage::PRIORITY_LOW)->toArray();

        $this->assertEquals(NotyMessage::PRIORITY_HIGH, $high['priority']);
        $this->assertEquals(NotyMessage::PRIORITY_LOW, $low['priority']);
    }

    /** @test */
    public function it_adds_single_action(): void
    {
        $data = NotyMessage::create('Test')
            ->action('View', 'http://example.com')
            ->toArray();

        $this->assertCount(1, $data['actions']);
        $this->assertEquals('View', $data['actions'][0]['name']);
        $this->assertEquals('http://example.com', $data['actions'][0]['url']);
        $this->assertFalse($data['actions'][0]['browser']);
    }

    /** @test */
    public function it_adds_action_with_browser_flag(): void
    {
        $data = NotyMessage::create('Test')
            ->action('View', 'http://example.com', true)
            ->toArray();

        $this->assertTrue($data['actions'][0]['browser']);
    }

    /** @test */
    public function it_adds_multiple_actions(): void
    {
        $data = NotyMessage::create('Test')
            ->action('View', 'http://example.com')
            ->action('Edit', 'http://example.com/edit')
            ->toArray();

        $this->assertCount(2, $data['actions']);
        $this->assertEquals('View', $data['actions'][0]['name']);
        $this->assertEquals('Edit', $data['actions'][1]['name']);
    }

    /** @test */
    public function it_adds_single_tag(): void
    {
        $data = NotyMessage::create('Test')
            ->tag('user_id', '123')
            ->toArray();

        $this->assertEquals('123', $data['tags']['user_id']);
    }

    /** @test */
    public function it_adds_multiple_tags(): void
    {
        $data = NotyMessage::create('Test')
            ->tags(['user_id' => '123', 'ip' => '127.0.0.1'])
            ->toArray();

        $this->assertEquals('123', $data['tags']['user_id']);
        $this->assertEquals('127.0.0.1', $data['tags']['ip']);
    }

    /** @test */
    public function it_adds_emoji_to_title(): void
    {
        $data = NotyMessage::create('Test')
            ->emoji('🎉')
            ->toArray();

        $this->assertEquals('🎉 Test', $data['title']);
    }

    /** @test */
    public function it_omits_null_values_in_array(): void
    {
        $data = NotyMessage::create('Test')->toArray();

        $this->assertArrayNotHasKey('message', $data);
        $this->assertArrayNotHasKey('channel', $data);
    }

    /** @test */
    public function it_sends_message_via_client(): void
    {
        $client = Mockery::mock(\Noty\Laravel\Support\Client::class);
        $client->shouldReceive('captureEvent')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn('event_id_123');

        app()->instance(\Noty\Laravel\Support\Client::class, $client);

        $result = NotyMessage::create('Test')->send();

        $this->assertEquals('event_id_123', $result);
    }

    /** @test */
    public function it_handles_fluent_chaining(): void
    {
        $data = NotyMessage::create('Test')
            ->message('Message')
            ->channel('orders')
            ->priority(NotyMessage::PRIORITY_HIGH)
            ->action('View', 'http://example.com', true)
            ->tag('order_id', '123')
            ->emoji('📦')
            ->toArray();

        $this->assertEquals('📦 Test', $data['title']);
        $this->assertEquals('Message', $data['message']);
        $this->assertEquals('orders', $data['channel']);
        $this->assertEquals(NotyMessage::PRIORITY_HIGH, $data['priority']);
        $this->assertCount(1, $data['actions']);
        $this->assertEquals('123', $data['tags']['order_id']);
    }

    /** @test */
    public function it_omits_empty_tags_from_output(): void
    {
        $data = NotyMessage::create('Test')->toArray();

        // Empty tags should not be included in the output
        $this->assertArrayNotHasKey('tags', $data);
    }

    /** @test */
    public function it_passes_tag_values_as_is(): void
    {
        $data = NotyMessage::create('Test')
            ->tag('user_id', 123)
            ->tag('count', 5)
            ->toArray();

        $this->assertEquals(123, $data['tags']['user_id']);
        $this->assertEquals(5, $data['tags']['count']);
        $this->assertIsInt($data['tags']['user_id']);
        $this->assertIsInt($data['tags']['count']);
    }
}

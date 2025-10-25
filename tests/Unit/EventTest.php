<?php

namespace Noty\Laravel\Tests\Unit;

use Noty\Laravel\Support\Event;
use Noty\Laravel\Tests\TestCase;

class EventTest extends TestCase
{
    /** @test */
    public function it_creates_event_with_required_fields(): void
    {
        $event = new Event(
            channel: 'channel_123',
            title: 'Test Event'
        );

        $this->assertEquals('channel_123', $event->channel);
        $this->assertEquals('Test Event', $event->title);
        $this->assertNull($event->message);
        $this->assertEquals('MEDIUM', $event->priority);
        $this->assertEmpty($event->actions);
        $this->assertEmpty($event->attachments);
        $this->assertEmpty($event->tags);
    }

    /** @test */
    public function it_creates_event_with_all_fields(): void
    {
        $event = new Event(
            channel: 'channel_123',
            title: 'Test Event',
            message: 'Test message',
            priority: 'HIGH',
            actions: [['name' => 'Action']],
            attachments: [['file' => 'test.pdf']],
            tags: ['user_id' => '123']
        );

        $this->assertEquals('channel_123', $event->channel);
        $this->assertEquals('Test Event', $event->title);
        $this->assertEquals('Test message', $event->message);
        $this->assertEquals('HIGH', $event->priority);
        $this->assertCount(1, $event->actions);
        $this->assertCount(1, $event->attachments);
        $this->assertArrayHasKey('user_id', $event->tags);
    }

    /** @test */
    public function it_converts_to_array_correctly(): void
    {
        $event = new Event(
            channel: 'channel_123',
            title: 'Test Event',
            message: 'Test message',
            priority: 'HIGH',
            tags: ['user_id' => '123']
        );

        $array = $event->toArray();

        $this->assertArrayHasKey('channel', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('priority', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertEquals('channel_123', $array['channel']);
        $this->assertEquals('Test Event', $array['title']);
    }

    /** @test */
    public function it_excludes_null_message_from_array(): void
    {
        $event = new Event(
            channel: 'channel_123',
            title: 'Test Event'
        );

        $array = $event->toArray();

        $this->assertArrayNotHasKey('message', $array);
    }

    /** @test */
    public function it_includes_message_when_provided(): void
    {
        $event = new Event(
            channel: 'channel_123',
            title: 'Test Event',
            message: 'Test message'
        );

        $array = $event->toArray();

        $this->assertArrayHasKey('message', $array);
        $this->assertEquals('Test message', $array['message']);
    }
}


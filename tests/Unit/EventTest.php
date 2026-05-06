<?php

namespace Noty\Laravel\Tests\Unit;

use Noty\Laravel\Support\Event;
use Noty\Laravel\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class EventTest extends TestCase
{
    /** @test */
    public function itCreatesEventWithRequiredFields(): void
    {
        $event = new Event(
            channel: 'channel_123',
            title: 'Test Event'
        );

        $this->assertEquals('channel_123', $event->channel);
        $this->assertEquals('Test Event', $event->title);
        $this->assertNull($event->message);
        $this->assertEquals('NORMAL', $event->priority);
        $this->assertEmpty($event->actions);
        $this->assertEmpty($event->attachments);
        $this->assertEmpty($event->tags);
    }

    /** @test */
    public function itCreatesEventWithAllFields(): void
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
    public function itConvertsToArrayCorrectly(): void
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
    public function itExcludesNullMessageFromArray(): void
    {
        $event = new Event(
            channel: 'channel_123',
            title: 'Test Event'
        );

        $array = $event->toArray();

        $this->assertArrayNotHasKey('message', $array);
    }

    /** @test */
    public function itIncludesMessageWhenProvided(): void
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

    /** @test */
    public function toArrayExcludesNormalPriority(): void
    {
        $event = new Event(channel: 'ch', title: 't', priority: 'NORMAL');

        $this->assertArrayNotHasKey('priority', $event->toArray());
    }

    /** @test */
    public function toArrayIncludesNonNormalPriority(): void
    {
        $high = new Event(channel: 'ch', title: 't', priority: 'HIGH');
        $low = new Event(channel: 'ch', title: 't', priority: 'LOW');

        $this->assertSame('HIGH', $high->toArray()['priority']);
        $this->assertSame('LOW', $low->toArray()['priority']);
    }

    /** @test */
    public function toArrayExcludesEmptyActionsAttachmentsAndTags(): void
    {
        $event = new Event(
            channel: 'ch',
            title: 't',
            actions: [],
            attachments: [],
            tags: []
        );

        $array = $event->toArray();

        $this->assertArrayNotHasKey('actions', $array);
        $this->assertArrayNotHasKey('attachments', $array);
        $this->assertArrayNotHasKey('tags', $array);
    }

    /** @test */
    public function toArrayIncludesActionsAttachmentsAndTagsWhenPopulated(): void
    {
        $event = new Event(
            channel: 'ch',
            title: 't',
            actions: [['name' => 'View', 'url' => 'https://x', 'browser' => true]],
            attachments: [['file' => 'a.pdf']],
            tags: ['k' => 'v']
        );

        $array = $event->toArray();

        $this->assertSame(
            [['name' => 'View', 'url' => 'https://x', 'browser' => true]],
            $array['actions']
        );
        $this->assertSame([['file' => 'a.pdf']], $array['attachments']);
        $this->assertSame(['k' => 'v'], $array['tags']);
    }

    /** @test */
    public function toArrayKeysOrderRequiredFieldsFirst(): void
    {
        $event = new Event(
            channel: 'ch',
            title: 't',
            message: 'm',
            priority: 'HIGH',
            tags: ['k' => 'v']
        );

        $keys = array_keys($event->toArray());

        // channel and title come first, in that order; rest follow conditional inclusion order.
        $this->assertSame('channel', $keys[0]);
        $this->assertSame('title', $keys[1]);
        $this->assertContains('message', $keys);
        $this->assertContains('priority', $keys);
        $this->assertContains('tags', $keys);
    }
}

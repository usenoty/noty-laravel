<?php

namespace Noty\Laravel\Tests\Unit;

use Noty\Laravel\NotyMessage;
use Noty\Laravel\Support\Client;
use Noty\Laravel\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class NotyMessageTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function itCreatesAMessageWithTitle(): void
    {
        $message = NotyMessage::create('Test Title');

        $this->assertInstanceOf(NotyMessage::class, $message);
    }

    /** @test */
    public function itBuildsArrayWithRequiredFields(): void
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
    public function itSetsMessageAndChannel(): void
    {
        $data = NotyMessage::create('Test')
            ->message('Test message')
            ->channel('auth')
            ->toArray()
        ;

        $this->assertEquals('Test message', $data['message']);
        $this->assertEquals('auth', $data['channel']);
    }

    /** @test */
    public function itSetsPriority(): void
    {
        $high = NotyMessage::create('Test')->priority(NotyMessage::PRIORITY_HIGH)->toArray();
        $low = NotyMessage::create('Test')->priority(NotyMessage::PRIORITY_LOW)->toArray();

        $this->assertEquals(NotyMessage::PRIORITY_HIGH, $high['priority']);
        $this->assertEquals(NotyMessage::PRIORITY_LOW, $low['priority']);
    }

    /** @test */
    public function itAddsSingleAction(): void
    {
        $data = NotyMessage::create('Test')
            ->action('View', 'http://example.com')
            ->toArray()
        ;

        $this->assertCount(1, $data['actions']);
        $this->assertEquals('View', $data['actions'][0]['name']);
        $this->assertEquals('http://example.com', $data['actions'][0]['url']);
        $this->assertFalse($data['actions'][0]['browser']);
    }

    /** @test */
    public function itAddsActionWithBrowserFlag(): void
    {
        $data = NotyMessage::create('Test')
            ->action('View', 'http://example.com', true)
            ->toArray()
        ;

        $this->assertTrue($data['actions'][0]['browser']);
    }

    /** @test */
    public function itAddsMultipleActions(): void
    {
        $data = NotyMessage::create('Test')
            ->action('View', 'http://example.com')
            ->action('Edit', 'http://example.com/edit')
            ->toArray()
        ;

        $this->assertCount(2, $data['actions']);
        $this->assertEquals('View', $data['actions'][0]['name']);
        $this->assertEquals('Edit', $data['actions'][1]['name']);
    }

    /** @test */
    public function itAddsSingleTag(): void
    {
        $data = NotyMessage::create('Test')
            ->tag('user_id', '123')
            ->toArray()
        ;

        $this->assertEquals('123', $data['tags']['user_id']);
    }

    /** @test */
    public function itAddsMultipleTags(): void
    {
        $data = NotyMessage::create('Test')
            ->tags(['user_id' => '123', 'ip' => '127.0.0.1'])
            ->toArray()
        ;

        $this->assertEquals('123', $data['tags']['user_id']);
        $this->assertEquals('127.0.0.1', $data['tags']['ip']);
    }

    /** @test */
    public function itAddsEmojiToTitle(): void
    {
        $data = NotyMessage::create('Test')
            ->emoji('🎉')
            ->toArray()
        ;

        $this->assertEquals('🎉 Test', $data['title']);
    }

    /** @test */
    public function itOmitsNullValuesInArray(): void
    {
        $data = NotyMessage::create('Test')->toArray();

        $this->assertArrayNotHasKey('message', $data);
        $this->assertArrayNotHasKey('channel', $data);
    }

    /** @test */
    public function itSendsMessageViaClient(): void
    {
        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('captureEvent')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturn('event_id_123')
        ;

        app()->instance(Client::class, $client);

        $result = NotyMessage::create('Test')->send();

        $this->assertEquals('event_id_123', $result);
    }

    /** @test */
    public function itHandlesFluentChaining(): void
    {
        $data = NotyMessage::create('Test')
            ->message('Message')
            ->channel('orders')
            ->priority(NotyMessage::PRIORITY_HIGH)
            ->action('View', 'http://example.com', true)
            ->tag('order_id', '123')
            ->emoji('📦')
            ->toArray()
        ;

        $this->assertEquals('📦 Test', $data['title']);
        $this->assertEquals('Message', $data['message']);
        $this->assertEquals('orders', $data['channel']);
        $this->assertEquals(NotyMessage::PRIORITY_HIGH, $data['priority']);
        $this->assertCount(1, $data['actions']);
        $this->assertEquals('123', $data['tags']['order_id']);
    }

    /** @test */
    public function itOmitsEmptyTagsFromOutput(): void
    {
        $data = NotyMessage::create('Test')->toArray();

        // Empty tags should not be included in the output
        $this->assertArrayNotHasKey('tags', $data);
    }

    /** @test */
    public function itPassesTagValuesAsIs(): void
    {
        $data = NotyMessage::create('Test')
            ->tag('user_id', 123)
            ->tag('count', 5)
            ->toArray()
        ;

        $this->assertEquals(123, $data['tags']['user_id']);
        $this->assertEquals(5, $data['tags']['count']);
        $this->assertIsInt($data['tags']['user_id']);
        $this->assertIsInt($data['tags']['count']);
    }

    /** @test */
    public function itAddsActionsViaArrayMethod(): void
    {
        $data = NotyMessage::create('Test')
            ->actions([
                ['name' => 'View', 'url' => 'https://example.com'],
                ['name' => 'Edit', 'url' => 'https://example.com/edit', 'browser' => true],
            ])
            ->toArray()
        ;

        $this->assertCount(2, $data['actions']);
        $this->assertSame('View', $data['actions'][0]['name']);
        $this->assertFalse($data['actions'][0]['browser']); // default when key omitted
        $this->assertSame('Edit', $data['actions'][1]['name']);
        $this->assertTrue($data['actions'][1]['browser']);
    }

    /** @test */
    public function actionsBulkMethodAppendsToExistingActions(): void
    {
        $data = NotyMessage::create('Test')
            ->action('First', 'https://a')
            ->actions([
                ['name' => 'Second', 'url' => 'https://b'],
            ])
            ->toArray()
        ;

        $this->assertCount(2, $data['actions']);
        $this->assertSame('First', $data['actions'][0]['name']);
        $this->assertSame('Second', $data['actions'][1]['name']);
    }

    /** @test */
    public function itAddsAttachment(): void
    {
        $data = NotyMessage::create('Test')
            ->attachment(['file' => 'invoice.pdf', 'size' => 1024])
            ->toArray()
        ;

        $this->assertArrayHasKey('attachments', $data);
        $this->assertCount(1, $data['attachments']);
        $this->assertSame('invoice.pdf', $data['attachments'][0]['file']);
        $this->assertSame(1024, $data['attachments'][0]['size']);
    }

    /** @test */
    public function itAccumulatesMultipleAttachments(): void
    {
        $data = NotyMessage::create('Test')
            ->attachment(['file' => 'a.pdf'])
            ->attachment(['file' => 'b.pdf'])
            ->toArray()
        ;

        $this->assertCount(2, $data['attachments']);
        $this->assertSame('a.pdf', $data['attachments'][0]['file']);
        $this->assertSame('b.pdf', $data['attachments'][1]['file']);
    }

    /** @test */
    public function itOmitsAttachmentsKeyWhenEmpty(): void
    {
        $data = NotyMessage::create('Test')->toArray();

        $this->assertArrayNotHasKey('attachments', $data);
    }

    /** @test */
    public function tagsBulkMergesWithExistingTags(): void
    {
        $data = NotyMessage::create('Test')
            ->tag('a', 1)
            ->tags(['b' => 2, 'c' => 3])
            ->tag('d', 4)
            ->toArray()
        ;

        $this->assertSame([
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ], $data['tags']);
    }

    /** @test */
    public function tagsBulkOverwritesDuplicateKeys(): void
    {
        $data = NotyMessage::create('Test')
            ->tag('user_id', 1)
            ->tags(['user_id' => 2])
            ->toArray()
        ;

        $this->assertSame(2, $data['tags']['user_id']);
    }

    /** @test */
    public function priorityIsExcludedWhenLeftAtNormalDefault(): void
    {
        $data = NotyMessage::create('Test')
            ->priority(NotyMessage::PRIORITY_NORMAL)
            ->toArray()
        ;

        $this->assertArrayNotHasKey('priority', $data);
    }
}

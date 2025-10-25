<?php

namespace Noty\Laravel;

class NotyMessage
{
    // Priority constants
    public const PRIORITY_HIGH = 'HIGH';
    public const PRIORITY_MEDIUM = 'MEDIUM';
    public const PRIORITY_LOW = 'LOW';

    protected string $title;
    protected ?string $message = null;
    protected ?string $channel = null;
    protected string $priority = self::PRIORITY_MEDIUM;
    protected array $actions = [];
    protected array $attachments = [];
    protected array $tags = [];
    protected ?string $emoji = null;

    /**
     * Create a new Noty message
     */
    public static function create(string $title): static
    {
        return new self($title);
    }

    /**
     * Constructor
     */
    public function __construct(string $title)
    {
        $this->title = $title;
    }

    /**
     * Set the message body
     */
    public function message(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set the channel (named or direct ID)
     */
    public function channel(?string $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * Set the priority
     */
    public function priority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Add an action button
     */
    public function action(string $name, string $url, bool $browser = false): static
    {
        $this->actions[] = [
            'name' => $name,
            'url' => $url,
            'browser' => $browser,
        ];
        return $this;
    }

    /**
     * Add multiple actions at once
     */
    public function actions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->action(
                $action['name'],
                $action['url'],
                $action['browser'] ?? false
            );
        }
        return $this;
    }

    /**
     * Add an attachment
     */
    public function attachment(array $attachment): static
    {
        $this->attachments[] = $attachment;
        return $this;
    }

    /**
     * Add a tag for filtering
     */
    public function tag(string $key, mixed $value): static
    {
        $this->tags[$key] = $value;
        return $this;
    }

    /**
     * Add multiple tags at once
     */
    public function tags(array $tags): static
    {
        $this->tags = array_merge($this->tags, $tags);
        return $this;
    }

    /**
     * Add an emoji prefix to the title
     */
    public function emoji(string $emoji): static
    {
        $this->emoji = $emoji;
        return $this;
    }

    /**
     * Build the title with emoji prefix if set
     */
    protected function getTitle(): string
    {
        if ($this->emoji) {
            return $this->emoji . ' ' . $this->title;
        }
        return $this->title;
    }

    /**
     * Convert to array for captureEvent
     */
    public function toArray(): array
    {
        $data = [
            'title' => $this->getTitle(),
            'priority' => $this->priority,
            'actions' => $this->actions,
            'attachments' => $this->attachments,
            'tags' => $this->tags,
        ];

        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        if ($this->channel !== null) {
            $data['channel'] = $this->channel;
        }

        return $data;
    }

    /**
     * Send the message via Noty
     */
    public function send(): ?string
    {
        return app(\Noty\Laravel\Support\Client::class)->captureEvent($this->toArray());
    }
}

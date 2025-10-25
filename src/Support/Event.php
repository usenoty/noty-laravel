<?php

namespace Noty\Laravel\Support;

class Event
{
    public string $channel;
    public string $title;
    public ?string $message;
    public string $priority;
    public array $actions;
    public array $attachments;
    public array $tags;

    public function __construct(
        string $channel,
        string $title,
        ?string $message = null,
        string $priority = 'NORMAL',
        array $actions = [],
        array $attachments = [],
        array $tags = []
    ) {
        $this->channel = $channel;
        $this->title = $title;
        $this->message = $message;
        $this->priority = $priority;
        $this->actions = $actions;
        $this->attachments = $attachments;
        $this->tags = $tags;
    }

    public function toArray(): array
    {
        $data = [
            'channel' => $this->channel,
            'title'   => $this->title,
        ];

        // Only include optional fields if they have values
        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        if ($this->priority !== 'NORMAL') { // Only include if not default
            $data['priority'] = $this->priority;
        }

        if (!empty($this->actions)) {
            $data['actions'] = $this->actions;
        }

        if (!empty($this->attachments)) {
            $data['attachments'] = $this->attachments;
        }

        if (!empty($this->tags)) {
            $data['tags'] = $this->tags;
        }

        return $data;
    }
}

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
        string $priority = 'MEDIUM',
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
            'channel'     => $this->channel,
            'title'       => $this->title,
            'priority'    => $this->priority,
            'actions'     => $this->actions,
            'attachments' => $this->attachments,
            'tags'        => $this->tags,
        ];

        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        return $data;
    }
}

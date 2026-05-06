<?php

namespace Noty\Laravel\Support;

class Event
{
    public function __construct(
        public readonly string $channel,
        public readonly string $title,
        public readonly ?string $message = null,
        public readonly string $priority = 'NORMAL',
        public readonly array $actions = [],
        public readonly array $attachments = [],
        public readonly array $tags = [],
    ) {}

    public function toArray(): array
    {
        $data = [
            'channel' => $this->channel,
            'title' => $this->title,
        ];

        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        if ($this->priority !== 'NORMAL') {
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

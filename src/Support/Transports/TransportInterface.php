<?php

namespace Noty\Laravel\Support\Transports;

use Noty\Laravel\Support\Event;

interface TransportInterface
{
    public function send(Event $event): ?string;

    public function flush(float $timeoutSeconds = 1.0): void;
}

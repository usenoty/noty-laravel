<?php

namespace Noty\Laravel\Support\Transports;

use Noty\Laravel\Support\Event;

interface TransportInterface
{
    /**
     * Hand the event off to the underlying transport.
     *
     * Returns the resolved channel id used to dispatch the event (a transport-local
     * correlation handle), or null when the transport could not even queue the
     * delivery (e.g. the underlying client threw before producing a promise).
     * The Noty API does not return an event id synchronously — callers should not
     * treat the return value as a remote identifier.
     */
    public function send(Event $event): ?string;

    /**
     * Flush any events buffered by the transport.
     *
     * The $timeoutSeconds parameter is best-effort: it represents how long the
     * caller is willing to wait, but the actual upper bound is dominated by the
     * configured per-request HTTP timeout. Implementations must not throw —
     * delivery failures are swallowed and reported through their own logging
     * channels.
     */
    public function flush(float $timeoutSeconds = 1.0): void;
}

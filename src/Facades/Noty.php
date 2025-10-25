<?php

namespace Noty\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Noty\Laravel\Support\Client;

/**
 * @method static ?string captureEvent(array $data)          Capture and send an event
 * @method static void    flush(float $timeoutSeconds = 1.0) Flush pending events
 */
class Noty extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Client::class;
    }
}

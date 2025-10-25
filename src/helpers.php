<?php

use Noty\Laravel\Support\Client;

if (!function_exists('noty')) {
    /**
     * Get the Noty client instance.
     */
    function noty(): Client
    {
        return app(Client::class);
    }
}

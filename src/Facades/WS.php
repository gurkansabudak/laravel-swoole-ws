<?php

namespace EFive\Ws\Facades;

use Illuminate\Support\Facades\Facade;

final class WS extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ws.router';
    }
}

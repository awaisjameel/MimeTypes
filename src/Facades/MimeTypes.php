<?php

namespace AwaisJameel\MimeTypes\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AwaisJameel\MimeTypes\MimeTypes
 */
class MimeTypes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AwaisJameel\MimeTypes\MimeTypes::class;
    }
}

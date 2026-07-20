<?php

declare(strict_types=1);

namespace AwaisJameel\MimeTypes\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, list<string>> all()
 * @method static bool has(string $mimeOrExtension)
 * @method static string toExtension(string $mime)
 * @method static list<string> extensionsFor(string $mime)
 * @method static string toMime(string $extension)
 * @method static string getExtensionOrMime(string $mimeOrExtension)
 * @method static array<string, list<string>> refresh()
 *
 * @see \AwaisJameel\MimeTypes\MimeTypes
 */
class MimeTypes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AwaisJameel\MimeTypes\MimeTypes::class;
    }
}

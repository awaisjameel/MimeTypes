<?php

declare(strict_types=1);

namespace AwaisJameel\MimeTypes\Exceptions;

class UnknownMimeTypeOrExtensionException extends MimeTypesException
{
    public static function forMime(string $mime): self
    {
        return new self("Unable to resolve an extension for the unknown MIME type [{$mime}].");
    }

    public static function forExtension(string $extension): self
    {
        return new self("Unable to resolve a MIME type for the unknown extension [{$extension}].");
    }

    public static function forValue(string $value): self
    {
        return new self("[{$value}] is not a known MIME type or file extension.");
    }
}

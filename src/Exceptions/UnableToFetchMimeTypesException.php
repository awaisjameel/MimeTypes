<?php

declare(strict_types=1);

namespace AwaisJameel\MimeTypes\Exceptions;

use Throwable;

class UnableToFetchMimeTypesException extends MimeTypesException
{
    public static function requestFailed(string $url, ?Throwable $previous = null): self
    {
        return new self("Unable to fetch the MIME type list from [{$url}].", previous: $previous);
    }

    public static function invalidResponse(string $url): self
    {
        return new self("The MIME type list fetched from [{$url}] was empty or malformed.");
    }
}

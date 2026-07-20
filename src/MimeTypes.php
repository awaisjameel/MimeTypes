<?php

declare(strict_types=1);

namespace AwaisJameel\MimeTypes;

use AwaisJameel\MimeTypes\Exceptions\UnableToFetchMimeTypesException;
use AwaisJameel\MimeTypes\Exceptions\UnknownMimeTypeOrExtensionException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MimeTypes
{
    /**
     * The resolved MIME type map, memoized for the lifetime of this instance.
     *
     * @var array{mimeToExtensions: array<string, list<string>>, extensionToMime: array<string, string>}|null
     */
    protected ?array $resolved = null;

    /**
     * Get the full MIME type => extensions map, fetching and caching it if necessary.
     *
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->resolve()['mimeToExtensions'];
    }

    /**
     * Determine whether the given MIME type or file extension is known.
     */
    public function has(string $mimeOrExtension): bool
    {
        $resolved = $this->resolve();

        return isset($resolved['mimeToExtensions'][$mimeOrExtension])
            || isset($resolved['extensionToMime'][$mimeOrExtension]);
    }

    /**
     * Resolve the primary file extension for the given MIME type.
     *
     * @throws UnknownMimeTypeOrExtensionException
     */
    public function toExtension(string $mime): string
    {
        return $this->extensionsFor($mime)[0];
    }

    /**
     * Resolve every known file extension for the given MIME type.
     *
     * @return list<string>
     *
     * @throws UnknownMimeTypeOrExtensionException
     */
    public function extensionsFor(string $mime): array
    {
        return $this->resolve()['mimeToExtensions'][$mime]
            ?? throw UnknownMimeTypeOrExtensionException::forMime($mime);
    }

    /**
     * Resolve the MIME type for the given file extension.
     *
     * @throws UnknownMimeTypeOrExtensionException
     */
    public function toMime(string $extension): string
    {
        $extension = ltrim($extension, '.');

        return $this->resolve()['extensionToMime'][$extension]
            ?? throw UnknownMimeTypeOrExtensionException::forExtension($extension);
    }

    /**
     * Resolve either the extension for a MIME type or the MIME type for an extension.
     *
     * @throws UnknownMimeTypeOrExtensionException
     */
    public function getExtensionOrMime(string $mimeOrExtension): string
    {
        $resolved = $this->resolve();

        if (isset($resolved['mimeToExtensions'][$mimeOrExtension])) {
            return $resolved['mimeToExtensions'][$mimeOrExtension][0];
        }

        $extension = ltrim($mimeOrExtension, '.');

        if (isset($resolved['extensionToMime'][$extension])) {
            return $resolved['extensionToMime'][$extension];
        }

        throw UnknownMimeTypeOrExtensionException::forValue($mimeOrExtension);
    }

    /**
     * Discard the cached MIME type map and fetch a fresh copy immediately.
     *
     * @return array<string, list<string>>
     */
    public function refresh(): array
    {
        $this->resolved = null;

        $this->cacheStore()->forget($this->cacheKey());

        return $this->all();
    }

    /**
     * Resolve the memoized MIME type map, populating it from cache (or the origin) if needed.
     *
     * @return array{mimeToExtensions: array<string, list<string>>, extensionToMime: array<string, string>}
     */
    protected function resolve(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        return $this->resolved = $this->cacheStore()->remember(
            $this->cacheKey(),
            now()->addMinutes((int) config('mimetypes.ttl', 1440)),
            fn () => $this->buildMaps($this->fetchMimeTypeLines()),
        );
    }

    /**
     * Fetch the raw `mime type -> extensions` lines, falling back to the bundled
     * snapshot when the origin cannot be reached and the fallback is enabled.
     *
     * @return list<string>
     *
     * @throws UnableToFetchMimeTypesException
     */
    protected function fetchMimeTypeLines(): array
    {
        $url = (string) config('mimetypes.url');

        try {
            $response = Http::timeout((int) config('mimetypes.http.timeout', 5))
                ->connectTimeout((int) config('mimetypes.http.connect_timeout', 3))
                ->retry(
                    times: (int) config('mimetypes.http.retry.times', 2),
                    sleepMilliseconds: (int) config('mimetypes.http.retry.sleep', 100),
                    throw: false,
                )
                ->get($url);

            if ($response->failed() || trim($response->body()) === '') {
                throw UnableToFetchMimeTypesException::invalidResponse($url);
            }

            return $this->splitLines($response->body());
        } catch (Throwable $e) {
            if (! config('mimetypes.fallback_to_bundled_snapshot', true)) {
                throw $e instanceof UnableToFetchMimeTypesException
                    ? $e
                    : UnableToFetchMimeTypesException::requestFailed($url, $e);
            }

            return $this->splitLines($this->bundledSnapshot());
        }
    }

    /**
     * @return list<string>
     */
    protected function splitLines(string $content): array
    {
        return explode("\n", $content);
    }

    /**
     * Read the MIME type snapshot bundled with the package, used as an offline fallback.
     */
    protected function bundledSnapshot(): string
    {
        return file_get_contents(__DIR__.'/../resources/mime.types') ?: '';
    }

    /**
     * Parse `mime.types` formatted lines into a bidirectional lookup structure.
     *
     * @param  list<string>  $lines
     * @return array{mimeToExtensions: array<string, list<string>>, extensionToMime: array<string, string>}
     */
    protected function buildMaps(array $lines): array
    {
        $mimeToExtensions = [];
        $extensionToMime = [];

        foreach ($lines as $line) {
            if ($line === '' || $line[0] === '#' || ! preg_match_all('/(\S+)/', $line, $matches)) {
                continue;
            }

            $tokens = $matches[1];

            if (count($tokens) < 2) {
                continue;
            }

            $mime = Str::lower($tokens[0]);
            $extensions = array_slice($tokens, 1);

            $mimeToExtensions[$mime] = $extensions;

            foreach ($extensions as $extension) {
                $extensionToMime[Str::lower($extension)] ??= $mime;
            }
        }

        return compact('mimeToExtensions', 'extensionToMime');
    }

    protected function cacheStore(): CacheRepository
    {
        return Cache::store(config('mimetypes.cache_store'));
    }

    protected function cacheKey(): string
    {
        return (string) config('mimetypes.cache_key', 'mimetypes.mime_type_map');
    }
}

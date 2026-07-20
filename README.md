# MimeTypes

[![Latest Version on Packagist](https://img.shields.io/packagist/v/awaisjameel/mimetypes.svg?style=flat-square)](https://packagist.org/packages/awaisjameel/mimetypes)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/awaisjameel/mimetypes/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/awaisjameel/mimetypes/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/awaisjameel/mimetypes/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/awaisjameel/mimetypes/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/awaisjameel/mimetypes.svg?style=flat-square)](https://packagist.org/packages/awaisjameel/mimetypes)

**MimeTypes** is a Laravel package for resolving MIME types and file extensions against the canonical [Apache `mime.types`](https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types) list — the same public-domain dataset used by Apache HTTP Server, cURL, and countless other tools.

The list is fetched over HTTP, cached for as long as you like, and — unlike a plain HTTP call — never leaves your application without an answer: if the origin is unreachable, MimeTypes transparently falls back to a snapshot bundled with the package, so a network hiccup can never break file upload validation or content-type detection in production.

## Features

- **Bi-directional resolution** — MIME type → extension(s) and extension → MIME type.
- **Cached** — the resolved map is cached (TTL, store, and key are all configurable) so lookups never hit the network on the hot path.
- **Resilient by default** — falls back to a bundled snapshot of `mime.types` if the source URL is slow, down, or unreachable, instead of throwing.
- **Typed exceptions** — `UnknownMimeTypeOrExtensionException` and `UnableToFetchMimeTypesException` instead of a generic `Exception`, so you can catch precisely what you expect.
- **Dependency-injection friendly** — a proper injectable, container-bound service, not a static-only utility. Use the facade or inject it directly.
- **Zero-config out of the box**, fully configurable when you need it.

## Requirements

| MimeTypes | PHP  | Laravel          |
|-----------|------|-------------------|
| ^2.0      | ^8.2 | ^10.0, ^11.0, ^12.0, ^13.0 |

## Installation

Install the package via Composer:

```bash
composer require awaisjameel/mimetypes
```

The package's service provider and `MimeTypes` facade are auto-discovered — there's nothing else to register.

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="mimetypes-config"
```

This is the config that gets published, with every option documented — see [Configuration](#configuration) below for what each one does:

```php
return [
    'url' => env('MIMETYPES_URL', 'https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types'),
    'ttl' => (int) env('MIMETYPES_TTL', 1440),
    'cache_store' => env('MIMETYPES_CACHE_STORE'),
    'cache_key' => 'mimetypes.mime_type_map',
    'http' => [
        'timeout' => (int) env('MIMETYPES_HTTP_TIMEOUT', 5),
        'connect_timeout' => (int) env('MIMETYPES_HTTP_CONNECT_TIMEOUT', 3),
        'retry' => [
            'times' => (int) env('MIMETYPES_HTTP_RETRY_TIMES', 2),
            'sleep' => (int) env('MIMETYPES_HTTP_RETRY_SLEEP', 100),
        ],
    ],
    'fallback_to_bundled_snapshot' => (bool) env('MIMETYPES_FALLBACK_TO_BUNDLED_SNAPSHOT', true),
];
```

## Usage

The recommended way to use the package is through its facade:

```php
use AwaisJameel\MimeTypes\Facades\MimeTypes;

MimeTypes::toExtension('text/html');   // 'html'
MimeTypes::toMime('jpg');              // 'image/jpeg'
```

Prefer dependency injection? The underlying `AwaisJameel\MimeTypes\MimeTypes` class is bound as a singleton in the container, so you can type-hint it anywhere:

```php
use AwaisJameel\MimeTypes\MimeTypes;

class AttachmentController
{
    public function __construct(private MimeTypes $mimeTypes)
    {
    }

    public function store()
    {
        $extension = $this->mimeTypes->toExtension($uploadedMime);
    }
}
```

### Resolving a MIME type from an extension

```php
MimeTypes::toMime('jpg');   // 'image/jpeg'
MimeTypes::toMime('.jpg');  // 'image/jpeg' — a leading dot is stripped automatically
```

Throws `AwaisJameel\MimeTypes\Exceptions\UnknownMimeTypeOrExtensionException` if the extension isn't recognized.

### Resolving an extension from a MIME type

```php
MimeTypes::toExtension('image/jpeg'); // 'jpeg' — the first/primary extension
```

Some MIME types map to more than one extension (`image/jpeg` → `jpeg`, `jpg`, `jpe`). To get all of them:

```php
MimeTypes::extensionsFor('image/jpeg'); // ['jpeg', 'jpg', 'jpe']
```

### Checking whether a value is known

Useful for validation, where you want a boolean instead of a try/catch:

```php
if (! MimeTypes::has($request->file('document')->getMimeType())) {
    abort(422, 'Unsupported file type.');
}
```

### Resolving either direction at once

If you have a value that could be either a MIME type or an extension:

```php
MimeTypes::getExtensionOrMime('text/html'); // 'html'
MimeTypes::getExtensionOrMime('html');      // 'text/html'
```

### Getting the full map

```php
MimeTypes::all(); // ['text/html' => ['html', 'htm'], 'image/jpeg' => ['jpeg', 'jpg', 'jpe'], ...]
```

### Forcing a refresh

The map is cached according to your [`ttl`](#configuration) config, but you can bypass the cache and refetch immediately — useful in a scheduled command that keeps the cache warm:

```php
MimeTypes::refresh();
```

## Exceptions

| Exception | Thrown when |
|---|---|
| `AwaisJameel\MimeTypes\Exceptions\UnknownMimeTypeOrExtensionException` | The given MIME type or extension isn't in the resolved map. |
| `AwaisJameel\MimeTypes\Exceptions\UnableToFetchMimeTypesException` | The source URL couldn't be reached or returned an unusable response, **and** [`fallback_to_bundled_snapshot`](#configuration) is disabled. |

Both extend the package's base `AwaisJameel\MimeTypes\Exceptions\MimeTypesException`, so you can catch either specifically or catch everything the package throws in one place:

```php
use AwaisJameel\MimeTypes\Exceptions\MimeTypesException;
use AwaisJameel\MimeTypes\Exceptions\UnknownMimeTypeOrExtensionException;
use AwaisJameel\MimeTypes\Facades\MimeTypes;

try {
    $extension = MimeTypes::toExtension($mime);
} catch (UnknownMimeTypeOrExtensionException) {
    $extension = 'bin';
} catch (MimeTypesException $e) {
    report($e);
    $extension = 'bin';
}
```

## Configuration

| Key | Env variable | Default | Description |
|---|---|---|---|
| `url` | `MIMETYPES_URL` | Apache's `mime.types` | Source URL the MIME type list is fetched from. Must be in the standard `mime.types` format. |
| `ttl` | `MIMETYPES_TTL` | `1440` (24 hours) | Minutes the resolved map is cached for before being fetched again. |
| `cache_store` | `MIMETYPES_CACHE_STORE` | `null` (default store) | Which cache store to use. |
| `cache_key` | — | `mimetypes.mime_type_map` | The cache key the resolved map is stored under. |
| `http.timeout` | `MIMETYPES_HTTP_TIMEOUT` | `5` | Request timeout, in seconds. |
| `http.connect_timeout` | `MIMETYPES_HTTP_CONNECT_TIMEOUT` | `3` | Connection timeout, in seconds. |
| `http.retry.times` | `MIMETYPES_HTTP_RETRY_TIMES` | `2` | Number of attempts before giving up. |
| `http.retry.sleep` | `MIMETYPES_HTTP_RETRY_SLEEP` | `100` | Milliseconds to wait between attempts. |
| `fallback_to_bundled_snapshot` | `MIMETYPES_FALLBACK_TO_BUNDLED_SNAPSHOT` | `true` | Fall back to the bundled snapshot when the source URL can't be reached, instead of throwing `UnableToFetchMimeTypesException`. |

## Testing your own code against this package

Because `MimeTypes` is resolved through the container and its HTTP calls go through Laravel's `Http` client, you can fake the source in your own tests with `Http::fake()` rather than hitting the real Apache server:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*' => Http::response("text/html\thtml htm\n"),
]);
```

Run this package's own test suite with:

```bash
composer test
```

Or with coverage:

```bash
composer test-coverage
```

## Upgrading from 1.x

Version 2.0 is a from-the-ground-up rewrite. If you're upgrading from 1.x:

- `MimeTypes::generateUpToDateMimeArray()` is now `MimeTypes::all()`.
- `MimeTypes::getExtensionOrMime()` is still available and behaves the same, but now throws `UnknownMimeTypeOrExtensionException` instead of a generic `Exception`.
- The config file gained several new keys (`url`, `cache_store`, `cache_key`, `http`, `fallback_to_bundled_snapshot`) — republish it with `--force` to see the new defaults, or add the keys you need manually.
- The previously broken `mime_types_ttl` config key (it was read from the wrong config namespace and silently ignored) is now `ttl`, and it works.
- `AwaisJameel\MimeTypes\MimeTypes` is no longer a static-only utility — its methods are regular instance methods, resolved as a singleton from the container. Static-style calls through `AwaisJameel\MimeTypes\Facades\MimeTypes` continue to work exactly as before.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Awais Jameel](https://github.com/awaisjameel)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

# Changelog

All notable changes to `MimeTypes` will be documented in this file.

## 3.0.0 - 2026-10-26

- Enforce Pint, PHPStan, strict manifest validation and dependency audits on pull requests and pushes; consolidate duplicate/automatic-formatting workflows.

### Changed

- **Breaking:** require maintained Laravel 12 or 13; PHP 8.2 remains the minimum.
- Update dependency support to current stable releases, including Pest 5 on PHP 8.4+.
- Declare the Illuminate cache, HTTP, and support components and Guzzle used at runtime.
- Remove unused Ray, Laravel Pest plugin, and PHPUnit-specific PHPStan extension;
  rely on Pest's own architecture plugin and console dependencies.
- Test supported PHP 8.2–8.5 / Laravel 12–13 combinations in CI.

### Fixed

- Reject empty or malformed successful HTTP responses and use the configured fallback.
- Parse indented and inline comments without creating invalid MIME mappings.
- Add regression coverage for fallback, connection errors, cache reuse, and cache TTL.
- Exclude installed dependencies and local test artifacts from release archives.

## 2.0.0 - 2026-07-20

### Added

- `MimeTypes::has()` to check whether a MIME type or extension is known without a try/catch.
- `MimeTypes::toExtension()` and `MimeTypes::toMime()` as explicit, single-direction alternatives to `getExtensionOrMime()`.
- `MimeTypes::extensionsFor()` to get every extension mapped to a MIME type, not just the first one.
- `MimeTypes::refresh()` to force-refetch the map, bypassing the cache.
- Typed exceptions: `UnknownMimeTypeOrExtensionException` and `UnableToFetchMimeTypesException`, both extending a common `MimeTypesException`.
- A snapshot of `mime.types` bundled with the package, used as an automatic offline fallback when the source URL can't be reached (configurable via `fallback_to_bundled_snapshot`).
- New config keys: `url`, `cache_store`, `cache_key`, `http.timeout`, `http.connect_timeout`, `http.retry.times`, `http.retry.sleep`, `fallback_to_bundled_snapshot`, all overridable via environment variables.
- `AwaisJameel\MimeTypes\MimeTypes` is now bound as a singleton in the container and can be resolved via constructor injection.

### Changed

- **Breaking:** `MimeTypes::generateUpToDateMimeArray()` is renamed to `MimeTypes::all()`.
- **Breaking:** `AwaisJameel\MimeTypes\MimeTypes` methods are now regular instance methods rather than static methods. Use the `AwaisJameel\MimeTypes\Facades\MimeTypes` facade (unchanged call syntax) or inject the class directly.
- **Breaking:** invalid lookups now throw `UnknownMimeTypeOrExtensionException` instead of a generic `Exception`.
- The source URL is now fetched over HTTPS instead of HTTP.
- The HTTP request now has a configurable timeout and retry policy instead of hanging indefinitely on a slow origin.
- Raised minimum PHP version to `^8.2` and added support for Laravel 12 and 13.

### Fixed

- The `mime_types_ttl` config option was read from the wrong config namespace (`mime_types.*` instead of `mimetypes.*`) and was silently ignored — the cache always used the hardcoded 1440-minute default regardless of what was published. The option (now named `ttl`) is fixed and actually applies.
- The reverse (extension → MIME type) lookup was rebuilt from scratch on every single call; it's now built once alongside the forward map and cached together.
- Removed unused `database/migrations` and `database/factories` scaffolding left over from the package skeleton — this package has no models or database tables.

## 1.0.0

Initial release.

<?php

use AwaisJameel\MimeTypes\Exceptions\UnableToFetchMimeTypesException;
use AwaisJameel\MimeTypes\Exceptions\UnknownMimeTypeOrExtensionException;
use AwaisJameel\MimeTypes\Facades\MimeTypes;
use AwaisJameel\MimeTypes\MimeTypes as MimeTypesService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function fakeMimeTypesSource(int $status = 200): void
{
    Http::fake([
        '*' => Http::response(<<<'MIME'
            # a comment line, which must be ignored
            text/html                                      html htm
            image/jpeg                                     jpeg jpg jpe
            application/vnd.openxmlformats-officedocument.spreadsheetml.sheet    xlsx
            MIME, $status),
    ]);
}

it('fetches the mime type map from the source url', function () {
    fakeMimeTypesSource();

    $map = MimeTypes::all();

    expect($map)->toHaveKey('text/html')
        ->and($map['text/html'])->toBe(['html', 'htm'])
        ->and($map['image/jpeg'])->toBe(['jpeg', 'jpg', 'jpe']);

    Http::assertSentCount(1);
});

it('only hits the network once thanks to caching', function () {
    fakeMimeTypesSource();

    MimeTypes::all();
    MimeTypes::all();
    MimeTypes::all();

    Http::assertSentCount(1);
});

it('resolves the primary extension for a mime type', function () {
    fakeMimeTypesSource();

    expect(MimeTypes::toExtension('text/html'))->toBe('html');
});

it('resolves every known extension for a mime type', function () {
    fakeMimeTypesSource();

    expect(MimeTypes::extensionsFor('text/html'))->toBe(['html', 'htm']);
});

it('resolves the mime type for an extension', function () {
    fakeMimeTypesSource();

    expect(MimeTypes::toMime('jpg'))->toBe('image/jpeg');
});

it('resolves the mime type for an extension prefixed with a dot', function () {
    fakeMimeTypesSource();

    expect(MimeTypes::toMime('.jpg'))->toBe('image/jpeg');
});

it('resolves either an extension or a mime type', function () {
    fakeMimeTypesSource();

    expect(MimeTypes::getExtensionOrMime('text/html'))->toBe('html')
        ->and(MimeTypes::getExtensionOrMime('xlsx'))
        ->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('reports whether a mime type or extension is known', function () {
    fakeMimeTypesSource();

    expect(MimeTypes::has('text/html'))->toBeTrue()
        ->and(MimeTypes::has('jpg'))->toBeTrue()
        ->and(MimeTypes::has('not-a-real-thing'))->toBeFalse();
});

it('throws when resolving an unknown mime type', function () {
    fakeMimeTypesSource();

    MimeTypes::toExtension('application/does-not-exist');
})->throws(UnknownMimeTypeOrExtensionException::class);

it('throws when resolving an unknown extension', function () {
    fakeMimeTypesSource();

    MimeTypes::toMime('doesnotexist');
})->throws(UnknownMimeTypeOrExtensionException::class);

it('throws when getExtensionOrMime is given an unknown value', function () {
    fakeMimeTypesSource();

    MimeTypes::getExtensionOrMime('nonsense');
})->throws(UnknownMimeTypeOrExtensionException::class);

it('refreshes the cached map by hitting the network again', function () {
    fakeMimeTypesSource();

    MimeTypes::all();
    MimeTypes::refresh();

    Http::assertSentCount(2);
});

it('falls back to the bundled snapshot when the source url cannot be reached', function () {
    fakeMimeTypesSource(status: 500);

    $map = MimeTypes::all();

    expect($map)->toHaveKey('text/html')
        ->and($map)->toHaveKey('image/jpeg');
});

it('throws when the source url fails and the bundled fallback is disabled', function () {
    config()->set('mimetypes.fallback_to_bundled_snapshot', false);

    fakeMimeTypesSource(status: 500);

    MimeTypes::all();
})->throws(UnableToFetchMimeTypesException::class);

it('is bound as a singleton in the container', function () {
    expect(app(MimeTypesService::class))->toBe(app(MimeTypesService::class));
});

it('stores the resolved map under the configured cache key and store', function () {
    fakeMimeTypesSource();

    MimeTypes::all();

    expect(Cache::store(config('mimetypes.cache_store'))->has(config('mimetypes.cache_key')))->toBeTrue();
});

it('can be resolved via constructor injection', function () {
    fakeMimeTypesSource();

    $mimeTypes = app(MimeTypesService::class);

    expect($mimeTypes->toExtension('text/html'))->toBe('html');
});

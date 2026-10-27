<?php

use AwaisJameel\MimeTypes\Exceptions\UnableToFetchMimeTypesException;
use AwaisJameel\MimeTypes\Exceptions\UnknownMimeTypeOrExtensionException;
use AwaisJameel\MimeTypes\Facades\MimeTypes;
use AwaisJameel\MimeTypes\MimeTypes as MimeTypesService;
use Illuminate\Http\Client\ConnectionException;
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

it('falls back when a successful response has no usable mappings', function (string $body) {
    Http::fake(['*' => Http::response($body)]);

    expect(MimeTypes::toMime('jpg'))->toBe('image/jpeg');
})->with(['', '   ', '# comments only', '<html>Maintenance mode</html>', 'not-a-mime ext']);

it('rejects malformed responses when fallback is disabled', function () {
    config()->set('mimetypes.fallback_to_bundled_snapshot', false);
    Http::fake(['*' => Http::response('<html>Maintenance mode</html>')]);

    MimeTypes::all();
})->throws(UnableToFetchMimeTypesException::class);

it('parses comments and line endings without treating comments as extensions', function () {
    Http::fake(['*' => Http::response("  # indented comment\r\nimage/jpeg JPG jpg jpeg # primary image\r\ntext/plain txt\r\n")]);

    expect(MimeTypes::all())->toBe(['image/jpeg' => ['jpg', 'jpeg'], 'text/plain' => ['txt']])
        ->and(MimeTypes::has('#'))->toBeFalse()
        ->and(MimeTypes::toMime('jpg'))->toBe('image/jpeg');
});

it('reuses persistent cache in a new service instance', function () {
    fakeMimeTypesSource();

    (new MimeTypesService)->all();
    (new MimeTypesService)->all();

    Http::assertSentCount(1);
});

it('expires persistent cache according to the configured ttl', function () {
    config()->set('mimetypes.ttl', 1);
    fakeMimeTypesSource();

    (new MimeTypesService)->all();
    $this->travel(61)->seconds();
    (new MimeTypesService)->all();

    Http::assertSentCount(2);
});

it('falls back on connection errors and preserves the cause when fallback is disabled', function () {
    Http::fake(fn () => throw new ConnectionException('Offline'));
    config()->set('mimetypes.http.retry.times', 1);

    expect((new MimeTypesService)->toMime('jpg'))->toBe('image/jpeg');

    Cache::flush();
    config()->set('mimetypes.fallback_to_bundled_snapshot', false);

    try {
        (new MimeTypesService)->all();
        $this->fail('Expected a failed request to throw.');
    } catch (UnableToFetchMimeTypesException $e) {
        expect($e->getPrevious())->toBeInstanceOf(ConnectionException::class);
    }
});

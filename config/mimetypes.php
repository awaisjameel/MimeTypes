<?php

// config for AwaisJameel/MimeTypes
return [

    /*
    |--------------------------------------------------------------------------
    | Source URL
    |--------------------------------------------------------------------------
    |
    | The canonical Apache MIME type definitions are fetched from this URL.
    | The response is expected to be in the standard `mime.types` format:
    | one `mime/type  ext1 ext2 ...` mapping per line.
    |
    */

    'url' => env('MIMETYPES_URL', 'https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types'),

    /*
    |--------------------------------------------------------------------------
    | Cache Time To Live
    |--------------------------------------------------------------------------
    |
    | The number of minutes the resolved MIME type map is cached for before
    | it is fetched again from the source URL above.
    |
    */

    'ttl' => (int) env('MIMETYPES_TTL', 1440),

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | The cache store used to persist the resolved MIME type map. Set to
    | `null` to use your application's default cache store.
    |
    */

    'cache_store' => env('MIMETYPES_CACHE_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Cache Key
    |--------------------------------------------------------------------------
    |
    | The cache key the resolved MIME type map is stored under.
    |
    */

    'cache_key' => 'mimetypes.mime_type_map',

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Fine-tune the HTTP request made to fetch the MIME type list, so a slow
    | or unreachable source can never hang a request indefinitely.
    |
    */

    'http' => [
        'timeout' => (int) env('MIMETYPES_HTTP_TIMEOUT', 5),
        'connect_timeout' => (int) env('MIMETYPES_HTTP_CONNECT_TIMEOUT', 3),
        'retry' => [
            'times' => (int) env('MIMETYPES_HTTP_RETRY_TIMES', 2),
            'sleep' => (int) env('MIMETYPES_HTTP_RETRY_SLEEP', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback To Bundled Snapshot
    |--------------------------------------------------------------------------
    |
    | When the source URL cannot be reached (network error, timeout, or a
    | non-successful response), the package can fall back to the snapshot
    | of `mime.types` bundled with it so lookups keep working offline.
    | Disable this to have a failed fetch throw an exception instead.
    |
    */

    'fallback_to_bundled_snapshot' => (bool) env('MIMETYPES_FALLBACK_TO_BUNDLED_SNAPSHOT', true),

];

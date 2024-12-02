<?php

namespace AwaisJameel\MimeTypes;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MimeTypes
{
    protected const APACHE_MIME_TYPES_URL = 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';
    protected const CACHE_KEY = 'mime_types';

    /**
     * Generate an up-to-date MIME types array with caching for 24 hours.
     *
     * @throws Exception
     */
    public static function generateUpToDateMimeArray(): array
    {
        try {
            // Cache::forget(Self::CACHE_KEY);

            // Try to get MIME types from cache
            $mimeTypes = Cache::get(self::CACHE_KEY);

            if ($mimeTypes === null) {
                // Cache miss, fetch from the URL
                $response = Http::get(self::APACHE_MIME_TYPES_URL);

                if ($response->failed()) {
                    throw new Exception('Failed to fetch MIME types from the URL.');
                }

                $mimeTypes = [];
                $contentLines = explode("\n", $response->body());

                foreach ($contentLines as $x) {
                    if (isset($x[0]) && $x[0] !== '#' && preg_match_all('#([^\s]+)#', $x, $out) && isset($out[1]) && ($c = count($out[1])) > 1) {
                        for ($i = 1; $i < $c; $i++) {
                            $mimeTypes[$out[1][0]][] = $out[1][$i];
                        }
                    }
                }

                // Cache the MIME types for default for 24 hours
                Cache::put(self::CACHE_KEY, $mimeTypes, now()->addMinutes(config('mime_types.mime_types_ttl', 1440)));
            }

            return $mimeTypes;
        } catch (Exception $e) {
            throw new Exception('Unable to get MIME types list.');
        }
    }

    /**
     * Returns the extension or mime based on given param which can be either an extension or mime.
     *
     * @throws Exception
     */
    public static function getExtensionOrMime(string $mimeOrExtension)
    {
        $mimeTypesList = self::generateUpToDateMimeArray();

        $extensionToMime = [];
        foreach ($mimeTypesList as $mime => $extensions) {
            foreach ($extensions as $ext) {
                $extensionToMime[$ext] = $mime;
            }
        }

        if (isset($mimeTypesList[$mimeOrExtension])) {
            return $mimeTypesList[$mimeOrExtension][0]; // MIME to extension
        }

        if (isset($extensionToMime[$mimeOrExtension])) {
            return $extensionToMime[$mimeOrExtension]; // Extension to MIME
        }

        throw new Exception("Invalid MIME type or extension!");

    }
}
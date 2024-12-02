# MimeTypes

**MimeTypes** is a Laravel package designed to dynamically fetch, cache, and resolve MIME types and file extensions. It retrieves the latest MIME type mappings from the official [Apache MIME types repository](http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types) and provides an easy-to-use API for resolving MIME types from file extensions and vice versa.

With built-in caching support and seamless integration into Laravel's ecosystem, this package ensures optimal performance and up-to-date data for handling file types.

### Features

- **Dynamic MIME Type Fetching**: Automatically fetch the latest MIME types from Apache's repository.
- **Bi-Directional Resolution**: Resolve MIME types to extensions and extensions to MIME types.
- **Caching for Performance**: Cache MIME type mappings for a configurable duration (default: 24 hours).
- **Laravel Integration**: Fully compatible with Laravel, using its caching and HTTP systems.
- **Customizable TTL**: Configure cache expiration to suit your application's requirements.
- **Robust Error Handling**: Clear and descriptive exceptions for invalid inputs or network issues.

### Use Cases

* **File Upload Validation** : Ensure files match the expected MIME type or extension before processing.
* **Content-Type Detection** : Dynamically determine the appropriate MIME type for file downloads or API responses.
* **Dynamic File Handling** : Manage and validate file operations based on the latest MIME standards.

### Why Use MimeTypes?

Dealing with MIME types can be cumbersome when relying on outdated static mappings or manually maintaining lists. **MimeTypes** automates the process, giving you:

* Up-to-date MIME mappings.
* Improved performance with caching.
* Simplicity with a Laravel-tailored API.

Whether you're working on file upload systems, API responses, or content-type validation, this package provides a reliable and efficient solution for managing MIME types.

## Installation

You can install the package via composer:

```bash
composer require awaisjameel/mimetypes
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="mimetypes-config"
```

This is the contents of the published config file:

```php
return [
    'mime_types_ttl' => 1440, // Default cache duration in minutes
];
```

## Usage

```php
use AwaisJameel\MimeTypes\MimeTypes;

//get Extension from Mime Type
try {
    $extension = MimeTypes::getExtensionOrMime('text/html');
    echo $extension; // Output: html
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

//get Mime Type from Extension
try {
    $mimeType = MimeTypesService::getExtensionOrMime('jpg');
    echo $mimeType; // Output: image/jpeg
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

//If the provided MIME type or extension is invalid, the package will throw an exception:
try {
    $result = MimeTypesService::getExtensionOrMime('invalid/mime');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage(); // Output: Invalid MIME type or extension!
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

## Security Vulnerabilities

## Credits

- [Awais Jameel](https://github.com/awaisjameel)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


[![Latest Version on Packagist](https://img.shields.io/packagist/v/awaisjameel/mimetypes.svg?style=flat-square)](https://packagist.org/packages/awaisjameel/mimetypes)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/awaisjameel/mimetypes/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/awaisjameel/mimetypes/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/awaisjameel/mimetypes/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/awaisjameel/mimetypes/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/awaisjameel/mimetypes.svg?style=flat-square)](https://packagist.org/packages/awaisjameel/mimetypes)

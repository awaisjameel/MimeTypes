# A service for fetching and resolving MIME types dynamically.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/awaisjameel/mimetypes.svg?style=flat-square)](https://packagist.org/packages/awaisjameel/mimetypes)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/awaisjameel/mimetypes/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/awaisjameel/mimetypes/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/awaisjameel/mimetypes/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/awaisjameel/mimetypes/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/awaisjameel/mimetypes.svg?style=flat-square)](https://packagist.org/packages/awaisjameel/mimetypes)


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

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Awais Jameel](https://github.com/awaisjameel)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

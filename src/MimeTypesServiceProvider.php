<?php

namespace AwaisJameel\MimeTypes;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MimeTypesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('mimetypes')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(MimeTypes::class);
    }
}

<?php

namespace AwaisJameel\MimeTypes\Tests;

use AwaisJameel\MimeTypes\MimeTypesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            MimeTypesServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'array');
    }
}

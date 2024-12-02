<?php

use AwaisJameel\MimeTypes\MimeTypes;

it('can fetch MIME types from origin url', function () {

    $mimeTypes = MimeTypes::generateUpToDateMimeArray();

    $this->assertContains('html', $mimeTypes['text/html']);
    $this->assertContains('htm', $mimeTypes['text/html']);
    $this->assertContains('jpeg', $mimeTypes['image/jpeg']);
    $this->assertContains('jpg', $mimeTypes['image/jpeg']);

    $this->assertArrayHasKey('text/html', $mimeTypes);
    $this->assertArrayHasKey('image/jpeg', $mimeTypes);
});

it('can find extension from mime', function () {

    $extension = MimeTypes::getExtensionOrMime('text/html');

    $this->assertEquals('html', $extension);
});

it('can find mime from extension', function () {

    $extension = MimeTypes::getExtensionOrMime('xlsx');

    $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $extension);
});

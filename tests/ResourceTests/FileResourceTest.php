<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\File;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class FileResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return File::class;
    }

    public function getResourceName(): string
    {
        return 'File';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // File creation requires file upload which is complex
        // Skip CRUD tests, only run property discovery and listing
        return null;
    }
}

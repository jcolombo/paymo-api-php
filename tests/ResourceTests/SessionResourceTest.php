<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Session;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class SessionResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return Session::class;
    }

    public function getResourceName(): string
    {
        return 'Session';
    }

    public function getResourceCategory(): string
    {
        return 'read_only';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // Session is read-only
        return null;
    }
}

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

    /**
     * Skip fetch test for Session - uses string tokens, not integer IDs.
     *
     * @override OVERRIDE-004
     * @see OVERRIDES.md#override-004
     */
    protected function getSkipFetchReason(): ?string
    {
        return 'Session uses string tokens as IDs (OVERRIDE-004)';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // Session is read-only
        return null;
    }
}

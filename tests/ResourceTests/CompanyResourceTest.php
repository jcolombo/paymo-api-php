<?php
/**
 * Paymo API PHP SDK - Company Resource Test
 *
 * Comprehensive tests for the Company resource (read-only singleton).
 * Company is a special singleton resource that doesn't support list().
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\ResourceTests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Company;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class CompanyResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return Company::class;
    }

    public function getResourceName(): string
    {
        return 'Company';
    }

    public function getResourceCategory(): string
    {
        return 'read_only';
    }

    public function isSingleton(): bool
    {
        return true;
    }

    protected function createTestResource(): ?AbstractResource
    {
        // Company is read-only, cannot create
        return null;
    }

    /**
     * Company is a singleton - fetch without ID
     */
    protected function fetchResourceForDiscovery(): ?AbstractResource
    {
        // Company::new()->fetch() fetches the singleton
        return Company::new()->fetch();
    }
}

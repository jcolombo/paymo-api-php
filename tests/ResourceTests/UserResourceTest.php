<?php
/**
 * Paymo API PHP SDK - User Resource Test
 *
 * Comprehensive tests for the User resource (configured anchor).
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\ResourceTests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\User;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class UserResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return User::class;
    }

    public function getResourceName(): string
    {
        return 'User';
    }

    public function getResourceCategory(): string
    {
        return 'configured_anchor';
    }

    public function requiresAnchor(): bool
    {
        return true;
    }

    public function getAnchorKey(): ?string
    {
        return 'user_id';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // User cannot be created via API (would require email invite flow)
        return null;
    }

    /**
     * Override to fetch using configured anchor
     */
    protected function fetchResourceForDiscovery(): ?AbstractResource
    {
        $userId = $this->config->getAnchor('user_id');
        if (!$userId) {
            return null;
        }

        return User::new()->fetch($userId);
    }
}

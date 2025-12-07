<?php
/**
 * Paymo API PHP SDK - Workflow Resource Test
 *
 * Comprehensive tests for the Workflow resource (read-only).
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\ResourceTests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Workflow;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class WorkflowResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return Workflow::class;
    }

    public function getResourceName(): string
    {
        return 'Workflow';
    }

    public function getResourceCategory(): string
    {
        return 'read_only';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // Workflow is read-only
        return null;
    }
}

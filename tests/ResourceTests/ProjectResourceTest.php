<?php
/**
 * Paymo API PHP SDK - Project Resource Test
 *
 * Comprehensive tests for the Project resource.
 * Uses base class ensureClient() for dependency management.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\ResourceTests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class ProjectResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return Project::class;
    }

    public function getResourceName(): string
    {
        return 'Project';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // Use base class method for client (with anchor support)
        $clientId = $this->ensureClient();

        if (!$clientId) {
            $this->logWarning("Could not get/create client for project");
            return null;
        }

        $data = $this->factory->projectData($clientId);

        $project = new Project();
        $project->name = $data['name'];
        $project->client_id = $clientId;
        $project->description = $data['description'];
        $project->create();

        return $project;
    }
}

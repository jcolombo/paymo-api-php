<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\ProjectStatus;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class ProjectStatusResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return ProjectStatus::class;
    }

    public function getResourceName(): string
    {
        return 'ProjectStatus';
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
        return 'project_status_id';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // ProjectStatus cannot be created via API (system statuses)
        return null;
    }

    protected function fetchResourceForDiscovery(): ?AbstractResource
    {
        $statusId = $this->config->getAnchor('project_status_id');
        if (!$statusId) {
            return null;
        }
        return ProjectStatus::new()->fetch($statusId);
    }
}

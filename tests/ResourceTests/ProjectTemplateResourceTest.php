<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\ProjectTemplate;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class ProjectTemplateResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return ProjectTemplate::class;
    }

    public function getResourceName(): string
    {
        return 'ProjectTemplate';
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
        return 'project_template_id';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // ProjectTemplate cannot be created via API
        return null;
    }

    protected function fetchResourceForDiscovery(): ?AbstractResource
    {
        $templateId = $this->config->getAnchor('project_template_id');
        if (!$templateId) {
            return null;
        }
        return ProjectTemplate::new()->fetch($templateId);
    }
}

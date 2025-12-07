<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\EstimateTemplate;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class EstimateTemplateResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return EstimateTemplate::class;
    }

    public function getResourceName(): string
    {
        return 'EstimateTemplate';
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
        return 'estimate_template_id';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // EstimateTemplate cannot be created via API
        return null;
    }

    protected function fetchResourceForDiscovery(): ?AbstractResource
    {
        $templateId = $this->config->getAnchor('estimate_template_id');
        if (!$templateId) {
            return null;
        }
        return EstimateTemplate::new()->fetch($templateId);
    }
}

<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\InvoiceTemplate;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class InvoiceTemplateResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return InvoiceTemplate::class;
    }

    public function getResourceName(): string
    {
        return 'InvoiceTemplate';
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
        return 'invoice_template_id';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // InvoiceTemplate cannot be created via API
        return null;
    }

    protected function fetchResourceForDiscovery(): ?AbstractResource
    {
        $templateId = $this->config->getAnchor('invoice_template_id');
        if (!$templateId) {
            return null;
        }
        return InvoiceTemplate::new()->fetch($templateId);
    }
}

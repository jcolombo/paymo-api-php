<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Webhook;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class WebhookResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return Webhook::class;
    }

    public function getResourceName(): string
    {
        return 'Webhook';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    protected function createTestResource(): ?AbstractResource
    {
        $webhook = new Webhook();
        $webhook->target_url = 'https://example.com/webhook/' . time();
        $webhook->event = 'model.insert.Task';
        $webhook->create();

        return $webhook;
    }
}

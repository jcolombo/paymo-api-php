<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Estimate;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class EstimateResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return Estimate::class;
    }

    public function getResourceName(): string
    {
        return 'Estimate';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    protected function createTestResource(): ?AbstractResource
    {
        $clientId = $this->config->getAnchor('client_id');
        if (!$clientId) {
            $client = new Client();
            $client->name = $this->factory->uniqueName('Client');
            $client->create();
            $this->cleanupManager->track('Client', $client->id, Client::class);
            $clientId = $client->id;
        }

        $estimate = new Estimate();
        $estimate->client_id = $clientId;
        $estimate->title = $this->factory->uniqueName('Estimate');
        $estimate->create();

        return $estimate;
    }
}

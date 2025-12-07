<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Invoice;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class InvoiceResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function getResourceName(): string
    {
        return 'Invoice';
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

        $invoice = new Invoice();
        $invoice->client_id = $clientId;
        $invoice->title = $this->factory->uniqueName('Invoice');
        $invoice->create();

        return $invoice;
    }
}

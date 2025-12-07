<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\InvoiceItem;
use Jcolombo\PaymoApiPhp\Entity\Resource\Invoice;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * InvoiceItem Resource Test
 *
 * NOTE: InvoiceItem collections require a parent filter (invoice_id).
 * This is an SDK-enforced validation, not an API limitation.
 * See InvoiceItemCollection::validateFetch() for the validation logic.
 */
class InvoiceItemResourceTest extends ResourceTest
{
    private ?int $invoiceId = null;

    public function getResourceClass(): string
    {
        return InvoiceItem::class;
    }

    public function getResourceName(): string
    {
        return 'InvoiceItem';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    /**
     * InvoiceItem requires an invoice_id filter for list operations.
     * Returns the filter key and method to get the value.
     */
    public function getRequiredParentFilter(): ?array
    {
        return ['invoice_id', 'ensureInvoice'];
    }

    protected function createTestResource(): ?AbstractResource
    {
        if (!$this->invoiceId) {
            $this->invoiceId = $this->ensureInvoice();
        }
        if (!$this->invoiceId) {
            return null;
        }

        $item = new InvoiceItem();
        $item->invoice_id = $this->invoiceId;
        $item->item = $this->factory->uniqueName('Item');
        $item->price = 100.00;
        $item->create();

        return $item;
    }

    protected function ensureInvoice(): ?int
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
        $this->cleanupManager->track('Invoice', $invoice->id, Invoice::class);

        return $invoice->id;
    }
}

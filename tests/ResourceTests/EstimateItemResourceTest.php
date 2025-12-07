<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\EstimateItem;
use Jcolombo\PaymoApiPhp\Entity\Resource\Estimate;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * EstimateItem Resource Test
 *
 * NOTE: EstimateItem collections require a parent filter (estimate_id).
 * This is an SDK-enforced validation, not an API limitation.
 * See EstimateItemCollection::validateFetch() for the validation logic.
 */
class EstimateItemResourceTest extends ResourceTest
{
    private ?int $estimateId = null;

    public function getResourceClass(): string
    {
        return EstimateItem::class;
    }

    public function getResourceName(): string
    {
        return 'EstimateItem';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    /**
     * EstimateItem requires an estimate_id filter for list operations.
     * Returns the filter key and method to get the value.
     */
    public function getRequiredParentFilter(): ?array
    {
        return ['estimate_id', 'ensureEstimate'];
    }

    protected function createTestResource(): ?AbstractResource
    {
        if (!$this->estimateId) {
            $this->estimateId = $this->ensureEstimate();
        }
        if (!$this->estimateId) {
            return null;
        }

        $item = new EstimateItem();
        $item->estimate_id = $this->estimateId;
        $item->item = $this->factory->uniqueName('Item');
        $item->price = 100.00;
        $item->create();

        return $item;
    }

    protected function ensureEstimate(): ?int
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
        $this->cleanupManager->track('Estimate', $estimate->id, Estimate::class);

        return $estimate->id;
    }
}

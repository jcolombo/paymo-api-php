<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Booking;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Booking Resource Test
 *
 * NOTE: Booking collections require either:
 * - A date range (start_date AND end_date), OR
 * - A parent filter (user_task_id, task_id, project_id, or user_id)
 *
 * This is an SDK-enforced validation, not an API limitation.
 * See BookingCollection::validateFetch() for the validation logic.
 */
class BookingResourceTest extends ResourceTest
{
    private ?int $projectId = null;

    public function getResourceClass(): string
    {
        return Booking::class;
    }

    public function getResourceName(): string
    {
        return 'Booking';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    /**
     * Booking requires a parent filter or date range for list operations.
     * Returns the filter key and method to get the value.
     */
    public function getRequiredParentFilter(): ?array
    {
        return ['project_id', 'ensureProject'];
    }

    protected function createTestResource(): ?AbstractResource
    {
        if (!$this->projectId) {
            $this->projectId = $this->ensureProject();
        }
        if (!$this->projectId) {
            return null;
        }

        $userId = $this->config->getAnchor('user_id');
        if (!$userId) {
            $this->logWarning("No user_id anchor configured for Booking");
            return null;
        }

        $booking = new Booking();
        $booking->project_id = $this->projectId;
        $booking->user_id = $userId;
        $booking->start_date = date('Y-m-d', strtotime('+1 day'));
        $booking->end_date = date('Y-m-d', strtotime('+2 days'));
        $booking->create();

        return $booking;
    }

    protected function ensureProject(): ?int
    {
        $clientId = $this->config->getAnchor('client_id');
        if (!$clientId) {
            $client = new Client();
            $client->name = $this->factory->uniqueName('Client');
            $client->create();
            $this->cleanupManager->track('Client', $client->id, Client::class);
            $clientId = $client->id;
        }

        $project = new Project();
        $project->name = $this->factory->uniqueName('Project');
        $project->client_id = $clientId;
        $project->create();
        $this->cleanupManager->track('Project', $project->id, Project::class);

        return $project->id;
    }
}

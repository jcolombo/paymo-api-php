<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Expense;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class ExpenseResourceTest extends ResourceTest
{
    private ?int $projectId = null;

    public function getResourceClass(): string
    {
        return Expense::class;
    }

    public function getResourceName(): string
    {
        return 'Expense';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    protected function createTestResource(): ?AbstractResource
    {
        if (!$this->projectId) {
            $this->projectId = $this->ensureProject();
        }
        if (!$this->projectId) {
            return null;
        }

        $expense = new Expense();
        $expense->project_id = $this->projectId;
        $expense->amount = 50.00;
        $expense->date = date('Y-m-d');
        $expense->create();

        return $expense;
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

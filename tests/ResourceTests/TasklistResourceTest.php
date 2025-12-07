<?php
/**
 * Paymo API PHP SDK - Tasklist Resource Test
 *
 * Comprehensive tests for the Tasklist resource.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\ResourceTests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Tasklist;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class TasklistResourceTest extends ResourceTest
{
    /**
     * @var int|null Project ID for creating tasklists
     */
    private ?int $projectId = null;

    public function getResourceClass(): string
    {
        return Tasklist::class;
    }

    public function getResourceName(): string
    {
        return 'Tasklist';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // Ensure we have a project
        if (!$this->projectId) {
            $this->projectId = $this->ensureProject();
        }

        if (!$this->projectId) {
            $this->logWarning("Could not create project for tasklist");
            return null;
        }

        $data = $this->factory->tasklistData($this->projectId);

        $tasklist = new Tasklist();
        $tasklist->name = $data['name'];
        $tasklist->project_id = $this->projectId;
        $tasklist->create();

        return $tasklist;
    }

    /**
     * Ensure we have a project
     */
    protected function ensureProject(): ?int
    {
        $clientId = $this->config->getAnchor('client_id');

        if (!$clientId) {
            $clientData = $this->factory->clientData();
            $client = new Client();
            $client->name = $clientData['name'];
            $client->create();
            $this->cleanupManager->track('Client', $client->id, Client::class);
            $clientId = $client->id;
        }

        $projectData = $this->factory->projectData($clientId);
        $project = new Project();
        $project->name = $projectData['name'];
        $project->client_id = $clientId;
        $project->create();

        $this->cleanupManager->track('Project', $project->id, Project::class);

        return $project->id;
    }
}

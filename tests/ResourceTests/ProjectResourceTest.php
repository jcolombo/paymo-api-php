<?php
/**
 * Paymo API PHP SDK - Project Resource Test
 *
 * Comprehensive tests for the Project resource.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\ResourceTests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class ProjectResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return Project::class;
    }

    public function getResourceName(): string
    {
        return 'Project';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // Use configured client or create one
        $clientId = $this->config->getAnchor('client_id');

        if (!$clientId) {
            // Create a test client
            $clientData = $this->factory->clientData();
            $client = new Client();
            $client->name = $clientData['name'];
            $client->create();
            $this->cleanupManager->track('Client', $client->id, Client::class);
            $clientId = $client->id;
        }

        $data = $this->factory->projectData($clientId);

        $project = new Project();
        $project->name = $data['name'];
        $project->client_id = $clientId;
        $project->description = $data['description'];
        $project->create();

        return $project;
    }
}

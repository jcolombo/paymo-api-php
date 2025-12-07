<?php
/**
 * Paymo API PHP SDK - Task Resource Test
 *
 * Comprehensive tests for the Task resource.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\ResourceTests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;
use Jcolombo\PaymoApiPhp\Entity\Resource\Tasklist;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class TaskResourceTest extends ResourceTest
{
    /**
     * @var int|null Tasklist ID for creating tasks
     */
    private ?int $tasklistId = null;

    public function getResourceClass(): string
    {
        return Task::class;
    }

    public function getResourceName(): string
    {
        return 'Task';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    protected function createTestResource(): ?AbstractResource
    {
        // Ensure we have a tasklist
        if (!$this->tasklistId) {
            $this->tasklistId = $this->ensureTasklist();
        }

        if (!$this->tasklistId) {
            $this->logWarning("Could not create tasklist for task");
            return null;
        }

        $data = $this->factory->taskData($this->tasklistId);

        $task = new Task();
        $task->name = $data['name'];
        $task->tasklist_id = $this->tasklistId;
        $task->create();

        return $task;
    }

    /**
     * Ensure we have a tasklist to create tasks in
     */
    private function ensureTasklist(): ?int
    {
        // First, ensure we have a project
        $projectId = $this->ensureProject();
        if (!$projectId) {
            return null;
        }

        // Create a tasklist
        $tasklistData = $this->factory->tasklistData($projectId);
        $tasklist = new Tasklist();
        $tasklist->name = $tasklistData['name'];
        $tasklist->project_id = $projectId;
        $tasklist->create();

        $this->cleanupManager->track('Tasklist', $tasklist->id, Tasklist::class);

        return $tasklist->id;
    }

    /**
     * Ensure we have a project
     */
    private function ensureProject(): ?int
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

<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Subtask;
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;
use Jcolombo\PaymoApiPhp\Entity\Resource\Tasklist;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class SubtaskResourceTest extends ResourceTest
{
    private ?int $taskId = null;

    public function getResourceClass(): string
    {
        return Subtask::class;
    }

    public function getResourceName(): string
    {
        return 'Subtask';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    protected function createTestResource(): ?AbstractResource
    {
        if (!$this->taskId) {
            $this->taskId = $this->ensureTask();
        }
        if (!$this->taskId) {
            return null;
        }

        $subtask = new Subtask();
        $subtask->name = $this->factory->uniqueName('Subtask');
        $subtask->task_id = $this->taskId;
        $subtask->create();

        return $subtask;
    }

    protected function ensureTask(): ?int
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

        $tasklist = new Tasklist();
        $tasklist->name = $this->factory->uniqueName('Tasklist');
        $tasklist->project_id = $project->id;
        $tasklist->create();
        $this->cleanupManager->track('Tasklist', $tasklist->id, Tasklist::class);

        $task = new Task();
        $task->name = $this->factory->uniqueName('Task');
        $task->tasklist_id = $tasklist->id;
        $task->create();
        $this->cleanupManager->track('Task', $task->id, Task::class);

        return $task->id;
    }
}

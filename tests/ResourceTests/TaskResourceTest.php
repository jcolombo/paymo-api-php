<?php
/**
 * Paymo API PHP SDK - Task Resource Test
 *
 * Comprehensive tests for the Task resource.
 * Uses base class ensureTasklist() for dependency management.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\ResourceTests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class TaskResourceTest extends ResourceTest
{
    /**
     * @var int|null Tasklist ID for creating tasks (cached)
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
        // Ensure we have a tasklist (uses base class method with anchor support)
        if (!$this->tasklistId) {
            $this->tasklistId = $this->ensureTasklist();
        }

        if (!$this->tasklistId) {
            $this->logWarning("Could not get/create tasklist for task");
            return null;
        }

        $data = $this->factory->taskData($this->tasklistId);

        $task = new Task();
        $task->name = $data['name'];
        $task->tasklist_id = $this->tasklistId;
        $task->create();

        return $task;
    }
}

<?php
/**
 * Paymo API PHP SDK - Cleanup Manager
 *
 * Manages tracking and cleanup of resources created during testing.
 * Ensures resources are deleted in the correct order to respect
 * parent-child relationships.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\Fixtures
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\Fixtures;

use Jcolombo\PaymoApiPhp\Tests\TestOutput;
use Jcolombo\PaymoApiPhp\Tests\TestResult;
use Throwable;

class CleanupManager
{
    /**
     * Resource deletion priority (lower = delete first)
     * Children must be deleted before parents
     */
    private const DELETION_PRIORITY = [
        // Level 1: Deepest children
        'Subtask' => 10,
        'TimeEntry' => 10,
        'Comment' => 10,
        'File' => 10,
        'EstimateItem' => 10,
        'InvoiceItem' => 10,
        'InvoicePayment' => 10,
        'RecurringProfileItem' => 10,

        // Level 2: Task-level resources
        'Task' => 20,
        'TaskAssignment' => 20,
        'Booking' => 20,
        'TaskRecurringProfile' => 20,

        // Level 3: Project-level resources
        'Tasklist' => 30,
        'Milestone' => 30,
        'Discussion' => 30,
        'Expense' => 30,

        // Level 4: Financial documents
        'Invoice' => 40,
        'Estimate' => 40,
        'RecurringProfile' => 40,

        // Level 5: Organization-level
        'Project' => 50,
        'WorkflowStatus' => 50,
        'Webhook' => 50,

        // Level 6: Client-level
        'ClientContact' => 60,

        // Level 7: Top-level (usually anchors, not deleted)
        'Client' => 70,
    ];

    /**
     * @var array Tracked resources keyed by type
     */
    private array $resources = [];

    /**
     * @var TestOutput|null Output handler
     */
    private ?TestOutput $output;

    /**
     * @var TestResult|null Result tracker for recording operations
     */
    private ?TestResult $results;

    /**
     * @var bool Whether to actually delete (false = dry run)
     */
    private bool $executeDeletes;

    /**
     * @var array Resources that failed to delete
     */
    private array $failedDeletes = [];

    /**
     * Constructor
     *
     * @param TestOutput|null $output Output handler
     * @param bool $executeDeletes Whether to actually delete
     * @param TestResult|null $results Result tracker for recording operations
     */
    public function __construct(?TestOutput $output = null, bool $executeDeletes = true, ?TestResult $results = null)
    {
        $this->output = $output;
        $this->executeDeletes = $executeDeletes;
        $this->results = $results;
    }

    /**
     * Set the result tracker
     *
     * @param TestResult $results Result tracker
     */
    public function setResults(TestResult $results): void
    {
        $this->results = $results;
    }

    /**
     * Track a resource for later cleanup
     *
     * @param string $type Resource type (e.g., 'Project', 'Task')
     * @param int $id Resource ID
     * @param string $className Full class name for deletion
     * @param string $name Optional name for reporting
     */
    public function track(string $type, int $id, string $className, string $name = ''): void
    {
        if (!isset($this->resources[$type])) {
            $this->resources[$type] = [];
        }

        $this->resources[$type][] = [
            'id' => $id,
            'class' => $className,
            'tracked_at' => time(),
            'name' => $name,
        ];

        // Record creation in results tracker
        if ($this->results !== null) {
            $this->results->recordCreation($type, $id, $name);
        }
    }

    /**
     * Check if a resource is being tracked
     *
     * @param string $type Resource type
     * @param int $id Resource ID
     * @return bool Whether resource is tracked
     */
    public function isTracked(string $type, int $id): bool
    {
        if (!isset($this->resources[$type])) {
            return false;
        }

        foreach ($this->resources[$type] as $resource) {
            if ($resource['id'] === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get count of tracked resources
     *
     * @return int Total count
     */
    public function getCount(): int
    {
        $count = 0;
        foreach ($this->resources as $resources) {
            $count += count($resources);
        }
        return $count;
    }

    /**
     * Get tracked resources by type
     *
     * @param string $type Resource type
     * @return array Resources of that type
     */
    public function getByType(string $type): array
    {
        return $this->resources[$type] ?? [];
    }

    /**
     * Get all tracked resources
     *
     * @return array All resources
     */
    public function getAll(): array
    {
        return $this->resources;
    }

    /**
     * Perform cleanup of all tracked resources
     * SDK uses singleton connection - no connection parameter needed
     *
     * @return array Results ['success' => int, 'failed' => int]
     */
    public function cleanup(): array
    {
        $success = 0;
        $failed = 0;

        // Get sorted list of resource types by deletion priority
        $sortedTypes = $this->getSortedTypes();

        foreach ($sortedTypes as $type) {
            if (!isset($this->resources[$type])) {
                continue;
            }

            // Reverse order within type (delete newest first)
            $resources = array_reverse($this->resources[$type]);

            foreach ($resources as $resource) {
                $result = $this->deleteResource($type, $resource);

                if ($result) {
                    $success++;
                } else {
                    $failed++;
                }
            }
        }

        // Clear tracked resources
        $this->resources = [];

        return [
            'success' => $success,
            'failed' => $failed,
            'failures' => $this->failedDeletes,
        ];
    }

    /**
     * Get resource types sorted by deletion priority
     *
     * @return array Sorted type names
     */
    private function getSortedTypes(): array
    {
        $types = array_keys($this->resources);

        usort($types, function ($a, $b) {
            $priorityA = self::DELETION_PRIORITY[$a] ?? 100;
            $priorityB = self::DELETION_PRIORITY[$b] ?? 100;
            return $priorityA - $priorityB;
        });

        return $types;
    }

    /**
     * Delete a single resource
     * SDK uses singleton connection - no connection parameter needed
     *
     * @param string $type Resource type
     * @param array $resource Resource info
     * @return bool Success
     */
    private function deleteResource(string $type, array $resource): bool
    {
        $id = $resource['id'];
        $className = $resource['class'];

        if (!$this->executeDeletes) {
            if ($this->output) {
                $this->output->dryRun("Would delete {$type} #{$id}");
            }
            return true;
        }

        try {
            if (!class_exists($className)) {
                throw new \Exception("Class not found: {$className}");
            }

            // Use the static deleteById method - it handles id assignment correctly
            // The direct $instance->id = $id approach doesn't work because id is READONLY
            $className::deleteById($id);

            if ($this->output) {
                $this->output->cleanup("Deleted {$type} #{$id}");
            }

            // Record successful deletion in results tracker
            if ($this->results !== null) {
                $this->results->recordDeletion($type, $id);
            }

            return true;

        } catch (Throwable $e) {
            // 404 errors are OK - resource was already deleted (possibly by a parent cascade)
            if (strpos($e->getMessage(), 'not found') !== false ||
                strpos($e->getMessage(), 'Not Found') !== false ||
                strpos($e->getMessage(), '404') !== false) {
                if ($this->output) {
                    $this->output->cleanup("Already deleted {$type} #{$id} (not found)");
                }
                return true;
            }

            $this->failedDeletes[] = [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ];

            if ($this->output) {
                $this->output->cleanup("Failed to delete {$type} #{$id}: " . $e->getMessage(), false);
            }

            // Record failed deletion in results tracker
            if ($this->results !== null) {
                $this->results->recordDeleteFailure($type, $id, $e->getMessage());
            }

            return false;
        }
    }

    /**
     * Remove a resource from tracking (e.g., if already deleted)
     *
     * @param string $type Resource type
     * @param int $id Resource ID
     */
    public function untrack(string $type, int $id): void
    {
        if (!isset($this->resources[$type])) {
            return;
        }

        $this->resources[$type] = array_filter(
            $this->resources[$type],
            fn($resource) => $resource['id'] !== $id
        );
    }

    /**
     * Clear all tracked resources without deleting
     */
    public function clear(): void
    {
        $this->resources = [];
        $this->failedDeletes = [];
    }

    /**
     * Get failed deletes from last cleanup
     *
     * @return array Failed deletes
     */
    public function getFailedDeletes(): array
    {
        return $this->failedDeletes;
    }

    /**
     * Export tracking state for debugging
     *
     * @return array State info
     */
    public function toArray(): array
    {
        $summary = [];
        foreach ($this->resources as $type => $resources) {
            $summary[$type] = array_map(fn($r) => $r['id'], $resources);
        }

        return [
            'count' => $this->getCount(),
            'resources' => $summary,
            'failed_deletes' => $this->failedDeletes,
        ];
    }
}

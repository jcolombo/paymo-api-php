<?php
/**
 * Paymo API PHP SDK - Test Result Tracker
 *
 * Tracks test execution results, timing, failure information,
 * and resource creation/cleanup for complete audit trail.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests;

class TestResult
{
    /**
     * @var int Number of passed tests
     */
    private int $passed = 0;

    /**
     * @var int Number of failed tests
     */
    private int $failed = 0;

    /**
     * @var int Number of skipped tests
     */
    private int $skipped = 0;

    /**
     * @var float Start time of test run
     */
    private float $startTime;

    /**
     * @var float|null End time of test run
     */
    private ?float $endTime = null;

    /**
     * @var array Failure details
     */
    private array $failures = [];

    /**
     * @var array Skipped test details
     */
    private array $skippedDetails = [];

    /**
     * @var array All test results
     */
    private array $allResults = [];

    /**
     * @var string|null Current test group
     */
    private ?string $currentGroup = null;

    /**
     * @var array Resources created during testing [type => [id => details]]
     */
    private array $createdResources = [];

    /**
     * @var array Resources successfully deleted [type => [id => details]]
     */
    private array $deletedResources = [];

    /**
     * @var array Resources that failed to delete [type => [id => error]]
     */
    private array $failedDeletes = [];

    /**
     * @var array Resources that were updated during testing
     */
    private array $updatedResources = [];

    /**
     * Constructor - starts timing
     */
    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    /**
     * Set the current test group
     */
    public function setCurrentGroup(string $group): void
    {
        $this->currentGroup = $group;
    }

    /**
     * Record a passed test
     *
     * @param string $testName Name of the test
     * @param float $duration Duration in seconds
     */
    public function recordPass(string $testName, float $duration): void
    {
        $this->passed++;
        $this->allResults[] = [
            'group' => $this->currentGroup,
            'test' => $testName,
            'status' => 'passed',
            'duration' => $duration,
            'message' => null,
        ];
    }

    /**
     * Record a failed test
     *
     * @param string $testName Name of the test
     * @param float $duration Duration in seconds
     * @param string $message Failure message
     */
    public function recordFailure(string $testName, float $duration, string $message): void
    {
        $this->failed++;
        $this->failures[] = [
            'group' => $this->currentGroup,
            'test' => $testName,
            'duration' => $duration,
            'message' => $message,
        ];
        $this->allResults[] = [
            'group' => $this->currentGroup,
            'test' => $testName,
            'status' => 'failed',
            'duration' => $duration,
            'message' => $message,
        ];
    }

    /**
     * Record a skipped test
     *
     * @param string $testName Name of the test
     * @param string $reason Reason for skipping
     */
    public function recordSkip(string $testName, string $reason): void
    {
        $this->skipped++;
        $this->skippedDetails[] = [
            'group' => $this->currentGroup,
            'test' => $testName,
            'reason' => $reason,
        ];
        $this->allResults[] = [
            'group' => $this->currentGroup,
            'test' => $testName,
            'status' => 'skipped',
            'duration' => 0,
            'message' => $reason,
        ];
    }

    /**
     * Record a resource creation
     *
     * @param string $type Resource type (e.g., 'Project', 'Task')
     * @param int $id Resource ID
     * @param string $name Resource name/identifier
     */
    public function recordCreation(string $type, int $id, string $name = ''): void
    {
        if (!isset($this->createdResources[$type])) {
            $this->createdResources[$type] = [];
        }
        $this->createdResources[$type][$id] = [
            'id' => $id,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'group' => $this->currentGroup,
        ];
    }

    /**
     * Record a resource update
     *
     * @param string $type Resource type
     * @param int $id Resource ID
     * @param string $field Field that was updated
     */
    public function recordUpdate(string $type, int $id, string $field = ''): void
    {
        $key = "{$type}:{$id}";
        if (!isset($this->updatedResources[$key])) {
            $this->updatedResources[$key] = [
                'type' => $type,
                'id' => $id,
                'fields' => [],
                'group' => $this->currentGroup,
            ];
        }
        if ($field) {
            $this->updatedResources[$key]['fields'][] = $field;
        }
    }

    /**
     * Record a successful resource deletion
     *
     * @param string $type Resource type
     * @param int $id Resource ID
     */
    public function recordDeletion(string $type, int $id): void
    {
        if (!isset($this->deletedResources[$type])) {
            $this->deletedResources[$type] = [];
        }
        $this->deletedResources[$type][$id] = [
            'id' => $id,
            'deleted_at' => date('Y-m-d H:i:s'),
        ];

        // Remove from created if it was tracked
        if (isset($this->createdResources[$type][$id])) {
            unset($this->createdResources[$type][$id]);
        }
    }

    /**
     * Record a failed resource deletion
     *
     * @param string $type Resource type
     * @param int $id Resource ID
     * @param string $error Error message
     */
    public function recordDeleteFailure(string $type, int $id, string $error): void
    {
        if (!isset($this->failedDeletes[$type])) {
            $this->failedDeletes[$type] = [];
        }
        $this->failedDeletes[$type][$id] = [
            'id' => $id,
            'error' => $error,
            'attempted_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Mark the end of the test run
     */
    public function finish(): void
    {
        $this->endTime = microtime(true);
    }

    /**
     * Get total number of tests
     */
    public function getTotal(): int
    {
        return $this->passed + $this->failed + $this->skipped;
    }

    /**
     * Get number of passed tests
     */
    public function getPassed(): int
    {
        return $this->passed;
    }

    /**
     * Get number of failed tests
     */
    public function getFailed(): int
    {
        return $this->failed;
    }

    /**
     * Get number of skipped tests
     */
    public function getSkipped(): int
    {
        return $this->skipped;
    }

    /**
     * Check if there were any failures
     */
    public function hasFailures(): bool
    {
        return $this->failed > 0;
    }

    /**
     * Get total duration in seconds
     */
    public function getDuration(): float
    {
        $endTime = $this->endTime ?? microtime(true);
        return $endTime - $this->startTime;
    }

    /**
     * Get all failures
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * Get all skipped tests
     */
    public function getSkippedDetails(): array
    {
        return $this->skippedDetails;
    }

    /**
     * Get all results
     */
    public function getAllResults(): array
    {
        return $this->allResults;
    }

    /**
     * Get results by group
     */
    public function getResultsByGroup(): array
    {
        $byGroup = [];
        foreach ($this->allResults as $result) {
            $group = $result['group'] ?? 'unknown';
            if (!isset($byGroup[$group])) {
                $byGroup[$group] = [];
            }
            $byGroup[$group][] = $result;
        }
        return $byGroup;
    }

    /**
     * Get count of created resources
     */
    public function getCreatedCount(): int
    {
        $count = 0;
        foreach ($this->createdResources as $resources) {
            $count += count($resources);
        }
        return $count;
    }

    /**
     * Get count of deleted resources
     */
    public function getDeletedCount(): int
    {
        $count = 0;
        foreach ($this->deletedResources as $resources) {
            $count += count($resources);
        }
        return $count;
    }

    /**
     * Get count of failed deletes
     */
    public function getFailedDeleteCount(): int
    {
        $count = 0;
        foreach ($this->failedDeletes as $resources) {
            $count += count($resources);
        }
        return $count;
    }

    /**
     * Get resources still remaining (created but not deleted)
     */
    public function getRemainingResources(): array
    {
        return $this->createdResources;
    }

    /**
     * Get failed deletes
     */
    public function getFailedDeletes(): array
    {
        return $this->failedDeletes;
    }

    /**
     * Get updated resources
     */
    public function getUpdatedResources(): array
    {
        return $this->updatedResources;
    }

    /**
     * Check if there are cleanup issues
     */
    public function hasCleanupIssues(): bool
    {
        return $this->getFailedDeleteCount() > 0 || $this->getCreatedCount() > 0;
    }

    /**
     * Get summary statistics
     */
    public function getSummary(): array
    {
        $totalCreated = 0;
        foreach ($this->createdResources as $resources) {
            $totalCreated += count($resources);
        }
        foreach ($this->deletedResources as $resources) {
            $totalCreated += count($resources); // Count deleted too as they were created
        }

        return [
            'total' => $this->getTotal(),
            'passed' => $this->passed,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'duration' => $this->getDuration(),
            'pass_rate' => ($this->getTotal() - $this->skipped) > 0
                ? round(($this->passed / ($this->getTotal() - $this->skipped)) * 100, 1)
                : 0,
            'resources_created' => $totalCreated,
            'resources_deleted' => $this->getDeletedCount(),
            'resources_remaining' => $this->getCreatedCount(),
            'delete_failures' => $this->getFailedDeleteCount(),
        ];
    }

    /**
     * Convert to array for JSON output
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->getSummary(),
            'failures' => $this->failures,
            'skipped' => $this->skippedDetails,
            'results' => $this->allResults,
            'resources' => [
                'remaining' => $this->createdResources,
                'deleted' => $this->deletedResources,
                'failed_deletes' => $this->failedDeletes,
                'updated' => $this->updatedResources,
            ],
        ];
    }

    /**
     * Merge results from another TestResult
     */
    public function merge(TestResult $other): void
    {
        $this->passed += $other->passed;
        $this->failed += $other->failed;
        $this->skipped += $other->skipped;
        $this->failures = array_merge($this->failures, $other->failures);
        $this->skippedDetails = array_merge($this->skippedDetails, $other->skippedDetails);
        $this->allResults = array_merge($this->allResults, $other->allResults);

        // Merge resource tracking
        foreach ($other->createdResources as $type => $resources) {
            if (!isset($this->createdResources[$type])) {
                $this->createdResources[$type] = [];
            }
            $this->createdResources[$type] = array_merge($this->createdResources[$type], $resources);
        }
        foreach ($other->deletedResources as $type => $resources) {
            if (!isset($this->deletedResources[$type])) {
                $this->deletedResources[$type] = [];
            }
            $this->deletedResources[$type] = array_merge($this->deletedResources[$type], $resources);
        }
        foreach ($other->failedDeletes as $type => $resources) {
            if (!isset($this->failedDeletes[$type])) {
                $this->failedDeletes[$type] = [];
            }
            $this->failedDeletes[$type] = array_merge($this->failedDeletes[$type], $resources);
        }
    }
}

<?php
/**
 * Paymo API PHP SDK - Test Ownership Registry
 *
 * Central singleton registry that tracks all resources created during test runs.
 * This provides a safety mechanism to ensure mutations (update/delete) can ONLY
 * be performed on resources that were created by the test suite.
 *
 * SAFETY MECHANISM:
 * -----------------
 * Before any mutation (update/delete) API call, the test suite MUST call
 * verifyTestCreated() to confirm the resource was created by tests.
 * If verification fails, the mutation is blocked to protect production data.
 *
 * USAGE:
 * ------
 * ```php
 * // Register a created resource immediately after creation
 * TestOwnershipRegistry::register('project', 12345);
 *
 * // Before any mutation, verify ownership
 * if (!TestOwnershipRegistry::verifyTestCreated('project', 12345)) {
 *     throw new RuntimeException("Cannot mutate resource not created by tests");
 * }
 *
 * // Get all registered resources
 * $all = TestOwnershipRegistry::getAll();
 *
 * // Clear registry (at start of test run)
 * TestOwnershipRegistry::clear();
 * ```
 *
 * @package    Jcolombo\PaymoApiPhp\Tests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests;

/**
 * Singleton registry tracking all test-created resources.
 *
 * This class provides absolute protection against accidental mutation of
 * production resources during test runs. It maintains a master list of
 * every resource ID created during the test session.
 */
class TestOwnershipRegistry
{
    /**
     * Master registry of all test-created resources.
     * Structure: ['resource_type' => [id1, id2, ...], ...]
     *
     * @var array<string, array<int>>
     */
    private static array $registry = [];

    /**
     * Detailed tracking with metadata for reporting.
     * Structure: [['type' => 'project', 'id' => 123, 'created_at' => timestamp, 'test' => 'TestName'], ...]
     *
     * @var array<int, array{type: string, id: int, created_at: float, test: string}>
     */
    private static array $detailed = [];

    /**
     * Counter for total registrations
     *
     * @var int
     */
    private static int $count = 0;

    /**
     * Register a resource as test-created.
     *
     * Call this IMMEDIATELY after successfully creating a resource via API.
     * This adds the resource to the ownership registry, allowing future
     * mutations on this resource.
     *
     * @param string $resourceType The resource type (e.g., 'project', 'task', 'client')
     * @param int    $id           The resource ID returned from the API
     * @param string $testName     Optional test name for tracking (default: 'unknown')
     *
     * @return void
     */
    public static function register(string $resourceType, int $id, string $testName = 'unknown'): void
    {
        $resourceType = strtolower($resourceType);

        // Initialize array for this resource type if needed
        if (!isset(self::$registry[$resourceType])) {
            self::$registry[$resourceType] = [];
        }

        // Add to registry if not already present
        if (!in_array($id, self::$registry[$resourceType], true)) {
            self::$registry[$resourceType][] = $id;
            self::$detailed[] = [
                'type' => $resourceType,
                'id' => $id,
                'created_at' => microtime(true),
                'test' => $testName
            ];
            self::$count++;
        }
    }

    /**
     * Verify a resource was created by tests before allowing mutation.
     *
     * This is the CRITICAL safety check. Call this IMMEDIATELY before any
     * update() or delete() API call. If this returns false, DO NOT proceed
     * with the mutation.
     *
     * @param string $resourceType The resource type to check
     * @param int    $id           The resource ID to verify
     *
     * @return bool TRUE if resource was created by tests, FALSE otherwise
     */
    public static function verifyTestCreated(string $resourceType, int $id): bool
    {
        $resourceType = strtolower($resourceType);

        if (!isset(self::$registry[$resourceType])) {
            return false;
        }

        return in_array($id, self::$registry[$resourceType], true);
    }

    /**
     * Get all registered resources of a specific type.
     *
     * @param string $resourceType The resource type
     *
     * @return array<int> Array of resource IDs
     */
    public static function getByType(string $resourceType): array
    {
        $resourceType = strtolower($resourceType);
        return self::$registry[$resourceType] ?? [];
    }

    /**
     * Get the complete registry.
     *
     * @return array<string, array<int>> All registered resources by type
     */
    public static function getAll(): array
    {
        return self::$registry;
    }

    /**
     * Get detailed registration info for reporting.
     *
     * @return array<int, array{type: string, id: int, created_at: float, test: string}>
     */
    public static function getDetailed(): array
    {
        return self::$detailed;
    }

    /**
     * Get total count of registered resources.
     *
     * @return int
     */
    public static function getCount(): int
    {
        return self::$count;
    }

    /**
     * Check if registry is empty (read-only mode or no creates).
     *
     * @return bool
     */
    public static function isEmpty(): bool
    {
        return self::$count === 0;
    }

    /**
     * Remove a resource from the registry (after successful deletion).
     *
     * Call this after successfully deleting a resource to keep the registry
     * accurate.
     *
     * @param string $resourceType The resource type
     * @param int    $id           The resource ID
     *
     * @return bool TRUE if resource was found and removed, FALSE otherwise
     */
    public static function unregister(string $resourceType, int $id): bool
    {
        $resourceType = strtolower($resourceType);

        if (!isset(self::$registry[$resourceType])) {
            return false;
        }

        $key = array_search($id, self::$registry[$resourceType], true);
        if ($key !== false) {
            unset(self::$registry[$resourceType][$key]);
            // Re-index array
            self::$registry[$resourceType] = array_values(self::$registry[$resourceType]);

            // Also remove from detailed list
            foreach (self::$detailed as $k => $entry) {
                if ($entry['type'] === $resourceType && $entry['id'] === $id) {
                    unset(self::$detailed[$k]);
                    break;
                }
            }
            self::$detailed = array_values(self::$detailed);
            self::$count--;

            return true;
        }

        return false;
    }

    /**
     * Clear the entire registry.
     *
     * Call this at the START of a test run to ensure a clean slate.
     * This should be called before any tests execute.
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$registry = [];
        self::$detailed = [];
        self::$count = 0;
    }

    /**
     * Get a summary string for display.
     *
     * @return string Human-readable summary of registered resources
     */
    public static function getSummary(): string
    {
        if (self::isEmpty()) {
            return "No resources created during test run (read-only mode)";
        }

        $lines = ["Test-Created Resources (" . self::$count . " total):"];
        foreach (self::$registry as $type => $ids) {
            $count = count($ids);
            $idList = implode(', ', array_slice($ids, 0, 5));
            if ($count > 5) {
                $idList .= ', ... (' . ($count - 5) . ' more)';
            }
            $lines[] = "  {$type}: [{$idList}]";
        }

        return implode("\n", $lines);
    }
}

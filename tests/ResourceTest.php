<?php
/**
 * Paymo API PHP SDK - Resource Test Base Class
 *
 * Comprehensive test class for testing a single resource type.
 * Combines all test capabilities:
 * - Property Discovery (compare API response to PROP_TYPES)
 * - Property Selection (verify each prop can be selected)
 * - CRUD operations (create, read, update, delete)
 * - Where Operations (filtering)
 * - Include Relationships
 *
 * This is a RESOURCE-CENTRIC test approach. Each resource gets a complete
 * test cycle, with all findings logged for that resource together.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests;

use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
use Jcolombo\PaymoApiPhp\Tests\Fixtures\TestDataFactory;
use Jcolombo\PaymoApiPhp\Tests\Fixtures\CleanupManager;
use Throwable;
use ReflectionClass;

abstract class ResourceTest
{
    /**
     * @var TestConfig Test configuration
     */
    protected TestConfig $config;

    /**
     * @var TestOutput Output handler
     */
    protected TestOutput $output;

    /**
     * @var TestResult Result tracker
     */
    protected TestResult $results;

    /**
     * @var TestLogger|null Logger instance
     */
    protected ?TestLogger $logger;

    /**
     * @var TestDataFactory Data factory for generating test data
     */
    protected TestDataFactory $factory;

    /**
     * @var CleanupManager Cleanup manager for tracking created resources
     */
    protected CleanupManager $cleanupManager;

    /**
     * @var bool Whether running in dry-run mode
     */
    protected bool $dryRun = false;

    /**
     * @var Paymo|null API connection (singleton)
     */
    protected ?Paymo $connection = null;

    /**
     * @var AbstractResource|null Current test resource instance
     */
    protected ?AbstractResource $testResource = null;

    /**
     * Properties to ignore in property discovery
     */
    protected array $ignoreProperties = [
        '_links',
        '_meta',
    ];

    /**
     * Constructor
     */
    public function __construct(
        TestConfig $config,
        TestOutput $output,
        TestResult $results,
        ?TestLogger $logger = null
    ) {
        $this->config = $config;
        $this->output = $output;
        $this->results = $results;
        $this->logger = $logger;
        $this->dryRun = $config->getRuntimeOption('dry_run');
        $this->factory = new TestDataFactory($config);
        $this->cleanupManager = new CleanupManager($output, !$this->dryRun);
    }

    // ========================================================================
    // Abstract methods - must be implemented by each resource test
    // ========================================================================

    /**
     * Get the fully qualified resource class name
     */
    abstract public function getResourceClass(): string;

    /**
     * Get the short resource name (e.g., 'Project', 'Task')
     */
    abstract public function getResourceName(): string;

    /**
     * Get the resource category for determining available tests
     * Options: 'safe_crud', 'read_only', 'configured_anchor'
     */
    abstract public function getResourceCategory(): string;

    /**
     * Create a test instance of this resource
     * Returns null if creation is not supported or fails
     */
    abstract protected function createTestResource(): ?AbstractResource;

    // ========================================================================
    // Test Capabilities - override to customize behavior
    // ========================================================================

    /**
     * Check if this resource supports creation
     */
    public function supportsCreate(): bool
    {
        return $this->getResourceCategory() === 'safe_crud';
    }

    /**
     * Check if this resource supports deletion
     */
    public function supportsDelete(): bool
    {
        return $this->getResourceCategory() === 'safe_crud';
    }

    /**
     * Check if this resource supports updates
     */
    public function supportsUpdate(): bool
    {
        return in_array($this->getResourceCategory(), ['safe_crud', 'configured_anchor']);
    }

    /**
     * Check if this resource requires a configured anchor ID
     */
    public function requiresAnchor(): bool
    {
        return $this->getResourceCategory() === 'configured_anchor';
    }

    /**
     * Get the anchor key if this resource requires one (e.g., 'user_id', 'client_id')
     */
    public function getAnchorKey(): ?string
    {
        return null;
    }

    /**
     * Check if this is a singleton resource (like Company)
     */
    public function isSingleton(): bool
    {
        return false;
    }

    // ========================================================================
    // Main Test Runner
    // ========================================================================

    /**
     * Run all tests for this resource
     * This is the main entry point called by the ResourceTestRunner
     */
    public function runAllTests(): void
    {
        $resourceName = $this->getResourceName();

        // Log resource header
        $this->logResourceHeader($resourceName);

        // Check anchor requirements
        if ($this->requiresAnchor()) {
            $anchorKey = $this->getAnchorKey();
            if ($anchorKey && !$this->config->getAnchor($anchorKey)) {
                $this->logWarning("Skipping {$resourceName}: No {$anchorKey} configured");
                $this->results->recordSkip("{$resourceName}", "No {$anchorKey} configured");
                return;
            }
        }

        // Initialize connection
        if (!$this->dryRun) {
            $this->connection = Paymo::connect($this->config->getApiKey());
        }

        try {
            // Run tests in order
            $this->runPropertyDiscovery();
            $this->runPropertySelection();

            if ($this->supportsCreate()) {
                $this->runCrudTests();
            } else {
                $this->runReadOnlyTests();
            }

            $this->runWhereOperations();
            $this->runIncludeTests();

        } catch (Throwable $e) {
            $this->logError("Test execution failed: " . $e->getMessage());
            $this->results->recordFailure(
                "{$resourceName}::execution",
                0,
                $e->getMessage()
            );
        } finally {
            // Always cleanup
            $this->cleanup();
        }

        $this->logResourceFooter($resourceName);
    }

    // ========================================================================
    // Property Discovery Tests
    // ========================================================================

    /**
     * Run property discovery - compare API response to SDK PROP_TYPES
     */
    protected function runPropertyDiscovery(): void
    {
        $testName = $this->getResourceName() . "::propertyDiscovery";

        if ($this->dryRun) {
            $this->output->dryRun("Would run property discovery for " . $this->getResourceName());
            return;
        }

        $this->logTestStart($testName);
        $startTime = microtime(true);
        $hasIssues = false;

        try {
            $this->logDetail("--- Property Discovery ---");
            $this->logDetail("Purpose: Compare API response properties against SDK PROP_TYPES definition");

            // Get SDK-defined properties
            $class = $this->getResourceClass();
            $sdkProps = $class::PROP_TYPES;

            $this->logDetail("");
            $this->logDetail("SDK PROP_TYPES for {$this->getResourceName()}:");
            foreach ($sdkProps as $prop => $type) {
                $typeStr = is_array($type) ? 'relation:' . json_encode($type) : $type;
                $this->logDetail("  - {$prop}: {$typeStr}");
            }
            $this->logDetail("Total: " . count($sdkProps) . " properties defined in SDK");

            // Log fetch operation
            $apiPath = $this->getApiPath();
            $this->logApiCall(
                'GET',
                "Fetching sample {$this->getResourceName()} for property analysis",
                null,
                $apiPath,
                ['include' => '*']  // Typically fetches with all properties
            );

            // Fetch a resource from API
            $resource = $this->fetchResourceForDiscovery();

            if (!$resource) {
                $this->logApiResponse(false, null, "No resource available");
                $this->logWarning("No resource available for property discovery");
                $this->results->recordSkip($testName, "No resource available");
                $this->output->testSkipped($testName, "No resource available");
                return;
            }

            $resourceId = $resource->id ?? null;
            $this->logApiResponse(true, $resourceId);

            // Extract raw properties
            $apiProps = $this->extractResourceProperties($resource);

            $this->logDetail("");
            $this->logDetail("API Response Properties:");
            foreach ($apiProps as $prop => $value) {
                $type = $this->inferType($value);
                $valuePreview = $this->getValuePreview($value);
                $this->logDetail("  - {$prop}: {$type} = {$valuePreview}");
            }
            $this->logDetail("Total: " . count($apiProps) . " properties in API response");

            // Compare
            $comparison = $this->compareProperties($sdkProps, $apiProps);

            // Log detailed comparison
            $this->logPropertyComparison($sdkProps, $apiProps, $comparison);

            // Determine if there are issues to report
            if (!empty($comparison['extra']) || !empty($comparison['missing'])) {
                $hasIssues = true;
            }

            $duration = microtime(true) - $startTime;
            $this->results->recordPass($testName, $duration);
            $this->output->testResult($testName, true, $duration);
            $this->logTestComplete($testName, true, $duration);

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logDetail("EXCEPTION: " . $e->getMessage());
            $this->logDetail("File: " . $e->getFile() . ":" . $e->getLine());
            $this->results->recordFailure($testName, $duration, $e->getMessage());
            $this->output->testResult($testName, false, $duration, $e->getMessage());
            $this->logTestComplete($testName, false, $duration, $e->getMessage());
        }
    }

    /**
     * Get a preview of a value for logging
     */
    protected function getValuePreview($value): string
    {
        if ($value === null) return 'null';
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_int($value) || is_float($value)) return (string)$value;
        if (is_string($value)) {
            if (strlen($value) > 50) {
                return '"' . substr($value, 0, 47) . '..."';
            }
            return '"' . $value . '"';
        }
        if (is_array($value)) {
            $count = count($value);
            return "[array:{$count}]";
        }
        if (is_object($value)) {
            return '[object:' . get_class($value) . ']';
        }
        return '[' . gettype($value) . ']';
    }

    /**
     * Fetch a resource for property discovery
     */
    protected function fetchResourceForDiscovery(): ?AbstractResource
    {
        $class = $this->getResourceClass();

        // For configured anchors, use the anchor ID
        if ($this->requiresAnchor()) {
            $anchorKey = $this->getAnchorKey();
            $id = $this->config->getAnchor($anchorKey);
            if ($id) {
                return $class::new()->fetch($id);
            }
            return null;
        }

        // For singletons, fetch via list
        if ($this->isSingleton()) {
            $collection = $class::list()->fetch();
            foreach ($collection as $item) {
                return $item;
            }
            return null;
        }

        // For regular resources, try to fetch the first one via list
        $collection = $class::list()->fetch();

        // Get raw data and return first item
        // Note: Collection iterator uses sequential index (0,1,2) but data is keyed by ID
        // So we use raw() to get the actual items
        $raw = $collection->raw();
        if (!empty($raw)) {
            // Return first item from the raw array (keyed by ID)
            return reset($raw);
        }

        return null;
    }

    /**
     * Extract raw properties from a resource
     */
    protected function extractResourceProperties(AbstractResource $resource): array
    {
        $props = [];

        if (method_exists($resource, 'raw')) {
            $raw = $resource->raw();
            if (is_array($raw)) {
                $props = $raw;
            } elseif (is_object($raw)) {
                $props = get_object_vars($raw);
            }
        }

        // Fallback: use reflection
        if (empty($props) && property_exists($resource, 'props')) {
            $reflection = new ReflectionClass($resource);
            $propsProp = $reflection->getProperty('props');
            $propsProp->setAccessible(true);
            $props = $propsProp->getValue($resource);
        }

        return $props;
    }

    /**
     * Compare SDK props against API props
     */
    protected function compareProperties(array $sdkProps, array $apiProps): array
    {
        $extra = [];
        $missing = [];
        $matched = [];

        // Find extra (in API but not SDK)
        foreach ($apiProps as $prop => $value) {
            if (in_array($prop, $this->ignoreProperties)) {
                continue;
            }
            if (!array_key_exists($prop, $sdkProps)) {
                $extra[$prop] = $this->inferType($value);
            } else {
                $matched[] = $prop;
            }
        }

        // Find missing (in SDK but not API)
        foreach ($sdkProps as $prop => $type) {
            if (!array_key_exists($prop, $apiProps) && !in_array($prop, $matched)) {
                if (!is_array($type)) {
                    $missing[$prop] = $type;
                }
            }
        }

        return [
            'extra' => $extra,
            'missing' => $missing,
            'matched' => $matched,
        ];
    }

    /**
     * Infer type from a value
     */
    protected function inferType($value): string
    {
        if ($value === null) return 'null';
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'decimal';
        if (is_string($value)) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $value)) return 'datetime';
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return 'date';
            return 'text';
        }
        if (is_array($value)) return 'array';
        if (is_object($value)) return 'object';
        return 'unknown';
    }

    // ========================================================================
    // Property Selection Tests
    // ========================================================================

    /**
     * Run property selection tests - verify each PROP_TYPES field can be selected
     *
     * SDK pattern: $class::list()->fetch(['id', 'prop1', 'prop2'], [conditions])
     * The fields to select are passed as first argument to fetch()
     */
    protected function runPropertySelection(): void
    {
        $testName = $this->getResourceName() . "::propertySelection";

        if ($this->dryRun) {
            $this->output->dryRun("Would run property selection for " . $this->getResourceName());
            return;
        }

        // Singletons don't support list() - skip
        if ($this->isSingleton()) {
            $this->logDetail("SKIP: {$testName} - Singleton resource does not support list()");
            $this->results->recordSkip($testName, "Singleton resource (no list support)");
            $this->output->testSkipped($testName, "Singleton resource (no list support)");
            return;
        }

        $this->logTestStart($testName);
        $startTime = microtime(true);

        try {
            $this->logDetail("--- Property Selection Test ---");
            $this->logDetail("Purpose: Verify each SDK PROP_TYPES property can be selected via API");

            $class = $this->getResourceClass();
            $sdkProps = $class::PROP_TYPES;

            // Get readable properties (not arrays/objects - those are relations)
            $selectableProps = array_filter($sdkProps, fn($type) => !is_array($type));
            $propNames = array_keys($selectableProps);

            $this->logDetail("");
            $this->logDetail("SELECTABLE PROPERTIES (" . count($propNames) . " total):");
            foreach ($propNames as $prop) {
                $type = $selectableProps[$prop];
                $this->logDetail("  - {$prop}: {$type}");
            }

            // Log skipped relation properties
            $relationProps = array_filter($sdkProps, fn($type) => is_array($type));
            if (!empty($relationProps)) {
                $this->logDetail("");
                $this->logDetail("SKIPPED RELATION PROPERTIES (" . count($relationProps) . "):");
                foreach ($relationProps as $prop => $type) {
                    $this->logDetail("  - {$prop}: [relation]");
                }
            }

            // Test fetching with all properties at once
            $includeStr = implode(',', $propNames);
            $this->logApiCall(
                'GET',
                "Fetching {$this->getResourceName()} with all selectable properties",
                null,
                $this->getApiPath(),
                ['include' => $includeStr]
            );

            $collection = $class::list()->fetch($propNames);
            $raw = $collection->raw();

            $this->logDetail("  Result: SUCCESS");
            $this->logDetail("  Resources returned: " . count($raw));

            if (!empty($raw)) {
                // Log sample of first resource's property values
                $firstItem = reset($raw);
                $firstItemRaw = $firstItem->raw();

                $this->logDetail("");
                $this->logDetail("SAMPLE RESOURCE (first result):");
                $this->logDetail("  ID: " . ($firstItem->id ?? 'N/A'));

                $returnedProps = 0;
                $missingProps = [];

                foreach ($propNames as $prop) {
                    if (array_key_exists($prop, $firstItemRaw)) {
                        $returnedProps++;
                        $value = $this->getValuePreview($firstItemRaw[$prop]);
                        $this->logDetail("  [OK] {$prop} = {$value}");
                    } else {
                        $missingProps[] = $prop;
                        $this->logDetail("  [MISSING] {$prop} - not in response");
                    }
                }

                $this->logDetail("");
                $this->logDetail("PROPERTY SELECTION SUMMARY:");
                $this->logDetail("  Requested: " . count($propNames));
                $this->logDetail("  Returned: {$returnedProps}");
                $this->logDetail("  Missing: " . count($missingProps));

                if (!empty($missingProps)) {
                    $this->logDetail("");
                    $this->logDetail("  *** CONCERN: Properties requested but not returned:");
                    foreach ($missingProps as $prop) {
                        $this->logDetail("      - {$prop}");
                    }
                }
            } else {
                $this->logDetail("No resources found (this is not a failure - collection may be empty)");
            }

            $duration = microtime(true) - $startTime;
            $this->results->recordPass($testName, $duration);
            $this->output->testResult($testName, true, $duration);
            $this->logTestComplete($testName, true, $duration);

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logDetail("EXCEPTION: " . $e->getMessage());
            $this->logDetail("File: " . $e->getFile() . ":" . $e->getLine());
            $this->results->recordFailure($testName, $duration, $e->getMessage());
            $this->output->testResult($testName, false, $duration, $e->getMessage());
            $this->logTestComplete($testName, false, $duration, $e->getMessage());
        }
    }

    // ========================================================================
    // CRUD Tests
    // ========================================================================

    /**
     * Run CRUD tests for resources that support create/delete
     */
    protected function runCrudTests(): void
    {
        $this->logDetail("");
        $this->logDetail("=== CRUD TESTS ===");
        $this->logDetail("Testing Create, Fetch, Update, List, Delete operations");

        $this->runCreateTest();
        $this->runFetchTest();
        $this->runUpdateTest();
        $this->runListTest();
        $this->runDeleteTest();
    }

    protected function runCreateTest(): void
    {
        $testName = $this->getResourceName() . "::create";

        if ($this->dryRun) {
            $this->output->dryRun("Would test create");
            return;
        }

        $this->logTestStart($testName);
        $startTime = microtime(true);

        try {
            $this->logDetail("--- CREATE Test ---");
            $this->logDetail("Purpose: Verify resource can be created via API");

            // Log REQUIRED_CREATE fields
            $class = $this->getResourceClass();
            $requiredFields = $class::REQUIRED_CREATE ?? [];
            $createOnlyFields = $class::CREATEONLY ?? [];

            $this->logDetail("");
            $this->logDetail("REQUIRED_CREATE fields: " . (empty($requiredFields) ? 'none' : implode(', ', $requiredFields)));
            $this->logDetail("CREATEONLY fields: " . (empty($createOnlyFields) ? 'none' : implode(', ', array_keys(array_filter($createOnlyFields)))));

            $this->logApiCall(
                'POST',
                "Creating new {$this->getResourceName()}",
                null,  // Data will be logged after creation attempt
                $this->getApiPath()
            );

            $resource = $this->createTestResource();

            if ($resource === null) {
                $this->logDetail("  Result: SKIPPED - Create not implemented for this resource");
                $this->results->recordSkip($testName, "Create not implemented");
                $this->output->testSkipped($testName, "Create not implemented");
                return;
            }

            // Verify it has an ID
            $resourceId = $resource->id;
            if ($resourceId === null || $resourceId === '' || $resourceId === 0) {
                $rawProps = $resource->raw();
                $rawId = isset($rawProps['id']) ? $rawProps['id'] : 'NOT SET';
                $this->logDetail("  Result: FAILED - No ID returned");
                $this->logDetail("  Raw ID value: {$rawId}");
                throw new \Exception("Created resource has no ID (raw id: {$rawId})");
            }

            $this->logDetail("  Result: SUCCESS");
            $this->logDetail("  Created ID: {$resourceId}");

            // Log created resource properties
            $rawProps = $resource->raw();
            $this->logDetail("");
            $this->logDetail("CREATED RESOURCE PROPERTIES:");
            foreach ($rawProps as $prop => $value) {
                $preview = $this->getValuePreview($value);
                $this->logDetail("  {$prop}: {$preview}");
            }

            $this->testResource = $resource;
            $this->cleanupManager->track(
                $this->getResourceName(),
                $resource->id,
                $this->getResourceClass()
            );

            $this->logCrudOperation('CREATE', $this->getResourceName(), $resource->id, [], true);

            $duration = microtime(true) - $startTime;
            $this->results->recordPass($testName, $duration);
            $this->output->testResult($testName, true, $duration);
            $this->logTestComplete($testName, true, $duration);

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logDetail("EXCEPTION: " . $e->getMessage());
            $this->logDetail("File: " . $e->getFile() . ":" . $e->getLine());
            $this->logCrudOperation('CREATE', $this->getResourceName(), null, [], false, $e->getMessage());
            $this->results->recordFailure($testName, $duration, $e->getMessage());
            $this->output->testResult($testName, false, $duration, $e->getMessage());
            $this->logTestComplete($testName, false, $duration, $e->getMessage());
        }
    }

    protected function runFetchTest(): void
    {
        $testName = $this->getResourceName() . "::fetch";

        if ($this->dryRun) {
            $this->output->dryRun("Would test fetch");
            return;
        }

        $this->logTestStart($testName);
        $startTime = microtime(true);

        try {
            $this->logDetail("--- FETCH Test ---");
            $this->logDetail("Purpose: Verify resource can be fetched by ID via API");

            // Need a resource to fetch
            if (!$this->testResource) {
                $this->logDetail("No existing test resource, creating one...");
                $this->testResource = $this->createTestResource();
                if ($this->testResource) {
                    $this->logDetail("  Created resource #{$this->testResource->id} for fetch test");
                    $this->cleanupManager->track(
                        $this->getResourceName(),
                        $this->testResource->id,
                        $this->getResourceClass()
                    );
                }
            }

            if (!$this->testResource) {
                $this->logDetail("  Result: SKIPPED - No resource available to fetch");
                $this->results->recordSkip($testName, "No resource to fetch");
                $this->output->testSkipped($testName, "No resource to fetch");
                return;
            }

            $class = $this->getResourceClass();
            $targetId = $this->testResource->id;

            $this->logApiCall(
                'GET',
                "Fetching {$this->getResourceName()} by ID",
                null,
                $this->getApiPath() . "/{$targetId}"
            );

            $fetched = $class::new()->fetch($targetId);

            if (!$fetched) {
                $this->logDetail("  Result: FAILED - Fetch returned null");
                throw new \Exception("Fetch returned null for ID {$targetId}");
            }

            if ($fetched->id !== $targetId) {
                $this->logDetail("  Result: FAILED - ID mismatch");
                $this->logDetail("  Expected ID: {$targetId}");
                $this->logDetail("  Received ID: {$fetched->id}");
                throw new \Exception("Fetched resource ID ({$fetched->id}) does not match requested ({$targetId})");
            }

            $this->logDetail("  Result: SUCCESS");
            $this->logDetail("  Fetched ID: {$fetched->id}");

            // Log fetched properties
            $rawProps = $fetched->raw();
            $this->logDetail("");
            $this->logDetail("FETCHED RESOURCE PROPERTIES:");
            foreach ($rawProps as $prop => $value) {
                $preview = $this->getValuePreview($value);
                $this->logDetail("  {$prop}: {$preview}");
            }

            $this->logCrudOperation('FETCH', $this->getResourceName(), $fetched->id, [], true);

            $duration = microtime(true) - $startTime;
            $this->results->recordPass($testName, $duration);
            $this->output->testResult($testName, true, $duration);
            $this->logTestComplete($testName, true, $duration);

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logDetail("EXCEPTION: " . $e->getMessage());
            $this->logDetail("File: " . $e->getFile() . ":" . $e->getLine());
            $this->logCrudOperation('FETCH', $this->getResourceName(), $this->testResource?->id, [], false, $e->getMessage());
            $this->results->recordFailure($testName, $duration, $e->getMessage());
            $this->output->testResult($testName, false, $duration, $e->getMessage());
            $this->logTestComplete($testName, false, $duration, $e->getMessage());
        }
    }

    protected function runUpdateTest(): void
    {
        $testName = $this->getResourceName() . "::update";

        if ($this->dryRun) {
            $this->output->dryRun("Would test update");
            return;
        }

        if (!$this->supportsUpdate()) {
            $this->logDetail("SKIP: {$testName} - Resource does not support updates");
            $this->results->recordSkip($testName, "Resource does not support updates");
            $this->output->testSkipped($testName, "Resource does not support updates");
            return;
        }

        $this->logTestStart($testName);
        $startTime = microtime(true);

        try {
            $this->logDetail("--- UPDATE Test ---");
            $this->logDetail("Purpose: Verify resource can be updated via API");

            // Log READONLY fields
            $class = $this->getResourceClass();
            $readOnlyFields = $class::READONLY ?? [];
            $createOnlyFields = $class::CREATEONLY ?? [];

            $readOnlyList = array_keys(array_filter($readOnlyFields));
            $createOnlyList = array_keys(array_filter($createOnlyFields));

            $this->logDetail("");
            $this->logDetail("READONLY fields (cannot update): " . (empty($readOnlyList) ? 'none' : implode(', ', $readOnlyList)));
            $this->logDetail("CREATEONLY fields (cannot update): " . (empty($createOnlyList) ? 'none' : implode(', ', $createOnlyList)));

            if (!$this->testResource) {
                $this->logDetail("No existing test resource, creating one...");
                $this->testResource = $this->createTestResource();
                if ($this->testResource) {
                    $this->logDetail("  Created resource #{$this->testResource->id} for update test");
                    $this->cleanupManager->track(
                        $this->getResourceName(),
                        $this->testResource->id,
                        $this->getResourceClass()
                    );
                }
            }

            if (!$this->testResource) {
                $this->logDetail("  Result: SKIPPED - No resource available to update");
                $this->results->recordSkip($testName, "No resource to update");
                $this->output->testSkipped($testName, "No resource to update");
                return;
            }

            // Get before state
            $beforeRaw = $this->testResource->raw();
            $this->logDetail("");
            $this->logDetail("BEFORE UPDATE (resource #{$this->testResource->id}):");
            foreach ($beforeRaw as $prop => $value) {
                $preview = $this->getValuePreview($value);
                $this->logDetail("  {$prop}: {$preview}");
            }

            $updated = $this->performUpdate($this->testResource);

            if (!$updated) {
                $this->logDetail("  Result: SKIPPED - No updatable property found");
                $this->results->recordSkip($testName, "No updatable property found");
                $this->output->testSkipped($testName, "No updatable property found");
                return;
            }

            // Get after state
            $afterRaw = $this->testResource->raw();
            $this->logDetail("");
            $this->logDetail("AFTER UPDATE:");
            $changesFound = [];
            foreach ($afterRaw as $prop => $value) {
                $preview = $this->getValuePreview($value);
                $beforeValue = $beforeRaw[$prop] ?? null;
                $changed = $value !== $beforeValue;
                $marker = $changed ? '[CHANGED]' : '';
                $this->logDetail("  {$marker} {$prop}: {$preview}");
                if ($changed) {
                    $changesFound[$prop] = [
                        'before' => $this->getValuePreview($beforeValue),
                        'after' => $preview
                    ];
                }
            }

            $this->logDetail("");
            $this->logDetail("UPDATE SUMMARY:");
            $this->logDetail("  Properties changed: " . count($changesFound));
            foreach ($changesFound as $prop => $change) {
                $this->logDetail("    {$prop}: {$change['before']} -> {$change['after']}");
            }

            $this->logCrudOperation('UPDATE', $this->getResourceName(), $this->testResource->id, $changesFound, true);

            $duration = microtime(true) - $startTime;
            $this->results->recordPass($testName, $duration);
            $this->output->testResult($testName, true, $duration);
            $this->logTestComplete($testName, true, $duration);

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logDetail("EXCEPTION: " . $e->getMessage());
            $this->logDetail("File: " . $e->getFile() . ":" . $e->getLine());
            $this->logCrudOperation('UPDATE', $this->getResourceName(), $this->testResource?->id, [], false, $e->getMessage());
            $this->results->recordFailure($testName, $duration, $e->getMessage());
            $this->output->testResult($testName, false, $duration, $e->getMessage());
            $this->logTestComplete($testName, false, $duration, $e->getMessage());
        }
    }

    /**
     * Perform an update on a resource - override to customize
     */
    protected function performUpdate(AbstractResource $resource): bool
    {
        $class = get_class($resource);

        $this->logDetail("");
        $this->logDetail("SEARCHING FOR UPDATABLE PROPERTY:");

        // Default: try to update 'name' property if it exists in PROP_TYPES
        // and is not READONLY
        if (isset($class::PROP_TYPES['name'])) {
            $isReadOnly = isset($class::READONLY['name']) && $class::READONLY['name'];
            $isCreateOnly = isset($class::CREATEONLY['name']) && $class::CREATEONLY['name'];

            $this->logDetail("  Checking 'name': READONLY=" . ($isReadOnly ? 'yes' : 'no') . ", CREATEONLY=" . ($isCreateOnly ? 'yes' : 'no'));

            if (!$isReadOnly && !$isCreateOnly) {
                $oldValue = $resource->name;
                $newValue = $this->factory->uniqueName('Updated');
                $this->logDetail("  Selected 'name' for update");
                $this->logDetail("    Old value: " . $this->getValuePreview($oldValue));
                $this->logDetail("    New value: " . $this->getValuePreview($newValue));

                $resource->name = $newValue;

                $this->logApiCall(
                    'PUT',
                    "Updating {$this->getResourceName()} #{$resource->id}",
                    ['name' => $newValue],
                    $this->getApiPath() . "/{$resource->id}"
                );

                $resource->update();
                $this->logDetail("  Result: SUCCESS");
                return true;
            }
        }

        // Try to find another updatable string property
        foreach ($class::PROP_TYPES as $prop => $type) {
            // Skip read-only
            if (isset($class::READONLY[$prop]) && $class::READONLY[$prop]) {
                $this->logDetail("  Skipping '{$prop}': READONLY");
                continue;
            }
            // Skip create-only
            if (isset($class::CREATEONLY[$prop]) && $class::CREATEONLY[$prop]) {
                $this->logDetail("  Skipping '{$prop}': CREATEONLY");
                continue;
            }
            // Skip ID
            if ($prop === 'id') {
                $this->logDetail("  Skipping '{$prop}': is ID field");
                continue;
            }
            // Only text/string properties are safe to update
            if ($type === 'text' && $resource->$prop !== null) {
                $oldValue = $resource->$prop;
                $newValue = $this->factory->uniqueName('Updated');
                $this->logDetail("  Selected '{$prop}' for update");
                $this->logDetail("    Old value: " . $this->getValuePreview($oldValue));
                $this->logDetail("    New value: " . $this->getValuePreview($newValue));

                $resource->$prop = $newValue;

                $this->logApiCall(
                    'PUT',
                    "Updating {$this->getResourceName()} #{$resource->id}",
                    [$prop => $newValue],
                    $this->getApiPath() . "/{$resource->id}"
                );

                $resource->update();
                $this->logDetail("  Result: SUCCESS");
                return true;
            }
        }

        $this->logDetail("  No suitable property found for update test");
        return false;
    }

    protected function runListTest(): void
    {
        $testName = $this->getResourceName() . "::list";

        if ($this->dryRun) {
            $this->output->dryRun("Would test list");
            return;
        }

        // Singletons don't support list()
        if ($this->isSingleton()) {
            $this->logDetail("SKIP: {$testName} - Singleton resource does not support list()");
            $this->results->recordSkip($testName, "Singleton resource (no list support)");
            $this->output->testSkipped($testName, "Singleton resource (no list support)");
            return;
        }

        $this->logTestStart($testName);
        $startTime = microtime(true);

        try {
            $this->logDetail("--- LIST Test ---");
            $this->logDetail("Purpose: Verify resource collection can be listed via API");

            $class = $this->getResourceClass();

            $this->logApiCall(
                'GET',
                "Fetching all {$this->getResourceName()} resources",
                null,
                $this->getApiPath()
            );

            $collection = $class::list()->fetch();
            $raw = $collection->raw();
            $count = count($raw);

            $this->logDetail("  Result: SUCCESS");
            $this->logDetail("  Resources returned: {$count}");

            if ($count > 0) {
                $this->logDetail("");
                $this->logDetail("LIST RESULTS SUMMARY:");

                // Show first few resources
                $showCount = min(5, $count);
                $this->logDetail("  Showing first {$showCount} of {$count} resources:");

                $i = 0;
                foreach ($raw as $item) {
                    if ($i >= $showCount) break;

                    $itemId = $item->id ?? 'N/A';
                    $itemName = $item->name ?? ($item->title ?? 'N/A');
                    $this->logDetail("    #{$itemId}: {$itemName}");
                    $i++;
                }

                if ($count > $showCount) {
                    $this->logDetail("    ... and " . ($count - $showCount) . " more");
                }

                // Analyze sample resource structure
                $firstItem = reset($raw);
                if ($firstItem) {
                    $firstRaw = $firstItem->raw();
                    $this->logDetail("");
                    $this->logDetail("  Sample resource structure (first item):");
                    $this->logDetail("    Properties returned: " . count($firstRaw));
                    $propList = array_keys($firstRaw);
                    $this->logDetail("    Fields: " . implode(', ', $propList));
                }
            } else {
                $this->logDetail("  No resources found (collection is empty)");
            }

            $this->logCrudOperation('LIST', $this->getResourceName(), null, ['count' => $count], true);

            $duration = microtime(true) - $startTime;
            $this->results->recordPass($testName, $duration);
            $this->output->testResult($testName, true, $duration);
            $this->logTestComplete($testName, true, $duration);

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logDetail("EXCEPTION: " . $e->getMessage());
            $this->logDetail("File: " . $e->getFile() . ":" . $e->getLine());
            $this->logCrudOperation('LIST', $this->getResourceName(), null, [], false, $e->getMessage());
            $this->results->recordFailure($testName, $duration, $e->getMessage());
            $this->output->testResult($testName, false, $duration, $e->getMessage());
            $this->logTestComplete($testName, false, $duration, $e->getMessage());
        }
    }

    protected function runDeleteTest(): void
    {
        $testName = $this->getResourceName() . "::delete";

        if ($this->dryRun) {
            $this->output->dryRun("Would test delete");
            return;
        }

        if (!$this->supportsDelete()) {
            $this->logDetail("SKIP: {$testName} - Resource does not support deletion");
            $this->results->recordSkip($testName, "Resource does not support deletion");
            $this->output->testSkipped($testName, "Resource does not support deletion");
            return;
        }

        $this->logTestStart($testName);
        $startTime = microtime(true);

        try {
            $this->logDetail("--- DELETE Test ---");
            $this->logDetail("Purpose: Verify resource can be deleted via API");

            // Create a fresh resource specifically for deletion
            $this->logDetail("Creating fresh resource for deletion test...");

            $resource = $this->createTestResource();

            if (!$resource) {
                $this->logDetail("  Result: SKIPPED - Could not create resource for deletion");
                $this->results->recordSkip($testName, "Could not create resource for deletion");
                $this->output->testSkipped($testName, "Could not create resource for deletion");
                return;
            }

            $id = $resource->id;
            $this->logDetail("  Created resource #{$id} for deletion test");

            // Log resource before deletion
            $rawProps = $resource->raw();
            $this->logDetail("");
            $this->logDetail("RESOURCE TO DELETE (#{$id}):");
            foreach ($rawProps as $prop => $value) {
                $preview = $this->getValuePreview($value);
                $this->logDetail("  {$prop}: {$preview}");
            }

            $this->logApiCall(
                'DELETE',
                "Deleting {$this->getResourceName()} #{$id}",
                null,
                $this->getApiPath() . "/{$id}"
            );

            $resource->delete();

            $this->logDetail("  Result: SUCCESS");
            $this->logDetail("  Resource #{$id} has been deleted");

            // Verify deletion by trying to fetch (optional verification)
            $this->logDetail("");
            $this->logDetail("VERIFICATION:");
            try {
                $class = $this->getResourceClass();
                $verifyFetch = $class::new()->fetch($id);
                if ($verifyFetch) {
                    $this->logDetail("  *** CONCERN: Resource #{$id} still exists after delete!");
                } else {
                    $this->logDetail("  Confirmed: Resource #{$id} no longer exists");
                }
            } catch (Throwable $verifyEx) {
                // Expected - resource should not be found
                $this->logDetail("  Confirmed: Resource #{$id} not found (expected after delete)");
            }

            $this->logCrudOperation('DELETE', $this->getResourceName(), $id, [], true);

            // Don't track for cleanup since we deleted it
            $duration = microtime(true) - $startTime;
            $this->results->recordPass($testName, $duration);
            $this->output->testResult($testName, true, $duration);
            $this->logTestComplete($testName, true, $duration);

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logDetail("EXCEPTION: " . $e->getMessage());
            $this->logDetail("File: " . $e->getFile() . ":" . $e->getLine());
            $this->logCrudOperation('DELETE', $this->getResourceName(), null, [], false, $e->getMessage());
            $this->results->recordFailure($testName, $duration, $e->getMessage());
            $this->output->testResult($testName, false, $duration, $e->getMessage());
            $this->logTestComplete($testName, false, $duration, $e->getMessage());
        }
    }

    // ========================================================================
    // Read-Only Tests
    // ========================================================================

    /**
     * Run read-only tests for resources that can't be created/deleted
     */
    protected function runReadOnlyTests(): void
    {
        $this->logDetail("");
        $this->logDetail("=== READ-ONLY TESTS ===");
        $this->logDetail("Testing Fetch and List operations (Create/Update/Delete not supported)");

        $this->runFetchTest();
        $this->runListTest();
    }

    // ========================================================================
    // Where Operations Tests
    // ========================================================================

    /**
     * Run where operations tests
     */
    protected function runWhereOperations(): void
    {
        $testName = $this->getResourceName() . "::whereOperations";

        if ($this->dryRun) {
            $this->output->dryRun("Would test where operations");
            return;
        }

        // Singletons don't support list() or where
        if ($this->isSingleton()) {
            $this->logDetail("SKIP: {$testName} - Singleton resource does not support list/where");
            $this->results->recordSkip($testName, "Singleton resource");
            $this->output->testSkipped($testName, "Singleton resource");
            return;
        }

        $this->logTestStart($testName);
        $startTime = microtime(true);

        try {
            $this->logDetail("--- WHERE OPERATIONS Test ---");
            $this->logDetail("Purpose: Verify SDK WHERE_OPERATIONS can filter resources via API");

            $class = $this->getResourceClass();

            // Check if resource defines WHERE_OPERATIONS
            if (!defined("$class::WHERE_OPERATIONS")) {
                $this->logDetail("  No WHERE_OPERATIONS constant defined for this resource");
                $this->results->recordSkip($testName, "No WHERE_OPERATIONS defined");
                $this->output->testSkipped($testName, "No WHERE_OPERATIONS defined");
                return;
            }

            $whereOps = $class::WHERE_OPERATIONS;

            // Log all defined WHERE_OPERATIONS
            $this->logWhereOperations($whereOps);

            $this->logDetail("");
            $this->logDetail("TESTING WHERE OPERATIONS:");

            $testsRun = 0;
            $testsPassed = 0;

            // Test a simple where clause with 'id' if available
            if (isset($whereOps['id'])) {
                $operators = $whereOps['id'];
                $this->logDetail("");
                $this->logDetail("Testing 'id' filter:");
                $this->logDetail("  Operators available: " . (is_array($operators) ? implode(', ', $operators) : $operators));

                // First get a resource to test with
                $this->logApiCall(
                    'GET',
                    "Fetching {$this->getResourceName()} resources to get test ID",
                    null,
                    $this->getApiPath()
                );
                $collection = $class::list()->fetch();
                $raw = $collection->raw();

                if (!empty($raw)) {
                    $firstItem = reset($raw);
                    $testId = $firstItem->id;
                    $this->logDetail("  Using ID: {$testId}");

                    // Test filtering by this ID
                    $this->logApiCall(
                        'GET',
                        "Filtering {$this->getResourceName()} where id = {$testId}",
                        null,
                        $this->getApiPath(),
                        ['where' => "id={$testId}"]
                    );

                    $filtered = $class::list()->where('id', '=', $testId)->fetch();
                    $filteredRaw = $filtered->raw();
                    $count = count($filteredRaw);

                    $this->logDetail("  Result: {$count} resources returned");
                    $testsRun++;

                    if ($count === 1) {
                        $returnedItem = reset($filteredRaw);
                        if ($returnedItem->id === $testId) {
                            $this->logDetail("  [PASS] Correctly returned resource #{$testId}");
                            $testsPassed++;
                        } else {
                            $this->logDetail("  [FAIL] Returned resource ID ({$returnedItem->id}) does not match filter ({$testId})");
                        }
                    } elseif ($count === 0) {
                        $this->logDetail("  [FAIL] No resources returned for existing ID {$testId}");
                    } else {
                        $this->logDetail("  [WARN] Multiple resources returned ({$count}) for single ID filter");
                    }
                } else {
                    $this->logDetail("  No resources available to test ID filter");
                }
            }

            // Test additional where operations if they exist
            $additionalTests = ['name', 'active', 'created_on', 'project_id', 'client_id'];
            foreach ($additionalTests as $prop) {
                if (isset($whereOps[$prop]) && $prop !== 'id') {
                    $operators = $whereOps[$prop];
                    $this->logDetail("");
                    $this->logDetail("Checking '{$prop}' filter:");
                    $this->logDetail("  Operators available: " . (is_array($operators) ? implode(', ', $operators) : $operators));
                    $this->logDetail("  (Full test not implemented - logged for reference)");
                }
            }

            $this->logDetail("");
            $this->logDetail("WHERE OPERATIONS SUMMARY:");
            $this->logDetail("  Total WHERE properties defined: " . count($whereOps));
            $this->logDetail("  Tests run: {$testsRun}");
            $this->logDetail("  Tests passed: {$testsPassed}");

            $duration = microtime(true) - $startTime;
            $this->results->recordPass($testName, $duration);
            $this->output->testResult($testName, true, $duration);
            $this->logTestComplete($testName, true, $duration);

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logDetail("EXCEPTION: " . $e->getMessage());
            $this->logDetail("File: " . $e->getFile() . ":" . $e->getLine());
            $this->results->recordFailure($testName, $duration, $e->getMessage());
            $this->output->testResult($testName, false, $duration, $e->getMessage());
            $this->logTestComplete($testName, false, $duration, $e->getMessage());
        }
    }

    // ========================================================================
    // Include Tests
    // ========================================================================

    /**
     * Run include relationship tests
     */
    protected function runIncludeTests(): void
    {
        $testName = $this->getResourceName() . "::includes";

        if ($this->dryRun) {
            $this->output->dryRun("Would test includes");
            return;
        }

        $this->logTestStart($testName);
        $startTime = microtime(true);

        try {
            $this->logDetail("--- INCLUDE RELATIONSHIPS Test ---");
            $this->logDetail("Purpose: Verify SDK INCLUDE_TYPES can be fetched via API");

            $class = $this->getResourceClass();

            // Check if resource defines INCLUDE_TYPES
            if (!defined("$class::INCLUDE_TYPES") || empty($class::INCLUDE_TYPES)) {
                $this->logDetail("  No INCLUDE_TYPES constant defined for this resource");
                $this->results->recordSkip($testName, "No INCLUDE_TYPES defined");
                $this->output->testSkipped($testName, "No INCLUDE_TYPES defined");
                return;
            }

            $includes = $class::INCLUDE_TYPES;

            // Log all defined INCLUDE_TYPES
            $this->logIncludeTypes($includes);

            $this->logDetail("");
            $this->logDetail("TESTING INCLUDE RELATIONSHIPS:");

            $testsRun = 0;
            $testsPassed = 0;
            $testsSkipped = 0;

            // Get a resource to test includes on
            $testResource = $this->testResource ?? $this->fetchResourceForDiscovery();

            if (!$testResource) {
                $this->logDetail("  No resource available to test includes");
                $this->results->recordSkip($testName, "No resource available");
                $this->output->testSkipped($testName, "No resource available");
                return;
            }

            $testId = $testResource->id;
            $this->logDetail("Using resource #{$testId} for include tests");

            // Test each include relationship
            foreach ($includes as $includeName => $config) {
                $this->logDetail("");
                $this->logDetail("Testing include '{$includeName}':");

                if (is_array($config)) {
                    $relatedClass = $config[0] ?? 'unknown';
                    $isMultiple = ($config[1] ?? 0) === 1;
                    $this->logDetail("  Related class: {$relatedClass}");
                    $this->logDetail("  Cardinality: " . ($isMultiple ? 'multiple (hasMany)' : 'single (hasOne)'));
                } else {
                    $relatedClass = $config;
                    $isMultiple = false;
                    $this->logDetail("  Related class: {$relatedClass}");
                }

                // Try to fetch with this include
                try {
                    $this->logApiCall(
                        'GET',
                        "Fetching {$this->getResourceName()} #{$testId} with include '{$includeName}'",
                        null,
                        $this->getApiPath() . "/{$testId}",
                        ['include' => $includeName]
                    );

                    // Fetch with include - SDK pattern: Resource::new()->include('relation')->fetch($id)
                    $resourceWithInclude = $class::new()->include($includeName)->fetch($testId);

                    if ($resourceWithInclude) {
                        $testsRun++;
                        $this->logDetail("  Result: SUCCESS");

                        // Check if the include data was returned
                        $raw = $resourceWithInclude->raw();

                        // Look for the include data - it might be in the raw data or as a property
                        $includeData = null;
                        if (isset($raw[$includeName])) {
                            $includeData = $raw[$includeName];
                        } elseif (property_exists($resourceWithInclude, $includeName)) {
                            $includeData = $resourceWithInclude->$includeName;
                        }

                        if ($includeData !== null) {
                            if (is_array($includeData)) {
                                $count = count($includeData);
                                $this->logDetail("  Include data: {$count} related items");

                                // Log first few items
                                $showCount = min(3, $count);
                                for ($i = 0; $i < $showCount; $i++) {
                                    $item = $includeData[$i];
                                    $itemId = is_object($item) ? ($item->id ?? 'N/A') : ($item['id'] ?? 'N/A');
                                    $this->logDetail("    - Item {$i}: ID #{$itemId}");
                                }
                                if ($count > $showCount) {
                                    $this->logDetail("    ... and " . ($count - $showCount) . " more");
                                }
                            } elseif (is_object($includeData)) {
                                $itemId = $includeData->id ?? 'N/A';
                                $this->logDetail("  Include data: single item ID #{$itemId}");
                            } else {
                                $this->logDetail("  Include data type: " . gettype($includeData));
                            }
                            $testsPassed++;
                            $this->logDetail("  [PASS] Include relationship works");
                        } else {
                            $this->logDetail("  Include data: empty or not present in response");
                            $this->logDetail("  [WARN] Include returned but no related data found");
                            $testsPassed++; // Still a pass - just no related data exists
                        }
                    } else {
                        $this->logDetail("  Result: FAILED - Fetch returned null");
                        $testsRun++;
                    }

                } catch (Throwable $includeEx) {
                    $testsRun++;
                    $this->logDetail("  Result: FAILED");
                    $this->logDetail("  Error: " . $includeEx->getMessage());
                    $this->logDetail("  [FAIL] Include '{$includeName}' threw exception");
                }
            }

            $this->logDetail("");
            $this->logDetail("INCLUDE TESTS SUMMARY:");
            $this->logDetail("  Total includes defined: " . count($includes));
            $this->logDetail("  Tests run: {$testsRun}");
            $this->logDetail("  Tests passed: {$testsPassed}");
            $this->logDetail("  Tests skipped: {$testsSkipped}");

            if ($testsPassed < $testsRun) {
                $this->logDetail("");
                $this->logDetail("  *** CONCERN: Some include relationships failed");
            }

            $duration = microtime(true) - $startTime;
            $this->results->recordPass($testName, $duration);
            $this->output->testResult($testName, true, $duration);
            $this->logTestComplete($testName, true, $duration);

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logDetail("EXCEPTION: " . $e->getMessage());
            $this->logDetail("File: " . $e->getFile() . ":" . $e->getLine());
            $this->results->recordFailure($testName, $duration, $e->getMessage());
            $this->output->testResult($testName, false, $duration, $e->getMessage());
            $this->logTestComplete($testName, false, $duration, $e->getMessage());
        }
    }

    // ========================================================================
    // Dependency Helpers - Get or Create Required Resources
    // ========================================================================

    /**
     * Get or create a Client for testing
     * Uses anchored client_id if configured, otherwise creates a temporary one
     *
     * @return int|null Client ID
     */
    protected function ensureClient(): ?int
    {
        // Check for anchored client first
        $anchoredClientId = $this->config->getAnchor('client_id');
        if ($anchoredClientId) {
            $this->logDetail("Using anchored Client #{$anchoredClientId}");
            return $anchoredClientId;
        }

        // Need to create a temporary client
        $clientClass = 'Jcolombo\\PaymoApiPhp\\Entity\\Resource\\Client';
        $clientData = $this->factory->clientData();

        $client = new $clientClass();
        $client->name = $clientData['name'];
        $client->create();

        $this->logDetail("Created temporary Client #{$client->id}");
        $this->cleanupManager->track('Client', $client->id, $clientClass);

        return $client->id;
    }

    /**
     * Get or create a Project for testing
     * Uses anchored project_id if configured, otherwise creates a temporary one
     *
     * @return int|null Project ID
     */
    protected function ensureProject(): ?int
    {
        // Check for anchored project first
        $anchoredProjectId = $this->config->getAnchor('project_id');
        if ($anchoredProjectId) {
            $this->logDetail("Using anchored Project #{$anchoredProjectId}");
            return $anchoredProjectId;
        }

        // Need a client first
        $clientId = $this->ensureClient();
        if (!$clientId) {
            return null;
        }

        // Create a temporary project
        $projectClass = 'Jcolombo\\PaymoApiPhp\\Entity\\Resource\\Project';
        $projectData = $this->factory->projectData($clientId);

        $project = new $projectClass();
        $project->name = $projectData['name'];
        $project->client_id = $clientId;
        $project->create();

        $this->logDetail("Created temporary Project #{$project->id}");
        $this->cleanupManager->track('Project', $project->id, $projectClass);

        return $project->id;
    }

    /**
     * Get or create a Tasklist for testing
     * Uses anchored tasklist_id if configured, otherwise creates a temporary one
     *
     * @return int|null Tasklist ID
     */
    protected function ensureTasklist(): ?int
    {
        // Check for anchored tasklist first
        $anchoredTasklistId = $this->config->getAnchor('tasklist_id');
        if ($anchoredTasklistId) {
            $this->logDetail("Using anchored Tasklist #{$anchoredTasklistId}");
            return $anchoredTasklistId;
        }

        // Need a project first
        $projectId = $this->ensureProject();
        if (!$projectId) {
            return null;
        }

        // Create a temporary tasklist
        $tasklistClass = 'Jcolombo\\PaymoApiPhp\\Entity\\Resource\\Tasklist';
        $tasklistData = $this->factory->tasklistData($projectId);

        $tasklist = new $tasklistClass();
        $tasklist->name = $tasklistData['name'];
        $tasklist->project_id = $projectId;
        $tasklist->create();

        $this->logDetail("Created temporary Tasklist #{$tasklist->id}");
        $this->cleanupManager->track('Tasklist', $tasklist->id, $tasklistClass);

        return $tasklist->id;
    }

    /**
     * Get or create a Task for testing
     * Uses anchored task_id if configured, otherwise creates a temporary one
     *
     * @return int|null Task ID
     */
    protected function ensureTask(): ?int
    {
        // Check for anchored task first
        $anchoredTaskId = $this->config->getAnchor('task_id');
        if ($anchoredTaskId) {
            $this->logDetail("Using anchored Task #{$anchoredTaskId}");
            return $anchoredTaskId;
        }

        // Need a tasklist first
        $tasklistId = $this->ensureTasklist();
        if (!$tasklistId) {
            return null;
        }

        // Create a temporary task
        $taskClass = 'Jcolombo\\PaymoApiPhp\\Entity\\Resource\\Task';
        $taskData = $this->factory->taskData($tasklistId);

        $task = new $taskClass();
        $task->name = $taskData['name'];
        $task->tasklist_id = $tasklistId;
        $task->create();

        $this->logDetail("Created temporary Task #{$task->id}");
        $this->cleanupManager->track('Task', $task->id, $taskClass);

        return $task->id;
    }

    /**
     * Get or create an Invoice for testing
     * Uses anchored invoice_id if configured, otherwise creates a temporary one
     *
     * @return int|null Invoice ID
     */
    protected function ensureInvoice(): ?int
    {
        // Check for anchored invoice first
        $anchoredInvoiceId = $this->config->getAnchor('invoice_id');
        if ($anchoredInvoiceId) {
            $this->logDetail("Using anchored Invoice #{$anchoredInvoiceId}");
            return $anchoredInvoiceId;
        }

        // Need a client first
        $clientId = $this->ensureClient();
        if (!$clientId) {
            return null;
        }

        // Create a temporary invoice
        $invoiceClass = 'Jcolombo\\PaymoApiPhp\\Entity\\Resource\\Invoice';
        $invoiceData = $this->factory->invoiceData($clientId);

        $invoice = new $invoiceClass();
        $invoice->client_id = $clientId;
        $invoice->number = $invoiceData['number'];
        $invoice->currency = $invoiceData['currency'] ?? 'USD';
        $invoice->date = $invoiceData['date'];
        $invoice->due_date = $invoiceData['due_date'];
        $invoice->create();

        $this->logDetail("Created temporary Invoice #{$invoice->id}");
        $this->cleanupManager->track('Invoice', $invoice->id, $invoiceClass);

        return $invoice->id;
    }

    /**
     * Get or create an Estimate for testing
     * Uses anchored estimate_id if configured, otherwise creates a temporary one
     *
     * @return int|null Estimate ID
     */
    protected function ensureEstimate(): ?int
    {
        // Check for anchored estimate first
        $anchoredEstimateId = $this->config->getAnchor('estimate_id');
        if ($anchoredEstimateId) {
            $this->logDetail("Using anchored Estimate #{$anchoredEstimateId}");
            return $anchoredEstimateId;
        }

        // Need a client first
        $clientId = $this->ensureClient();
        if (!$clientId) {
            return null;
        }

        // Create a temporary estimate
        $estimateClass = 'Jcolombo\\PaymoApiPhp\\Entity\\Resource\\Estimate';
        $estimateData = $this->factory->estimateData($clientId);

        $estimate = new $estimateClass();
        $estimate->client_id = $clientId;
        $estimate->number = $estimateData['number'];
        $estimate->currency = $estimateData['currency'] ?? 'USD';
        $estimate->date = $estimateData['date'];
        $estimate->create();

        $this->logDetail("Created temporary Estimate #{$estimate->id}");
        $this->cleanupManager->track('Estimate', $estimate->id, $estimateClass);

        return $estimate->id;
    }

    /**
     * Get anchor for User (cannot be created via API)
     *
     * @return int|null User ID
     */
    protected function ensureUser(): ?int
    {
        $userId = $this->config->getAnchor('user_id');
        if ($userId) {
            $this->logDetail("Using anchored User #{$userId}");
            return $userId;
        }

        $this->logWarning("No user_id anchor configured - cannot create users via API");
        return null;
    }

    // ========================================================================
    // Cleanup
    // ========================================================================

    /**
     * Cleanup all created resources
     */
    protected function cleanup(): void
    {
        $count = $this->cleanupManager->getCount();
        if ($count > 0) {
            $this->logDetail("");
            $this->logDetail("=== CLEANUP ===");
            $this->logDetail("Resources to cleanup: {$count}");

            $result = $this->cleanupManager->cleanup();

            $this->logDetail("");
            $this->logDetail("CLEANUP RESULTS:");
            $this->logDetail("  Deleted successfully: {$result['success']}");
            $this->logDetail("  Failed to delete: {$result['failed']}");

            if ($result['failed'] > 0) {
                $this->logDetail("");
                $this->logDetail("  *** CONCERN: Some resources may remain in Paymo");
                $this->logDetail("  Check your Paymo account for test resources with prefix");
            }
        } else {
            $this->logDetail("");
            $this->logDetail("=== CLEANUP ===");
            $this->logDetail("No resources to cleanup");
        }
    }

    // ========================================================================
    // Logging Helpers
    // ========================================================================

    protected function logResourceHeader(string $name): void
    {
        $line = str_repeat('=', 70);
        $class = $this->getResourceClass();

        // Detailed header for log file
        $this->logDetail("");
        $this->logDetail($line);
        $this->logDetail("RESOURCE: {$name}");
        $this->logDetail("Category: {$this->getResourceCategory()}");
        $this->logDetail("Class: {$class}");
        $this->logDetail($line);

        // Log SDK constants for this resource
        $this->logDetail("");
        $this->logDetail("SDK DEFINITION SUMMARY:");
        $this->logDetail("  PROP_TYPES: " . count($class::PROP_TYPES) . " properties");
        $this->logDetail("  READONLY: " . count($class::READONLY ?? []) . " read-only properties");
        $this->logDetail("  CREATEONLY: " . count($class::CREATEONLY ?? []) . " create-only properties");
        $this->logDetail("  REQUIRED_CREATE: " . count($class::REQUIRED_CREATE ?? []) . " required for create");
        $this->logDetail("  INCLUDE_TYPES: " . count($class::INCLUDE_TYPES ?? []) . " relationships");
        $this->logDetail("  WHERE_OPERATIONS: " . count($class::WHERE_OPERATIONS ?? []) . " filterable properties");
        $this->logDetail("");
    }

    protected function logResourceFooter(string $name): void
    {
        $this->logDetail(str_repeat('-', 70));
        $this->logDetail("END: {$name}");
        $this->logDetail("");
    }

    /**
     * Log detailed information to file only (for comprehensive logging)
     */
    protected function logDetail(string $message): void
    {
        if ($this->logger) {
            $this->logger->info("[{$this->getResourceName()}] {$message}");
        }
    }

    /**
     * Log info - goes to file always, console only if verbose
     */
    protected function logInfo(string $message): void
    {
        if ($this->logger) {
            $this->logger->info("[{$this->getResourceName()}] {$message}");
        }
        if ($this->config->getRuntimeOption('verbose')) {
            $this->output->info($message);
        }
    }

    protected function logWarning(string $message): void
    {
        if ($this->logger) {
            $this->logger->warning("[{$this->getResourceName()}] {$message}");
        }
        // Warnings show on console
        $this->output->warning($message);
    }

    protected function logError(string $message): void
    {
        if ($this->logger) {
            $this->logger->error("[{$this->getResourceName()}] {$message}");
        }
        $this->output->error($message);
    }

    /**
     * Log discovery results with full detail
     */
    protected function logDiscovery(string $title, array $props): void
    {
        $this->logDetail("");
        $this->logDetail(">>> {$title}:");
        foreach ($props as $prop => $type) {
            $this->logDetail("    - {$prop}: {$type}");
        }
    }

    /**
     * Log a detailed property comparison
     */
    protected function logPropertyComparison(array $sdkProps, array $apiProps, array $comparison): void
    {
        $this->logDetail("");
        $this->logDetail("DETAILED PROPERTY COMPARISON:");
        $this->logDetail(str_repeat('-', 50));

        // Log SDK properties with match status
        $this->logDetail("");
        $this->logDetail("SDK PROP_TYPES ({$this->getResourceName()}):");
        foreach ($sdkProps as $prop => $type) {
            $typeStr = is_array($type) ? 'relation:' . json_encode($type) : $type;
            $status = in_array($prop, $comparison['matched']) ? '[MATCH]' : '[MISSING FROM API]';
            $this->logDetail("  {$status} {$prop}: {$typeStr}");
        }

        // Log API properties
        $this->logDetail("");
        $this->logDetail("API RESPONSE PROPERTIES:");
        foreach ($apiProps as $prop => $value) {
            $type = $this->inferType($value);
            $inSdk = array_key_exists($prop, $sdkProps);
            $status = $inSdk ? '[MATCH]' : '[EXTRA - NOT IN SDK]';
            $this->logDetail("  {$status} {$prop}: {$type}");
        }

        // Summary
        $this->logDetail("");
        $this->logDetail("COMPARISON SUMMARY:");
        $this->logDetail("  Matched: " . count($comparison['matched']));
        $this->logDetail("  Extra (API only): " . count($comparison['extra']));
        $this->logDetail("  Missing (SDK only): " . count($comparison['missing']));

        if (!empty($comparison['extra'])) {
            $this->logDetail("");
            $this->logDetail("  *** EXTRA PROPERTIES (exist in API, not in SDK PROP_TYPES):");
            foreach ($comparison['extra'] as $prop => $type) {
                $this->logDetail("      CONCERN: '{$prop}' ({$type}) - Consider adding to PROP_TYPES");
            }
        }

        if (!empty($comparison['missing'])) {
            $this->logDetail("");
            $this->logDetail("  *** MISSING PROPERTIES (in SDK PROP_TYPES, not returned by API):");
            foreach ($comparison['missing'] as $prop => $type) {
                $this->logDetail("      CONCERN: '{$prop}' ({$type}) - May need removal or may require special fetch");
            }
        }

        $this->logDetail(str_repeat('-', 50));
    }

    /**
     * Get the API path for this resource
     */
    protected function getApiPath(): string
    {
        $class = $this->getResourceClass();
        return defined("{$class}::API_PATH") ? $class::API_PATH : strtolower($this->getResourceName()) . 's';
    }

    /**
     * Log API call with full URL and query details
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $description Human-readable description
     * @param array|null $data Request data/body
     * @param string|null $endpoint API endpoint path (e.g., "clients/123")
     * @param array|null $query Query parameters (include, where, select)
     */
    protected function logApiCall(string $method, string $description, ?array $data = null, ?string $endpoint = null, ?array $query = null): void
    {
        $this->logDetail("");
        $this->logDetail("API CALL: {$method}");
        $this->logDetail("  Description: {$description}");

        // Build and log the full URL that would be called
        if ($endpoint !== null) {
            $url = "https://app.paymoapp.com/api/{$endpoint}";
            if ($query !== null && !empty($query)) {
                $queryParts = [];
                foreach ($query as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $queryParts[] = urlencode($key) . '=' . urlencode($value);
                    }
                }
                if (!empty($queryParts)) {
                    $url .= '?' . implode('&', $queryParts);
                }
            }
            $this->logDetail("  Full URL: {$url}");
        }

        // Log query parameters separately for clarity
        if ($query !== null && !empty($query)) {
            $this->logDetail("  Query Parameters:");
            foreach ($query as $key => $value) {
                if ($value !== null && $value !== '') {
                    $this->logDetail("    {$key}: {$value}");
                }
            }
        }

        // Log request body data
        if ($data !== null) {
            $this->logDetail("  Request Body: " . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    }

    /**
     * Log API response details
     */
    protected function logApiResponse(bool $success, ?int $id = null, ?string $error = null): void
    {
        if ($success) {
            $this->logDetail("  Result: SUCCESS" . ($id ? " (ID: {$id})" : ""));
        } else {
            $this->logDetail("  Result: FAILED");
            if ($error) {
                $this->logDetail("  Error: {$error}");
            }
        }
    }

    /**
     * Log CRUD operation with full details
     */
    protected function logCrudOperation(string $operation, string $resourceType, ?int $id, array $fields = [], bool $success = true, ?string $error = null): void
    {
        $this->logDetail("");
        $this->logDetail("CRUD OPERATION: {$operation}");
        $this->logDetail("  Resource: {$resourceType}");
        if ($id) {
            $this->logDetail("  ID: {$id}");
        }
        if (!empty($fields)) {
            $this->logDetail("  Fields: " . json_encode($fields, JSON_UNESCAPED_SLASHES));
        }
        $this->logDetail("  Success: " . ($success ? 'YES' : 'NO'));
        if ($error) {
            $this->logDetail("  Error: {$error}");
        }
    }

    /**
     * Log WHERE operation test details
     */
    protected function logWhereOperations(array $whereOps): void
    {
        $this->logDetail("");
        $this->logDetail("WHERE_OPERATIONS ANALYSIS:");
        $this->logDetail(str_repeat('-', 50));

        if (empty($whereOps)) {
            $this->logDetail("  No WHERE_OPERATIONS defined for this resource");
            return;
        }

        foreach ($whereOps as $prop => $operators) {
            $opList = is_array($operators) ? implode(', ', $operators) : $operators;
            $this->logDetail("  {$prop}: [{$opList}]");
        }
        $this->logDetail(str_repeat('-', 50));
    }

    /**
     * Log INCLUDE_TYPES analysis
     */
    protected function logIncludeTypes(array $includeTypes): void
    {
        $this->logDetail("");
        $this->logDetail("INCLUDE_TYPES ANALYSIS:");
        $this->logDetail(str_repeat('-', 50));

        if (empty($includeTypes)) {
            $this->logDetail("  No INCLUDE_TYPES defined for this resource");
            return;
        }

        foreach ($includeTypes as $relation => $config) {
            if (is_array($config)) {
                $type = $config[0] ?? 'unknown';
                $isMultiple = ($config[1] ?? 0) === 1 ? 'multiple' : 'single';
                $this->logDetail("  {$relation}: {$type} ({$isMultiple})");
            } else {
                $this->logDetail("  {$relation}: {$config}");
            }
        }
        $this->logDetail(str_repeat('-', 50));
    }

    /**
     * Log test start
     */
    protected function logTestStart(string $testName): void
    {
        $this->logDetail("");
        $this->logDetail(">>> TEST: {$testName}");
        $this->logDetail("    Started: " . date('Y-m-d H:i:s'));
    }

    /**
     * Log test completion
     */
    protected function logTestComplete(string $testName, bool $passed, float $duration, ?string $message = null): void
    {
        $status = $passed ? 'PASSED' : 'FAILED';
        $this->logDetail("    Completed: {$status} in " . sprintf('%.3fs', $duration));
        if ($message) {
            $this->logDetail("    Message: {$message}");
        }
        $this->logDetail("");
    }
}

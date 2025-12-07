<?php
/**
 * Paymo API PHP SDK - Resource Dependency Analyzer
 *
 * Analyzes resource dependencies to determine what needs to be created
 * or anchored before testing can proceed.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests;

class DependencyAnalyzer
{
    /**
     * Resource dependency tree
     * Maps resource => [required parent resources]
     */
    private const DEPENDENCIES = [
        // Top-level resources (no dependencies except Client for some)
        'Client' => [],
        'Company' => [],
        'Session' => [],
        'Workflow' => [],
        'User' => [],
        'ProjectTemplate' => [],
        'InvoiceTemplate' => [],
        'EstimateTemplate' => [],
        'ProjectStatus' => [],
        'Webhook' => [],

        // Project-level (requires Client)
        'Project' => ['Client'],

        // Project children (require Project)
        'Tasklist' => ['Project'],
        'Milestone' => ['Project'],
        'Discussion' => ['Project'],
        'File' => ['Project'],

        // Task-level (requires Tasklist which requires Project)
        'Task' => ['Tasklist', 'Project'],

        // Task children
        'Subtask' => ['Task', 'Tasklist', 'Project'],
        'TimeEntry' => ['Task', 'Tasklist', 'Project'],
        'Comment' => ['Task', 'Tasklist', 'Project'],  // Can also be on Discussion/File

        // Booking requires Project and User
        'Booking' => ['Project', 'User'],

        // Financial - Invoice chain
        'Invoice' => ['Client'],
        'InvoiceItem' => ['Invoice', 'Client'],

        // Financial - Estimate chain
        'Estimate' => ['Client'],
        'EstimateItem' => ['Estimate', 'Client'],

        // Expense requires Project
        'Expense' => ['Project'],
    ];

    /**
     * Maps dependencies to anchor keys
     */
    private const ANCHOR_MAP = [
        'Client' => 'client_id',
        'Project' => 'project_id',
        'User' => 'user_id',
        'Tasklist' => 'tasklist_id',
        'Task' => 'task_id',
        'Invoice' => 'invoice_id',
        'Estimate' => 'estimate_id',
        'Workflow' => 'workflow_id',
        'ProjectTemplate' => 'project_template_id',
        'InvoiceTemplate' => 'invoice_template_id',
        'EstimateTemplate' => 'estimate_template_id',
        'ProjectStatus' => 'project_status_id',
    ];

    /**
     * Resources that can be safely created and deleted
     */
    private const SAFE_TO_CREATE = [
        'Client', 'Project', 'Tasklist', 'Task', 'Subtask', 'TimeEntry',
        'Milestone', 'Discussion', 'Comment', 'File', 'Booking',
        'Invoice', 'InvoiceItem', 'Estimate', 'EstimateItem', 'Expense', 'Webhook'
    ];

    /**
     * @var TestConfig
     */
    private TestConfig $config;

    /**
     * Constructor
     */
    public function __construct(TestConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Analyze what resources will be tested and their dependencies
     *
     * @param array $resources Resources to test (empty = all)
     * @return array Analysis results
     */
    public function analyze(array $resources = []): array
    {
        // Determine which resources will actually be tested
        $toTest = $this->getResourcesToTest($resources);

        // Analyze dependencies for each resource
        $dependencies = [];
        $willCreate = [];
        $willUseAnchors = [];
        $missingAnchors = [];
        $cannotTest = [];

        foreach ($toTest as $resource) {
            $resourceDeps = $this->getResourceDependencies($resource);
            $dependencies[$resource] = $resourceDeps;

            foreach ($resourceDeps as $dep) {
                $anchorKey = self::ANCHOR_MAP[$dep] ?? null;
                $hasAnchor = $anchorKey && $this->config->getAnchor($anchorKey);

                if ($hasAnchor) {
                    $willUseAnchors[$dep] = $this->config->getAnchor($anchorKey);
                } elseif (in_array($dep, self::SAFE_TO_CREATE)) {
                    $willCreate[$dep] = true;
                } else {
                    $missingAnchors[$dep] = $anchorKey;
                    if (!isset($cannotTest[$resource])) {
                        $cannotTest[$resource] = [];
                    }
                    $cannotTest[$resource][] = $dep;
                }
            }
        }

        return [
            'resources_to_test' => $toTest,
            'dependencies' => $dependencies,
            'will_use_anchors' => $willUseAnchors,
            'will_create' => array_keys($willCreate),
            'missing_anchors' => $missingAnchors,
            'cannot_test' => $cannotTest,
        ];
    }

    /**
     * Get the list of resources that will be tested
     */
    private function getResourcesToTest(array $requested): array
    {
        $allResources = array_keys(self::DEPENDENCIES);

        // If specific resources requested, use those
        if (!empty($requested) && !in_array('all', $requested)) {
            return array_intersect($requested, $allResources);
        }

        // Otherwise apply config filtering
        $only = $this->config->getOnlyResources();
        $skip = $this->config->getSkippedResources();

        if (!empty($only)) {
            $allResources = array_intersect($allResources, $only);
        }

        if (!empty($skip)) {
            $allResources = array_diff($allResources, $skip);
        }

        return array_values($allResources);
    }

    /**
     * Get all dependencies for a resource (recursive)
     */
    private function getResourceDependencies(string $resource): array
    {
        $deps = self::DEPENDENCIES[$resource] ?? [];
        $allDeps = [];

        foreach ($deps as $dep) {
            $allDeps[] = $dep;
            // Recursively get dependencies of dependencies
            $subDeps = $this->getResourceDependencies($dep);
            foreach ($subDeps as $subDep) {
                if (!in_array($subDep, $allDeps)) {
                    $allDeps[] = $subDep;
                }
            }
        }

        return array_unique($allDeps);
    }

    /**
     * Get a human-readable summary of what will happen
     */
    public function getSummary(array $analysis): array
    {
        $summary = [];

        // Resources being tested
        $summary['testing'] = $analysis['resources_to_test'];

        // What anchors will be used
        $summary['using_anchors'] = [];
        foreach ($analysis['will_use_anchors'] as $resource => $id) {
            $summary['using_anchors'][] = "{$resource} #{$id}";
        }

        // What will be created temporarily
        $summary['will_create'] = $analysis['will_create'];

        // What cannot be tested due to missing anchors
        $summary['cannot_test'] = [];
        foreach ($analysis['cannot_test'] as $resource => $missing) {
            $summary['cannot_test'][] = "{$resource} (needs: " . implode(', ', $missing) . ")";
        }

        // Suggested anchor additions
        $summary['suggested_anchors'] = [];
        foreach ($analysis['missing_anchors'] as $resource => $anchorKey) {
            if ($anchorKey) {
                $summary['suggested_anchors'][$anchorKey] = $resource;
            }
        }

        return $summary;
    }

    /**
     * Check if a resource can be tested with current config
     */
    public function canTest(string $resource): bool
    {
        $deps = $this->getResourceDependencies($resource);

        foreach ($deps as $dep) {
            $anchorKey = self::ANCHOR_MAP[$dep] ?? null;
            $hasAnchor = $anchorKey && $this->config->getAnchor($anchorKey);
            $canCreate = in_array($dep, self::SAFE_TO_CREATE);

            if (!$hasAnchor && !$canCreate) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the anchor key for a resource
     */
    public static function getAnchorKey(string $resource): ?string
    {
        return self::ANCHOR_MAP[$resource] ?? null;
    }

    /**
     * Check if a resource can be safely created/deleted
     */
    public static function isSafeToCreate(string $resource): bool
    {
        return in_array($resource, self::SAFE_TO_CREATE);
    }

    /**
     * Get direct dependencies for a resource
     */
    public static function getDirectDependencies(string $resource): array
    {
        return self::DEPENDENCIES[$resource] ?? [];
    }
}

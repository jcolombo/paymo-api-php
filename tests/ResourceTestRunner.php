<?php
/**
 * Paymo API PHP SDK - Resource Test Runner
 *
 * Orchestrates running tests in a RESOURCE-CENTRIC manner.
 * For each resource (filtered by only/skip config):
 *   - Runs all test types (property discovery, CRUD, where ops, includes)
 *   - Logs all findings for that resource together
 *   - Cleans up before moving to next resource
 *
 * @package    Jcolombo\PaymoApiPhp\Tests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests;

use Jcolombo\PaymoApiPhp\Tests\Fixtures\CleanupManager;
use Throwable;

class ResourceTestRunner
{
    /**
     * @var TestConfig Test configuration
     */
    private TestConfig $config;

    /**
     * @var TestOutput Output handler
     */
    private TestOutput $output;

    /**
     * @var TestResult Result tracker
     */
    private TestResult $results;

    /**
     * @var TestLogger|null Logger
     */
    private ?TestLogger $logger;

    /**
     * @var CleanupManager Global cleanup manager for emergency cleanup
     */
    private CleanupManager $globalCleanupManager;

    /**
     * Registry of all resource test classes
     * Maps resource name => test class
     */
    private array $resourceRegistry;

    /**
     * Constructor
     */
    public function __construct(TestConfig $config, TestOutput $output, ?TestLogger $logger = null)
    {
        $this->config = $config;
        $this->output = $output;
        $this->logger = $logger;
        $this->results = new TestResult();
        $this->globalCleanupManager = new CleanupManager(
            $output,
            !$config->getRuntimeOption('dry_run')
        );
        $this->resourceRegistry = $this->buildResourceRegistry();
    }

    /**
     * Build the registry of all available resource tests
     */
    private function buildResourceRegistry(): array
    {
        return [
            // Safe CRUD Resources (can create and delete)
            'Client' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\ClientResourceTest',
                'category' => 'safe_crud',
            ],
            'Project' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\ProjectResourceTest',
                'category' => 'safe_crud',
            ],
            'Tasklist' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\TasklistResourceTest',
                'category' => 'safe_crud',
            ],
            'Task' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\TaskResourceTest',
                'category' => 'safe_crud',
            ],
            'Subtask' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\SubtaskResourceTest',
                'category' => 'safe_crud',
            ],
            'TimeEntry' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\TimeEntryResourceTest',
                'category' => 'safe_crud',
            ],
            'Milestone' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\MilestoneResourceTest',
                'category' => 'safe_crud',
            ],
            'Discussion' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\DiscussionResourceTest',
                'category' => 'safe_crud',
            ],
            'Comment' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\CommentResourceTest',
                'category' => 'safe_crud',
            ],
            'File' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\FileResourceTest',
                'category' => 'safe_crud',
            ],
            'Booking' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\BookingResourceTest',
                'category' => 'safe_crud',
            ],
            'Invoice' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\InvoiceResourceTest',
                'category' => 'safe_crud',
            ],
            'InvoiceItem' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\InvoiceItemResourceTest',
                'category' => 'safe_crud',
            ],
            'Estimate' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\EstimateResourceTest',
                'category' => 'safe_crud',
            ],
            'EstimateItem' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\EstimateItemResourceTest',
                'category' => 'safe_crud',
            ],
            'Expense' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\ExpenseResourceTest',
                'category' => 'safe_crud',
            ],
            'Webhook' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\WebhookResourceTest',
                'category' => 'safe_crud',
            ],

            // Read-Only Resources
            'Company' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\CompanyResourceTest',
                'category' => 'read_only',
            ],
            'Session' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\SessionResourceTest',
                'category' => 'read_only',
            ],
            'Workflow' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\WorkflowResourceTest',
                'category' => 'read_only',
            ],

            // Configured Anchor Resources (require pre-existing IDs)
            'User' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\UserResourceTest',
                'category' => 'configured_anchor',
                'anchor' => 'user_id',
            ],
            'ProjectTemplate' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\ProjectTemplateResourceTest',
                'category' => 'configured_anchor',
                'anchor' => 'project_template_id',
            ],
            'InvoiceTemplate' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\InvoiceTemplateResourceTest',
                'category' => 'configured_anchor',
                'anchor' => 'invoice_template_id',
            ],
            'EstimateTemplate' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\EstimateTemplateResourceTest',
                'category' => 'configured_anchor',
                'anchor' => 'estimate_template_id',
            ],
            'ProjectStatus' => [
                'class' => 'Jcolombo\\PaymoApiPhp\\Tests\\ResourceTests\\ProjectStatusResourceTest',
                'category' => 'configured_anchor',
                'anchor' => 'project_status_id',
            ],
        ];
    }

    /**
     * Run tests for all configured resources
     */
    public function runAllTests(): void
    {
        $this->output->banner();

        $testedCount = 0;
        $skippedCount = 0;

        foreach ($this->resourceRegistry as $resourceName => $info) {
            // Check if this resource should be tested per config
            if (!$this->config->shouldTestResource($resourceName)) {
                $this->logInfo("SKIPPING {$resourceName}: Excluded by configuration");
                $skippedCount++;
                continue;
            }

            // Check anchor requirements for configured_anchor resources
            if ($info['category'] === 'configured_anchor') {
                $anchorKey = $info['anchor'] ?? null;
                if ($anchorKey && !$this->config->getAnchor($anchorKey)) {
                    $this->logInfo("SKIPPING {$resourceName}: No {$anchorKey} configured");
                    $this->results->recordSkip($resourceName, "No {$anchorKey} configured");
                    $skippedCount++;
                    continue;
                }
            }

            // Check if safe_crud resources have required client_id
            if ($info['category'] === 'safe_crud' && !$this->config->getAnchor('client_id')) {
                $this->logInfo("SKIPPING {$resourceName}: No client_id configured (required for CRUD)");
                $this->results->recordSkip($resourceName, "No client_id configured");
                $skippedCount++;
                continue;
            }

            $this->runResourceTests($resourceName, $info);
            $testedCount++;

            // Check for stop on failure
            if ($this->config->getRuntimeOption('stop_on_failure') && $this->results->hasFailures()) {
                $this->output->warning("Stopping due to failure");
                break;
            }
        }

        $this->logInfo("");
        $this->logInfo("Resources tested: {$testedCount}");
        $this->logInfo("Resources skipped: {$skippedCount}");

        $this->results->finish();
    }

    /**
     * Run tests for specific resources
     *
     * When resources are explicitly passed on command line, they bypass
     * the config's 'only' and 'skip' settings - user's explicit request wins.
     *
     * @param array $resourceNames Resource names to test
     */
    public function runResources(array $resourceNames): void
    {
        $this->output->banner();

        foreach ($resourceNames as $resourceName) {
            if (!isset($this->resourceRegistry[$resourceName])) {
                $this->output->warning("Unknown resource: {$resourceName}");
                continue;
            }

            // Command line resources bypass config filtering
            // They were explicitly requested by the user
            $info = $this->resourceRegistry[$resourceName];
            $this->runResourceTests($resourceName, $info);

            if ($this->config->getRuntimeOption('stop_on_failure') && $this->results->hasFailures()) {
                $this->output->warning("Stopping due to failure");
                break;
            }
        }

        $this->results->finish();
    }

    /**
     * Run all tests for a single resource
     *
     * @param string $resourceName Resource name
     * @param array $info Resource info from registry
     */
    private function runResourceTests(string $resourceName, array $info): void
    {
        $testClass = $info['class'];

        // Check if class exists
        if (!class_exists($testClass)) {
            $this->results->recordSkip($resourceName, "Test class not implemented");
            $this->output->testSkipped($resourceName, "Test class not implemented");
            return;
        }

        $this->output->groupHeader($resourceName, "Category: {$info['category']}");

        try {
            /** @var ResourceTest $test */
            $test = new $testClass(
                $this->config,
                $this->output,
                $this->results,
                $this->logger
            );

            $test->runAllTests();

        } catch (Throwable $e) {
            $this->results->recordFailure(
                "{$resourceName}::setup",
                0,
                "Test setup failed: " . $e->getMessage()
            );
            $this->output->error("Test setup failed for {$resourceName}: " . $e->getMessage());
        }

        $this->output->line();
    }

    /**
     * Get the test results
     */
    public function getResults(): TestResult
    {
        return $this->results;
    }

    /**
     * Get available resources
     */
    public function getAvailableResources(): array
    {
        return array_keys($this->resourceRegistry);
    }

    /**
     * Get resources by category
     */
    public function getResourcesByCategory(string $category): array
    {
        return array_filter(
            $this->resourceRegistry,
            fn($info) => $info['category'] === $category
        );
    }

    /**
     * Perform emergency cleanup
     */
    public function emergencyCleanup(): void
    {
        if ($this->globalCleanupManager->getCount() === 0) {
            return;
        }

        $this->output->warning("Performing emergency cleanup...");
        $result = $this->globalCleanupManager->cleanup();

        $this->output->cleanup(
            sprintf("Emergency cleanup: %d deleted, %d failed",
                $result['success'],
                $result['failed']
            ),
            $result['failed'] === 0
        );
    }

    /**
     * Log info message
     */
    private function logInfo(string $message): void
    {
        if ($this->logger) {
            $this->logger->info("[ResourceTestRunner] {$message}");
        }
        if ($this->config->getRuntimeOption('verbose')) {
            $this->output->info($message);
        }
    }
}

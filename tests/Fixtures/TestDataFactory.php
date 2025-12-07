<?php
/**
 * Paymo API PHP SDK - Test Data Factory
 *
 * Generates test data with appropriate prefixes and defaults for
 * creating test resources safely.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\Fixtures
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\Fixtures;

use Jcolombo\PaymoApiPhp\Tests\TestConfig;

class TestDataFactory
{
    /**
     * @var TestConfig Test configuration
     */
    private TestConfig $config;

    /**
     * @var int Counter for unique names
     */
    private static int $counter = 0;

    /**
     * Constructor
     *
     * @param TestConfig $config Test configuration
     */
    public function __construct(TestConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Generate a unique prefixed name
     *
     * @param string $baseName Base name
     * @return string Unique prefixed name
     */
    public function uniqueName(string $baseName): string
    {
        self::$counter++;
        $timestamp = date('His');
        $prefix = $this->config->getPrefix();
        return "{$prefix}-{$timestamp}-" . self::$counter . " {$baseName}";
    }

    /**
     * Generate client test data
     *
     * @param array $overrides Override default values
     * @return array Client data
     */
    public function clientData(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->uniqueName('Test Client'),
            'email' => 'test-' . uniqid() . '@example.com',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'country' => 'United States',
            'phone' => '555-0100',
            'fiscal_information' => 'Test fiscal info',
        ], $overrides);
    }

    /**
     * Generate project test data
     *
     * @param int $clientId Client ID
     * @param array $overrides Override default values
     * @return array Project data
     */
    public function projectData(int $clientId, array $overrides = []): array
    {
        return array_merge([
            'name' => $this->uniqueName('Test Project'),
            'client_id' => $clientId,
            'description' => 'Created by test suite',
            'billable' => false,
            'active' => true,
        ], $overrides);
    }

    /**
     * Generate tasklist test data
     *
     * @param int $projectId Project ID
     * @param array $overrides Override default values
     * @return array Tasklist data
     */
    public function tasklistData(int $projectId, array $overrides = []): array
    {
        return array_merge([
            'name' => $this->uniqueName('Test Tasklist'),
            'project_id' => $projectId,
        ], $overrides);
    }

    /**
     * Generate task test data
     *
     * @param int $tasklistId Tasklist ID
     * @param array $overrides Override default values
     * @return array Task data
     */
    public function taskData(int $tasklistId, array $overrides = []): array
    {
        return array_merge([
            'name' => $this->uniqueName('Test Task'),
            'tasklist_id' => $tasklistId,
            'description' => 'Test task description',
            'complete' => false,
        ], $overrides);
    }

    /**
     * Generate subtask test data
     *
     * @param int $taskId Task ID
     * @param array $overrides Override default values
     * @return array Subtask data
     */
    public function subtaskData(int $taskId, array $overrides = []): array
    {
        return array_merge([
            'name' => $this->uniqueName('Test Subtask'),
            'task_id' => $taskId,
            'complete' => false,
        ], $overrides);
    }

    /**
     * Generate time entry test data
     *
     * @param int $taskId Task ID
     * @param int $userId User ID
     * @param array $overrides Override default values
     * @return array Time entry data
     */
    public function timeEntryData(int $taskId, int $userId, array $overrides = []): array
    {
        return array_merge([
            'task_id' => $taskId,
            'user_id' => $userId,
            'start_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'end_time' => date('Y-m-d H:i:s'),
            'description' => 'Test time entry',
        ], $overrides);
    }

    /**
     * Generate milestone test data
     *
     * @param int $projectId Project ID
     * @param array $overrides Override default values
     * @return array Milestone data
     */
    public function milestoneData(int $projectId, array $overrides = []): array
    {
        return array_merge([
            'name' => $this->uniqueName('Test Milestone'),
            'project_id' => $projectId,
            'due_date' => date('Y-m-d', strtotime('+30 days')),
        ], $overrides);
    }

    /**
     * Generate discussion test data
     *
     * @param int $projectId Project ID
     * @param array $overrides Override default values
     * @return array Discussion data
     */
    public function discussionData(int $projectId, array $overrides = []): array
    {
        return array_merge([
            'name' => $this->uniqueName('Test Discussion'),
            'project_id' => $projectId,
            'content' => 'Test discussion content',
        ], $overrides);
    }

    /**
     * Generate comment test data
     *
     * @param int|null $taskId Task ID (optional)
     * @param int|null $discussionId Discussion ID (optional)
     * @param array $overrides Override default values
     * @return array Comment data
     */
    public function commentData(?int $taskId = null, ?int $discussionId = null, array $overrides = []): array
    {
        $data = [
            'content' => 'Test comment: ' . $this->uniqueName(''),
        ];

        if ($taskId !== null) {
            $data['task_id'] = $taskId;
        } elseif ($discussionId !== null) {
            $data['discussion_id'] = $discussionId;
        }

        return array_merge($data, $overrides);
    }

    /**
     * Generate booking test data
     *
     * @param int $projectId Project ID
     * @param int $userId User ID
     * @param array $overrides Override default values
     * @return array Booking data
     */
    public function bookingData(int $projectId, int $userId, array $overrides = []): array
    {
        $startDate = date('Y-m-d', strtotime('+7 days'));
        return array_merge([
            'project_id' => $projectId,
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => date('Y-m-d', strtotime($startDate . ' +5 days')),
            'hours_per_day' => 8,
        ], $overrides);
    }

    /**
     * Generate invoice test data
     *
     * @param int $clientId Client ID
     * @param array $overrides Override default values
     * @return array Invoice data
     */
    public function invoiceData(int $clientId, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $clientId,
            'number' => 'TEST-' . date('Ymd') . '-' . self::$counter,
            'currency' => 'USD',
            'date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'status' => 'draft',
        ], $overrides);
    }

    /**
     * Generate invoice item test data
     *
     * @param int $invoiceId Invoice ID
     * @param array $overrides Override default values
     * @return array Invoice item data
     */
    public function invoiceItemData(int $invoiceId, array $overrides = []): array
    {
        return array_merge([
            'invoice_id' => $invoiceId,
            'item' => $this->uniqueName('Test Item'),
            'description' => 'Test invoice item',
            'price_unit' => 100.00,
            'quantity' => 1,
        ], $overrides);
    }

    /**
     * Generate estimate test data
     *
     * @param int $clientId Client ID
     * @param array $overrides Override default values
     * @return array Estimate data
     */
    public function estimateData(int $clientId, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $clientId,
            'number' => 'EST-' . date('Ymd') . '-' . self::$counter,
            'currency' => 'USD',
            'date' => date('Y-m-d'),
            'status' => 'draft',
        ], $overrides);
    }

    /**
     * Generate expense test data
     *
     * @param int $projectId Project ID
     * @param array $overrides Override default values
     * @return array Expense data
     */
    public function expenseData(int $projectId, array $overrides = []): array
    {
        return array_merge([
            'project_id' => $projectId,
            'date' => date('Y-m-d'),
            'amount' => 50.00,
            'notes' => $this->uniqueName('Test Expense'),
        ], $overrides);
    }

    /**
     * Generate workflow status test data
     *
     * @param int $workflowId Workflow ID
     * @param array $overrides Override default values
     * @return array Workflow status data
     */
    public function workflowStatusData(int $workflowId, array $overrides = []): array
    {
        return array_merge([
            'workflow_id' => $workflowId,
            'name' => $this->uniqueName('Test Status'),
            'color' => '#' . substr(md5(uniqid()), 0, 6), // Random color
            'seq' => 999, // Put at end
        ], $overrides);
    }

    /**
     * Generate recurring profile test data
     *
     * @param int $clientId Client ID
     * @param array $overrides Override default values
     * @return array Recurring profile data
     */
    public function recurringProfileData(int $clientId, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $clientId,
            'name' => $this->uniqueName('Test Recurring'),
            'interval_value' => 1,
            'interval_unit' => 'month',
            'start_date' => date('Y-m-d'),
            'status' => 'draft',
        ], $overrides);
    }

    /**
     * Generate task recurring profile test data
     *
     * @param int $projectId Project ID
     * @param int $tasklistId Tasklist ID
     * @param array $overrides Override default values
     * @return array Task recurring profile data
     */
    public function taskRecurringProfileData(int $projectId, int $tasklistId, array $overrides = []): array
    {
        return array_merge([
            'name' => $this->uniqueName('Test Recurring Task'),
            'project_id' => $projectId,
            'tasklist_id' => $tasklistId,
            'frequency' => 'weekly',
            'interval' => 1,
            'recurring_start_date' => date('Y-m-d'),
            'active' => false, // Don't actually generate tasks
        ], $overrides);
    }

    /**
     * Generate webhook test data
     *
     * @param array $overrides Override default values
     * @return array Webhook data
     */
    public function webhookData(array $overrides = []): array
    {
        return array_merge([
            'target_url' => 'https://httpbin.org/post',
            'events' => ['task.create'],
        ], $overrides);
    }

    /**
     * Generate client contact test data
     *
     * @param int $clientId Client ID
     * @param array $overrides Override default values
     * @return array Client contact data
     */
    public function clientContactData(int $clientId, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $clientId,
            'name' => $this->uniqueName('Test Contact'),
            'email' => 'contact-' . uniqid() . '@example.com',
            'phone' => '555-0' . rand(100, 999),
        ], $overrides);
    }
}

<?php
/**
 * Paymo API PHP SDK - Test Logger
 *
 * Handles logging test execution details to file for debugging and diagnosis.
 * Captures success, failure, warnings, and detailed error information.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests;

class TestLogger
{
    /**
     * Log levels in order of severity
     */
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    /**
     * Level priority map
     */
    private const LEVEL_PRIORITY = [
        self::LEVEL_DEBUG => 0,
        self::LEVEL_INFO => 1,
        self::LEVEL_WARNING => 2,
        self::LEVEL_ERROR => 3,
    ];

    /**
     * @var string Path to log file
     */
    private string $logPath;

    /**
     * @var string Minimum log level
     */
    private string $level;

    /**
     * @var bool Include timestamps in log entries
     */
    private bool $includeTimestamps;

    /**
     * @var bool Include stack traces for errors
     */
    private bool $includeStackTraces;

    /**
     * @var bool Whether logging is enabled
     */
    private bool $enabled;

    /**
     * @var resource|null File handle
     */
    private $fileHandle = null;

    /**
     * @var string Session identifier for this test run
     */
    private string $sessionId;

    /**
     * @var float Start time of test run
     */
    private float $startTime;

    /**
     * Constructor
     *
     * @param TestConfig $config Test configuration
     */
    public function __construct(TestConfig $config)
    {
        $this->enabled = $config->isLoggingEnabled();
        $this->logPath = $config->getLogPath();
        $this->level = $config->getLogLevel();
        $this->includeTimestamps = $config->shouldIncludeTimestamps();
        $this->includeStackTraces = $config->shouldIncludeStackTraces();
        $this->sessionId = date('Ymd_His') . '_' . substr(uniqid(), -6);
        $this->startTime = microtime(true);

        if ($this->enabled) {
            // Check if log should be reset
            if ($config->shouldResetLog()) {
                $this->resetLogFile();
            }
            $this->initLogFile();
        }
    }

    /**
     * Reset (clear) the log file
     */
    private function resetLogFile(): void
    {
        if (file_exists($this->logPath)) {
            file_put_contents($this->logPath, '');
        }
    }

    /**
     * Initialize log file
     */
    private function initLogFile(): void
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->fileHandle = fopen($this->logPath, 'a');
        if ($this->fileHandle === false) {
            $this->enabled = false;
            return;
        }

        // Write session header
        $this->writeRaw("\n" . str_repeat('=', 80) . "\n");
        $this->writeRaw("TEST SESSION: {$this->sessionId}\n");
        $this->writeRaw("Started: " . date('Y-m-d H:i:s') . "\n");
        $this->writeRaw(str_repeat('=', 80) . "\n\n");
    }

    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log a test result
     */
    public function testResult(string $testName, bool $passed, float $duration, ?string $message = null): void
    {
        $status = $passed ? 'PASS' : 'FAIL';
        $durationStr = sprintf('%.3fs', $duration);

        $logMessage = "[{$status}] {$testName} ({$durationStr})";
        if ($message !== null) {
            $logMessage .= "\n       Reason: {$message}";
        }

        $this->log($passed ? self::LEVEL_INFO : self::LEVEL_ERROR, $logMessage);
    }

    /**
     * Log a skipped test
     */
    public function testSkipped(string $testName, string $reason): void
    {
        $this->log(self::LEVEL_INFO, "[SKIP] {$testName}\n       Reason: {$reason}");
    }

    /**
     * Log a group header
     */
    public function groupHeader(string $groupName, string $description): void
    {
        $this->writeRaw("\n" . str_repeat('-', 60) . "\n");
        $this->writeRaw("GROUP: {$groupName} - {$description}\n");
        $this->writeRaw(str_repeat('-', 60) . "\n");
    }

    /**
     * Log resource creation
     */
    public function resourceCreated(string $type, $id, ?string $name = null): void
    {
        $msg = "Resource Created: {$type} #{$id}";
        if ($name) {
            $msg .= " ({$name})";
        }
        $this->debug($msg);
    }

    /**
     * Log resource update
     */
    public function resourceUpdated(string $type, $id): void
    {
        $this->debug("Resource Updated: {$type} #{$id}");
    }

    /**
     * Log resource deletion
     */
    public function resourceDeleted(string $type, $id): void
    {
        $this->debug("Resource Deleted: {$type} #{$id}");
    }

    /**
     * Log resource deletion failure
     */
    public function resourceDeleteFailed(string $type, $id, string $error): void
    {
        $this->error("Delete Failed: {$type} #{$id}\n       Error: {$error}");
    }

    /**
     * Log an exception with full details
     */
    public function exception(\Throwable $e, ?string $context = null): void
    {
        $message = "EXCEPTION";
        if ($context) {
            $message .= " in {$context}";
        }
        $message .= ": " . get_class($e) . "\n";
        $message .= "       Message: " . $e->getMessage() . "\n";
        $message .= "       File: " . $e->getFile() . ":" . $e->getLine();

        if ($this->includeStackTraces) {
            $message .= "\n       Stack Trace:\n";
            foreach (explode("\n", $e->getTraceAsString()) as $line) {
                $message .= "         {$line}\n";
            }
        }

        $this->log(self::LEVEL_ERROR, $message);
    }

    /**
     * Log API request
     */
    public function apiRequest(string $method, string $endpoint, ?array $data = null): void
    {
        $msg = "API Request: {$method} {$endpoint}";
        if ($data && $this->shouldLog(self::LEVEL_DEBUG)) {
            $msg .= "\n       Data: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        $this->debug($msg);
    }

    /**
     * Log API response
     */
    public function apiResponse(int $statusCode, ?array $data = null, ?string $error = null): void
    {
        if ($error) {
            $this->error("API Response: {$statusCode} - Error: {$error}");
        } else {
            $this->debug("API Response: {$statusCode} OK");
        }
    }

    /**
     * Log configuration used for test run
     */
    public function logConfiguration(TestConfig $config): void
    {
        $this->info("Configuration:");
        $this->writeRaw("  Config File: " . ($config->getConfigPath() ?? 'defaults') . "\n");
        $this->writeRaw("  API Key: " . $config->getMaskedApiKey() . "\n");
        $this->writeRaw("  Prefix: " . $config->getPrefix() . "\n");
        $this->writeRaw("  Dry Run: " . ($config->getRuntimeOption('dry_run') ? 'Yes' : 'No') . "\n");
        $this->writeRaw("  Verbose: " . ($config->getRuntimeOption('verbose') ? 'Yes' : 'No') . "\n");

        $anchors = $config->getAnchors();
        $this->writeRaw("  Anchors:\n");
        foreach ($anchors as $key => $value) {
            $this->writeRaw("    {$key}: " . ($value ?? 'not set') . "\n");
        }
    }

    /**
     * Log final test summary
     */
    public function logSummary(TestResult $results): void
    {
        $summary = $results->getSummary();
        $duration = microtime(true) - $this->startTime;

        $this->writeRaw("\n" . str_repeat('=', 80) . "\n");
        $this->writeRaw("TEST SUMMARY\n");
        $this->writeRaw(str_repeat('=', 80) . "\n\n");

        $this->writeRaw("Results:\n");
        $this->writeRaw("  Total:   {$summary['total']}\n");
        $this->writeRaw("  Passed:  {$summary['passed']}\n");
        $this->writeRaw("  Failed:  {$summary['failed']}\n");
        $this->writeRaw("  Skipped: {$summary['skipped']}\n");
        $this->writeRaw("  Duration: " . sprintf('%.2fs', $duration) . "\n\n");

        // Resource operations
        $this->writeRaw("Resource Operations:\n");
        $this->writeRaw("  Created: {$summary['resources_created']}\n");
        $this->writeRaw("  Updated: " . count($results->getUpdatedResources()) . "\n");
        $this->writeRaw("  Deleted: {$summary['resources_deleted']}\n");
        $this->writeRaw("  Remaining: {$summary['resources_remaining']}\n");
        $this->writeRaw("  Delete Failures: {$summary['delete_failures']}\n\n");

        // Log failures in detail
        $failures = $results->getFailures();
        if (!empty($failures)) {
            $this->writeRaw("FAILURES:\n");
            $this->writeRaw(str_repeat('-', 40) . "\n");
            foreach ($failures as $i => $failure) {
                $num = $i + 1;
                $this->writeRaw("\n{$num}. {$failure['test']}\n");
                $this->writeRaw("   Duration: " . sprintf('%.3fs', $failure['duration']) . "\n");
                $this->writeRaw("   Message: {$failure['message']}\n");
            }
            $this->writeRaw("\n");
        }

        // Log cleanup issues
        if ($results->hasCleanupIssues()) {
            $this->writeRaw("CLEANUP ISSUES:\n");
            $this->writeRaw(str_repeat('-', 40) . "\n");

            $remaining = $results->getRemainingResources();
            if (!empty($remaining)) {
                $this->writeRaw("\nResources NOT cleaned up:\n");
                foreach ($remaining as $type => $ids) {
                    $this->writeRaw("  {$type}: #" . implode(', #', $ids) . "\n");
                }
            }

            $deleteFailures = $results->getFailedDeletes();
            if (!empty($deleteFailures)) {
                $this->writeRaw("\nFailed delete attempts:\n");
                foreach ($deleteFailures as $failure) {
                    $this->writeRaw("  {$failure['type']} #{$failure['id']}: {$failure['error']}\n");
                }
            }
        }

        $this->writeRaw("\n" . str_repeat('=', 80) . "\n");
        $this->writeRaw("Session {$this->sessionId} completed at " . date('Y-m-d H:i:s') . "\n");
        $this->writeRaw("Status: " . ($summary['failed'] === 0 ? 'PASSED' : 'FAILED') . "\n");
        $this->writeRaw(str_repeat('=', 80) . "\n\n");
    }

    /**
     * Write a log entry
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled || !$this->shouldLog($level)) {
            return;
        }

        $prefix = '';
        if ($this->includeTimestamps) {
            $prefix .= '[' . date('Y-m-d H:i:s') . '] ';
        }
        $prefix .= '[' . strtoupper($level) . '] ';

        // Replace context placeholders
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $message = str_replace('{' . $key . '}', (string)$value, $message);
            }
        }

        $this->writeRaw($prefix . $message . "\n");
    }

    /**
     * Write raw text to log
     */
    private function writeRaw(string $text): void
    {
        if (!$this->enabled || $this->fileHandle === null) {
            return;
        }

        fwrite($this->fileHandle, $text);
        fflush($this->fileHandle);
    }

    /**
     * Check if a level should be logged
     */
    private function shouldLog(string $level): bool
    {
        $currentPriority = self::LEVEL_PRIORITY[$this->level] ?? 1;
        $messagePriority = self::LEVEL_PRIORITY[$level] ?? 1;

        return $messagePriority >= $currentPriority;
    }

    /**
     * Get the log file path
     */
    public function getLogPath(): string
    {
        return $this->logPath;
    }

    /**
     * Check if logging is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Close the log file
     */
    public function close(): void
    {
        if ($this->fileHandle !== null) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }

    /**
     * Destructor - ensure file is closed
     */
    public function __destruct()
    {
        $this->close();
    }
}

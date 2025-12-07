<?php
/**
 * Paymo API PHP SDK - Test Output Formatter
 *
 * Handles console output formatting with colors, progress indicators,
 * and result display.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests;

class TestOutput
{
    // ANSI color codes
    private const RESET = "\033[0m";
    private const BOLD = "\033[1m";
    private const DIM = "\033[2m";

    private const RED = "\033[31m";
    private const GREEN = "\033[32m";
    private const YELLOW = "\033[33m";
    private const BLUE = "\033[34m";
    private const MAGENTA = "\033[35m";
    private const CYAN = "\033[36m";
    private const WHITE = "\033[37m";

    private const BG_RED = "\033[41m";
    private const BG_GREEN = "\033[42m";

    // Box drawing characters
    private const BOX_TOP_LEFT = "\u{2554}";
    private const BOX_TOP_RIGHT = "\u{2557}";
    private const BOX_BOTTOM_LEFT = "\u{255A}";
    private const BOX_BOTTOM_RIGHT = "\u{255D}";
    private const BOX_HORIZONTAL = "\u{2550}";
    private const BOX_VERTICAL = "\u{2551}";
    private const BOX_T_DOWN = "\u{2566}";
    private const BOX_T_UP = "\u{2569}";
    private const BOX_T_RIGHT = "\u{2560}";
    private const BOX_T_LEFT = "\u{2563}";

    /**
     * @var bool Verbose output mode
     */
    private bool $verbose;

    /**
     * @var bool Quiet output mode
     */
    private bool $quiet;

    /**
     * @var bool JSON output mode
     */
    private bool $jsonMode;

    /**
     * @var bool Whether terminal supports colors
     */
    private bool $useColors;

    /**
     * @var int Terminal width
     */
    private int $termWidth;

    /**
     * @var TestLogger|null Optional logger for file output
     */
    private ?TestLogger $logger = null;

    /**
     * Constructor
     *
     * @param bool $verbose Enable verbose output
     * @param bool $quiet Enable quiet output
     * @param bool $jsonMode Output as JSON
     */
    public function __construct(bool $verbose = false, bool $quiet = false, bool $jsonMode = false)
    {
        $this->verbose = $verbose;
        $this->quiet = $quiet;
        $this->jsonMode = $jsonMode;
        $this->useColors = $this->detectColorSupport();
        $this->termWidth = $this->detectTerminalWidth();
    }

    /**
     * Set the logger for file output
     */
    public function setLogger(TestLogger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger instance
     */
    public function getLogger(): ?TestLogger
    {
        return $this->logger;
    }

    /**
     * Detect if terminal supports colors
     */
    private function detectColorSupport(): bool
    {
        if ($this->jsonMode) {
            return false;
        }

        // Check common indicators
        if (getenv('NO_COLOR')) {
            return false;
        }

        if (getenv('TERM') === 'dumb') {
            return false;
        }

        // Check if stdout is a TTY
        if (function_exists('posix_isatty') && posix_isatty(STDOUT)) {
            return true;
        }

        // Windows 10+ supports ANSI
        if (DIRECTORY_SEPARATOR === '\\') {
            return (getenv('ANSICON') !== false)
                || (getenv('ConEmuANSI') === 'ON')
                || (getenv('WT_SESSION') !== false);
        }

        return true;
    }

    /**
     * Detect terminal width
     */
    private function detectTerminalWidth(): int
    {
        // Try to get terminal width
        if (function_exists('exec')) {
            $output = [];
            @exec('tput cols 2>/dev/null', $output);
            if (!empty($output[0]) && is_numeric($output[0])) {
                return (int)$output[0];
            }
        }

        // Fallback
        return 80;
    }

    /**
     * Apply color to text
     */
    private function color(string $text, string $color): string
    {
        if (!$this->useColors) {
            return $text;
        }
        return $color . $text . self::RESET;
    }

    /**
     * Display the test suite banner
     */
    public function banner(): void
    {
        if ($this->quiet || $this->jsonMode) {
            return;
        }

        $width = min($this->termWidth, 70);
        $line = str_repeat(self::BOX_HORIZONTAL, $width - 2);

        echo "\n";
        echo $this->color(self::BOX_TOP_LEFT . $line . self::BOX_TOP_RIGHT, self::CYAN) . "\n";
        echo $this->color(self::BOX_VERTICAL, self::CYAN);
        echo $this->centerText("Paymo API PHP SDK - Test Suite Validator", $width - 2);
        echo $this->color(self::BOX_VERTICAL, self::CYAN) . "\n";
        echo $this->color(self::BOX_BOTTOM_LEFT . $line . self::BOX_BOTTOM_RIGHT, self::CYAN) . "\n";
        echo "\n";
    }

    /**
     * Center text within a given width
     */
    private function centerText(string $text, int $width): string
    {
        $textLen = strlen($text);
        if ($textLen >= $width) {
            return $text;
        }
        $padding = ($width - $textLen) / 2;
        return str_repeat(' ', (int)floor($padding)) . $text . str_repeat(' ', (int)ceil($padding));
    }

    /**
     * Display a header
     */
    public function header(string $text): void
    {
        if ($this->quiet || $this->jsonMode) {
            return;
        }

        echo "\n";
        echo $this->color(self::BOLD . $text . self::RESET, self::WHITE) . "\n";
        echo $this->color(str_repeat('=', strlen($text)), self::DIM . self::WHITE) . "\n";
        echo "\n";
    }

    /**
     * Display a sub-header
     */
    public function subHeader(string $text): void
    {
        if ($this->quiet || $this->jsonMode) {
            return;
        }

        echo $this->color(self::BOLD . $text . self::RESET, self::CYAN) . "\n";
    }

    /**
     * Display a group header (for test groups)
     */
    public function groupHeader(string $name, string $description = ''): void
    {
        // Log to file
        if ($this->logger !== null) {
            $this->logger->groupHeader($name, $description);
        }

        if ($this->quiet || $this->jsonMode) {
            return;
        }

        $arrow = $this->color("\u{25B6} ", self::CYAN);
        $text = $this->color(self::BOLD . $name, self::WHITE);
        if ($description) {
            $text .= $this->color(" - " . $description, self::DIM . self::WHITE);
        }

        echo $arrow . $text . self::RESET . "\n";
    }

    /**
     * Display a list item
     */
    public function listItem(string $text): void
    {
        if ($this->quiet || $this->jsonMode) {
            return;
        }

        echo "  " . $text . "\n";
    }

    /**
     * Display an empty line
     */
    public function line(): void
    {
        if ($this->quiet || $this->jsonMode) {
            return;
        }

        echo "\n";
    }

    /**
     * Display a success message
     */
    public function success(string $message): void
    {
        // Log to file
        if ($this->logger !== null) {
            $this->logger->info($message);
        }

        if ($this->jsonMode) {
            return;
        }

        $icon = $this->color("\u{2713}", self::GREEN);
        echo "  {$icon} " . $message . "\n";
    }

    /**
     * Display an error message
     */
    public function error(string $message): void
    {
        // Log to file
        if ($this->logger !== null) {
            $this->logger->error($message);
        }

        if ($this->jsonMode) {
            return;
        }

        $icon = $this->color("\u{2717}", self::RED);
        echo "  {$icon} " . $this->color($message, self::RED) . "\n";
    }

    /**
     * Display a warning message
     */
    public function warning(string $message): void
    {
        // Log to file
        if ($this->logger !== null) {
            $this->logger->warning($message);
        }

        if ($this->jsonMode) {
            return;
        }

        $icon = $this->color("\u{26A0}", self::YELLOW);
        echo "  {$icon} " . $this->color($message, self::YELLOW) . "\n";
    }

    /**
     * Display an info message
     */
    public function info(string $message): void
    {
        if ($this->quiet || $this->jsonMode) {
            return;
        }

        $icon = $this->color("\u{2139}", self::BLUE);
        echo "  {$icon} " . $message . "\n";
    }

    /**
     * Display test result line
     *
     * @param string $testName Test name
     * @param bool $passed Whether test passed
     * @param float $duration Duration in seconds
     * @param string|null $message Optional failure message
     */
    public function testResult(string $testName, bool $passed, float $duration, ?string $message = null): void
    {
        // Log to file
        if ($this->logger !== null) {
            $this->logger->testResult($testName, $passed, $duration, $message);
        }

        if ($this->jsonMode) {
            return;
        }

        $icon = $passed
            ? $this->color("\u{2713}", self::GREEN)
            : $this->color("\u{2717}", self::RED);

        $durationStr = $this->color(sprintf("[%.1fs]", $duration), self::DIM . self::WHITE);

        $nameColor = $passed ? self::WHITE : self::RED;
        $name = $this->color($testName, $nameColor);

        // Calculate padding for alignment
        $padding = max(0, 60 - strlen($testName));

        echo "  {$icon} {$name}" . str_repeat(' ', $padding) . " {$durationStr}\n";

        if (!$passed && $message && ($this->verbose || !$this->quiet)) {
            echo "    " . $this->color($message, self::DIM . self::RED) . "\n";
        }
    }

    /**
     * Display skipped test
     */
    public function testSkipped(string $testName, string $reason): void
    {
        // Log to file
        if ($this->logger !== null) {
            $this->logger->testSkipped($testName, $reason);
        }

        if ($this->quiet || $this->jsonMode) {
            return;
        }

        $icon = $this->color("\u{23ED}", self::YELLOW);
        $name = $this->color($testName, self::DIM . self::WHITE);

        echo "  {$icon} {$name}\n";

        if ($this->verbose) {
            echo "    " . $this->color("Skipped: " . $reason, self::DIM . self::YELLOW) . "\n";
        }
    }

    /**
     * Display cleanup progress
     */
    public function cleanup(string $message, bool $success = true): void
    {
        if ($this->quiet || $this->jsonMode) {
            return;
        }

        $icon = $success
            ? $this->color("\u{2713}", self::GREEN)
            : $this->color("\u{2717}", self::RED);

        echo "  {$icon} {$message}\n";
    }

    /**
     * Display verbose/debug message
     */
    public function debug(string $message): void
    {
        if (!$this->verbose || $this->jsonMode) {
            return;
        }

        echo $this->color("    [DEBUG] " . $message, self::DIM . self::MAGENTA) . "\n";
    }

    /**
     * Display dry-run indicator
     */
    public function dryRun(string $action): void
    {
        if ($this->quiet || $this->jsonMode) {
            return;
        }

        echo $this->color("  [DRY-RUN] " . $action, self::CYAN) . "\n";
    }

    /**
     * Display final results summary
     */
    public function displayResults(TestResult $results): void
    {
        // Log final summary to file
        if ($this->logger !== null) {
            $this->logger->logSummary($results);
        }

        if ($this->jsonMode) {
            echo json_encode($results->toArray(), JSON_PRETTY_PRINT) . "\n";
            return;
        }

        $width = min($this->termWidth, 70);
        $line = str_repeat(self::BOX_HORIZONTAL, $width - 2);
        $innerWidth = $width - 4;

        echo "\n";
        echo $this->color(self::BOX_T_RIGHT . $line . self::BOX_T_LEFT, self::CYAN) . "\n";

        // Results header
        echo $this->color(self::BOX_VERTICAL, self::CYAN);
        echo $this->centerText("RESULTS", $innerWidth + 2);
        echo $this->color(self::BOX_VERTICAL, self::CYAN) . "\n";

        echo $this->color(self::BOX_T_RIGHT . $line . self::BOX_T_LEFT, self::CYAN) . "\n";

        // Stats
        $stats = sprintf(
            "Total: %d    Passed: %d    Failed: %d    Skipped: %d",
            $results->getTotal(),
            $results->getPassed(),
            $results->getFailed(),
            $results->getSkipped()
        );

        echo $this->color(self::BOX_VERTICAL, self::CYAN);
        echo "  " . $stats . str_repeat(' ', max(0, $innerWidth - strlen($stats))) . "  ";
        echo $this->color(self::BOX_VERTICAL, self::CYAN) . "\n";

        // Duration
        $duration = sprintf("Time: %.1fs", $results->getDuration());
        echo $this->color(self::BOX_VERTICAL, self::CYAN);
        echo "  " . $duration . str_repeat(' ', max(0, $innerWidth - strlen($duration))) . "  ";
        echo $this->color(self::BOX_VERTICAL, self::CYAN) . "\n";

        echo $this->color(self::BOX_T_RIGHT . $line . self::BOX_T_LEFT, self::CYAN) . "\n";

        // Final status
        if ($results->hasFailures()) {
            $status = "FAILED";
            $statusColor = self::RED;
        } else {
            $status = "PASSED";
            $statusColor = self::GREEN;
        }

        echo $this->color(self::BOX_VERTICAL, self::CYAN);
        $statusText = $this->color("  " . ($results->hasFailures() ? "\u{2717}" : "\u{2713}") . " " . $status, $statusColor);
        echo $statusText . str_repeat(' ', max(0, $innerWidth - strlen($status) - 4 + ($this->useColors ? 10 : 0))) . "  ";
        echo $this->color(self::BOX_VERTICAL, self::CYAN) . "\n";

        echo $this->color(self::BOX_BOTTOM_LEFT . $line . self::BOX_BOTTOM_RIGHT, self::CYAN) . "\n";

        // Display resource tracking summary
        $this->displayResourceSummary($results);

        // List failures if any
        $failures = $results->getFailures();
        if (!empty($failures)) {
            echo "\n" . $this->color("Failed Tests:", self::RED . self::BOLD) . "\n";
            $i = 1;
            foreach ($failures as $failure) {
                echo $this->color("  {$i}. {$failure['test']}", self::RED) . "\n";
                if (!empty($failure['message'])) {
                    echo $this->color("     " . $failure['message'], self::DIM . self::RED) . "\n";
                }
                $i++;
            }
        }

        // Display cleanup issues
        $this->displayCleanupIssues($results);

        // Display log review recommendation
        $this->displayLogRecommendation($results);

        echo "\n";
    }

    /**
     * Display resource tracking summary
     */
    private function displayResourceSummary(TestResult $results): void
    {
        $summary = $results->getSummary();

        // Only show if there were any resource operations
        if ($summary['resources_created'] === 0 && $summary['resources_deleted'] === 0) {
            return;
        }

        echo "\n" . $this->color("Resource Operations:", self::BOLD . self::WHITE) . "\n";

        // Created
        $createdIcon = $this->color("+", self::GREEN);
        echo "  {$createdIcon} Created: " . $this->color((string)$summary['resources_created'], self::GREEN) . "\n";

        // Updated
        $updatedResources = $results->getUpdatedResources();
        $updatedCount = count($updatedResources);
        if ($updatedCount > 0) {
            $updatedIcon = $this->color("~", self::YELLOW);
            echo "  {$updatedIcon} Updated: " . $this->color((string)$updatedCount, self::YELLOW) . "\n";
        }

        // Deleted
        $deletedIcon = $this->color("-", self::CYAN);
        echo "  {$deletedIcon} Deleted: " . $this->color((string)$summary['resources_deleted'], self::CYAN) . "\n";

        // Remaining (if any - this is concerning)
        if ($summary['resources_remaining'] > 0) {
            $remainingIcon = $this->color("!", self::YELLOW);
            echo "  {$remainingIcon} Remaining: " . $this->color((string)$summary['resources_remaining'], self::YELLOW);
            echo $this->color(" (may need manual cleanup)", self::DIM . self::YELLOW) . "\n";
        }

        // Failed deletes (if any - this is bad)
        if ($summary['delete_failures'] > 0) {
            $failedIcon = $this->color("\u{2717}", self::RED);
            echo "  {$failedIcon} Delete Failures: " . $this->color((string)$summary['delete_failures'], self::RED) . "\n";
        }
    }

    /**
     * Display cleanup issues in detail
     */
    private function displayCleanupIssues(TestResult $results): void
    {
        if (!$results->hasCleanupIssues()) {
            return;
        }

        echo "\n" . $this->color("Cleanup Issues:", self::YELLOW . self::BOLD) . "\n";

        // Show remaining resources
        $remaining = $results->getRemainingResources();
        if (!empty($remaining)) {
            echo $this->color("  Resources NOT cleaned up (may exist in Paymo):", self::YELLOW) . "\n";
            foreach ($remaining as $type => $resources) {
                if (empty($resources)) {
                    continue;
                }
                echo "    " . $this->color($type . ":", self::WHITE);
                $ids = array_keys($resources);
                echo " " . implode(', ', array_map(fn($id) => "#{$id}", $ids)) . "\n";
            }
        }

        // Show failed deletes
        $failedDeletes = $results->getFailedDeletes();
        if (!empty($failedDeletes)) {
            echo $this->color("  Failed delete attempts:", self::RED) . "\n";
            foreach ($failedDeletes as $type => $failures) {
                foreach ($failures as $id => $info) {
                    echo "    " . $this->color("{$type} #{$id}:", self::RED);
                    echo " " . $this->color($info['error'], self::DIM . self::RED) . "\n";
                }
            }
        }

        echo "\n" . $this->color("  TIP: Check your Paymo account and manually delete test resources", self::DIM . self::WHITE) . "\n";
        echo $this->color("       with the prefix used during testing.", self::DIM . self::WHITE) . "\n";
    }

    /**
     * Display log review recommendation
     *
     * Shows whether detailed log review is recommended based on test results
     */
    private function displayLogRecommendation(TestResult $results): void
    {
        // Determine if log review is needed
        $needsReview = false;
        $reasons = [];

        // Check for failures
        if ($results->hasFailures()) {
            $needsReview = true;
            $reasons[] = "test failures detected";
        }

        // Check for cleanup issues
        if ($results->hasCleanupIssues()) {
            $needsReview = true;
            $reasons[] = "cleanup issues found";
        }

        // Check for skipped tests (may indicate configuration issues)
        if ($results->getSkipped() > 0) {
            $reasons[] = $results->getSkipped() . " tests skipped";
        }

        echo "\n";

        if ($needsReview) {
            // Log review is recommended
            echo $this->color("Log Review: ", self::BOLD . self::YELLOW);
            echo $this->color("RECOMMENDED", self::YELLOW . self::BOLD);
            echo "\n";

            foreach ($reasons as $reason) {
                echo "  " . $this->color("\u{2022}", self::YELLOW) . " " . $reason . "\n";
            }

            if ($this->logger !== null) {
                echo "\n  " . $this->color("Log file: " . $this->logger->getLogPath(), self::WHITE) . "\n";
                echo "  " . $this->color("The log contains detailed API calls, property comparisons,", self::DIM . self::WHITE) . "\n";
                echo "  " . $this->color("and diagnostics for troubleshooting issues.", self::DIM . self::WHITE) . "\n";
            }
        } else {
            // All good
            $icon = $this->color("\u{2713}", self::GREEN);
            echo "{$icon} " . $this->color("All tests passed - no log review needed", self::GREEN) . "\n";

            if (!empty($reasons)) {
                // There are informational items (like skips) but no critical issues
                echo "  " . $this->color("Note: ", self::DIM . self::WHITE);
                echo $this->color(implode(', ', $reasons), self::DIM . self::WHITE) . "\n";
            }

            // Show log file location for reference (only if not already shown)
            if ($this->logger !== null) {
                echo "\n  " . $this->color("Log file: " . $this->logger->getLogPath(), self::DIM . self::WHITE) . "\n";
            }
        }
    }

    /**
     * Display progress indicator
     */
    public function progress(int $current, int $total): void
    {
        if ($this->quiet || $this->jsonMode) {
            return;
        }

        $percent = $total > 0 ? round(($current / $total) * 100) : 0;
        $bar = str_repeat("\u{2588}", (int)($percent / 5)) . str_repeat("\u{2591}", 20 - (int)($percent / 5));

        echo "\r  [{$bar}] {$percent}% ({$current}/{$total})";

        if ($current === $total) {
            echo "\n";
        }
    }
}

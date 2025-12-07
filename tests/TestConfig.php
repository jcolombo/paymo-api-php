<?php
/**
 * Paymo API PHP SDK - Test Configuration Manager
 *
 * Handles loading and managing test configuration from the SDK config file.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests;

use Exception;

class TestConfig
{
    /**
     * Default testing configuration values
     */
    private const DEFAULTS = [
        'enabled' => false,
        'api_key' => '',
        'prefix' => '[TEST]',
        'anchors' => [
            'client_id' => null,
            'user_id' => null,
            'project_id' => null,
            'tasklist_id' => null,
            'task_id' => null,
            'invoice_id' => null,
            'estimate_id' => null,
            'project_template_id' => null,
            'invoice_template_id' => null,
            'estimate_template_id' => null,
            'project_status_id' => null,
            'workflow_id' => null,
        ],
        'modes' => [
            'dry_run' => false,
            'verbose' => false,
            'stop_on_failure' => false,
            'cleanup_on_failure' => true,
            'interactive' => true,
        ],
        'groups' => [
            'core' => true,
            'safe_crud' => true,
            'read_only' => true,
            'configured_anchors' => true,
            'properties' => true,
            'includes' => true,
        ],
        'resources' => [
            'skip' => [],
            'only' => [],
        ],
        'timeouts' => [
            'per_test' => 30,
            'per_group' => 300,
            'total' => 1800,
        ],
        'logging' => [
            'enabled' => true,
            'path' => null,  // null = default to tests/validation-results.log
            'level' => 'info',  // debug, info, warning, error
            'include_timestamps' => true,
            'include_stack_traces' => true,
        ],
    ];

    /**
     * Loaded configuration merged with defaults
     */
    private array $config;

    /**
     * Full SDK configuration (for connection settings)
     */
    private array $fullConfig;

    /**
     * Runtime option overrides
     */
    private array $runtimeOptions = [];

    /**
     * Path to loaded configuration file
     */
    private ?string $configPath;

    /**
     * Constructor - loads configuration from file
     *
     * @param string|null $configFile Optional path to config file
     * @throws Exception If config file cannot be loaded
     */
    public function __construct(?string $configFile = null)
    {
        $this->loadConfig($configFile);
    }

    /**
     * Load configuration from file
     *
     * @param string|null $configFile Optional path to config file
     * @throws Exception If config file cannot be loaded
     */
    private function loadConfig(?string $configFile): void
    {
        // Find config file
        $this->configPath = $this->findConfigFile($configFile);

        if ($this->configPath === null) {
            // Use defaults if no config file found
            $this->fullConfig = [];
            $this->config = self::DEFAULTS;
            return;
        }

        // Load and parse config
        $content = file_get_contents($this->configPath);
        if ($content === false) {
            throw new Exception("Could not read config file: {$this->configPath}");
        }

        $parsed = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in config file: " . json_last_error_msg());
        }

        $this->fullConfig = $parsed;

        // Merge testing config with defaults
        $testingConfig = $parsed['testing'] ?? [];
        $this->config = $this->mergeConfig(self::DEFAULTS, $testingConfig);
    }

    /**
     * Find the configuration file to use
     *
     * @param string|null $configFile Explicit config file path
     * @return string|null Path to config file or null if not found
     */
    private function findConfigFile(?string $configFile): ?string
    {
        // Explicit file provided
        if ($configFile !== null) {
            if (file_exists($configFile)) {
                return realpath($configFile);
            }
            throw new Exception("Config file not found: $configFile");
        }

        // Search order for config files
        $searchPaths = [
            PAYMO_PACKAGE_ROOT . '/paymoapi.config.json',
            PAYMO_PACKAGE_ROOT . '/default.paymoapi.config.json',
        ];

        foreach ($searchPaths as $path) {
            if (file_exists($path)) {
                return realpath($path);
            }
        }

        return null;
    }

    /**
     * Recursively merge configuration arrays
     *
     * @param array $defaults Default values
     * @param array $overrides Override values
     * @return array Merged configuration
     */
    private function mergeConfig(array $defaults, array $overrides): array
    {
        $result = $defaults;

        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])) {
                $result[$key] = $this->mergeConfig($defaults[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if testing is enabled
     */
    public function isTestingEnabled(): bool
    {
        return (bool)$this->config['enabled'];
    }

    /**
     * Get API key for testing
     */
    public function getApiKey(): ?string
    {
        $key = $this->config['api_key'] ?? null;
        return $key ?: null;
    }

    /**
     * Set API key (override from command line)
     */
    public function setApiKey(string $key): void
    {
        $this->config['api_key'] = $key;
        if (!empty($key)) {
            $this->config['enabled'] = true;
        }
    }

    /**
     * Get masked API key for display
     */
    public function getMaskedApiKey(): string
    {
        $key = $this->getApiKey();
        if (empty($key)) {
            return '';
        }
        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }
        return '***' . substr($key, -6);
    }

    /**
     * Get test data prefix
     */
    public function getPrefix(): string
    {
        return $this->config['prefix'] ?? '[TEST]';
    }

    /**
     * Get all anchor IDs
     */
    public function getAnchors(): array
    {
        return $this->config['anchors'] ?? [];
    }

    /**
     * Get specific anchor ID
     */
    public function getAnchor(string $key): ?int
    {
        $anchors = $this->getAnchors();
        $value = $anchors[$key] ?? null;
        return $value ? (int)$value : null;
    }

    /**
     * Set anchor ID (override from command line)
     */
    public function setAnchor(string $key, int $value): void
    {
        if (!isset($this->config['anchors'])) {
            $this->config['anchors'] = [];
        }
        $this->config['anchors'][$key] = $value;
    }

    /**
     * Get mode configuration
     */
    public function getModes(): array
    {
        return $this->config['modes'] ?? [];
    }

    /**
     * Get specific mode setting
     */
    public function getMode(string $key): bool
    {
        $modes = $this->getModes();
        return (bool)($modes[$key] ?? false);
    }

    /**
     * Set runtime option (overrides config modes)
     */
    public function setRuntimeOption(string $key, $value): void
    {
        $this->runtimeOptions[$key] = $value;
    }

    /**
     * Get runtime option (checks overrides first, then config)
     */
    public function getRuntimeOption(string $key)
    {
        if (array_key_exists($key, $this->runtimeOptions)) {
            return $this->runtimeOptions[$key];
        }
        return $this->getMode($key);
    }

    /**
     * Check if a test group is enabled
     */
    public function isGroupEnabled(string $group): bool
    {
        $groups = $this->config['groups'] ?? [];
        return (bool)($groups[$group] ?? true);
    }

    /**
     * Get resources to skip
     */
    public function getSkippedResources(): array
    {
        return $this->config['resources']['skip'] ?? [];
    }

    /**
     * Get resources to run only
     */
    public function getOnlyResources(): array
    {
        return $this->config['resources']['only'] ?? [];
    }

    /**
     * Check if a resource should be tested
     */
    public function shouldTestResource(string $resource): bool
    {
        $only = $this->getOnlyResources();
        $skip = $this->getSkippedResources();

        // If 'only' is specified, resource must be in it
        if (!empty($only) && !in_array($resource, $only)) {
            return false;
        }

        // Check skip list
        if (in_array($resource, $skip)) {
            return false;
        }

        return true;
    }

    /**
     * Get timeout values
     */
    public function getTimeouts(): array
    {
        return $this->config['timeouts'] ?? [];
    }

    /**
     * Get specific timeout value
     */
    public function getTimeout(string $key): int
    {
        $timeouts = $this->getTimeouts();
        return (int)($timeouts[$key] ?? 30);
    }

    /**
     * Get SDK connection configuration
     */
    public function getConnectionConfig(): array
    {
        return $this->fullConfig['connection'] ?? [];
    }

    /**
     * Get the path to the loaded config file
     */
    public function getConfigPath(): ?string
    {
        return $this->configPath;
    }

    /**
     * Check if required anchors for a group are configured
     */
    public function hasRequiredAnchorsForGroup(string $group): bool
    {
        $anchors = $this->getAnchors();

        switch ($group) {
            case 'safe_crud':
                return !empty($anchors['client_id']);

            case 'configured_anchors':
                // At least one configured anchor needed
                return !empty($anchors['user_id'])
                    || !empty($anchors['project_template_id'])
                    || !empty($anchors['invoice_template_id'])
                    || !empty($anchors['estimate_template_id'])
                    || !empty($anchors['project_status_id']);

            case 'core':
            case 'read_only':
            case 'properties':
            case 'includes':
            default:
                return true;
        }
    }

    /**
     * Get prefixed test name
     *
     * @param string $name Base name
     * @return string Prefixed name with timestamp
     */
    public function prefixName(string $name): string
    {
        $prefix = $this->getPrefix();
        $timestamp = date('His');
        return "{$prefix}-{$timestamp} {$name}";
    }

    /**
     * Get logging configuration
     */
    public function getLogging(): array
    {
        return $this->config['logging'] ?? [];
    }

    /**
     * Check if logging is enabled
     */
    public function isLoggingEnabled(): bool
    {
        $logging = $this->getLogging();
        return (bool)($logging['enabled'] ?? true);
    }

    /**
     * Get log file path
     * Returns configured path, or default path relative to tests directory
     */
    public function getLogPath(): string
    {
        $logging = $this->getLogging();
        $path = $logging['path'] ?? null;

        if ($path === null) {
            // Default to tests/validation-results.log
            return dirname(__FILE__) . '/validation-results.log';
        }

        // If relative path, make it relative to tests directory
        if ($path[0] !== '/') {
            return dirname(__FILE__) . '/' . $path;
        }

        return $path;
    }

    /**
     * Get log level (debug, info, warning, error)
     */
    public function getLogLevel(): string
    {
        $logging = $this->getLogging();
        return $logging['level'] ?? 'info';
    }

    /**
     * Check if timestamps should be included in logs
     */
    public function shouldIncludeTimestamps(): bool
    {
        $logging = $this->getLogging();
        return (bool)($logging['include_timestamps'] ?? true);
    }

    /**
     * Check if stack traces should be included for errors
     */
    public function shouldIncludeStackTraces(): bool
    {
        $logging = $this->getLogging();
        return (bool)($logging['include_stack_traces'] ?? true);
    }

    /**
     * Check if log should be reset (cleared) at start
     * Checks both config setting and runtime option (CLI --reset-log flag)
     */
    public function shouldResetLog(): bool
    {
        // CLI flag takes precedence
        if ($this->getRuntimeOption('reset_log')) {
            return true;
        }
        // Then check config
        $modes = $this->getModes();
        return (bool)($modes['reset_log'] ?? false);
    }

    /**
     * Export config for debugging
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->isTestingEnabled(),
            'api_key' => $this->getMaskedApiKey(),
            'prefix' => $this->getPrefix(),
            'anchors' => $this->getAnchors(),
            'modes' => $this->getModes(),
            'runtime_options' => $this->runtimeOptions,
            'groups' => $this->config['groups'] ?? [],
            'resources' => $this->config['resources'] ?? [],
            'timeouts' => $this->getTimeouts(),
            'config_path' => $this->configPath,
        ];
    }
}

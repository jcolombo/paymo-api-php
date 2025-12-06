<?php
/**
 * Paymo API PHP SDK - Configuration Management
 *
 * Provides centralized configuration management for the SDK using a singleton pattern.
 * Supports loading configuration from JSON files with cascading overrides.
 *
 * @package    Jcolombo\PaymoApiPhp
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020-2025 Joel Colombo / 360 PSG, Inc.
 * @license    MIT License
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 * @see        https://github.com/paymoapp/api Official Paymo API Documentation
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Jcolombo\PaymoApiPhp;

use Noodlehaus\Config as OriginalConfig;

/**
 * Extended Config Class
 *
 * Extends the Noodlehaus Config class to allow for future custom functionality
 * while maintaining full compatibility with the original configuration behavior.
 * This wrapper enables SDK-specific enhancements without modifying the vendor package.
 *
 * @package  Jcolombo\PaymoApiPhp
 * @see      OriginalConfig The underlying configuration library
 * @internal This class is used internally by Configuration
 */
class Config extends OriginalConfig
{
}

/**
 * SDK Configuration Manager
 *
 * Singleton class that manages all SDK configuration through JSON configuration files.
 * Configuration values are loaded from the default package configuration and can be
 * overridden by user-defined configuration files.
 *
 * ## Configuration Hierarchy
 *
 * Configuration is loaded in order, with later files overriding earlier values:
 * 1. Package default: `default.paymoapi.config.json` (in package root)
 * 2. User overrides: `paymoapi.config.json` (in your project)
 *
 * ## Available Configuration Options
 *
 * ### Connection Settings (`connection.*`)
 * - `connection.url` (string): API base URL, default: "https://app.paymoapp.com/api/"
 * - `connection.defaultName` (string): Default connection name prefix
 * - `connection.verify` (bool): Whether to verify connection on connect()
 * - `connection.timeout` (float): Request timeout in seconds, default: 15.0
 *
 * ### Path Settings (`path.*`)
 * - `path.cache` (string|null): Directory path for cache files
 * - `path.logs` (string|null): Directory path for log files
 *
 * ### Feature Toggles (`enabled.*`)
 * - `enabled.cache` (bool): Enable response caching
 * - `enabled.logging` (bool): Enable request/response logging
 *
 * ### Logging Settings (`log.*`)
 * - `log.connections` (bool): Log connection events
 * - `log.requests` (bool): Log API requests
 *
 * ### Development Settings
 * - `devMode` (bool): Enable development mode validations
 *
 * ### Entity Mapping (`classMap.*`)
 * - `classMap.defaultCollection` (string): Default collection class
 * - `classMap.entity.*` (object): Entity-to-class mappings
 *
 * ## Usage Examples
 *
 * ```php
 * use Jcolombo\PaymoApiPhp\Configuration;
 *
 * // Get a configuration value using dot notation
 * $timeout = Configuration::get('connection.timeout');
 *
 * // Check if a configuration key exists
 * if (Configuration::has('path.cache')) {
 *     $cachePath = Configuration::get('path.cache');
 * }
 *
 * // Set a runtime configuration value (not persisted)
 * Configuration::set('connection.timeout', 30.0);
 *
 * // Load additional configuration from a file
 * Configuration::load('/path/to/config/directory');
 *
 * // Get all configuration as an array
 * $allConfig = Configuration::all();
 * ```
 *
 * ## Creating a Custom Configuration File
 *
 * Create a file named `paymoapi.config.json` in your project:
 *
 * ```json
 * {
 *   "connection": {
 *     "timeout": 30.0
 *   },
 *   "enabled": {
 *     "cache": true,
 *     "logging": true
 *   },
 *   "path": {
 *     "cache": "/var/cache/paymo",
 *     "logs": "/var/log/paymo"
 *   }
 * }
 * ```
 *
 * @package Jcolombo\PaymoApiPhp
 * @author  Joel Colombo <jc-dev@360psg.com>
 * @since   0.1.0
 */
class Configuration
{
    /**
     * Path to the default configuration file included with the package.
     *
     * This JSON file contains all default settings and the complete entity mapping
     * for all Paymo resource types. User configuration files can override any of
     * these values.
     *
     * @var string Absolute path to default.paymoapi.config.json
     */
    public const DEFAULT_CONFIGURATION_PATH = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'default.paymoapi.config.json';

    /**
     * Singleton instance of the Configuration class.
     *
     * Ensures only one configuration manager exists throughout the application
     * lifecycle, preventing duplicate loading and potential inconsistencies.
     *
     * @var Configuration|null The singleton instance
     */
    private static ?Configuration $instance = null;

    /**
     * The underlying Noodlehaus Config instance.
     *
     * Handles the actual configuration file parsing, merging, and value retrieval.
     * Supports JSON, YAML, INI, and other common configuration formats.
     *
     * @var Config|null The config handler instance
     */
    private $config;

    /**
     * Ordered list of configuration file paths to load.
     *
     * Configuration files are loaded in order, with later files overriding
     * values from earlier files. The default package configuration is always
     * loaded first.
     *
     * @var string[] Array of configuration file paths
     */
    private array $paths;

    /**
     * Private constructor - enforces singleton pattern.
     *
     * Initializes the configuration by loading the default package configuration.
     * Additional configuration files can be added via the load() method.
     *
     * @internal Use Configuration::load() or Configuration::get() to access configuration
     */
    private function __construct()
    {
        $this->paths = [realpath(self::DEFAULT_CONFIGURATION_PATH)];
        $this->config = Config::load($this->paths);
    }

    /**
     * Get a configuration value by key.
     *
     * Retrieves a value from the merged configuration using dot notation for
     * nested keys. Returns NULL if the key does not exist.
     *
     * ## Examples
     *
     * ```php
     * // Get a simple value
     * $url = Configuration::get('connection.url');
     * // Returns: "https://app.paymoapp.com/api/"
     *
     * // Get a nested value
     * $projectClass = Configuration::get('classMap.entity.project.resource');
     * // Returns: "Jcolombo\\PaymoApiPhp\\Entity\\Resource\\Project"
     *
     * // Get a non-existent key
     * $missing = Configuration::get('foo.bar.baz');
     * // Returns: null
     * ```
     *
     * @param string $key Dot-notation path to the configuration value (e.g., "connection.timeout")
     *
     * @return mixed|null The configuration value, or NULL if not found
     *
     * @since 0.1.0
     */
    public static function get(string $key)
    {
        return self::load()->config->get($key);
    }

    /**
     * Load or retrieve the singleton Configuration instance.
     *
     * This is the primary entry point for the Configuration class. It creates
     * the singleton instance on first call and optionally loads additional
     * configuration from the specified path.
     *
     * ## Examples
     *
     * ```php
     * // Get the configuration instance (creates if needed)
     * $config = Configuration::load();
     *
     * // Load additional configuration from a directory
     * $config = Configuration::load('/path/to/project');
     * // This looks for /path/to/project/paymoapi.config.json
     *
     * // Load additional configuration from a specific file
     * $config = Configuration::load('/path/to/custom-config.json');
     * ```
     *
     * @param string|null $path Optional path to load additional configuration from:
     *                          - Directory path: Looks for "paymoapi.config.json" in that directory
     *                          - File path: Loads the specified configuration file
     *                          - null: Just returns the existing instance
     *
     * @return Configuration The singleton Configuration instance
     *
     * @since 0.1.0
     */
    public static function load(string $path = null) : ?Configuration
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        self::$instance->overload($path);

        return self::$instance;
    }

    /**
     * Add a configuration file to the loading chain.
     *
     * The new configuration file will be merged with existing configuration,
     * with values from the new file taking precedence over existing values.
     * Duplicate paths are ignored to prevent redundant loading.
     *
     * @param string|null $path Path to configuration file or directory:
     *                          - If a file: Uses that file directly
     *                          - If a directory: Looks for "paymoapi.config.json"
     *                          - If null: No action taken
     *
     * @internal Called by load() method
     */
    public function overload(string $path = null) : void
    {
        if (!is_null($path)) {
            $configFile = null;
            if (is_file($path) && file_exists($path)) {
                $configFile = $path;
            }
            if (!$configFile) {
                $realpath = dirname($path);
                $configFile = $realpath.DIRECTORY_SEPARATOR.'paymoapi.config.json';
            }
            if (!in_array($configFile, $this->paths, true) && file_exists($configFile)) {
                $this->paths[] = $configFile;
                $this->config = Config::load($this->paths);
            }
        }
    }

    /**
     * Check if a configuration key exists.
     *
     * Returns TRUE if the key exists in the configuration and has a non-null value.
     * Useful for checking optional configuration before attempting to use it.
     *
     * ## Examples
     *
     * ```php
     * // Check if caching is configured
     * if (Configuration::has('path.cache')) {
     *     Cache::init(Configuration::get('path.cache'));
     * }
     *
     * // Check nested keys
     * if (Configuration::has('classMap.entity.project.resource')) {
     *     // Project entity is mapped
     * }
     * ```
     *
     * @param string $key Dot-notation path to check (e.g., "enabled.cache")
     *
     * @return bool TRUE if the key exists and has a value, FALSE otherwise
     *
     * @since 0.1.0
     */
    public static function has(string $key) : bool
    {
        return self::load()->config->has($key);
    }

    /**
     * Set a configuration value at runtime.
     *
     * Modifies the configuration in memory only - changes are NOT persisted
     * to the configuration file and will be lost when the script ends.
     * Useful for testing or temporary configuration overrides.
     *
     * ## Examples
     *
     * ```php
     * // Temporarily increase timeout for a long operation
     * Configuration::set('connection.timeout', 60.0);
     *
     * // Disable caching for debugging
     * Configuration::set('enabled.cache', false);
     *
     * // Clear a value (makes has() return false)
     * Configuration::set('path.cache', null);
     * ```
     *
     * @param string $key Dot-notation path to the setting (e.g., "connection.timeout")
     * @param mixed  $val The value to set. Use NULL to unset.
     *
     * @return void
     *
     * @todo  Implement optional persistence to write changes back to config file
     *
     * @since 0.1.0
     */
    public static function set(string $key, $val) : void
    {
        self::load()->config->set($key, $val);
    }

    /**
     * Get all configuration settings as an array.
     *
     * Returns the complete merged configuration from all loaded configuration
     * files. Useful for debugging or exporting configuration state.
     *
     * ## Example
     *
     * ```php
     * // Dump all configuration for debugging
     * $allConfig = Configuration::all();
     * print_r($allConfig);
     *
     * // Check what entity mappings are defined
     * $entityMap = Configuration::all()['classMap']['entity'];
     * ```
     *
     * @return array Complete configuration as associative array
     *
     * @since 0.1.0
     */
    public static function all() : array
    {
        return self::load()->config->all();
    }

    /**
     * Reset configuration to package defaults.
     *
     * Removes all custom configuration overrides and reloads only the default
     * package configuration. Useful for testing or when you need a clean slate.
     *
     * ## Example
     *
     * ```php
     * // Reset to defaults before a test
     * Configuration::load()->reset();
     *
     * // Now all values are back to package defaults
     * $timeout = Configuration::get('connection.timeout'); // 15.0 (default)
     * ```
     *
     * @return void
     *
     * @since 0.1.0
     */
    public function reset() : void
    {
        $this->paths = [realpath(self::DEFAULT_CONFIGURATION_PATH)];
        self::load();
    }

}

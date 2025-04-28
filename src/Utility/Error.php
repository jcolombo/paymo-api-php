<?php

namespace Jcolombo\PaymoApiPhp\Utility;

use DateTime;
use Exception;
use Jcolombo\PaymoApiPhp\Configuration;
use RuntimeException;

/**
 * Class Error
 * A robust error handling system for the Paymo API library that provides centralized error management
 * with configurable severity levels, multiple output methods, and contextual error tracking.
 * Features:
 * - Singleton pattern for global error management
 * - Configuration integration with JSON-based settings
 * - Multiple output handlers (logging, echo, PHP errors)
 * - Request context tracking for API calls
 * - Custom error context storage
 * - Error stack collection and retrieval
 * - Automatic log file handling and rotation
 * Severity Levels:
 * - notice: Low-priority informational messages (non-disruptive)
 * - warn: Warning messages (potential issues, but operation continues)
 * - fatal: Critical errors (terminates execution)
 * Handler Modes:
 * - log: Writes errors to configured log file with timestamp and context
 * - echo: Outputs formatted errors to stdout
 * - silent: Suppresses error output but still records to stack
 * Configuration Options (via default.paymoapi.config.json):
 * ```json
 * {
 *   "error": {
 *     "handlers": {
 *       "notice": ["log"],
 *       "warn": ["log"],
 *       "fatal": ["log", "echo"]
 *     },
 *     "logFile": null,
 *     "triggerPhpErrors": false,
 *     "enabled": true
 *   }
 * }
 * ```
 * Usage Examples:
 * ```php
 * // Basic error throwing
 * Error::throw('notice', null, 100, 'Informational message');
 * // Initialize with custom configuration
 * Error::init([
 *     'logFilename' => 'api-errors.log',
 *     'handlers' => [
 *         'notice' => ['log'],
 *         'warn' => ['log', 'echo'],
 *         'fatal' => ['log', 'echo']
 *     ],
 *     'triggerPhpErrors' => true,
 *     'enabled' => true
 * ]);
 * // Add request context
 * Error::i()->setRequest('https://api.example.com/endpoint', ['param' => 'value']);
 * // Add custom context
 * Error::i()->addContext('transaction_id', '123xyz');
 * // Throw error with context
 * Error::throw('fatal',
 *     ['details' => 'Connection timeout'],
 *     500,
 *     'API connection failed'
 * );
 * // Retrieve error stack
 * $errors = Error::i()->getAllErrors();
 * ```
 * Log Format:
 * [timestamp] SEVERITY: message | Code: code | URL: request_url
 * Error Entry Structure:
 * ```php
 * [
 *     'timestamp'    => 'YYYY-MM-DD HH:mm:ss',
 *     'severity'     => 'NOTICE|WARN|FATAL',
 *     'message'      => 'Error message',
 *     'code'         => 'Error code',
 *     'error'        => ['Additional error data'],
 *     'request_url'  => 'API endpoint URL',
 *     'request_data' => ['Request parameters'],
 *     'context'      => ['Custom context data']
 * ]
 * ```
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class Error
{
  /**
   * @var self|null Singleton instance of the Error class
   */
  private static ?self $instance = null;

  /**
   * @var string|null Path to the log file where errors will be written
   */
  private ?string $logFile;

  /**
   * @var resource|null Open file handle for log file
   */
  private $logHandle;

  /**
   * @var array<string, array<string>> Mapping of severity levels to their handlers
   */
  private array $handlers;

  /**
   * @var bool Flag to determine if PHP's trigger_error() should be called
   */
  private bool $triggerPhpErrors;

  /**
   * @var bool Whether the custom Error handler system is globally enabled
   */
  private bool $handlerEnabled = true;

  /**
   * @var string Stores the URL of the last API request for context
   */
  private string $requestUrl = '';

  /**
   * @var array Stores the data sent with the last API request
   */
  private array $requestData = [];

  /**
   * @var array Stores custom context data for error reporting
   */
  private array $context = [];

  /**
   * @var array Collection of all errors that occurred during execution
   */
  private array $errorStack = [];

  /**
   * Private constructor to prevent direct instantiation
   * Initializes default handler configuration from the Configuration system
   */
  private function __construct() {
    $this->loadConfiguration();
  }

  /**
   * Loads configuration from the Configuration system
   *
   * @return void
   */
  private function loadConfiguration() : void {
    $this->handlers = Configuration::get('error.handlers') ?? [
      'notice' => ['log'],
      'warn'   => ['log'],
      'fatal'  => ['log', 'echo']
    ];

    $this->triggerPhpErrors = Configuration::get('error.triggerPhpErrors') ?? false;
    $this->handlerEnabled = Configuration::get('error.enabled') !== false;

    $logPath = Configuration::get('path.logs');
    $logFilename = Configuration::get('error.logFilename') ?? 'error.log';
    $this->logFile = $logPath && $logFilename ? rtrim($logPath,
                                                      DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$logFilename : null;

    if ($this->logFile) {
      $this->logHandle = @fopen($this->logFile, 'ab');
    }

    if (!$this->logHandle) {
      $this->logFile = null;
    }
  }

  /**
   * Initializes or returns the Error handler instance with optional configuration
   *
   * @param array|null $config Configuration array with optional keys:
   *                           - logFilename: string log filename
   *                           - handlers: array Severity level handler configurations
   *                           - triggerPhpErrors: bool Whether to trigger PHP errors
   *                           - enabled: bool Whether the error system is enabled
   *
   * @return self Singleton instance of the Error handler
   */
  public static function init(array $config = null) : self {
    $instance = self::i();
    if (is_array($config)) {
      if (isset($config['logFilename'])) {
        Configuration::set('error.logFilename', $config['logFilename']);
      }
      if (isset($config['handlers'])) {
        Configuration::set('error.handlers', $config['handlers']);
      }
      if (isset($config['triggerPhpErrors'])) {
        Configuration::set('error.triggerPhpErrors', $config['triggerPhpErrors']);
      }
      if (isset($config['enabled'])) {
        Configuration::set('error.enabled', $config['enabled']);
      }
      $instance->loadConfiguration();
    }

    return $instance;
  }

  /**
   * Returns the singleton instance of the Error handler
   *
   * @return self The singleton Error instance
   */
  public static function i() : self {
    if (!self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   * Configures the Error handler with new settings
   *
   * @param array $options Configuration options array
   *
   * @return void
   */
  public function configure(array $options) : void {
    if (isset($options['logFilename'])) {
      Configuration::set('error.logFilename', $options['logFilename']);
      $this->logFile = $options['logFilename'];
    }

    if (isset($options['handlers'])) {
      Configuration::set('error.handlers', $options['handlers']);
      $this->handlers = $options['handlers'];
    }

    if (isset($options['triggerPhpErrors'])) {
      Configuration::set('error.triggerPhpErrors', $options['triggerPhpErrors']);
      $this->triggerPhpErrors = $options['triggerPhpErrors'];
    }

    if (isset($options['enabled'])) {
      Configuration::set('error.enabled', $options['enabled']);
      $this->handlerEnabled = $options['enabled'];
    }
  }

  /**
   * Resets error handling configuration to defaults from configuration system
   *
   * @return void
   */
  public function resetConfiguration() : void {
    $this->loadConfiguration();
  }

  /**
   * Sets the file path for error logging
   *
   * @param string $path Absolute path to the log file
   *
   * @return void
   */
  public function setLogFile(string $path) : void {
    $this->logFile = $path;
  }

  /**
   * Configures handlers for a specific error severity level
   *
   * @param string $severity Error severity level (notice|warn|fatal)
   * @param array  $modes    Array of handler modes (log|echo|silent)
   *
   * @return void
   */
  public function setHandler(string $severity, array $modes) : void {
    $this->handlers[$severity] = $modes;
  }

  /**
   * Enables or disables PHP error triggering
   *
   * @param bool $enabled Whether to trigger PHP errors
   *
   * @return void
   */
  public function setTriggerPhpErrors(bool $enabled) : void {
    $this->triggerPhpErrors = $enabled;
  }

  /**
   * Sets request context information for error tracking
   *
   * @param string       $url  The API request URL
   * @param array|object $data The request data/payload
   *
   * @return void
   */
  public function setRequest(string $url, $data = []) : void {
    $this->requestUrl = $url;
    $this->requestData = (array) $data;
  }

  /**
   * Adds custom context data for error reporting
   *
   * @param string $key   Context identifier
   * @param mixed  $value Context value
   *
   * @return void
   */
  public function addContext(string $key, $value) : void {
    $this->context[$key] = $value;
  }

  /**
   * Clears stored context data
   *
   * @param string|null $key Specific context key to clear, or null to clear all
   *
   * @return void
   */
  public function clearContext(string $key = null) : void {
    if ($key === null) {
      $this->context = [];
    } else {
      unset($this->context[$key]);
    }
  }

  /**
   * Static method to throw and handle errors
   *
   * @param string            $severity Error severity level (notice|warn|fatal)
   * @param array|object|null $error    Additional error data
   * @param int|string|null   $code     Error code
   * @param string|null       $message  Error message
   *
   * @throws Exception When severity is 'fatal' or when log directory creation fails
   * @return void
   */
  public static function throw(string $severity, $error = null, $code = null, string $message = null) : void {
    self::i()->handleError($severity, $error, $code, $message);
  }

  /**
   * Handles error processing and distribution to configured handlers
   * Internal method that processes errors based on severity and configuration.
   * Manages the error stack, triggers handlers, and handles fatal errors.
   *
   * @param string            $severity Error severity level (notice|warn|fatal)
   * @param array|object|null $error    Additional error data/context
   * @param int|string|null   $code     Error code (used for categorization/tracking)
   * @param string|null       $message  Human-readable error message
   *
   * @throws RuntimeException When severity is 'fatal' or critical errors occur
   * @return void
   */
  private function handleError(string $severity, $error, $code, ?string $message) : void {
    $timestamp = (new DateTime())->format('Y-m-d H:i:s');

    $defaultMessages = [
      'notice' => 'Notice occurred.',
      'warn'   => 'Warning issued.',
      'fatal'  => 'Fatal error encountered.'
    ];

    $finalMessage = $message ?? ($defaultMessages[$severity] ?? 'Unknown error.');

    if (!$this->handlerEnabled) {
      switch ($severity) {
        case 'fatal':
          trigger_error($finalMessage, E_USER_ERROR);
        case 'warn':
          trigger_error($finalMessage, E_USER_WARNING);
          break;
        default:
          trigger_error($finalMessage);
      }

      return;
    }

    $logEntry = [
      'timestamp'    => $timestamp,
      'severity'     => strtoupper($severity),
      'message'      => $finalMessage,
      'code'         => $code,
      'error'        => $error,
      'request_url'  => $this->requestUrl,
      'request_data' => $this->requestData,
      'context'      => $this->context
    ];

    $this->errorStack[] = $logEntry;

    $handlers = $this->handlers[$severity] ?? [];

    foreach ($handlers as $handler) {
      if ($handler === 'log') {
        $this->logError($logEntry);
      } elseif ($handler === 'echo') {
        $this->echoError($logEntry);
      }
    }

    if ($severity === 'fatal') {
      if ($this->triggerPhpErrors) {
        trigger_error($finalMessage, E_USER_ERROR);
      }
      throw new RuntimeException($finalMessage, (int) $code);
    }

    if ($this->triggerPhpErrors) {
      $phpLevel = ($severity === 'warn') ? E_USER_WARNING : E_USER_NOTICE;
      trigger_error($finalMessage, $phpLevel);
    }
  }

  /**
   * Writes error line to log file.
   *
   * @param array $logEntry
   *
   * @return void
   */
  private function logError(array $logEntry) : void {
    if (!$this->logHandle) {
      return;
    }

    $line = sprintf(
      "[%s] %s: %s | Code: %s | URL: %s",
      $logEntry['timestamp'],
      $logEntry['severity'],
      $logEntry['message'],
      $logEntry['code'] ?? 'none',
      $logEntry['request_url']
    );

    $this->write($line);
  }

  /**
   * Outputs error to stdout.
   *
   * @param array $logEntry
   *
   * @return void
   */
  private function echoError(array $logEntry) : void {
    $line = sprintf(
      "[%s] %s: %s | Code: %s | URL: %s\n",
      $logEntry['timestamp'],
      $logEntry['severity'],
      $logEntry['message'],
      $logEntry['code'] ?? 'none',
      $logEntry['request_url']
    );

    echo $line;
  }

  /**
   * Writes content to log file handle
   *
   * @param mixed $content
   *
   * @return void
   */
  private function write($content) : void {
    if (!$this->logHandle || $content === false) {
      return;
    }

    if ($content === true) {
      $content = 'true';
    }

    if (!is_string($content)) {
      $content = print_r($content, true);
    }

    fwrite($this->logHandle, $content."\n");
  }

  /**
   * Retrieves all collected errors
   *
   * @param bool $clear Whether to clear the error stack after retrieval
   *
   * @return array Array of error entries with complete context
   */
  public function getAllErrors(bool $clear = true) : array {
    $stack = $this->errorStack;
    if ($clear) {
      $this->errorStack = [];
    }

    return $stack;
  }

  /**
   * Destructor to close file handle
   */
  public function __destruct() {
    if (is_resource($this->logHandle)) {
      fclose($this->logHandle);
    }
  }
}
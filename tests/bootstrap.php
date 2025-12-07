<?php
/**
 * Paymo API PHP SDK - Test Suite Bootstrap
 *
 * Initializes autoloading and sets up the test environment.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

// Define test suite root
define('PAYMO_TEST_ROOT', __DIR__);
define('PAYMO_PACKAGE_ROOT', dirname(__DIR__));

// Load Composer autoloader (SDK classes)
$composerAutoload = PAYMO_PACKAGE_ROOT . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    die("Composer autoloader not found. Run 'composer install' first.\n");
}
require_once $composerAutoload;

// Register test suite autoloader
spl_autoload_register(function ($class) {
    // Only handle our test namespace
    $prefix = 'Jcolombo\\PaymoApiPhp\\Tests\\';
    $prefixLength = strlen($prefix);

    if (strncmp($prefix, $class, $prefixLength) !== 0) {
        return;
    }

    // Get the relative class name
    $relativeClass = substr($class, $prefixLength);

    // Convert namespace to file path
    $file = PAYMO_TEST_ROOT . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Set timezone if not already set
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// Error handler for test environment
set_error_handler(function ($severity, $message, $file, $line) {
    // Convert errors to exceptions for consistent handling
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

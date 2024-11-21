<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 9:23 PM
 * .
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * .
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * .
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Jcolombo\PaymoApiPhp\Utility;

use Jcolombo\PaymoApiPhp\Configuration;
use Jcolombo\PaymoApiPhp\Paymo;
use stdClass;

// **** Setting this variable globally right before running a PAYMO API request temporarily turns on logging
// **** There must be a valid logs path and it must be in devMode in the configuration to work.
// **** This is used to have "all" logging off but in dev mode able to toggle logs on/off between requests
// $FORCE_PAYMOAPI_LOGGING = true;

/**
 * Class Log
 * Used for development environments for logging API requests and responses. The API key is removed from the requests
 * for security purposes unless the setting for key storage is enabled.
 *
 * @author Joel Colombo
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class Log
{

  /**
   * @var Log|null Singleton instance of the Log class.
   */
  private static $log_instance = null;

  /**
   * @var string|null Path to the log file.
   */
  protected $log_file_path;

  /**
   * @var resource|null Pointer to the open log file.
   */
  protected $log_file = null;

  /**
   * @var bool Indicates if logging is enabled.
   */
  public $enabled = false;

  /**
   * @var bool Indicates if the application is in development mode.
   */
  public $inDevMode = false;

  /**
   * @const string A separator bar used in log entries.
   */
  const BAR = "=======================================================================================\n";

  /**
   * Checks if logging is enabled for the given Paymo instance.
   *
   * @param Paymo|null $paymo The Paymo instance.
   *
   * @return bool True if logging is enabled, false otherwise.
   */
  public static function enabled(?Paymo $paymo = null) {
    $log = Log::getLog();
    $enabled = $log->enabled;
    $log_it = is_null($paymo) || (is_object($paymo) && $paymo->useLogging);
    $forced = (($log->inDevMode || PAYMO_DEVELOPMENT_MODE) && ($FORCE_PAYMOAPI_LOGGING ?? false));
    if ($enabled && Log::path() && ($log_it || $forced)) {
      return true;
    }

    return false;
  }

  /**
   * Retrieves the path to the log file.
   *
   * @return string|null The log file path.
   */
  public static function path() {
    return Log::getLog()->getLogFile();
  }

  /**
   * Gets the log file pointer.
   *
   * @return resource|null The log file pointer.
   */
  public function getLogFile() {
    return $this->log_file ?? null;
  }

  /**
   * Writes a blank line to the log file if logging is enabled.
   *
   * @param Paymo|null $paymo The Paymo instance.
   *
   * @return void
   */
  public function blank(?Paymo $paymo) {
    if (Log::enabled($paymo)) {
      $this->write('', true);
    }
  }

  /**
   * Logs a message to the log file if logging is enabled.
   *
   * @param Paymo|null  $paymo  The Paymo instance.
   * @param mixed       $msg    The message to log.
   * @param string|null $prefix Optional prefix for the log message.
   * @param string|null $suffix Optional suffix for the log message.
   *
   * @return void
   */
  public function log(?Paymo $paymo, $msg, $prefix = null, $suffix = null) {
    if ($prefix === true) {
      $prefix = Log::BAR;
    }
    if ($suffix === true) {
      $suffix = Log::BAR;
    }
    if (Log::enabled($paymo)) {
      if (is_null($msg)) {
        $this->write('', true);
      }
      $name = is_null($paymo) ? '* Not Connected *' : $paymo->connectionName;
      $dt = date('Y-m-d H:i:s');
      $line_prefix = "[{$dt}] {$name} : ";
      if (!is_null($prefix) && $prefix !== '') {
        $this->write($prefix);
      }
      if (is_string($msg)) {
        $this->write($line_prefix.$msg, true);
      }
      if (is_array($msg)) {
        foreach ($msg as $item) {
          $this->log($paymo, $item);
        }
      }
      if (is_object($msg)) {
        if (isset($msg->type)) {
          $msg->prefix = $line_prefix;
          $msg->spacer = str_repeat(' ', strlen($line_prefix));
          $msg->connectionName = $name;
          switch ($msg->type) {
            case('NEW_CONNECTION'):
              $this->logNewConnection($msg);
              break;
            case('USE_CONNECTION'):
              $this->logUseConnection($msg);
              break;
            case('KILL_CONNECTION'):
              $this->logKillConnection($msg);
              break;
            case('START_REQUEST'):
              $this->logStartRequest($msg);
              break;
            case('GUZZLE_ERROR'):
              $this->logGuzzleError($msg);
              break;
            case('RESPONSE_DONE'):
              $this->logResponseDone($msg);
              break;
          }

          return;
        } else {
          $this->write($line_prefix, true);
          $this->write($msg, true);
        }
      }
      if (!is_null($suffix) && $suffix !== '') {
        $this->write($suffix);
      }
    }
  }

  /**
   * Logs a new connection.
   *
   * @param stdClass $obj The connection object.
   *
   * @return void
   */
  protected function logNewConnection($obj) {
    //$this->write(LOG::BAR);
    $this->write($obj->prefix."****** Create Connection -- ".$obj->connectionName." ******", true);
    if ($obj->data) {
      $this->write($obj->data, true);
    }
    //$this->write(LOG::BAR);
  }

  /**
   * Logs the use of a connection.
   *
   * @param stdClass $obj The connection object.
   *
   * @return void
   */
  protected function logUseConnection($obj) {
    $this->write($obj->prefix."###### Use Connection -- ".$obj->connectionName." ######", true);
    if ($obj->data) {
      $this->write($obj->data, true);
    }
  }

  /**
   * Logs the termination of a connection.
   *
   * @param stdClass $obj The connection object.
   *
   * @return void
   */
  protected function logKillConnection($obj) {
    $this->write($obj->prefix."XXXXXX End Connection -- ".$obj->connectionName." XXXXXX", true);
    if ($obj->data) {
      $this->write($obj->data, true);
    }
    //$this->write(LOG::BAR);
  }

  /**
   * Logs a Guzzle error.
   *
   * @param stdClass $obj The error object.
   *
   * @return void
   */
  protected function logGuzzleError($obj) {
    $this->write($obj->prefix."Guzzle Error...", true);
    $this->write($obj->data, true);
    //$this->write(LOG::BAR);
  }

  /**
   * Logs the start of an API request.
   *
   * @param stdClass $obj The request object.
   *
   * @return void
   */
  protected function logStartRequest($obj) {
    $this->write($obj->prefix."{$obj->data->method} {$obj->data->mode} /{$obj->data->resourceUrl}", true);
    if ($obj->data->data) {
      $this->write($obj->spacer."DATA: ".json_encode($obj->data->data, JSON_UNESCAPED_SLASHES), true);
    }
    if ($obj->data->include) {
      $this->write($obj->spacer."INCLUDE: ".$obj->data->include, true);
    }
    if ($obj->data->where) {
      $this->write($obj->spacer."WHERE: ".$obj->data->where, true);
    }
    if ($obj->data->files) {
      $this->write($obj->spacer."FILES: ".json_encode($obj->data->files, JSON_UNESCAPED_SLASHES), true);
    }
  }

  /**
   * Logs the completion of an API response.
   *
   * @param stdClass $obj The response object.
   *
   * @return void
   */
  protected function logResponseDone($obj) {
    $this->write($obj->prefix."RESPONSE: ", true);
    $this->write($obj->data, false);
    //$this->write(LOG::BAR);
  }

  /**
   * Writes a log entry to the log file.
   *
   * @param mixed $content    The content to write.
   * @param bool  $addNewline Whether to add a newline after the content.
   *
   * @return void
   */
  protected function write($content, $addNewline = false) {
    if (is_bool($content)) {
      $content = !!$content ? 'true' : 'false';
    }

    if ($this->log_file && ($content === '' || $content)) {
      if (is_string($content) || is_numeric($content)) {
        $c = (string) $content;
      } else {
        $c = print_r($content, true);
      }
      fwrite($this->log_file, $c);
      if ($addNewline) {
        fwrite($this->log_file, "\n");
      }
    }
  }

  /**
   * Creates a stdClass object with a type and data.
   *
   * @param string $type The type of the object.
   * @param mixed  $data The data to include in the object.
   *
   * @return stdClass The created object.
   */
  public static function obj(
    $type, $data
  ) : stdClass {
    $obj = new stdClass();
    $obj->type = $type;
    $obj->data = null;
    if (!!$data) {
      $obj->data = (object) $data;
    }

    return $obj;
  }

  /**
   * Retrieves or creates the singleton log instance.
   *
   * @return Log The singleton log instance.
   */
  public static function getLog() {
    if (!isset(self::$log_instance)) {
      self::$log_instance = new static();
    }

    return self::$log_instance;
  }

  /**
   * Private constructor for the Log class.
   * Initializes the log file and settings based on configuration.
   */
  private function __construct() {
    $this->log_file = null;
    $this->enabled = !!Configuration::get('enabled.logging');
    $this->inDevMode = !!Configuration::get('devMode');
    $logPath = Configuration::get('path.logs') ?? null;
    $this->log_file_path = !!$logPath ? $logPath.DIRECTORY_SEPARATOR.'paymo-api.log' : null;
    if ($this->log_file_path) {
      $this->log_file = fopen($this->log_file_path, "a");
    }
    if (!$this->log_file) {
      $this->enabled = false;
    }
  }

  /**
   * Destructor for the Log class.
   * Closes the log file if it is open.
   */
  public function __destruct() {
    if (!is_null($this->log_file)) {
      fclose($this->log_file);
    }
  }
}

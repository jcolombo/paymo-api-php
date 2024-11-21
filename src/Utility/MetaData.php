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

use Exception;
use stdClass;

/**
 * Class MetaData
 *
 * A singleton class to manage metadata using a dynamic property handler.
 */
class MetaData
{
  /**
   * The singleton instance of MetaData.
   *
   * @var MetaData|null
   */
  private static ?MetaData $instance = null;
  /**
  * The datastore for the metadata.
  *
   * @var stdClass|null
   */
  private ?stdClass $_dataStore;

  /**
   * Magic getter method for accessing properties dynamically.
   *
   * @param string $name The name of the property to get.
   * @return mixed|null The value of the property if set, null otherwise.
   */
  public function __get(string $name) {
    if ($name === '_dataStore') {
      return $this->_dataStore ?? null;
    }

    return $this->_dataStore->$name ?? null;
  }

  /**
   * Magic setter method for dynamically setting properties.
   *
   * @param string $name The name of the property to set.
   * @param mixed $value The value to set for the property.
   * @throws Exception If attempting to set the _dataStore property directly.
   */
  public function __set(string $name, $value) : void {
    if ($name === '_dataStore') {
      throw new Exception('Cannot set private property _dataStore');
    }
    if ($this->_dataStore === null) {
      $this->_dataStore = new stdClass();
    }
    $this->_dataStore->$name = $value;
  }

  /**
   * Clears the data in the singleton instance.
   *
   * @return MetaData The singleton instance with cleared data.
   */
  public static function clear() : MetaData {
    if (isset(self::$instance)) {
      self::$instance->_dataStore = new stdClass();
    }

    return self::store();
  }

  /**
   * Retrieves the singleton instance of MetaData.
   *
   * @return MetaData The singleton instance.
   */
  public static function store() : MetaData {
    if (self::$instance === null) {
      self::$instance = new static();
    }

    return self::$instance;
  }

  /**
   * Private constructor to prevent multiple instances.
   */
  private function __construct() {
    $this->_dataStore = new stdClass();
  }

  /**
   * Prevents the singleton instance from being cloned.
   */
  private function __clone() {
    // Prevent cloning of the singleton instance
  }

  /**
   * Prevents the singleton instance from being unserialized.
   *
   * @throws Exception If attempting to unserialize the singleton instance.
   */
  public function __wakeup() {
    throw new Exception("Cannot unserialize singleton");
  }
}

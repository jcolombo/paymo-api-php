<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/9/20, 3:51 PM
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

namespace Jcolombo\PaymoApiPhp\Cache;

/**
 * Class Cache
 * Store and Retrieve cache for requests to avoid excessive remote server requests
 *
 * @package Jcolombo\PaymoApiPhp\Cache
 */

class Cache {

  const DEFAULT_LIFESPAN = 300;

  private static $cache_instance = null;

  protected $system_dst = false;
  protected $request_cache_path = null;

  public $lifespan;
  public $fetch_callback = null;
  public $store_callback = null;

  /*
   * Static method to attempt to fetch a get request of specific settings
   *
   * @param $cache_key A compiled unique key to fetch the data from if it exists
   */
  static public function fetch($cache_key)
  {
    $c = Cache::getCache();
    if (!is_null($c->fetch_callback)) {
      return call_user_func($c->fetch_callback, $cache_key, $c->lifespan);
    }
    $cache_file = $c->request_cache_path."/{$cache_key}";
    if (static::isValidCache($cache_file)) {
      return file_get_contents($cache_file);
    }
    return null;
  }

  /*
   * Static method to store a specific get request results
   *
   * @param $cache_key A compiled unique key to store with as data
   * @param $response_data The response from the API to be stored for retrieval
   */
  static public function store($cache_key, $response_data)
  {
    $c = Cache::getCache();
    if (!is_null($c->store_callback)) {
      return call_user_func($c->store_callback, $cache_key, $response_data, $c->lifespan);
    }
    $cache_file = $c->request_cache_path."/{$cache_key}";
    return (file_put_contents($cache_file, $response_data)!==false);
  }

  /*
   * Get the correct unix timestamp of the last modified time for the passed cache file
   */
  static public function isValidCache($file)
  {
    $c = Cache::getCache();
    $lifespan = $c->lifespan;
    $system_dst = $c->system_dst;
    if (!file_exists($file)) { return false; }
    $filetime = filemtime($file);
    //echo "{$file}\nTime:{$filetime}\n\n";
    if (!$filetime) { return false; }
    $file_dst = (date('I', $filetime) == 1);
    $adjustment = 0;
    if($file_dst == false && $system_dst == true) { $adjustment = 3600; }
    else if($file_dst == true && $system_dst == false) { $adjustment = -3600; }
    $filetime = $filetime + $adjustment;
    $expire_time = time()-$lifespan;
    if ($filetime <= $expire_time) { return false; }
    return true;
  }

  /*
   * Set the current lifespan for cache checks. System uses the last set value for all further calls.
   * If you wish to reset it to default lifespan, simply don't pass the parameter or send it a zero
   *
   * @param $seconds The number of seconds before cache items should be considered obsolete
   */
  static public function lifespan($seconds=0)
  {
    $c = Cache::getCache();
    if ($seconds > 0) { $c->lifespan = $seconds; return; }
    $c->lifespan = static::DEFAULT_LIFESPAN;
  }

  /*
   * Register the callback methods in order to use external functions/objects to handle fetch/store methods
   * - The $cache_key parameter in the callbacks is the generated key name from this object
   * - The $lifespan parameter in the callbacks is a maximum number of seconds this cache should be to be considered valid
   * - The $storage parameter in the store callback is the actual data to be cached when called (vice-versa of course is the data returned by the fetch)
   * If defining one, both must be defined. To wipe out the callbacks, pass in NULL to both parameters.
   *
   * @param $fetch_callback A PHP "callable" definition to be passed 2 parameters... $cache_key and $lifespan
   * @param $store_callback A PHP "callable" definition to be passed 3 parameters... $cache_key, $storage, $lifespan
   */
  static public function registerCacheMethods($fetch_callback, $store_callback)
  {
    if ((is_null($fetch_callback) || is_null($store_callback)) && !(is_null($fetch_callback) && is_null($store_callback))) {
      throw new \Exception("Both callbacks must be defined or both must be NULL. One is NULL, one is not");
    }
    $c = Cache::getCache();
    $c->fetch_callback = $fetch_callback;
    $c->store_callback = $store_callback;
  }

  /*
   * Singleton instance retriever
   */
  public static function getCache() {
    if(!isset(self::$cache_instance)) {
      self::$cache_instance = new static();
    }
    return self::$cache_instance;
  }

  /*
   * Private constructor called by the singleton getCache method
   */
  private function __construct()
  {
    $this->system_dst = (date('I') == 1);
    $this->lifespan = static::DEFAULT_LIFESPAN;
    $this->request_cache_path = null;
    if (defined('PAYMOAPI_REQUEST_CACHE_PATH')) {
      $this->request_cache_path = PAYMOAPI_REQUEST_CACHE_PATH;
      if (!file_exists($this->request_cache_path.'/paymoapi-cache')) {
        mkdir($this->request_cache_path.'/paymoapi-cache');
      }
      $this->request_cache_path = $this->request_cache_path.'/paymoapi-cache';
    }
  }

}

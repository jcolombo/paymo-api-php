<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/9/20, 12:50 PM
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

use Noodlehaus\Config;

/**
 * Class Configuration
 * Wrapper class to add functionality and a singleton model to the Noodlehaus\Config object package
 *
 * @package Jcolombo\PaymoApiPhp
 */
class Configuration
{
    /**
     * The path to the built-in default configuration for the package, entities, and settings
     */
    public const DEFAULT_CONFIGURATION_PATH = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'default.paymoapi.config.json';

    /**
     * The static instance of the Configuration to insure only one copy of the configuration is loaded for the package
     *
     * @var Configuration | null
     */
    private static $instance = null;

    /**
     * The instance of the Noodlehaus config object for handling the configuration management
     *
     * @var Config|null
     */
    private $config = null;

    /**
     * The list of file paths to look for paymo.config.json files. Starts out with just the default included package
     * path Later paths in the array will overload specific sub-sections of the configuration found in earlier paths
     *
     * @var string[]
     */
    private $paths = [self::DEFAULT_CONFIGURATION_PATH];

    /**
     * Configuration constructor.
     * Create the singleton instance and load/overload all configuration options from all the paths to check for config
     * files
     */
    private function __construct()
    {
        $this->paths = [realpath(self::DEFAULT_CONFIGURATION_PATH)];
        $this->config = Config::load($this->paths);
    }

    /**
     * Return the value of the configuration property being requested. Null is returned if the property does not exist.
     *
     * @param string $key A dot notation of the path to the configuration value being sought
     *
     * @return mixed|null Returns whatever exists in the latest loaded configuration json file for this key path
     */
    public static function get($key)
    {
        return self::load()->config->get($key);
    }

    /**
     * The static method that must be called to get (or create) the single instance of the configuration handler
     *
     * @param null|string $path An optional path to a path (FULL DIRECTORY PATH) to search for a paymo.config.json
     *                          file. If left null, will just use the paths already in the list (defaulting to just the
     *                          built-in configuration file)
     *
     * @return Configuration Returns the instance of the configuration class for chaining to get/set/overload methods
     */
    public static function load($path = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        self::$instance->overload($path);

        return self::$instance;
    }

    /**
     * Add a configuration overload path to the set of paths used when loading configuration results. This value will
     * be available on the instance after the NEXT load call
     *
     * @param string | null $path If set to a string, pushes the path into the ->paths list for loading future
     *                 configurations
     */
    public function overload($path = null)
    {
        if (!is_null($path)) {
            $realpath = dirname($path);
            $configFile = $realpath.DIRECTORY_SEPARATOR.'paymoapi.config.json';
            if (!in_array($configFile, $this->paths) && file_exists($configFile)) {
                $this->paths[] = $configFile;
                $this->config = Config::load($this->paths);
            }
        }
    }

    /**
     * Check if the configuration has a value set for a specific key
     *
     * @param string $key Dot notation string to the configuration object property to be checked
     *
     * @return bool If the property exists (and is not null), returns true
     */
    public static function has($key)
    {
        return self::load()->config->has($key);
    }

    /**
     * Manually set the value of configuration property for use during this call.
     * At the current time, values set this way ONLY impact the current in-memory call of this static instance and are
     * lost after the script finishes. These set calls do not overwrite or store in the file system for future loads
     *
     * @param string $key The dot notation of the configuration property you are setting the value to
     * @param mixed  $val The value to be set for the configuration property. Setting it to null will make future calls
     *                    return a false on has and a null on get calls
     *
     * @todo Allow a mechanism to dynamically write modified configuration values to the file system for reuse
     */
    public static function set($key, $val)
    {
        return self::load()->config->set($key, $val);
    }

    /**
     * Reset the configuration object to its initial default state by setting the included configuration path to just
     * the included DEFAULT one
     */
    public function reset()
    {
        $this->paths = [realpath(self::DEFAULT_CONFIGURATION_PATH)];
        Configuration::load();
    }

}
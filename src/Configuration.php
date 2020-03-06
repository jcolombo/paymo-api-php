<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/6/20, 12:11 PM
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

class Configuration
{
    public const DEFAULT_CONFIGURATION_PATH = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'default.paymoapi.config.json';
    private static $instance = null;
    private $config = null;
    private $paths = [self::DEFAULT_CONFIGURATION_PATH];

    private function __construct()
    {
        $this->paths = [realpath(self::DEFAULT_CONFIGURATION_PATH)];
        $this->config = Config::load($this->paths);
    }

    public static function get($key)
    {
        return self::load()->config->get($key);
    }

    public static function load($path = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        self::$instance->overload($path);

        return self::$instance;
    }

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

    public static function has($key)
    {
        return self::load()->config->has($key);
    }

    public static function set($key, $val)
    {
        return self::load()->config->set($key, $val);
    }

    public function reset()
    {
        $this->paths = [realpath(self::DEFAULT_CONFIGURATION_PATH)];
    }

}
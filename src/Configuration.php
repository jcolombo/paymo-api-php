<?php

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
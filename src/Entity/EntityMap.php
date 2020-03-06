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

namespace Jcolombo\PaymoApiPhp\Entity;

use Exception;
use Jcolombo\PaymoApiPhp\Configuration;
use stdClass;

/**
 * Class EntityMap
 *
 * @package Jcolombo\PaymoApiPhp\Entity
 */
class EntityMap
{
    public const CONFIG_PATH = 'classMap.entity.';

    /**
     * @param string           $mapKey
     * @param null|string      $resourceClass
     * @param bool|string|null $collectionClass
     *
     * @throws Exception
     */
    public static function overload($mapKey, $resourceClass = null, $collectionClass = false)
    {
        // Set RESOURCE Class for $mapKey
        $resource = null;
        if (is_string($resourceClass)) {
            $resource = $resourceClass;
            if (Configuration::get('devMode') && class_exists($resourceClass)) {
                if (!is_subclass_of($resourceClass, "Jcolombo\PaymoApiPhp\Entity\AbstractResourcce")) {
                    throw new Exception("Overload [{$mapKey}] entity failed. {$resourceClass} does not extend PaymoApiPhp AbstractResource.");
                }
            } else {
                throw new Exception("Overloading an entity [{$mapKey}] requires a valid class name. Given: {$resourceClass}");
            }
        }
        if ($resource) {
            Configuration::set(self::CONFIG_PATH.$mapKey.'.resource', $resource);
        }

        // Set COLLECTION Class for $mapKey
        if ($collectionClass !== false && ($collectionClass === true || is_string($collectionClass) || is_null($collectionClass))) {
            $collection = $collectionClass;

            if (Configuration::get('devMode') && is_string($collectionClass)) {
                if (class_exists($collectionClass)) {
                    if (!is_subclass_of($collectionClass, "Jcolombo\PaymoApiPhp\Entity\AbstractCollection")) {
                        throw new Exception("Overload [{$mapKey}] collection failed. {$collectionClass} does not extend PaymoApiPhp AbstractCollection.");
                    }
                } else {
                    throw new Exception("Overloading a collection [{$mapKey}] requires a valid class name. Given: {$collectionClass}");
                }
            }

            if ($collection) {
                Configuration::set(self::CONFIG_PATH.$mapKey.'.collection', $collection);
            }
        }
    }

    public static function entity($key, $strict = false)
    {
        $key = self::extractKey($key);
        if (is_string($key)) {
            if (Configuration::has(self::CONFIG_PATH.$key)) {
                $object = new stdClass();
                $object->resource = self::resource($key);
                $object->collection = self::collection($key);

                return $object;
            }
        }
        if ($strict) {
            throw new Exception("[$key] does not have a configured entity defined");
        }

        return null;
    }

    public static function extractKey($key)
    {
        if (!is_string($key)) {
            return null;
        }
        if (strpos($key, ':')) {
            $parts = explode(':', $key, 2);
            if (self::exists(($parts[1]))) {
                return $parts[1];
            }
        }

        return $key;
    }

    public static function exists($key)
    {
        $key = self::extractKey($key);
        if (!is_string($key)) {
            return false;
        }

        return Configuration::has(self::CONFIG_PATH.$key);
    }

    public static function resource($key, $strict = false)
    {
        $key = self::extractKey($key);
        if ($strict && (!is_string($key) || !Configuration::has(self::CONFIG_PATH.$key.'.resource'))) {
            throw new Exception("[$key] does not have a configured resource class defined");
        }

        return Configuration::get(self::CONFIG_PATH.$key.'.resource');
    }

    public static function collection($key, $strict = false)
    {
        $key = self::extractKey($key);
        $cClass = null;
        if (is_string($key)) {
            $cClass = Configuration::get(self::CONFIG_PATH.$key.'.collection');
            if ($cClass === true) {
                $cClass = Configuration::get('classMap.defaultCollection');
            }
        }
        if ($strict && !$cClass) {
            throw new Exception("[$key] does not have a configured collection class defined");
        }

        return $cClass;
    }

}
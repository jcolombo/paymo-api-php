<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/6/20, 5:40 PM
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
    /**
     * The root path in the configuration file where the list of entities can be located
     * Used to simplify the code in each this classes methods
     */
    public const CONFIG_PATH = 'classMap.entity.';

    /**
     * Programmatic way to overload the classes used for the entities (resources and collections)
     * The changes are only modified for the duration of the script, they are not replaced permanently
     * To make permanent changes the map. Create a custom configuration file for the connection to map new classes to
     * entities
     *
     * @param string           $mapKey          The map key to reassign custom classes to.
     * @param null|string      $resourceClass   The fully namespaced class name that should replace the default
     *                                          resource object
     * @param bool|string|null $collectionClass The fully namespaced class name that should replace the default
     *                                          collection object. If set to TRUE will use the global default
     *                                          collection entity
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

    /**
     * Return the formatted entity configuration for a specific key (project, client, clients, etc)
     *
     * @param string $key    The entity key to be looked up
     * @param bool   $strict If set to true, throws an exception if the entity does not have a defined block in the
     *                       configuration. If not strict (default), simply returns null to be tested against by the
     *                       caller
     *
     * @throws Exception
     * @return stdClass|null
     */
    public static function entity($key, $strict = false)
    {
        $key = self::extractKey($key);
        if (is_string($key)) {
            if (Configuration::has(self::CONFIG_PATH.$key)) {
                $object = new stdClass();
                $object->type = Configuration::get(self::CONFIG_PATH.$key.'.type');
                $object->mappedKeys = self::mapKeys($key);
                $object->resource = self::resource($object->mappedKeys->resource ?? $key);
                $object->collection = self::collection($object->mappedKeys->collection ?? $key);

                return $object;
            }
        }
        if ($strict) {
            throw new Exception("[$key] does not have a configured entity defined");
        }

        return null;
    }

    /**
     * Look up the entity to see if it refers to a different entity for its resource and/or collection classes
     * If it does, it returns then as string names of the referenced keys. If not, they are null
     *
     * @param string $key The entity key to be looked up
     *
     * @return stdClass Returns a generic object with a ->resource and ->collection property (each is a string or null)
     */
    public static function mapKeys($key) {
        $map = new stdClass();
        $map -> resource = null;
        $map -> collection = null;
        $key = self::extractKey($key);
        if (is_string($key)) {
            if (Configuration::has(self::CONFIG_PATH.$key)) {
                $map = new stdClass();
                $map->resource = Configuration::get(self::CONFIG_PATH.$key.'.resourceKey');
                $map->collection = Configuration::get(self::CONFIG_PATH.$key.'.collectionKey');
            }
        }
        return $map;
    }

    /**
     * Strips out any ":" prefixes before an entity name, some aspects of the program store entities with a prefix
     * Primarily used when testing against property types that define things like "projects"=>"collection:projects"
     * or "client"=>"resource:client", etc.
     *
     * @param string $key The key to break down into its entity name
     *
     * @return string|null Returns the barebones entity key
     */
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

    /**
     * Check of the configuration has an entry for the $key entity
     *
     * @param string $key The key of the entity to check on
     *
     * @return bool If it exists or not in the configuration
     */
    public static function exists($key)
    {
        $key = self::extractKey($key);
        if (!is_string($key)) {
            return false;
        }

        return Configuration::has(self::CONFIG_PATH.$key);
    }

    /**
     * Get the resource class for the $key entity
     *
     * @param string $key    The entity key to be looked up
     * @param bool   $strict If set to true, throws an exception if the entity does not have a defined block in the
     *                       configuration. If not strict (default), simply returns null to be tested against by the
     *                       caller
     *
     * @throws Exception
     * @return string|null Returns the class name of the entity "resource" (single entity). Null if there is no
     *                     resource defined
     */
    public static function resource($key, $strict = false)
    {
        $key = self::extractKey($key);
        $resourceKey = Configuration::get(self::CONFIG_PATH.$key.'.resourceKey');
        $resource = Configuration::has(self::CONFIG_PATH.$key.'.resource');
        if (!$resource && $resourceKey) {
            $key = $resourceKey;
        }
        if ($strict && (!is_string($key) || !Configuration::has(self::CONFIG_PATH.$key.'.resource'))) {
            throw new Exception("[$key] does not have a configured resource class defined");
        }

        return Configuration::get(self::CONFIG_PATH.$key.'.resource');
    }

    /**
     * Get the collection class for the $key entity
     * If the collection configuration is set to a boolean true, the global default collection class is returned
     *
     * @param string $key    The entity key to be looked up
     * @param bool   $strict If set to true, throws an exception if the entity does not have a defined block in the
     *                       configuration. If not strict (default), simply returns null to be tested against by the
     *                       caller
     *
     * @throws Exception
     * @return string|null Returns the class name of the entity "collection" (single entity). Null if there is no
     *                     collection defined
     */
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
        $collectionKey = Configuration::get(self::CONFIG_PATH.$key.'.collectionKey');
        if (!$cClass && $collectionKey) {
            $cClass = self::collection($collectionKey);
        }
        if ($strict && !$cClass) {
            throw new Exception("[$key] does not have a configured collection class defined");
        }

        return $cClass;
    }

}
<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/9/20, 3:44 PM
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
use GuzzleHttp\Exception\GuzzleException;
use Jcolombo\PaymoApiPhp\Configuration;
use Jcolombo\PaymoApiPhp\Entity\Collection\EntityCollection;
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Request;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;

/**
 * Class AbstractResource
 *
 * @package Jcolombo\PaymoApiPhp\Entity
 */
abstract class AbstractResource extends AbstractEntity
{

    /**
     * Any child classes must define the list of constants in this array
     */
    public const REQUIRED_CONSTANTS = [
        'LABEL', 'API_PATH', 'API_ENTITY', 'REQUIRED_CREATE', 'READONLY', 'INCLUDE_TYPES', 'PROP_TYPES', 'WHERE_OPERATIONS'
    ];

    /**
     * The current values for the defined props for this instance of the resource
     *
     * @var array
     */
    protected $props = [];

    /**
     * A list of values with associative keys for "props" set with values that are NOT valid maps properties or includes
     *
     * @var array
     */
    protected $unlisted = [];

    /**
     * The values of the props as set after a clean load from the database (this is reset every time the resource is
     * loaded from the API
     *
     * @var array
     */
    protected $loaded = [];

    /**
     * An array of keys that have valid "included" resources for this object as defined by the valid include constant
     * of this resource type
     *
     * @var array
     */
    protected $included = [];

    /**
     * The default Resource constructor
     * Requires a Paymo connection instance or attempts to find/create one.
     * When in development mode, will validate the object class has all required defined constants
     *
     * @param array | Paymo | string | null $paymo Either an API Key, Paymo Connection, config settings array (from
     *                                             another entitied getConfiguration call), or null to get first
     *                                             connection available
     *
     * @throws Exception
     */
    public function __construct($paymo = null)
    {
        parent::__construct($paymo);
        if (Configuration::get('devMode')) {
            $missingConstants = [];
            foreach (self::REQUIRED_CONSTANTS as $k) {
                $classname = get_class($this);
                if (!constant($classname.'::'.$k)) {
                    $missingConstants[] = $k;
                }
            }
            if (count($missingConstants) > 0) {
                throw new Exception("Attempting to create malformed Entity. Missing class CONSTANTS for '".implode("', '",
                                                                                                                   $missingConstants)."'");
            }
        }

        return $this;
    }

    /**
     * Static method to always create a resource or collection using the currently configured mapped class in Entity
     * Map
     * NOTE: Using this method to factory create your class will void IDE typehinting when developing (as it doesnt
     * know what class will return)
     *
     * @param array | Paymo | string | null $paymo Either an API Key,
     *                                             Paymo Connection, config settings array (from another entitied
     *                                             getConfiguration call), or null to get first connection available
     *
     * @throws Exception
     * @return AbstractResource
     */
    public static function new($paymo = null)
    {
        return parent::new($paymo);
    }

    /**
     * Static method to build a collection object for selecting and holding a list of this particular resource type
     * This can be chained from directly to easily load a list in one line.
     * Ex: Project::list()->fetch()  Would return a list of ALL projects with ALL its data that this API connection has
     * access to.
     *
     * @param array | Paymo | string | null $paymo Either an API Key, Paymo Connection, config settings array (from
     *                                             another entitied getConfiguration call), or null to get first
     *                                             connection available
     *
     * @throws Exception
     * @return EntityCollection An empty EntityCollection instantiated based on the entityMap configuration for this
     *                          key
     */
    public static function list($paymo = null)
    {
        $entityKey = static::API_ENTITY;
        $cClass = EntityMap::collection($entityKey);
        if (!$cClass) {
            throw new Exception("Attempting to create a list for {$entityKey} without a configured entity map for the collection class defined");
        }
        $mappedKeys = EntityMap::mapKeys($entityKey);
        $entityKey = $mappedKeys->collection ?? $entityKey;

        return new $cClass($entityKey, $paymo);
    }

    /**
     * Class specific wrapper to create and validate the props and includes of the where condition for a specific
     * entity resource
     *
     * @param        $prop     {@see RequestCondition::where()}
     * @param        $value    {@see RequestCondition::where()}
     * @param string $operator {@see RequestCondition::where()}
     * @param bool   $validate {@see RequestCondition::where()}
     *
     * @throws Exception
     * @return RequestCondition
     */
    public static function where($prop, $value, $operator = '=', $validate = true)
    {
        return RequestCondition::where($prop, $value, $operator, $validate, static::API_ENTITY);
    }

    /**
     * Class specific wrapper to create and validate the props and includes of the HAS filter for a specific
     * entity resource
     *
     * @param string $include  {@see RequestCondition::has()}
     * @param int    $count    {@see RequestCondition::has()}
     * @param string $operator {@see RequestCondition::has()}
     *
     * @throws Exception
     * @return RequestCondition
     */
    public static function has($include, $count = 0, $operator = '>')
    {
        return RequestCondition::has($include, $count, $operator, static::API_ENTITY);
    }

    /**
     * Manual call in place of the direct magic method setter, allows for bulk property setting as array
     *
     * @param string | array $key   Either a prop key or an associative array of prop key=>value combinations
     * @param null           $value If $key is an array, this is ignored. Otherwise its used to set the value of $key
     *                              prop
     *
     * @throws Exception
     * @return AbstractResource Returns the object itself for optional object chaining
     */
    public function set($key, $value = null)
    {
        if (is_string($key)) {
            $this->__set($key, $value);
        } elseif (is_array($key)) {
            foreach ($key as $k => $v) {
                if (is_string($k)) {
                    $this->set($k, $v);
                }
            }
        }

        return $this;
    }

    /**
     * Overwrite the loaded values with the current values, thereby resetting the dirty state on all props
     * WARNING: Washing the object loses the last loaded or saved values and assumes the current values are
     * clean and saved via some other means. Entities are always auto-washed after loading and hydration is
     * complete
     *
     * @return AbstractResource Returns the object itself for optional object chaining
     */
    public function wash()
    {
        $this->loaded = $this->props;

        return $this;
    }

    public function relate($key, $object, $index = null)
    {
        // Find the object type for $key if its an array include use the associative index
        return $this;
    }

    /**
     * If enabled, will prevent API fetch calls from overwriting any current prop values if they are dirty
     * By default, API loads will overwrite any data in this object even if its dirty and unsaved
     *
     * @param bool $protect Set to true and any attempt to overwrite dirty props will throw an error
     *
     * @return AbstractResource Returns the object itself for optional object chaining
     */
    public function protectDirtyOverwrites($protect = true)
    {
        $this->overwriteDirtyWithRequests = !$protect;

        return $this;
    }

    /**
     * Call this with a TRUE value to always load from API (ignoring cache if it exists)
     * This will still STORE the cache results if Caching is enabled on the connection
     * Setting this back to false on the entity will re-enable caching if its ON in the connection
     * By default all entities will try to use cache if connection cache is set to true
     *
     * @param bool $ignore The setting for this objects cache use override
     *
     * @return AbstractResource Returns the object itself for optional object chaining
     */
    public function ignoreCache($ignore = true)
    {
        $this->useCacheIfAvailable = !$ignore;

        return $this;
    }

    /**
     * Execute an API call to populate this object with data based on a single ID for this entity type
     *
     * @param int | null $id         The ID to use to populate this object. If null, it uses the existing prop ID, if
     *                               still no value... will throw an Exception
     * @param string[]   $fields     An array of string props and/or include entities to get from the API call
     *
     * @throws Exception
     * @throws GuzzleException
     * @return AbstractResource Returns the instance of itself for chaining method potential
     */
    public function fetch($id = null, $fields = [])
    {
        if (is_null($id) && isset($this->props['id'])) {
            $id = $this->props['id'];
        }
        $label = $this::LABEL;
        if (!$id || (int) $id < 1) {
            throw new Exception("Attempted to fetch a {$label} without an id being passed");
        }
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $this->validateFetch($fields);
        if (!$this->overwriteDirtyWithRequests && $this->isDirty()) {
            $label = $this::LABEL;
            throw new Exception("{$label} attempted to fetch new data while it had dirty fields and protection is enabled.");
        }
        [$select, $include] = static::cleanupForRequest($this::API_ENTITY, $fields);
        $response = Request::fetch($this->connection, $this::API_PATH, $id,
                                   ['select' => $select, 'include' => $include]);
        if ($response->result) {
            $this->_hydrate($id, $response->result);
        }

        return $this;
    }

    /**
     * Check if there is at least one dirty key that doesnt match the last loaded or saved value
     *
     * @param bool $checkRelations If true, will look at and check all "included" entities recursively
     *
     * @return bool
     */
    public function isDirty($checkRelations = false)
    {
        $dirtySelf = count($this->getDirtyKeys()) > 0;
        if ($checkRelations) {
            $dirtyInclude = false;
            foreach ($this->included as $k => $v) {
                if (is_object($v)) {
                    $dirtyInclude = $v->isDirty(true);
                } elseif (is_array($v)) {
                    foreach ($v as $d) {
                        $dirtyInclude = $d->isDirty(true);
                        if ($dirtyInclude) {
                            return true;
                        }
                    }
                }
                if ($dirtyInclude) {
                    return true;
                }
            }
        }

        return $dirtySelf;
    }

    /**
     * Return a list of strings for the prop keys that dont match the last loaded or saved values
     *
     * @return string[] Array of prop string keys
     */
    public function getDirtyKeys()
    {
        $keys = [];
        foreach ($this->loaded as $k => $v) {
            if (isset($this->props[$k]) && $this->props[$k] !== $v) {
                $keys[] = $k;
            }
        }

        return $keys;
    }

    /**
     * Internal method to populate this object with the results from a paymo API call
     * This method is only exposed publicly to allow other hydration calls to propagate the process
     * It is not intended to be called directly (unless passing in the raw object results from a call elsewhere)
     *
     * @param int    $objectId       The ID of this object being populated
     * @param object $responseObject The standard object returned from the API call
     *
     * @throws Exception
     */
    public function _hydrate($objectId, $responseObject)
    {
        if (is_object($responseObject)) {
            $this->clear();
            $this->hydrationMode = true;
            $this->props['id'] = $objectId;
            foreach ($responseObject as $k => $v) {
                if ($this::isIncludable($this::API_ENTITY, $k)) {
                    $this->_hydrateInclude($k, $v);
                } else {
                    $this->__set($k, $v);
                }
            }
            $this->hydrationMode = false;
            $this->loaded = $this->props;
        }
    }

    /**
     * Resets the object to empty. Keeps any settings but clears the data from the props,
     * unlisted, loaded, and included collections.
     *
     * @return AbstractResource Returns the object itself for optional object chaining
     */
    public function clear()
    {
        $this->props = [];
        $this->unlisted = [];
        $this->loaded = [];
        $this->included = [];

        return $this;
    }

    /**
     * Supporting method to populate "include" hydration when child objects or lists are included in the response
     *
     * @param string         $entityKey string The valid include key from the INCLUDE_TYPES constant to be populated
     * @param object | array $object    The single include object or an array of objects depending on the key type
     *
     * @throws Exception If an entity class definition cannot be found for the provided key
     */
    private function _hydrateInclude($entityKey, $object)
    {
        $entityObject = EntityMap::entity($entityKey);
        $isCollection = !!$entityObject && $entityObject->type == 'collection' && !!$entityObject->collection;
        $className = $entityObject->resource;
        $result = null;
        if ($isCollection) {
            $collectionClass = $entityObject->collection;
            /** @var AbstractCollection $result */
            $result = new $collectionClass($entityKey, $this->getConfiguration());
            $result->_hydrate($object);
        } else {
            /** @var AbstractResource $result */
            $result = new $className($this->getConfiguration());
            $result->_hydrate($object->id, $object);
        }
        $this->included[$entityKey] = $result;
    }

    public function create()
    {
        foreach ($this::REQUIRED_CREATE as $k) {
            if (!isset($this->props[$k])) {
                $label = $this::LABEL;
                throw new Exception("Paymo: Creating a '{$label}' requires a value for '{$k}'");
            }
        }
        $createWith = $this->props;
        // Loop through read only props and strip them from $createWith;
        // Add warning if any READONLY props were set and create is attempted (flag to ignore this)
        // Create with REQUEST (POST)
        // Create any children that are possible
        // Hydrate this object
        // Reset loaded
        return true; // on Success
    }

    public function update($updateRelations = false)
    {
        $update = $this->props;
        foreach ($this::READONLY as $k) {
            unset($update[$k]);
        }
        // Compare fields in $update with $this->loaded and only post the dirty items
        // If $updateRelations, attempt to update() all children, true=ALL, number 1+ depth of relations
        // Save to DB with REQUEST (PUT)
        // Traverse and save hydrated children if modified as well
        // Update / Hydrate object with changes in response
        // Reset $this->loaded to current values
        return true; // on Success
    }

    public function delete($id = null)
    {
        if (is_null($id) && isset($this->props['id'])) {
            $id = $this->props['id'];
        }
        if (!$id || (int) $id < 1) {
            $label = $this::LABEL;
            throw new Exception("Attempted to delete a {$label} without an id being passed");
        }
        // Delete project with REQUEST (DELETE)
        $this->clear();

        return true; // on Success
    }

    /**
     * Return all values that exist in the unlisted collection
     *
     * @return mixed[]
     */
    public function unlisted()
    {
        return $this->unlisted;
    }

    /**
     * Return all the current values of the defined object props as an associative array
     * Passing a true parameter will check all defined keys (as null) even if they are not currently set
     *
     * @param bool $includeAll If true, will check the defined propType and return them as NULL if not set
     *
     * @return mixed[]
     */
    public function props($includeAll = false)
    {
        $props = $this->props;
        if ($includeAll) {
            $diff = array_diff_key($this::PROP_TYPES, $props);
            foreach ($diff as $k) {
                if (!isset($props[$k])) {
                    $props[$k] = null;
                }
            }
        }

        return $props;
    }

    /**
     * Return an array of the current prop values that do not match the last loaded or saved value
     *
     * @return array[] A multidimensional array with prop as keys and a 2 part assoc array for each key [original,
     *                 current]
     */
    public function getDirtyValues()
    {
        $keys = $this->getDirtyKeys();
        $values = [];
        foreach ($keys as $k) {
            $values[$k] = [
                'original' => isset($this->loaded[$k]) ? $this->loaded[$k] : null,
                'current' => isset($this->props[$k]) ? $this->props[$k] : null
            ];
        }

        return $values;
    }

    /**
     * Magic getter method
     * Will check the class props, unlisted and included object properties
     *
     * @param string $name Object property getter key
     *
     * @return mixed | null
     */
    public function __get($name)
    {
        if (key_exists($name, $this::PROP_TYPES)) {
            return isset($this->props[$name]) ? $this->props[$name] : null;
        } elseif (key_exists($name, $this->unlisted)) {
            return $this->unlisted[$name];
        } elseif (key_exists($name, $this->included)) {
            return $this->included[$name];
        }

        return null;
    }

    /**
     * Magic setter method
     * If the setter cannot find the key in the valid props, it will add the value to the "unlisted" array
     *
     * @param string $name  Object property to attempt magic setting
     * @param mixed  $value The value to attempt to set
     *
     * @throws Exception
     * @return void
     */
    public function __set($name, $value)
    {
        if ($this::isProp($this::API_ENTITY, $name)) {
            if ($this->hydrationMode || !in_array($name, $this::READONLY)) {
                $this->props[$name] = $value;
            }
        } else {
            $this->unlisted[$name] = $value;
        }
        // allow setting of a child included value
    }

}
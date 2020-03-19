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

namespace Jcolombo\PaymoApiPhp\Entity;

use Exception;
use Jcolombo\PaymoApiPhp\Cache\ScrubCache;
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Utility\Converter;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;

/**
 * Class AbstractEntity
 *
 * @package Jcolombo\PaymoApiPhp\Entity
 */
abstract class AbstractEntity
{
    /**
     * The valid possible operators usable in WHERE clauses when selecting lists of entities
     */
    public const VALID_OPERATORS = ['=', '!=', '<', '<=', '>', '>=', 'like', 'not like', 'in', 'not in', 'range'];

    /**
     * An array of resource keys that do not have an ID to be checked when fetching / updating
     */
    public const SKIP_ID_FETCH_UPDATE = ['company'];

    /**
     * A temporary boolean used automatically by the class to allow population of readonly props during API responses
     *
     * @var bool
     */
    protected $hydrationMode = false;

    /**
     * The instance of the Paymo connection used for this object
     *
     * @var Paymo|null
     */
    protected $connection = null;

    /**
     * Flag setting if true will automatically overwrite any existing data in this object when a new FETCh is called
     * If set to false, the object will throw an error if data was "manually" changed since the last load but not saved
     * before attempting to load new data.
     *
     * @var bool
     */
    protected $overwriteDirtyWithRequests = true;

    /**
     * Decide if this object should use cache on its fetch calls. Requires system wide caching also be enabled or its
     * always treated as false
     *
     * @var bool
     */
    protected $useCacheIfAvailable = true;

    /**
     * The default Entity constructor
     * Requires a Paymo connection instance or attempts to find/create one.
     *
     * @param array | Paymo | string | null $paymo Either an API Key, Paymo Connection, config settings array (from
     *                                             another entitied getConfiguration call), or null to get first
     *                                             connection available
     *
     * @throws Exception
     */
    public function __construct($paymo = null)
    {
        $connection = $paymo;
        // If its a configuration array param with at least a connection property... set the configuration manually
        // A configuration array MUST pass at minimum a connection property (it can be a string, null, or Paymo object)
        if (is_array($paymo) && isset($paymo['connection'])) {
            $connection = $paymo['connection'];
            $this->setConfiguration($paymo);
        }
        // Test the connection is a valid value
        if (is_null($connection)) {
            $this->connection = Paymo::connect();
        } elseif (is_string($connection)) {
            $this->connection = Paymo::connect($connection);
        } elseif (is_object($connection)) {
            $this->connection = $connection;
        } else {
            throw new Exception("No Connection Provided, Be sure you have connected with a Paymo::connect() call before using the API entities");
        }

        return $this;
    }

    /**
     * This method is used by other AbstractEntities to clone the settings for inheritance when dynamically creating new
     * objects from hydration methods.
     *
     * @param array $configurationArray Set of protected properties to be forced into specific values
     *
     * @throws Exception
     */
    public function setConfiguration($configurationArray)
    {
        if (!is_array($configurationArray)) {
            throw new Exception("Cloning configuration requires a single associative array or object passed to it");
        }
        if (isset($configurationArray['connection']) && is_a($configurationArray['connection'],
                                                             'Jcolombo\PaymoApiPhp\Paymo')) {
            $this->connection = $configurationArray['connection'];
        }
        if (isset($configurationArray['overwriteDirtyWithRequests'])) {
            $this->overwriteDirtyWithRequests = $configurationArray['overwriteDirtyWithRequests'];
        }
        if (isset($configurationArray['useCacheIfAvailable'])) {
            $this->useCacheIfAvailable = $configurationArray['useCacheIfAvailable'];
        }
    }

    /**
     * Static method to always create a resource or collection using the currently configured mapped class  in Entity
     * Map NOTE: Using this method to factory create your class will void IDE typehinting when developing (as it doesnt
     * know what class will return)
     *
     * @param null       $paymo                    * @param array | Paymo | string | null $paymo Either an API Key,
     *                                             Paymo Connection, config settings array (from another entitied
     *                                             getConfiguration call), or null to get first connection available
     * @param int | null $id                       An optional ID to pre-populate the ID property of the object
     *
     * @throws Exception
     * @return AbstractResource
     */
    public static function newResource($paymo = null, $id = null)
    {
        $realClass = EntityMap::resource(static::API_ENTITY);
        if (is_null($realClass)) {
            throw new Exception("No class found in the Entity Mapp for creating a {static::LABEL} with key '{static::API_ENTITY}'");
        }

        //@todo Anyone with ideas on how a simple way to typehint the return type correctly for IDE's (like PhpStorm). Minor concern.
        return new $realClass($paymo, $id);
    }

    /**
     * Static method to always create a resource or collection using the currently configured mapped class  in Entity
     * Map NOTE: Using this method to factory create your class will void IDE typehinting when developing (as it doesnt
     * know what class will return)
     *
     * @param null $paymo                          * @param array | Paymo | string | null $paymo Either an API Key,
     *                                             Paymo Connection, config settings array (from another entitied
     *                                             getConfiguration call), or null to get first connection available
     *
     * @throws Exception
     * @return AbstractCollection
     */
    public static function newCollection($paymo = null)
    {
        $realClass = EntityMap::resource(static::API_ENTITY);
        if (is_null($realClass)) {
            throw new Exception("No class found in the Entity Mapp for creating a {static::LABEL} with key '{static::API_ENTITY}'");
        }

        //@todo Anyone with ideas on how a simple way to typehint the return type correctly for IDE's (like PhpStorm). Minor concern.
        return new $realClass($paymo);
    }

    /**
     * Determine if a key is either a prop or an include type on a specific entity
     *
     * @param string $entityKey     The entity key to use in look up
     * @param string $propOrInclude The key that is being checked for allowance as either a prop or an include
     *
     * @throws Exception
     * @return bool
     */
    public static function isSelectable($entityKey, $propOrInclude = null)
    {
        if (is_null($propOrInclude)) {
            [$entityKey, $propOrInclude] = EntityMap::extractResourceProp($entityKey);
        }
        $entityResource = EntityMap::resource($entityKey);

        return !!$entityResource && (self::isProp($entityKey, $propOrInclude)
                || self::isIncludable($entityKey, $propOrInclude));
    }

    /**
     * Check if a specific entity has a valid property type allowed
     *
     * @param string      $entityKey The entity key to use in look up
     * @param null|string $propKey
     *
     * @throws Exception
     * @return bool
     */
    public static function isProp($entityKey, $propKey = null)
    {
        if (is_null($propKey)) {
            [$entityKey, $propKey] = EntityMap::extractResourceProp($entityKey);
        }
        $entityResource = EntityMap::resource($entityKey);

        return !!$entityResource && isset($entityResource::PROP_TYPES[$propKey]);
    }

    /**
     * Check if a specific entity has a valid include entity or collection allowed
     *
     * @param string $entityKey  The entity key to use in look up
     * @param string $includeKey The include key that is being checked for allowance
     *
     * @throws Exception
     * @return bool
     */
    public static function isIncludable($entityKey, $includeKey)
    {
        if (is_null($includeKey)) {
            [$entityKey, $includeKey] = EntityMap::extractResourceProp($entityKey);
        }
        $entityResource = EntityMap::resource($entityKey);

        return !!$entityResource && isset($entityResource::INCLUDE_TYPES[$includeKey]);
    }

    /**
     * Check if a specific WHERE limit operator is allowed on a specific entity resource property
     * If the value passed does not match the valid data type for a specific property it will return the error message
     * If it is valid and has a valid value, it will return true
     *
     * @param string $entityKey The resource key to check for the property with any operator restrictions
     * @param string $operator  The operator to check for valid use
     * @param mixed  $value     The value to be used in the where condition (validated against the expected prop type)
     *
     * @throws Exception
     * @return bool | string A boolean TRUE if all passed. Otherwise a string message for throwing in an Exception by
     *              caller
     */
    public static function allowWhere($entityKey, $operator, $value = null)
    {
        [$key, $prop] = EntityMap::extractResourceProp($entityKey);
        /** @var AbstractEntity $entityResource */
        $entityResource = EntityMap::resource($key);
        $ops = !!$entityResource ? $entityResource::INCLUDE_TYPES : [];
        $isProp = self::isProp($key, $prop);
        $whereValid = true;

        if ($isProp && !isset($ops[$prop])) {
            $whereValid = true;
        }
        if ($isProp && array_key_exists($prop, $ops) && is_null($ops[$prop])) {
            return "Property {$entityKey} cannot be used in WHERE conditions";
        }
        if ($isProp && array_key_exists($prop, $ops)) {
            $allowed = in_array($operator, $ops[$prop]);
            $notAllowed = in_array($operator, $ops['!'.$prop]);
            if (!$allowed || $notAllowed) {
                return "Property {$entityKey} cannot use the {$operator} operator.";
            }
        }
        if ($whereValid && !is_null($value)) {
            $datatype = static::getPropertyDataType($key, $prop);
            $primitive = Converter::getPrimitiveType($datatype);
            $enum = null;
            if (strpos($primitive, 'string::') === 0 || strpos($primitive, 'integer::') === 0) {
                $enum = explode('|', array_pop(explode('::', $primitive, 2)));
                $primitive = 'string';
            }
            if (in_array($operator, ['in', 'not in', 'range'])) {
                if (!is_array($value)) {
                    $value = [$value];
                }
                foreach ($value as $v) {
                    $valid_value = gettype($v) == $primitive;
                    if ($primitive == 'timestamp') {
                        $valid_value = gettype($v) == 'string' || gettype($v) == 'integer';
                    }
                    if ($primitive === 'string' && !is_null($enum) && is_array($enum)) {
                        if (!in_array($v.'', $enum)) {
                            return "WHERE: {$entityKey} property value \"{$v}\" does not meet list restrictions of allowed options [".implode(', ',
                                                                                                                                              $enum)."]";
                        }
                    }
                    if (!$valid_value) {
                        return "WHERE: {$entityKey} {$operator} expects array[{$primitive}] but got ".gettype($v).": {$v}";
                    }
                }
            } else {
                if (is_array($value)) {
                    return "WHERE: {$entityKey} {$operator} received an array, expected {$primitive}";
                }
                $valid = gettype($value) == $primitive;
                if ($primitive === 'string' && !is_null($enum) && is_array($enum)) {
                    if (!in_array($value.'', $enum)) {
                        return "WHERE: {$entityKey} property value \"{$value}\" does not meet list restrictions of allowed options [".implode(', ',
                                                                                                                                              $enum)."]";
                    }
                }
                if (!$valid) {
                    if ($primitive == 'timestamp') {
                        $valid = gettype($value) == 'string' || gettype($value) == 'integer';
                    }
                    if (!$valid) {
                        return "WHERE: {$entityKey} {$operator} expects {$primitive} but got ".gettype($value).": {$value}";
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get the defined property datatype (non PHP official) for the resource entity
     *
     * @param string $entityKey The entity to get the prop type from
     * @param string $prop      The property on $entityKey resource to get the datatype from
     *
     * @throws Exception
     * @return string | null Either the string name of the internal property type... or null if the property cant be
     *                found defined on the resource
     */
    public static function getPropertyDataType($entityKey, $prop)
    {
        $resClass = EntityMap::resource($entityKey);
        if ($resClass && self::isProp($entityKey, $prop)) {
            return $resClass::PROP_TYPES[$prop] ?? null;
        }

        return null;
    }

    /**
     * Scrub the fields and where conditional arrays to validate content
     *
     * @param string             $entityKey    An the short reference key name for the entity resource being cleaned up
     * @param string[]           $fields       An array of strings for props and includes to return on each base object
     * @param RequestCondition[] $whereFilters An array of RequestConditions to send to the API when getting lists
     *
     * @throws Exception
     * @return array Contains an item for $select, $include, and $where scrubbed for use by Request calls
     */
    protected static function cleanupForRequest($entityKey, $fields = [], $whereFilters = [])
    {
        $select = [];
        $include = [];
        foreach ($fields as $k) {
            if (self::isProp($entityKey, $k)) {
                $select[] = $k;
            } else {
                $include[] = $k;
            }
        }
        if (count($select) > 0 && !in_array('id', $select)) {
            $select[] = 'id';
        }
        $include = static::scrubInclude($include, $entityKey);
        $where = static::scrubWhere($whereFilters, $entityKey);

        return [$select, $include, $where];
    }

    /**
     * Clean up an array of include items to validate they exist and are allowed.
     * Will recursively traverse the include array and check each level and all sub-levels
     *
     * @param string[] $include   Array of strings to scrub for valid set of allowed props
     * @param string   $entityKey The root entity key to compare validation of the $include rules to
     *
     * @throws Exception
     * @return string[] A scrubbed version of only valid include keys. Insures ID keys are added if missing.
     */
    public static function scrubInclude($include, $entityKey)
    {
        $include = array_unique($include);
        $cached = ScrubCache::cache()->get($entityKey, $include);
        if (!is_null($cached)) {
            return $cached;
        }
        $realInclude = [];
        foreach ($include as $index => $i) {
            $parts = explode('.', $i, 3);
            $partCount = count($parts);
            if ($partCount === 1) {
                if (self::isIncludable($entityKey, $parts[0]) && !in_array($parts[0], $realInclude)) {
                    $realInclude[] = $parts[0];
                }
            } elseif ($partCount === 2) {
                if (self::isIncludable($entityKey, $parts[0])) {
                    $isProp = self::isProp($parts[0], $parts[1]);
                    $isInclude = !$isProp && self::isIncludable($parts[0], $parts[1]);
                    if ($isProp || $isInclude) {
                        if (!in_array($i, $realInclude)) {
                            $realInclude[] = $i;
                        }
                        if ($isProp && !in_array($parts[0].'.id', $realInclude)) {
                            $realInclude[] = $parts[0].'.id';
                        }
                    }
                }
            } else {
                if (self::isIncludable($entityKey, $parts[0]) && self::isIncludable($parts[0], $parts[1])) {
                    $goDeeper = strpos($parts[2], '.');
                    if (!$goDeeper) {
                        $isProp = self::isProp($parts[1], $parts[2]);
                        $isInclude = !$isProp && self::isIncludable($parts[1], $parts[2]);
                        if ($isProp || $isInclude) {
                            if (!in_array($i, $realInclude)) {
                                $realInclude[] = $i;
                            }
                            if ($isProp && !in_array("{$parts[0]}.{$parts[1]}.id", $realInclude)) {
                                $realInclude[] = "{$parts[0]}.{$parts[1]}.id";
                            }
                        }
                    } else {
                        $deepIncludes = self::scrubInclude($parts[1], $parts[2]);
                        foreach ($deepIncludes as $d) {
                            $tmp = "{$parts[0]}.{$parts[1]}.{$d}";
                            if (!in_array($tmp, $realInclude)) {
                                $realInclude[] = $tmp;
                            }
                        }
                    }
                    $tmp = "{$parts[0]}.id";
                    if (!in_array($tmp, $parts[0]) && !in_array($tmp, $realInclude)) {
                        $realInclude[] = $tmp;
                    }
                }
            }
        }
        $flipped = array_flip($realInclude);
        foreach ($realInclude as $i) {
            $tmp = substr($i, count($i) - 4, 3) === '.id' ? substr($i, 0, count($i) - 4) : null;
            if (!is_null($tmp) && isset($flipped[$tmp])) {
                unset($flipped[$i]);
            }
        }
        $cleaned = array_flip($flipped);
        ScrubCache::cache()->push($entityKey, $include, $cleaned);

        return $cleaned;
    }

    /**
     * Check all the properties of the RequestCondition against a specific entity to clean up its values before use
     *
     * @param RequestCondition[] $where     A RequestCondition to clean up for a specific entity resource (datatypes,
     *                                      etc)
     * @param string             $entityKey The entity to clean up the where "object" for
     *
     * @throws Exception
     * @return RequestCondition[] Returns a "clean" list of valid RequestConditions, having stripped any bad ones out
     */
    public static function scrubWhere($where, $entityKey)
    {
        $filteredWhere = [];
        foreach ($where as $w) {
            $pts = explode('.', $w->prop);
            if ($w->type === 'where') {
                if (count($pts) === 1 && self::isProp($entityKey, $pts[0])) {
                    $w->dataType = self::getPropertyDataType($entityKey, $pts[0]);
                    $filteredWhere[] = $w;
                } else {
                    $includeKey = $pts[0];
                    $eProp = array_pop($pts);
                    $eKey = array_pop($pts);
                    if (self::isIncludable($entityKey, $includeKey) && EntityMap::exists($eKey) && self::isProp($eKey,
                                                                                                                $eProp)) {
                        $w->dataType = self::getPropertyDataType($eKey, $eProp);
                        $filteredWhere[] = $w;
                    }
                }
            } elseif ($w->type === 'has') {
                if (count($pts) === 1 && self::isIncludable($entityKey, $pts[0])) {
                    $filteredWhere[] = $w;
                } else {
                    $eProp = array_pop($pts);
                    $eKey = array_pop($pts);
                    if (EntityMap::exists($eKey) && self::isIncludable($eKey, $eProp)) {
                        $filteredWhere[] = $w;
                    }
                }
            }
        }

        return $filteredWhere;
    }

    /**
     * Get all the entities settings as an associative array that can be used to clone them into a new entity
     *
     * @return array All the intended clone contents from this entity
     */
    public function getConfiguration()
    {
        return [
            'connection' => $this->connection,
            'overwriteDirtyWithRequests' => $this->overwriteDirtyWithRequests,
            'useCacheIfAvailable' => $this->useCacheIfAvailable
        ];
    }

    /**
     * Get an alternative "response" key to process the results if they dont match the original request key
     *
     * @param string|AbstractResource $objClass Either an instance or a string class name to check the class constants
     *
     * @return string Either an empty string or an appended alternative resource key
     */
    protected function getResponseKey($objClass)
    {
        return $objClass::API_RESPONSE_KEY ? ':'.$objClass::API_RESPONSE_KEY : '';
    }

    /**
     * Check the data types of the fields and where lists sent into the fetch calls of child entities
     *
     * @param string[]           $fields The list of fields to be checked as being all strings
     * @param RequestCondition[] $where  The list of where conditions to check all are RequestCondition objects
     *
     * @throws Exception
     * @return bool Returns true if all elements pass. Throws exception on any failures.
     */
    protected function validateFetch($fields = [], $where = [])
    {
        if (!is_array($fields)) {
            throw new Exception("Field list must be an array of fields to be selected");
        }
        if (!is_array($where)) {
            throw new Exception("Where list must be an array of RequestCondition objects");
        }
        foreach ($fields as $f) {
            if (!is_string($f)) {
                throw new Exception("All fields must be sent as plain text strings of each key desired");
            }
        }
        foreach ($where as $w) {
            if (!is_a($w, 'Jcolombo\PaymoApiPhp\Utility\RequestCondition')) {
                throw new Exception("Where conditions must all be instances of the RequestCondition class");
            }
        }

        return true;
    }

}
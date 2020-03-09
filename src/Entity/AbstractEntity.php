<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/9/20, 12:09 AM
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
use Jcolombo\PaymoApiPhp\Cache\ScrubCache;
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Request;
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
    public const VALID_OPERATORS = ['=', '!=', '<', '<=', '>', '>=', 'like', 'not like', 'in', 'not_in', 'range'];

    /**
     * @var bool
     */
    protected $hydrationMode = false;

    /**
     * @var array|Paymo|mixed|string|null
     */
    protected $connection = null;
    /**
     * @var bool
     */
    protected $overwriteDirtyWithRequests = true;
    /**
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
     * @param null $paymo                          * @param array | Paymo | string | null $paymo Either an API Key,
     *                                             Paymo Connection, config settings array (from another entitied
     *                                             getConfiguration call), or null to get first connection available
     *
     * @throws Exception
     * @return AbstractResource | AbstractCollection
     */
    public static function new($paymo = null)
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
     * @param      $entityKey
     * @param      $operator
     * @param      $value
     * @param bool $validate
     *
     * @throws Exception
     * @return bool | string
     */
    public static function allowWhere($entityKey, $operator, $value = null)
    {
        [$key, $prop] = EntityMap::extractResourceProp($entityKey);
        /** @var AbstractEntity $entityResource */
        $entityResource = EntityMap::resource($key);
        $ops = !!$entityResource ? $entityResource::INCLUDE_TYPES : [];
        $isProp = self::isProp($key, $prop);

        if ($isProp && !isset($ops[$prop])) {
            return true;
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

            return true;
        }
        if (!is_null($value)) {
            $datatype = static::getPropertyDataType($key, $prop);
            $primitive = Request::getPrimitiveType($datatype);
            if (in_array($operator, ['in', 'not in', 'range'])) {
                if (!is_array($value)) {
                    $value = [$value];
                }
                foreach ($value as $v) {
                    $valid_value = gettype($v) == $primitive;
                    if ($primitive == 'timestamp') {
                        $valid_value = gettype($v) == 'string' || gettype($v) == 'integer';
                    }
                    if (!$valid_value) {
                        return "WHERE: {$entityKey} {$operator} expects array[{$primitive}] but got ".gettype($v).": {$v}";
                    }
                }
            } else {
                if (is_array($value)) {
                    return "WHERE: {$entityKey} {$operator} received an array, expected {$primitive}";
                }
                $valid = gettype($value) != $primitive;
                if ($valid) {
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
     * Get the defined property datatype for the resource entity
     *
     * @param string $entityKey
     * @param string $prop
     *
     * @throws Exception
     * @return string | null
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
     * @param RequestCondition[] $where
     * @param string             $entityKey
     *
     * @throws Exception
     * @return RequestCondition[]
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
                    $eProp = array_pop($pts);
                    $eKey = array_pop($pts);
                    if (EntityMap::exists($eKey) && self::isProp($eKey, $eProp)) {
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

}
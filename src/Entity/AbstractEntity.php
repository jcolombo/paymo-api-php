<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/6/20, 11:45 PM
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
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;

abstract class AbstractEntity
{
    /**
     * The valid possible operators usable in WHERE clauses when selecting lists of entities
     */
    public const VALID_OPERATORS = ['=', '!=', '<', '<=', '>', '>=', 'like', 'not like', 'in', 'not_in'];

    protected $hydrationMode = false;

    protected $connection = null;
    protected $overwriteDirtyWithRequests = true;
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
     * Determine if a key is either a prop or an include type on a specific entity
     *
     * @param string $entityKey     The entity key to use in look up
     * @param string $propOrInclude The key that is being checked for allowance as either a prop or an include
     *
     * @throws Exception
     * @return bool
     */
    public static function isSelectable($entityKey, $propOrInclude)
    {
        $entityResource = EntityMap::resource($entityKey);

        return !!$entityResource && (self::isProp($entityKey, $propOrInclude)
                || self::isIncludable($entityKey, $propOrInclude));
    }

    /**
     * Check if a specific entity has a valid property type allowed
     *
     * @param string $entityKey  The entity key to use in look up
     * @param string $includeKey The prop key that is being checked for allowance
     *
     * @throws Exception
     * @return bool
     */
    public static function isProp($entityKey, $includeKey)
    {
        $entityResource = EntityMap::resource($entityKey);

        return !!$entityResource && isset($entityResource::PROP_TYPES[$includeKey]);
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
        $entityResource = EntityMap::resource($entityKey);

        return !!$entityResource && isset($entityResource::INCLUDE_TYPES[$includeKey]);
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
        // @todo Implement WHERE filter scrubbing
        $select = [];
        $include = [];
        $where = [];
        foreach ($fields as $k) {
            if (self::isProp($entityKey, $k)) {
                $select[] = $k;
            } else {
                $include[] = $k;
            }
        }
        if (count($select) > 0 && !in_array('id', $select)) { $select[] = 'id'; }
        $include = self::scrubInclude($include, $entityKey);

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
        if (!is_null($cached)) { return $cached; }
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
            $tmp = substr($i, count($i)-4, 3)==='.id' ? substr($i, 0, count($i)-4) : null;
            if (!is_null($tmp) && isset($flipped[$tmp])) {
                unset($flipped[$i]);
            }
        }
        $cleaned = array_flip($flipped);
        ScrubCache::cache()->push($entityKey, $include, $cleaned);
        return $cleaned;
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
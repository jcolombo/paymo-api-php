<?php

namespace Jcolombo\PaymoApiPhp\Entity;

use Exception;
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
                }
            }
        }

        return $realInclude;
    }

}
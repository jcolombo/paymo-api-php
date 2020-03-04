<?php

namespace Jcolombo\PaymoApiPhp\Entity;

use Exception;
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Request;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;

const PAYMO_ENTITY_MAP = [
    'project' => ['object'=>'Jcolombo\PaymoApiPhp\Entity\Project', 'collection'=>false],
    'projects' => ['object'=>'Jcolombo\PaymoApiPhp\Entity\Project', 'collection'=>true],
    'client' => ['object'=>'Jcolombo\PaymoApiPhp\Entity\Client', 'collection'=>false],
    'clients' => ['object'=>'Jcolombo\PaymoApiPhp\Entity\Client', 'collection'=>true],
    'projectstatus' => ['object'=>'Jcolombo\PaymoApiPhp\Entity\ProjectStatus', 'collection'=>false],
    'projectstatuses' => ['object'=>'Jcolombo\PaymoApiPhp\Entity\ProjectStatus', 'collection'=>true],
];

abstract class _AbstractEntity
{
    const _requiredConfiguration = [
        'label', 'apiPath', 'apiEntity', 'required', 'readonly', 'includeTypes', 'propTypes', 'where'
    ];

    const _validOperators = ['=', '!=', '<', '<=', '>', '>=', 'like', 'not like', 'in', 'not_in'];
    // IN & NOT IN require an array value to check.

    protected $connection = null;
    protected $overwriteDirtyWithRequests = true;
    protected $useCacheIfAvailable = true;
    protected $hydrationMode = false;
    protected $props    = [];
    protected $unlisted = [];
    protected $loaded   = [];
    protected $included = [];

    /**
     * The default Entity constructor
     *
     * Requires a Paymo connection instance or attempts to find/create one.
     * When in development mode, will validate the object class has all required defined constants
     *
     * @param Paymo | string | null $paymo Either an API Key, Paymo Connection, or null to get first connection available
     * @throws Exception
     */
    public function __construct(Paymo $paymo = null)
    {
        try {
            if (is_null($paymo)) {
                $this->connection = Paymo::connect();
            } elseif (is_string($paymo)) {
                $this->connection = Paymo::connect($paymo);
            } elseif (is_object($paymo)) {
                $this->connection = $paymo;
            } else {
                throw new Exception("No Connection");
            }
        } catch (Exception $e) {
            // Log Error
            // Kill App
            die($e);
        }
        if (defined('PAYMO_DEVELOPMENT_MODE') && PAYMO_DEVELOPMENT_MODE) {
            $missingConstants = [];
            foreach (self::_requiredConfiguration as $k) {
                $classname = get_class($this);
                if (!constant($classname.'::'.$k)) {
                    $missingConstants[] = $k;
                }
            }
            if (count($missingConstants) > 0) {
                throw new Exception("Attempting to create malformed Entity. Missing class CONSTANTS for '".implode("', '", $missingConstants)."'");
            }
        }
        return $this;
    }

    /**
     * Manual call in place of the direct magic method setter, allows for bulk property setting as array
     *
     * @param string | array $key Either a prop key or an associative array of prop key=>value combinations
     * @param null $value If $key is an array, this is ignored. Otherwise its used to set the value of $key prop
     * @return _AbstractEntity Returns the object itself for optional object chaining
     */
    public function set($key, $value=null)
    {
        if (is_string($key)) {
            $this->__set($key, $value);
        } elseif (is_array($key)) {
            foreach($key as $k => $v) {
                if (is_string($k)) {
                    $this->set($k, $v);
                }
            }
        }
        return $this;
    }

    /**
     * Resets the object to empty. Keeps any settings but clears the data from the props,
     * unlisted, loaded, and included collections.
     *
     * @return _AbstractEntity Returns the object itself for optional object chaining
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
     * Overwrite the loaded values with the current values, thereby resetting the dirty state on all props
     *
     * WARNING: Washing the object loses the last loaded or saved values and assumes the current values are
     * clean and saved via some other means. Entities are always auto-washed after loading and hydration is
     * complete
     *
     * @return _AbstractEntity Returns the object itself for optional object chaining
     */
    public function wash()
    {
        $this->loaded = $this->props;
        return $this;
    }


    public function relate($key, $object, $index=null) {
        // Find the object type for $key if its an array include use the associative index
        return $this;
    }

    /**
     * If enabled, will prevent API fetch calls from overwriting any current prop values if they are dirty
     *
     * By default, API loads will overwrite any data in this object even if its dirty and unsaved
     *
     * @param bool $protect Set to true and any attempt to overwrite dirty props will throw an error
     * @return _AbstractEntity Returns the object itself for optional object chaining
     */
    public function protectDirtyOverwrites($protect=true) {
        $this->overwriteDirtyWithRequests = !$protect;
        return $this;
    }

    /**
     * Call this with a TRUE value to always load from API (ignoring cache if it exists)
     *
     * This will still STORE the cache results if Caching is enabled on the connection
     * Setting this back to false on the entity will re-enable caching if its ON in the connection
     * By default all entities will try to use cache if connection cache is set to true
     *
     * @param bool $ignore The setting for this objects cache use override
     * @return _AbstractEntity Returns the object itself for optional object chaining
     */
    public function ignoreCache($ignore=true) {
        $this->useCacheIfAvailable = !$ignore;
        return $this;
    }

    /**
     * Execute an API call to populate this object with data based on a single ID for this entity type
     *
     * @param integer | null $id The ID to use to populate this object. If null, it uses the existing prop ID, if still no value... will throw an Exception
     * @param string[] $fields An array of string props and/or include entities to get from the API call
     * @return bool Returns true if populates successfully
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch($id=null, $fields=[])
    {
        if (is_null($id) && isset($this->props['id'])) { $id = $this->props['id']; }
        $label = $this::label;
        if (!$id || (int) $id < 1) {
            throw new Exception("Attempted to fetch a {$label} without an id being passed");
        }
        if (!$this->overwriteDirtyWithRequests && $this->isDirty()) {
            $label = $this::label;
            throw new Exception("{$label} attempted to fetch new data while it had dirty fields and protection is enabled.");
        }
        list ($select, $include) = $this::cleanupForRequest($this, $fields);
        $result = Request::fetch($this->connection, $this::apiPath, $id, $select, $include);
        if ($result) {
            $this->_hydrate($id, $result);
            return true;
        }
        return false;
    }

    /**
     * Scrub the fields and where conditional arrays to validate content
     *
     * @param _AbstractEntity $obj An instance of the entity class being used to GET data
     * @param string[] $fields An array of strings for props and includes to return on each base object
     * @param RequestCondition[] $whereFilters An array of RequestConditions to send to the API when getting lists
     * @return array Contains an item for $select, $include, and $where scrubbed for use by Request calls
     * @throws Exception
     */
    private function cleanupForRequest($obj, $fields=[], $whereFilters=[]) {
        // @todo Implement WHERE filter scrubbing
        $select = []; $include = []; $where = [];
        foreach($fields as $k) {
            if (isset($this::propTypes[$k])) {
                $select[] = $k;
            } else {
                $include[] = $k;
            }
        }
        $include = $obj::scrubInclude($include, $this::apiEntity);
        return array($select, $include, $where);
    }

    public function list($fields=[],$where=[],$validate=true) {
        // $where = [
        //   'prop' => string (key)
        //   'value' => any (validated against the operator)
        //   'operator' => valid operator defaults:"="
        //   'skipValidation' = boolean. if true, let any operator/value be used for this key
        //  ]

        // Call REQUEST (GET) with $fields and limit conditions set with WHERE
        // Return new hydrated collection array
        return [];
    }

    public function create()
    {
        foreach ($this::required as $k) {
            if (!isset($this->props[$k])) {
                $label = $this::label;
                throw new Exception("Paymo: Creating a '{$label}' requires a value for '{$k}'");
            }
        }
        $createWith = $this->props;
        // Loop through read only props and strip them from $createWith;
        // Add warning if any readonly props were set and create is attempted (flag to ignore this)
        // Create with REQUEST (POST)
        // Create any children that are possible
        // Hydrate this object
        // Reset loaded
        return true; // on Success
    }

    public function update($updateRelations=false)
    {
        $update = $this->props;
        foreach ($this::readonly as $k) {
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

    public function delete($id=null)
    {
        if (is_null($id) && isset($this->props['id'])) { $id = $this->props['id']; }
        if (!$id || (int) $id < 1) {
            $label = $this::label;
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
    public function unlisted() {
        return $this->unlisted;
    }

    /**
     * Return all the current values of the defined object props as an associative array
     *
     * Passing a true parameter will check all defined keys (as null) even if they are not currently set
     *
     * @param bool $includeAll If true, will check the defined propType and return them as NULL if not set
     * @return mixed[]
     */
    public function props($includeAll=false) {
        $props = $this->props;
        if ($includeAll) {
            $diff = array_diff_key($this::propTypes, $props);
            foreach($diff as $k) {
                if (!isset($props[$k])) { $props[$k] = null; }
            }
        }
        return $props;
    }

    /**
     * Check if there is at least one dirty key that doesnt match the last loaded or saved value
     *
     * @param bool $checkRelations If true, will look at and check all "included" entities recursively
     * @return bool
     */
    public function isDirty($checkRelations=false) {
        $dirtySelf = count($this->getDirtyKeys()) > 0;
        if ($checkRelations) {
            $dirtyInclude = false;
            foreach($this->included as $k => $v) {
                if (is_object($v)) {
                    $dirtyInclude = $v->isDirty(true);
                } elseif (is_array($v)) {
                    foreach($v as $d) {
                        $dirtyInclude = $d->isDirty(true);
                        if ($dirtyInclude) { return true; }
                    }
                }
                if ($dirtyInclude) { return true; }
            }
        }
        return $dirtySelf;
    }

    /**
     * Return a list of strings for the prop keys that dont match the last loaded or saved values
     *
     * @return string[] Array of prop string keys
     */
    public function getDirtyKeys() {
        $keys = [];
        foreach($this->loaded as $k => $v) {
            if (isset($this->props[$k]) && $this->props[$k] !== $v) {
                $keys[] = $k;
            }
        }
        return $keys;
    }

    /**
     * Return an array of the current prop values that do not match the last loaded or saved value
     *
     * @return array[] A multidimensional array with prop as keys and a 2 part assoc array for each key [original, current]
     */
    public function getDirtyValues() {
        $keys = $this->getDirtyKeys();
        $values = [];
        foreach($keys as $k) {
            $values[$k] = [
                'original'=>isset($this->loaded[$k])?$this->loaded[$k]:null,
                'current'=>isset($this->props[$k])?$this->props[$k]:null
            ];
        }
        return $values;
    }

    /**
     * Magic setter method
     *
     * If the setter cannot find the key in the valid props, it will add the value to the "unlisted" array
     *
     * @param string $name Object property to attempt magic setting
     * @param mixed $value The value to attempt to set
     * @return void
     */
    public function __set($name, $value)
    {
        if (key_exists($name, $this::propTypes)) {
            if ($this->hydrationMode || !in_array($name, $this::readonly)) {
                $this->props[$name] = $value;
            }
        } else {
            $this->unlisted[$name] = $value;
        }
        // allow setting of a child included value
    }

    /**
     * Magic getter method
     *
     * Will check the class props, unlisted and included object properties
     *
     * @param string $name Object property getter key
     * @return mixed | null
     */
    public function __get($name)
    {
        if (key_exists($name, $this::propTypes)) {
            return isset($this->props[$name]) ? $this->props[$name] : null;
        } elseif(key_exists($name, $this->unlisted)) {
            return $this->unlisted[$name];
        } elseif(key_exists($name, $this->included)) {
            return $this->included[$name];
        }
        return null;
    }

    // Not Intended for Actual Public Calling

    /**
     * Internal method to populate this object with the results from a paymo API call
     *
     * This method is only exposed publicly to allow other hydration calls to propagate the process
     * It is not intended to be called directly (unless passing in the raw object results from a call elsewhere)
     *
     * @param integer $objectId The ID of this object being populated
     * @param object $responseObject The standard object returned from the API call
     * @throws Exception
     */
    public function _hydrate($objectId, $responseObject) {
        if (is_object($responseObject)) {
            $this->clear();
            $this->hydrationMode = true;
            $this->props['id'] = $objectId;
            foreach ($responseObject as $k => $v) {
                if (!isset($this::propTypes[$k]) && isset($this::includeTypes[$k])) {
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
     * Supporting method to populate "include" hydration when child objects or lists are included in the response
     *
     * @param string $includeKey string The valid include key from the includeTypes constant to be populated
     * @param object | array $object The single include object or an array of objects depending on the key type
     * @throws Exception If an entity class definition cannot be found for the provided key
     */
    private function _hydrateInclude($includeKey, $object) {
        $entityObject = $this::getEntityClass($includeKey, 'all');
        $isCollection = !!$entityObject['collection'];
        $className = $entityObject['object'];
        $result = null;
        if ($isCollection) {
            $result = [];
            foreach($object as $o) {
                $tmp = new $className($this->connection);
                $tmp -> _hydrate($o->id, $o);
                $result[] = $tmp;
            }
        } else {
            $result = new $className($this->connection);
            $result -> _hydrate($object->id, $object);
        }
        $this->included[$includeKey] = $result;
    }

    // Static Methods

    /**
     * Lookup an entity settings and class name from a defined key
     *
     * @param string $key The reference key for an entity to be looked up
     * @param string $return May be 'all', 'object' or 'collection' based on what is needed in return if found
     * @param bool $allowNull If true, will return null if the class object cannot be found for the passed key
     * @return object|string|bool|null
     * @throws Exception
     */
    static function getEntityClass($key, $return='object', $allowNull=false) {
        if (strpos($key, '\\') !== false) { return $key; }
        $mapKey = null;
        if (isset(PAYMO_ENTITY_MAP[$key])) {
            $mapKey = $key;
        } elseif (strpos($key, ':')) {
            $parts = explode(':', $key, 2);
            if (isset(PAYMO_ENTITY_MAP[$parts[1]])) { $mapKey = $parts[1]; }
        }
        if ($mapKey) {
            switch($return) {
                case('object'): return PAYMO_ENTITY_MAP[$mapKey]['object']; break;
                case('collection'): return PAYMO_ENTITY_MAP[$mapKey]['collection']; break;
                case('all'): default: return PAYMO_ENTITY_MAP[$mapKey]; break;
            }
        }
        if (!$allowNull) {
            throw new Exception("Attempting to look up undefined entity [$key] from map");
        }
        return null;
    }

    /**
     * Check if a specific entity has a valid property type allowed
     *
     * @param string $entityKey The entity key to use in look up
     * @param string $includeKey The prop key that is being checked for allowance
     * @return bool
     * @throws Exception
     */
    static function isProp($entityKey, $includeKey) {
        $entityClass = self::getEntityClass($entityKey);
        return isset($entityClass::propTypes[$includeKey]);
    }

    /**
     * Check if a specific entity has a valid include entity or collection allowed
     *
     * @param string $entityKey The entity key to use in look up
     * @param string $includeKey The include key that is being checked for allowance
     * @return bool
     * @throws Exception
     */
    static function isIncludable($entityKey, $includeKey) {
        $entityClass = self::getEntityClass($entityKey);
        return isset($entityClass::includeTypes[$includeKey]);
    }

    /**
     * Determine if a key is either a prop or an include type on a specific entity
     *
     * @param string $entityKey The entity key to use in look up
     * @param string $propOrInclude The key that is being checked for allowance as either a prop or an include
     * @return bool
     * @throws Exception
     */
    static function isSelectable($entityKey, $propOrInclude) {
        $entityClass = self::getEntityClass($entityKey);
        return self::isProp($entityClass, $propOrInclude)
            || self::isIncludable($entityClass, $propOrInclude);
    }

    /**
     * Clean up an array of include items to validate they exist and are allowed.
     *
     * Will recursively traverse the include array and check each level and all sub-levels
     *
     * @param string[] $include Array of strings to scrub for valid set of allowed props
     * @param string $entityKey The root entity key to compare validation of the $include rules to
     * @return string[] A scrubbed version of only valid include keys. Insures ID keys are added if missing.
     * @throws Exception
     */
    static function scrubInclude($include, $entityKey) {
        $realInclude = [];
        foreach($include as $index => $i) {
            $parts = explode('.', $i, 3);
            $partCount = count($parts);
            if ($partCount===1) {
                if (self::isIncludable($entityKey, $parts[0]) && !in_array($parts[0], $realInclude)) {
                    $realInclude[] = $parts[0];
                }
            } elseif ($partCount===2) {
                if (self::isIncludable($entityKey, $parts[0])) {
                    $isProp = self::isProp($parts[0], $parts[1]);
                    $isInclude = !$isProp && self::isIncludable($parts[0], $parts[1]);
                    if ($isProp || $isInclude) {
                        if (!in_array($i, $realInclude)) {
                            $realInclude[] = $i;
                        }
                        if ($isProp && !in_array($parts[0] . '.id', $realInclude)) {
                            $realInclude[] = $parts[0] . '.id';
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
                        $deepIncludes = self::scrubInclude($parts[1], [$parts[2]]);
                        foreach($deepIncludes as $d) {
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
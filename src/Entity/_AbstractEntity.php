<?php

namespace Jcolombo\PaymoApiPhp\Entity;

use Exception;
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Request;

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

    // Chain Methods

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

    public function clear()
    {
        $this->props = [];
        $this->unlisted = [];
        $this->loaded = [];
        $this->included = [];
        return $this;
    }

    /*
     * Reset the loaded props to match the currently set props and clear the dirty flags.
     * WARNING: This will wipe out the current loaded values so props cannot be reverted to prior values
     */
    public function wash()
    {
        $this->loaded = $this->props;
        return $this;
    }

    // Add method to create children of valid includes
    public function relate($key, $object, $index=null) {
        // Find the object type for $key if its an array include use the associative index
        return $this;
    }

    public function protectDirtyOverwrites($protect=true) {
        $this->overwriteDirtyWithRequests = !$protect;
        return $this;
    }

    public function ignoreCache($ignore=true) {
        $this->useCacheIfAvailable = !$ignore;
        return $this;
    }

    // REQUEST calls

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
        $select = []; $include = [];
        foreach($fields as $k) {
            if (isset($this::propTypes[$k])) {
                $select[] = $k;
            } else {
                $include[] = $k;
            }
        }
        //var_dump($include);
        $include = $this::scrubInclude($include, $this::apiEntity);
        //var_dump($include);
        $result = Request::fetch($this->connection, $this::apiPath, $id, $select, $include);
        if ($result) {
            $this->_hydrate($id, $result);
            return true;
        }
        return false;
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

    public function list($fields=[],$where=[]) {
        // $where = [
        //   'prop' => string (key)
        //   'value' => any (validated against the operator)
        //   'operator' => valid operator defaults:"="
        //   'skipValidation' = boolean. if true, let any operator be used for any key
        //  ]

        // Call REQUEST (GET) with $fields and limit conditions set with WHERE
        // Return new hydrated collection array
        return [];
    }

    
    // Utilities

    public function unlisted() {
        return $this->unlisted;
    }

    public function props($includeAll=false) {
        // If !includeAll, add the keys from the propTypes constant as NULL
        return $this->props;
    }

    public function isDirty($checkRelations=false) {
        $dirtySelf = count($this->getDirtyKeys()) > 0;
        // If $checkRelations, also run the check on all children and childrens children,  true=ALL, number 1+ depth of relations
        return $dirtySelf;
    }

    public function getDirtyKeys() {
        $keys = [];
        foreach($this->loaded as $k => $v) {
            if (isset($this->props[$k]) && $this->props[$k] !== $v) {
                $keys[] = $k;
            }
        }
        return $keys;
    }

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

    // Magic Methods

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

    public function __get($name)
    {
        if (key_exists($name, $this::propTypes)) {
            return isset($this->props[$name]) ? $this->props[$name] : null;
        } elseif(key_exists($name, $this->unlisted)) {
            return $this->unlisted[$name];
        } elseif(key_exists($name, $this->included)) {
            return $this->included[$name];
        }
        // Check $this->included; return value OR [] if const included is "true" or null of its false
        return null;
    }

    // Not Intended for Actual Public Calling

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

    static function isProp($entityKey, $includeKey) {
        $entityClass = self::getEntityClass($entityKey);
        return isset($entityClass::propTypes[$includeKey]);
    }

    static function isIncludable($entityKey, $includeKey) {
        $entityClass = self::getEntityClass($entityKey);
        return isset($entityClass::includeTypes[$includeKey]);
    }

    static function isSelectable($entityKey, $propOrInclude) {
        $entityClass = self::getEntityClass($entityKey);
        return self::isProp($entityClass, $propOrInclude)
            || self::isIncludable($entityClass, $propOrInclude);
    }

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
<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/17/20, 4:12 PM
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
use GuzzleHttp\Exception\GuzzleException;
use Jcolombo\PaymoApiPhp\Configuration;
use Jcolombo\PaymoApiPhp\Entity\Collection\EntityCollection;
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Request;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
use stdClass;

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
        'LABEL', 'API_PATH', 'API_ENTITY', 'REQUIRED_CREATE', 'READONLY', 'CREATEONLY', 'INCLUDE_TYPES', 'PROP_TYPES', 'WHERE_OPERATIONS'
    ];

    /**
     * Default value to override the responding API object property to process. NULL unless defined by the child classes
     */
    public const API_RESPONSE_KEY = null;

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
     * @param int | null                    $id    An optional ID to pre-populate the ID property of the object
     *
     * @throws Exception
     */
    public function __construct($paymo = null, $id = null)
    {
        parent::__construct($paymo);
        if (Configuration::get('devMode')) {
            $missingConstants = [];
            foreach (self::REQUIRED_CONSTANTS as $k) {
                $classname = get_class($this);
                $constVal = constant($classname.'::'.$k);
                if (!is_array($constVal) && !$constVal) {
                    $missingConstants[] = $k;
                }
            }
            if (count($missingConstants) > 0) {
                throw new Exception("Attempting to create malformed Entity. Missing class CONSTANTS for '".implode("', '",
                                                                                                                   $missingConstants)."'");
            }
        }
        if (!is_null($id)) {
            $this->props['id'] = (int) $id;
            $this->wash();
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

    /**
     * Static method to always create a resource or collection using the currently configured mapped class in Entity
     * Map
     * NOTE: Using this method to factory create your class will void IDE typehinting when developing (as it doesnt
     * know what class will return)
     *
     * @param array | Paymo | string | null $paymo Either an API Key,
     *                                             Paymo Connection, config settings array (from another entitied
     *                                             getConfiguration call), or null to get first connection available
     * @param int | null                    $id    An optional ID to pre-populate the ID property of the object
     *
     * @throws Exception
     * @return AbstractResource
     */
    public static function new($paymo = null, $id = null)
    {
        return parent::newResource($paymo, $id);
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
     * Creates a new resource object and calls its delete method (setting the response equal to a new instance of the
     * entity with response results)
     *
     * @param int                           $id    The ID to perform the delete on
     * @param array | Paymo | string | null $paymo Either an API Key, Paymo Connection, config settings array (from
     *                                             another entitied getConfiguration call), or null to get first
     *                                             connection available
     *
     * @throws GuzzleException
     * @throws Exception
     * @return AbstractResource | null
     */
    public static function deleteById($id, $paymo = null)
    {
        $resourceClass = EntityMap::resource(static::API_ENTITY);
        $obj = null;
        if ($resourceClass) {
            /** @var AbstractResource $obj */
            $obj = new $resourceClass($paymo, $id);
            $obj->delete();
        }

        return $obj;
    }

    /**
     * Delete a resource of this class type with the either the ID passed to the method OR the id set on this objects
     * props Clears the object back to a reset fresh state after successfully deleting it.
     *
     * @param null $id The ID to perform the delete on
     *
     * @throws GuzzleException
     * @throws Exception
     * @return AbstractResource
     */
    public function delete()
    {
        $id = 0;
        if (isset($this->props['id'])) {
            $id = $this->props['id'];
        }
        if (!$id || (int) $id < 1) {
            $label = $this::LABEL;
            throw new Exception("Attempted to delete a {$label} without an id being passed");
        }
        $respKey = $this->getResponseKey($this);
        $response = Request::delete($this->connection, $this::API_PATH.$respKey, $id);
        if ($response && $response->success) {
            $this->clear();
            // @todo Populate a response summary of data on the object (like if it came from live, timestamp of request, timestamp of data retrieved/cache, etc
        }

        return $this;
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
        $checkId = in_array($this::API_ENTITY, static::SKIP_ID_FETCH_UPDATE) ? false : true;
        if (is_null($id) && isset($this->props['id'])) {
            $id = $this->props['id'];
        }
        $label = $this::LABEL;
        if ($checkId && (!$id || (int) $id < 1)) {
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
        if (!$checkId) {
            $id = -1;
        }
        $respKey = $this->getResponseKey($this);
        $response = Request::fetch($this->connection, $this::API_PATH.$respKey, $id,
                                   ['select' => $select, 'include' => $include]);
        if ($response->result) {
            $this->_hydrate($response->result, $id);
            // @todo Populate a response summary of data on the object (like if it came from live, timestamp of request, timestamp of data retrieved/cache, etc
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
        foreach ($this->props as $k => $v) {
            if (!isset($this->loaded[$k]) || (isset($this->loaded[$k]) && $this->loaded[$k] !== $v)) {
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
    public function _hydrate($responseObject, $objectId = null)
    {
        if (is_object($responseObject)) {
            $this->clear();
            $this->hydrationMode = true;
            foreach ($responseObject as $k => $v) {
                if ($this::isIncludable($this::API_ENTITY, $k)) {
                    $this->_hydrateInclude($k, $v);
                } else {
                    $this->__set($k, $v);
                }
            }
            if ($objectId && $objectId > 0) {
                $this->props['id'] = $objectId;
            } // Force ID to match the passed ID
            $this->hydrationMode = false;
            $this->loaded = $this->props;
        }
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
            $result->_hydrate($object, $object->id);
        }
        $this->included[$entityKey] = $result;
    }

    /**
     * Create a new resource entry in the Paymo system with the data existing in this object
     * Be careful calling this on an existing entity while leaving the stripReadonly option on as it will remove the
     * readonly values and create a new copy of the entity
     *
     * @param array $options An associative array of possible options for configuring the creation rules
     *                       [stripReadonly] : bool [Default: true] - Will strip any readonly props (like ID) from the
     *                       creation values
     *                       [cascade] : bool [Default: true] - Look for any child relations attached to this entity
     *                       and create them as well (they will NOT strip readonly and only create them if they have no
     *                       ID)
     *
     * @throws Exception
     * @throws GuzzleException
     * @return AbstractResource Returns itself fully hydrated with the results from the API.
     */
    public function create($options = [])
    {
        $cancelReadonly = $options['cancelReadonly'] ?? true;
        $stripReadonly = $options['stripReadonly'] ?? false;
        $cascade = $options['cascade'] ?? true;
        $label = $this::LABEL;
        foreach ($this::REQUIRED_CREATE as $k) {
            $success = $this->_validateCreateRequirement($k);
            if (!$success) {
                $propLabel = str_replace('||', ' OR ', $k);
                $propLabel = str_replace('|', ' or ', $propLabel);
                if (strpos($k, '&') !== false) {
                    $propLabel = str_replace('&', ' & ', $propLabel);
                }
                throw new Exception("Paymo: Creating a '{$label}' requires value for {$propLabel}");
            }
        }
        $createWith = $this->props;
        $continueCreate = true;
        if ($stripReadonly || $cancelReadonly) {
            foreach ($createWith as $p => $value) {
                $isNewCreatable = (!$this->id && in_array($p, static::CREATEONLY));
                if (isset(static::READONLY[$p]) && !$isNewCreatable) {
                    if ($cancelReadonly) {
                        $continueCreate = false;
                        break;
                    }
                    unset($createWith[$p]);
                }
            }
        }
        // @todo Validate all the properties being sent match their valid datatypes as defined in the class requirements
        // Only create this object if it DOES NOT have an id set
        if ($continueCreate && !isset($createWith['id']) || $createWith['id'] < 1) {
            $respKey = $this->getResponseKey($this);
            $response = Request::create($this->connection, $this::API_PATH.$respKey, $createWith);
            if ($response->result) {
                $this->_hydrate($response->result);
                // @todo Populate a response summary of data on the object (like if it came from live, timestamp of request, timestamp of data retrieved/cache, etc
            }
            if ($cascade) {
                // Create any children that are possible, currently cant create relations on the standalone resource
                //@todo Cascade through included entities and create them if relevant
            }
        }

        return $this;
    }

    protected function _validateCreateRequirement($key)
    {
        $success = false;
        if (is_string($key)) {
            if (strpos($key, '||') !== false) {
                $pts = explode('||', $key);
                $xor = 0;
                foreach ($pts as $oK) {
                    if ($this->_validateCreateRequirement($oK)) {
                        $xor++;
                    }
                }

                return $xor === 1;
            } elseif (strpos($key, '|') !== false) {
                $pts = explode('|', $key);
                foreach ($pts as $oK) {
                    if ($this->_validateCreateRequirement($oK)) {
                        return true;
                    }
                }

                return false;
            } elseif (strpos($key, '&') !== false) {
                $pts = explode('&', $key);
                $success = true;
                foreach ($pts as $aK) {
                    $success = $success && $this->_validateCreateRequirement($aK);
                }

                return $success;
            } else {
                return isset($this->props[$key]);
            }
        } elseif (is_array($key)) {
            $success = true;
            foreach ($key as $aK) {
                $success = $success && $this->_validateCreateRequirement($aK);
            }

            return $success;
        }

        return $success;
    }

    /**
     * Make an update request to the API to update data for a specific resource, requires an ID be set on the object
     * and have at least one dirtty non-ID prop (or dirty children if option is set)
     *
     * @param array $options An associative array of possible options for configuring the update rules
     *                       [updateRelations] : bool [Default: true] - Will traverse all relations and check for dirty
     *                       objects and trigger updates to each dirty one
     *                       [createRelations] : bool [Default: true] - Will create any related includes in collections
     *                       if they do not yet have ids
     *
     * @throws Exception
     * @throws GuzzleException
     * @return $this
     */
    public function update($options = [])
    {
        $checkId = in_array($this::API_ENTITY, static::SKIP_ID_FETCH_UPDATE) ? false : true;
        $updateRelations = $options['updateRelations'] ?? true;
        $createRelations = $options['createRelations'] ?? true;
        $id = 0;
        if (isset($this->props['id'])) {
            $id = $this->props['id'];
        }
        $label = $this::LABEL;
        if ($checkId && (!$id || (int) $id < 1)) {
            throw new Exception("Attempted to update a {$label} without an id being set");
        }
        $originalUpdate = $this->props;
        foreach ($this::READONLY as $k) {
            unset($originalUpdate[$k]);
        }
        $update = [];
        $dirty = $this->getDirtyKeys();
        foreach ($dirty as $k) {
            if (isset($originalUpdate[$k])) {
                $update[$k] = $originalUpdate[$k];
            }
        }
        // Compare fields in $update with $this->loaded and only post the dirty items
        // If $updateRelations, attempt to update() all children, true=ALL, number 1+ depth of relations
        if (count($update) > 0) {
            if (!$checkId) {
                $id = -1;
            }
            $respKey = $this->getResponseKey($this);
            $response = Request::update($this->connection, $this::API_PATH.$respKey, $id, $update);
            if ($response->result) {
                $this->_hydrate($response->result);
                // @todo Populate a response summary of data on the object (like if it came from live, timestamp of request, timestamp of data retrieved/cache, etc
            }
            // Traverse and save hydrated children if modified as well
        }

        return $this;
    }

    /**
     * Upload an image to an entity
     *
     * @param string $filepath  The path to the local file for uploading (usually is the the tmp uploaded path)
     * @param string $propKey   The property key for the upload file to be attached to. Defaults to 'image' as most
     *                          resources have a single image prop. Also used as the mutlipart upload variable name for
     *                          requests
     * @param bool   $isPropKey Determine if this is supposed to be checked against the prop list on the resource or
     *                          not
     *
     * @throws GuzzleException
     * @throws Exception
     * @return $this Return the object itself for chaining
     */
    public function image($filepath, $propKey = 'image', $isPropKey = true)
    {
        return $this->upload($filepath, $propKey, $isPropKey);
    }

    /**
     * Upload an image to an existing entity.
     *
     * @param string $filepath  The path to the local file for uploading (usually is the the tmp uploaded path)
     * @param string $propKey   The property key for the upload file to be attached to. Also used as the mutlipart
     *                          upload variable name for requests
     * @param bool   $isPropKey Determine if this is supposed to be checked against the prop list on the resource or
     *                          not
     *
     * @throws GuzzleException
     * @throws Exception
     * @return $this Return the object itself for chaining
     * @todo Refactor to allow for image uploads in the same call (means sending the data combined with file in
     *       multipart body)
     */
    protected function upload($filepath, $propKey, $isPropKey = true)
    {
        // If there is no valid prop for the image, ignore this method
        if (!$this->id || $this->id < 1) {
            throw new Exception("File [{$propKey}] for {static::API_ENTITY} requires an ID be set for uploading");
        }
        if (!file_exists($filepath)) {
            throw new Exception("Upload file not found at {$filepath}");
        }
        if (!$isPropKey || static::isProp(static::API_ENTITY, $propKey)) {
            $respKey = $this->getResponseKey($this);
            $response = Request::upload($this->connection, static::API_PATH.$respKey, $this->id, $propKey, $filepath);
            if ($response->result) {
                $this->_hydrate($response->result);
                // @todo Populate a response summary of data on the object (like if it came from live, timestamp of request, timestamp of data retrieved/cache, etc
            }
        }

        return $this;
    }

    /**
     * Upload a file attached to an entity
     *
     * @param string $filepath  The path to the local file for uploading (usually is the the tmp uploaded path)
     * @param string $propKey   The property key for the upload file to be attached to. Defaults to 'file' as most
     *                          resources add files via the 'file' key. Also used as the mutlipart upload variable name
     *                          for requests
     * @param bool   $isPropKey Determine if this is supposed to be checked against the prop list on the resource or
     *                          not
     *
     * @throws GuzzleException
     * @throws Exception
     * @return $this Return the object itself for chaining
     */
    public function file($filepath, $propKey = 'file', $isPropKey = false)
    {
        return $this->upload($filepath, $propKey, $isPropKey);
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
     * Return a stdClass object of the properties and its included relations (configured with options)
     *
     * @param array $options An associative array of setting options for how to flatten the responses.
     *                       [stripNull] : boolean [false] = Will only return props that are not set to null
     *
     * @return stdClass
     */
    public function flatten($options = [])
    {
        $stripNull = $options['stripNull'] ?? false;
        $data = $this->props;
        if ($stripNull) {
            foreach ($data as $i => $d) {
                if (is_null($d)) {
                    unset($data[$i]);
                }
            }
        }
        $response = json_decode(json_encode($data));
        foreach ($this->included as $k => $list) {
            $response->$k = $list->flatten($options);
        }

        return $response;
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
            $noIdCreateOnly = (!$this->id && in_array($name, $this::CREATEONLY));
            $canSet = ($this->hydrationMode || !in_array($name, $this::READONLY) || $noIdCreateOnly);
            if ($canSet) {
                $this->props[$name] = $value;
            }
        } else {
            $this->unlisted[$name] = $value;
        }
        // allow setting of a child included value
    }

}
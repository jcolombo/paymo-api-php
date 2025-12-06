<?php
/**
 * Paymo API PHP SDK - Abstract Entity Base Class
 *
 * The foundational base class for all Paymo entity types. Provides core functionality
 * for connection management, validation, and property type checking that is inherited
 * by both AbstractResource (single entities) and AbstractCollection (lists of entities).
 *
 * @package    Jcolombo\PaymoApiPhp\Entity
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020-2025 Joel Colombo / 360 PSG, Inc.
 * @license    MIT License
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 * @see        https://github.com/paymoapp/api Official Paymo API Documentation
 *
 * MIT License
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
use Jcolombo\PaymoApiPhp\Utility\Converter;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;

/**
 * Abstract Entity Base Class
 *
 * The root class of the SDK's entity hierarchy. All Paymo resources and collections
 * ultimately inherit from this class. It provides:
 *
 * - **Connection Management**: Links entities to Paymo API connections
 * - **Validation Utilities**: Static methods to validate properties, includes, and WHERE conditions
 * - **Factory Methods**: Create properly typed resources and collections via EntityMap
 * - **Configuration Cloning**: Transfer settings between related entities during hydration
 *
 * ## Class Hierarchy
 *
 * ```
 * AbstractEntity (this class)
 *     ├── AbstractResource (single entity)
 *     │       ├── Project
 *     │       ├── Task
 *     │       ├── Client
 *     │       └── ... (33 total resources)
 *     │
 *     └── AbstractCollection (list of entities)
 *             ├── EntityCollection (generic)
 *             ├── TimeEntryCollection
 *             └── ... (specialized collections)
 * ```
 *
 * ## Valid WHERE Operators
 *
 * When filtering entities in list operations, these operators are available:
 *
 * | Operator    | Description                           | Example                              |
 * |-------------|---------------------------------------|--------------------------------------|
 * | `=`         | Equals                                | `where('active', true)`              |
 * | `!=`        | Not equals                            | `where('status', 'draft', '!=')`     |
 * | `<`         | Less than                             | `where('budget', 1000, '<')`         |
 * | `<=`        | Less than or equals                   | `where('priority', 5, '<=')`         |
 * | `>`         | Greater than                          | `where('created_on', '2024-01-01')` |
 * | `>=`        | Greater than or equals                | `where('updated_on', $date, '>=')`   |
 * | `like`      | SQL LIKE pattern match                | `where('name', '%test%', 'like')`    |
 * | `not like`  | SQL NOT LIKE pattern                  | `where('name', '%temp%', 'not like')`|
 * | `in`        | Value in array                        | `where('status', ['a','b'], 'in')`   |
 * | `not in`    | Value not in array                    | `where('type', [1,2], 'not in')`     |
 * | `range`     | Between two values (array of 2)       | `where('price', [10,50], 'range')`   |
 *
 * ## Special Entity Keys
 *
 * Some entities don't have traditional IDs:
 * - `company` - Singleton entity, fetched without ID
 *
 * @package Jcolombo\PaymoApiPhp\Entity
 * @author  Joel Colombo <jc-dev@360psg.com>
 * @since   0.1.0
 *
 * @see     AbstractResource For single entity operations
 * @see     AbstractCollection For list operations
 * @see     EntityMap For entity class registration
 */
abstract class AbstractEntity
{
    /**
     * All valid comparison operators for WHERE clauses.
     *
     * These operators can be used when filtering entity lists. Each resource
     * may further restrict which operators are allowed on specific properties
     * via its WHERE_OPERATIONS constant.
     *
     * @var string[] Array of valid operator strings
     *
     * @see self::allowWhere() For operator validation
     * @see RequestCondition::where() For creating WHERE conditions
     */
    public const VALID_OPERATORS = ['=', '!=', '<', '<=', '>', '>=', 'like', 'not like', 'in', 'not in', 'range'];

    /**
     * Resource keys that don't use standard ID-based fetch/update.
     *
     * Some Paymo entities are singletons (like Company settings) and don't
     * have individual IDs. These are fetched with ID=-1 internally.
     *
     * @var string[] Array of entity keys that skip ID validation
     */
    public const SKIP_ID_FETCH_UPDATE = ['company'];

    /**
     * Internal flag indicating hydration mode is active.
     *
     * When TRUE, allows setting of READONLY properties (like `id`, `created_on`)
     * which are normally protected from manual modification. This is automatically
     * enabled during API response hydration and disabled afterward.
     *
     * @var bool TRUE when hydrating from API response
     *
     * @internal Used by _hydrate() methods
     */
    protected $hydrationMode = false;

    /**
     * The Paymo API connection instance for this entity.
     *
     * All API operations (fetch, create, update, delete) are executed
     * through this connection. Set during construction and can be
     * cloned to child entities via getConfiguration().
     *
     * @var Paymo|null The active connection or NULL if not yet established
     *
     * @see Paymo::connect() For establishing connections
     */
    protected $connection = null;

    /**
     * Controls whether fetch operations can overwrite dirty (unsaved) data.
     *
     * - **TRUE** (default): New fetch() calls will overwrite any unsaved changes
     * - **FALSE**: Throws exception if fetch() is called with dirty properties
     *
     * Use protectDirtyOverwrites(true) to enable protection.
     *
     * @var bool TRUE to allow overwrites, FALSE to protect dirty data
     *
     * @see AbstractResource::protectDirtyOverwrites() For setting this flag
     */
    protected $overwriteDirtyWithRequests = true;

    /**
     * Controls whether this entity should use cached responses.
     *
     * - **TRUE** (default): Use cache if connection has caching enabled
     * - **FALSE**: Always make fresh API calls (ignoreCache mode)
     *
     * This is an entity-level override. Global caching must also be enabled
     * on the connection for caching to actually occur.
     *
     * @var bool TRUE to use cache, FALSE to force fresh API calls
     *
     * @see AbstractResource::ignoreCache() For setting this flag
     * @see Paymo::$useCache For connection-level cache setting
     */
    protected $useCacheIfAvailable = true;

    /**
     * Initialize an entity with a Paymo connection.
     *
     * The constructor accepts multiple formats for flexibility:
     *
     * ## Connection Options
     *
     * ```php
     * // Option 1: Use existing/default connection (most common)
     * $entity = new SomeEntity();
     *
     * // Option 2: Provide API key to create/get connection
     * $entity = new SomeEntity('your-api-key');
     *
     * // Option 3: Provide existing Paymo connection
     * $paymo = Paymo::connect('api-key');
     * $entity = new SomeEntity($paymo);
     *
     * // Option 4: Configuration array (used internally for cloning)
     * $entity = new SomeEntity([
     *     'connection' => $paymo,
     *     'overwriteDirtyWithRequests' => false,
     *     'useCacheIfAvailable' => true
     * ]);
     * ```
     *
     * @param Paymo|string|array|null $paymo Connection specification:
     *                                       - null: Uses first available connection
     *                                       - string: API key to connect with
     *                                       - Paymo: Existing connection instance
     *                                       - array: Configuration with 'connection' key
     *
     * @throws Exception If no connection can be established or invalid type provided
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
            throw new Exception(
              "No Connection Provided, Be sure you have connected with a Paymo::connect() call before using the API entities"
            );
        }

        return;
    }

    /**
     * Apply configuration settings from another entity.
     *
     * Used internally when creating child entities during hydration to ensure
     * they inherit the same connection and behavioral settings as their parent.
     *
     * ## Configuration Keys
     *
     * | Key                        | Type   | Description                           |
     * |----------------------------|--------|---------------------------------------|
     * | `connection`               | Paymo  | The API connection to use             |
     * | `overwriteDirtyWithRequests` | bool | Whether to allow overwriting dirty data |
     * | `useCacheIfAvailable`      | bool   | Whether to use caching                |
     *
     * @param array $configurationArray Configuration settings to apply
     *
     * @throws Exception If parameter is not an array
     *
     * @internal Used by _hydrateInclude() and related methods
     * @see      self::getConfiguration() For retrieving configuration
     */
    public function setConfiguration($configurationArray)
    {
        if (!is_array($configurationArray)) {
            throw new Exception("Cloning configuration requires a single associative array or object passed to it");
        }
        if (isset($configurationArray['connection']) && $configurationArray['connection'] instanceof Paymo) {
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
     * Factory method to create a new resource instance via EntityMap.
     *
     * Creates a resource using the class registered in EntityMap for the calling
     * class's API_ENTITY key. This allows for custom resource class overrides
     * while maintaining API compatibility.
     *
     * ## Example
     *
     * ```php
     * // Usually called via the child class's new() method:
     * $project = Project::new();
     *
     * // Which internally calls:
     * $project = AbstractEntity::newResource('api-key', 123);
     * ```
     *
     * @param Paymo|string|array|null $paymo Connection specification (see constructor)
     * @param int|null                $id    Optional ID to pre-populate on the resource
     *
     * @throws Exception If no class is found in EntityMap for this entity key
     *
     * @return AbstractResource A new instance of the mapped resource class
     *
     * @see EntityMap::resource() For class lookup
     */
    public static function newResource($paymo = null, $id = null)
    {
        $realClass = EntityMap::resource(static::API_ENTITY);
        if (is_null($realClass)) {
            throw new Exception(
              "No class found in the Entity Mapp for creating a {static::LABEL} with key '{static::API_ENTITY}'"
            );
        }

        //@todo Anyone with ideas on how a simple way to typehint the return type correctly for IDE's (like PhpStorm). Minor concern.
        return new $realClass($paymo, $id);
    }

    /**
     * Factory method to create a new collection instance via EntityMap.
     *
     * Creates a collection using the class registered in EntityMap for the calling
     * class's API_ENTITY key. This allows for custom collection class overrides.
     *
     * ## Example
     *
     * ```php
     * // Usually called via the child class's list() method:
     * $collection = Project::list();
     *
     * // Which creates a collection for fetching project lists
     * ```
     *
     * @param Paymo|string|array|null $paymo Connection specification (see constructor)
     *
     * @throws Exception If no class is found in EntityMap for this entity key
     *
     * @return AbstractCollection A new instance of the mapped collection class
     *
     * @see EntityMap::collection() For class lookup
     */
    public static function newCollection($paymo = null)
    {
        $realClass = EntityMap::resource(static::API_ENTITY);
        if (is_null($realClass)) {
            throw new Exception(
              "No class found in the Entity Mapp for creating a {static::LABEL} with key '{static::API_ENTITY}'"
            );
        }

        //@todo Anyone with ideas on how a simple way to typehint the return type correctly for IDE's (like PhpStorm). Minor concern.
        return new $realClass($paymo);
    }

    /**
     * Check if a key is valid as either a property or an includable relation.
     *
     * Used during request validation to determine if a field can be selected
     * or included in API responses.
     *
     * ## Example
     *
     * ```php
     * // Check if 'name' is selectable on projects
     * if (AbstractEntity::isSelectable('project', 'name')) {
     *     // Can be used in select or fetch fields
     * }
     *
     * // Can also use dot notation
     * AbstractEntity::isSelectable('project.name'); // true
     * AbstractEntity::isSelectable('project.tasks'); // true (includable)
     * ```
     *
     * @param string      $entityKey     The entity key to check against (e.g., 'project')
     * @param string|null $propOrInclude The property/include key to validate,
     *                                   or NULL to extract from dot notation in $entityKey
     *
     * @throws Exception If EntityMap lookup fails
     *
     * @return bool TRUE if the key is a valid property or includable relation
     *
     * @see self::isProp() For property-only validation
     * @see self::isIncludable() For include-only validation
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
     * Check if a key is a valid property on an entity.
     *
     * Properties are scalar values stored directly on the entity (like name,
     * description, created_on). They are defined in each resource's PROP_TYPES constant.
     *
     * ## Example
     *
     * ```php
     * // Check direct property
     * AbstractEntity::isProp('project', 'name');        // true
     * AbstractEntity::isProp('project', 'description'); // true
     * AbstractEntity::isProp('project', 'tasks');       // false (it's an include)
     *
     * // Dot notation also works
     * AbstractEntity::isProp('project.name');           // true
     * ```
     *
     * @param string      $entityKey The entity key to check (e.g., 'project', 'task')
     * @param string|null $propKey   The property key to validate,
     *                               or NULL to extract from dot notation in $entityKey
     *
     * @throws Exception If EntityMap lookup fails
     * @return bool TRUE if the key is a valid property
     *
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
     * Check if a key is a valid includable relation on an entity.
     *
     * Includes are related entities or collections that can be loaded alongside
     * the main entity. They are defined in each resource's INCLUDE_TYPES constant.
     *
     * ## Example
     *
     * ```php
     * // Check includable relations
     * AbstractEntity::isIncludable('project', 'tasks');  // true
     * AbstractEntity::isIncludable('project', 'client'); // true
     * AbstractEntity::isIncludable('project', 'name');   // false (it's a prop)
     *
     * // Dot notation
     * AbstractEntity::isIncludable('project.tasks');     // true
     * ```
     *
     * @param string      $entityKey  The entity key to check (e.g., 'project', 'task')
     * @param string|null $includeKey The include key to validate,
     *                                or NULL to extract from dot notation in $entityKey
     *
     * @throws Exception If EntityMap lookup fails
     * @return bool TRUE if the key is a valid includable relation
     *
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
     * Validate whether a WHERE condition is allowed for a specific property.
     *
     * Performs comprehensive validation including:
     * 1. Whether the property exists on the entity
     * 2. Whether WHERE filtering is allowed on this property
     * 3. Whether the specified operator is allowed
     * 4. Whether the value matches the expected data type
     *
     * ## Example
     *
     * ```php
     * // Valid condition
     * $result = AbstractEntity::allowWhere('project.active', '=', true);
     * // Returns: true
     *
     * // Invalid operator for property
     * $result = AbstractEntity::allowWhere('project.name', 'range', ['a','z']);
     * // Returns: "Property project.name cannot use the range operator."
     *
     * // Invalid data type
     * $result = AbstractEntity::allowWhere('project.client_id', '=', 'not-a-number');
     * // Returns: "WHERE: project.client_id = expects integer but got string: not-a-number"
     * ```
     *
     * @param string $entityKey Full property path with dot notation (e.g., 'project.active')
     * @param string $operator  The comparison operator to validate
     * @param mixed  $value     Optional value to validate data type against
     *
     * @throws Exception If EntityMap lookup fails
     *
     * @return bool|string TRUE if valid, error message string if invalid
     *
     * @see RequestCondition::where() For creating valid WHERE conditions
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
                $arr = explode('::', $primitive, 2);
                $enum = explode('|', array_pop($arr));
                $primitive = 'string';
            }
            if (in_array($operator, ['in', 'not in', 'range'])) {
                if (!is_array($value)) {
                    $value = [$value];
                }
                foreach ($value as $v) {
                    $valid_value = gettype($v) == $primitive;
                    if ($primitive === 'timestamp') {
                        $valid_value = gettype($v) === 'string' || gettype($v) === 'integer';
                    }
                    if ($primitive === 'string' && !is_null($enum) && is_array($enum)) {
                        if (!in_array($v.'', $enum)) {
                            return "WHERE: {$entityKey} property value \"{$v}\" does not meet list restrictions of allowed options [".implode(
                                ', ',
                                $enum
                              )."]";
                        }
                    }
                    if (!$valid_value) {
                        return "WHERE: {$entityKey} {$operator} expects array[{$primitive}] but got ".gettype(
                            $v
                          ).": {$v}";
                    }
                }
            } else {
                if (is_array($value)) {
                    return "WHERE: {$entityKey} {$operator} received an array, expected {$primitive}";
                }
                $valid = gettype($value) == $primitive;
                if ($primitive === 'string' && !is_null($enum) && is_array($enum)) {
                    if (!in_array($value.'', $enum)) {
                        return "WHERE: {$entityKey} property value \"{$value}\" does not meet list restrictions of allowed options [".implode(
                            ', ',
                            $enum
                          )."]";
                    }
                }
                if (!$valid) {
                    if ($primitive === 'timestamp') {
                        $valid = gettype($value) === 'string' || gettype($value) === 'integer';
                    }
                    if (!$valid) {
                        return "WHERE: {$entityKey} {$operator} expects {$primitive} but got ".gettype(
                            $value
                          ).": {$value}";
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get the SDK data type for a property on an entity.
     *
     * Returns the type string as defined in the resource's PROP_TYPES constant.
     * These are SDK-specific types that map to PHP primitives via Converter.
     *
     * ## SDK Data Types
     *
     * | Type                    | Description                              |
     * |-------------------------|------------------------------------------|
     * | `text`                  | String value                             |
     * | `integer`               | Integer value                            |
     * | `decimal`               | Float/decimal value                      |
     * | `boolean`               | Boolean value                            |
     * | `date`                  | Date string (Y-m-d)                      |
     * | `datetime`              | DateTime string (Y-m-d H:i:s)            |
     * | `resource:entityname`   | Foreign key to another resource          |
     * | `collection:entityname` | Array of related entities                |
     * | `enum:val1\|val2\|val3` | Enumerated string values                 |
     * | `intEnum:25\|50\|75`    | Enumerated integer values                |
     *
     * ## Example
     *
     * ```php
     * $type = AbstractEntity::getPropertyDataType('project', 'name');
     * // Returns: 'text'
     *
     * $type = AbstractEntity::getPropertyDataType('task', 'project_id');
     * // Returns: 'resource:project'
     * ```
     *
     * @param string $entityKey The entity key (e.g., 'project', 'task')
     * @param string $prop      The property name
     *
     * @throws Exception If EntityMap lookup fails
     *
     * @return string|null The data type string, or NULL if property not found
     *
     * @see Converter::getPrimitiveType() For converting to PHP types
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
     * Validate and clean up field/include lists and WHERE conditions for API requests.
     *
     * Separates a combined field list into:
     * - **select**: Properties to return on the main entity
     * - **include**: Related entities to load
     *
     * Also validates WHERE conditions against the entity schema.
     *
     * ## Example
     *
     * ```php
     * // Mixed list of props and includes
     * $fields = ['name', 'description', 'client', 'tasks.name'];
     * $where = [Project::where('active', true)];
     *
     * [$select, $include, $where] = AbstractEntity::cleanupForRequest('project', $fields, $where);
     * // $select = ['name', 'description', 'id']  (id always added)
     * // $include = ['client', 'tasks.name', 'tasks.id']
     * // $where = [RequestCondition with dataType set]
     * ```
     *
     * @param string             $entityKey    The entity key being queried
     * @param string[]           $fields       Mixed array of property and include names
     * @param RequestCondition[] $whereFilters Array of WHERE/HAS conditions
     *
     * @throws Exception If validation fails
     *
     * @return array Three-element array: [select[], include[], where[]]
     *
     * @internal Used by fetch() and list() methods
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
     * Validate and clean up an include list for API requests.
     *
     * Processes include specifications at any nesting depth, ensuring all paths
     * are valid according to entity schemas. Also ensures ID fields are included
     * when specific properties are selected on related entities.
     *
     * ## Include Formats
     *
     * - `'client'` - Include the client relation
     * - `'client.name'` - Include client with only name property
     * - `'tasks.assignees.name'` - Nested include (tasks -> assignees -> name)
     *
     * ## Caching
     *
     * Results are cached via ScrubCache to avoid revalidating the same
     * include lists multiple times in a single request.
     *
     * ## Example
     *
     * ```php
     * $includes = ['client', 'tasks.name', 'invalid_relation'];
     * $cleaned = AbstractEntity::scrubInclude($includes, 'project');
     * // Returns: ['client', 'tasks.name', 'tasks.id']
     * // Note: 'invalid_relation' was removed, 'tasks.id' was auto-added
     * ```
     *
     * @param string[] $include   Array of include paths to validate
     * @param string   $entityKey The root entity key to validate against
     *
     * @throws Exception If EntityMap lookups fail
     *
     * @return string[] Cleaned array with only valid includes
     *
     * @see ScrubCache For include validation caching
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
                    if (!in_array($tmp, $realInclude)) {
                        $realInclude[] = $tmp;
                    }
                }
            }
        }
        $flipped = array_flip($realInclude);
        foreach ($realInclude as $i) {
            $tmp = substr($i, strlen($i) - 4, 3) === '.id' ? substr($i, 0, -4) : null;
            if (!is_null($tmp) && isset($flipped[$tmp])) {
                unset($flipped[$i]);
            }
        }
        $cleaned = array_flip($flipped);
        ScrubCache::cache()->push($entityKey, $include, $cleaned);

        return $cleaned;
    }

    /**
     * Validate and enrich WHERE conditions for API requests.
     *
     * Processes each RequestCondition to:
     * 1. Validate the property/include path exists
     * 2. Set the appropriate dataType for value conversion
     * 3. Filter out invalid conditions
     *
     * Supports both WHERE conditions (property filters) and HAS conditions
     * (relationship count filters).
     *
     * ## Example
     *
     * ```php
     * $conditions = [
     *     Project::where('active', true),
     *     Project::where('invalid_prop', 'value'),  // Will be filtered out
     *     Project::has('tasks', 0, '>')
     * ];
     *
     * $cleaned = AbstractEntity::scrubWhere($conditions, 'project');
     * // Returns array with only valid conditions, each with dataType set
     * ```
     *
     * @param RequestCondition[] $where     Array of conditions to validate
     * @param string             $entityKey The entity key to validate against
     *
     * @throws Exception If EntityMap lookups fail
     * @return RequestCondition[] Array of validated conditions with dataTypes set
     *
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
                    if (self::isIncludable($entityKey, $includeKey) && EntityMap::exists($eKey) && self::isProp(
                        $eKey,
                        $eProp
                      )) {
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
     * Get entity configuration for cloning to child entities.
     *
     * Returns an associative array of all configuration settings that should
     * be inherited by child entities created during hydration.
     *
     * ## Example
     *
     * ```php
     * // Get config from parent entity
     * $config = $project->getConfiguration();
     *
     * // Use it to create child with same settings
     * $task = new Task($config);
     * ```
     *
     * @return array Configuration array with keys:
     *               - connection: Paymo instance
     *               - overwriteDirtyWithRequests: bool
     *               - useCacheIfAvailable: bool
     *
     * @see self::setConfiguration() For applying configuration
     */
    public function getConfiguration()
    {
        return [
          'connection'                 => $this->connection,
          'overwriteDirtyWithRequests' => $this->overwriteDirtyWithRequests,
          'useCacheIfAvailable'        => $this->useCacheIfAvailable
        ];
    }

    /**
     * Get the API response key override if defined.
     *
     * Some Paymo API endpoints return data under a different key than expected.
     * This method checks for an API_RESPONSE_KEY constant and formats it
     * for use in Request methods.
     *
     * ## Example
     *
     * ```php
     * // TaskAssignment uses 'userstasks' path but 'taskassignments' response key
     * $respKey = $this->getResponseKey($taskAssignment);
     * // Returns: ':taskassignments'
     *
     * // Most entities have no override
     * $respKey = $this->getResponseKey($project);
     * // Returns: ''
     * ```
     *
     * @param AbstractResource|string $objClass Instance or class name to check
     *
     * @return string Empty string or ':responseKey' format
     *
     * @internal Used by Request methods for response parsing
     */
    protected function getResponseKey($objClass)
    {
        return $objClass::API_RESPONSE_KEY ? ':'.$objClass::API_RESPONSE_KEY : '';
    }

    /**
     * Validate parameters for fetch operations.
     *
     * Ensures field and where parameters are properly formatted arrays
     * with correct element types.
     *
     * @param string[]           $fields Array of field names (must be strings)
     * @param RequestCondition[] $where  Array of conditions (must be RequestCondition)
     *
     * @throws Exception If any validation fails with descriptive message
     *
     * @return bool TRUE if all validation passes
     *
     * @internal Called by fetch() before making API requests
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
            if (!$w instanceof RequestCondition) {
                throw new Exception("Where conditions must all be instances of the RequestCondition class");
            }
        }

        return true;
    }

}

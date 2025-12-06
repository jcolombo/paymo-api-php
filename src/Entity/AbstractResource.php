<?php
/**
 * Paymo API PHP SDK - Abstract Resource Base Class
 *
 * The core base class for all Paymo single-entity resources. Provides complete CRUD
 * operations (Create, Read, Update, Delete), property management with dirty tracking,
 * relationship handling, and file uploads.
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
use Jcolombo\PaymoApiPhp\Configuration;
use Jcolombo\PaymoApiPhp\Entity\Collection\EntityCollection;
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Request;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
use stdClass;

/**
 * Abstract Resource Base Class
 *
 * The primary class that all Paymo entity resources inherit from. This class provides
 * the complete interface for interacting with Paymo API resources including:
 *
 * - **CRUD Operations**: Create, Read (fetch), Update, Delete
 * - **Property Management**: Magic getters/setters with type validation
 * - **Dirty Tracking**: Knows which properties have changed since last save/load
 * - **Relationship Handling**: Load and manage related entities (includes)
 * - **File Uploads**: Upload images and files to entities
 * - **Query Building**: Static methods for WHERE and HAS conditions
 *
 * ## Basic Usage
 *
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
 *
 * // Connect to Paymo
 * Paymo::connect('your-api-key');
 *
 * // Fetch a project
 * $project = Project::new()->fetch(12345);
 * echo $project->name;
 *
 * // Update it
 * $project->name = "New Project Name";
 * $project->update();
 *
 * // Create a new project
 * $newProject = Project::new();
 * $newProject->name = "My New Project";
 * $newProject->client_id = 123;
 * $newProject->create();
 *
 * // Delete a project
 * Project::deleteById(12345);
 * ```
 *
 * ## Required Class Constants
 *
 * All child resource classes MUST define these constants:
 *
 * | Constant          | Type     | Description                                      |
 * |-------------------|----------|--------------------------------------------------|
 * | `LABEL`           | string   | Human-readable name (e.g., "Project")            |
 * | `API_PATH`        | string   | API endpoint path (e.g., "projects")             |
 * | `API_ENTITY`      | string   | Entity key for EntityMap (e.g., "project")       |
 * | `REQUIRED_CREATE` | string[] | Props required for create() operation            |
 * | `READONLY`        | string[] | Props that cannot be manually set                |
 * | `CREATEONLY`      | string[] | Props that can only be set during create()       |
 * | `INCLUDE_TYPES`   | array    | Valid includable relations with their types      |
 * | `PROP_TYPES`      | array    | Property definitions with data types             |
 * | `WHERE_OPERATIONS`| array    | Allowed WHERE operators per property             |
 *
 * ## Property Types
 *
 * Properties are defined in PROP_TYPES with these SDK types:
 *
 * - `text` - String value
 * - `integer` - Integer value
 * - `decimal` - Float value
 * - `boolean` - Boolean value
 * - `date` - Date string (Y-m-d)
 * - `datetime` - DateTime string
 * - `resource:entityname` - Foreign key reference
 * - `collection:entityname` - Array of related entities
 * - `enum:val1|val2` - Enumerated string values
 * - `intEnum:25|50|75` - Enumerated integer values
 *
 * ## Dirty Tracking
 *
 * The SDK tracks which properties have been modified since the last API operation:
 *
 * ```php
 * $project = Project::new()->fetch(123);
 * echo $project->isDirty();  // false
 *
 * $project->name = "Changed";
 * echo $project->isDirty();  // true
 *
 * $dirtyKeys = $project->getDirtyKeys();  // ['name']
 * $dirtyValues = $project->getDirtyValues();
 * // ['name' => ['original' => 'Old Name', 'current' => 'Changed']]
 *
 * $project->update();  // Only sends 'name' to API
 * echo $project->isDirty();  // false (after successful update)
 * ```
 *
 * ## Including Related Entities
 *
 * Load related entities in a single API call:
 *
 * ```php
 * // Include specific relations
 * $project = Project::new()->fetch(123, ['client', 'tasks', 'milestones']);
 *
 * // Access included relations
 * echo $project->client->name;
 * foreach ($project->tasks as $task) {
 *     echo $task->name;
 * }
 * ```
 *
 * @package Jcolombo\PaymoApiPhp\Entity
 * @author  Joel Colombo <jc-dev@360psg.com>
 * @since   0.1.0
 *
 * @see AbstractEntity For base entity functionality
 * @see AbstractCollection For list operations
 * @see EntityMap For entity registration
 */
abstract class AbstractResource extends AbstractEntity
{

    /**
     * Constants that must be defined by all child resource classes.
     *
     * During construction in development mode, the class verifies these
     * constants exist. This catches configuration errors early.
     *
     * @var string[] List of required constant names
     */
    public const REQUIRED_CONSTANTS = [
        'LABEL', 'API_PATH', 'API_ENTITY', 'REQUIRED_CREATE', 'READONLY', 'CREATEONLY', 'INCLUDE_TYPES', 'PROP_TYPES', 'WHERE_OPERATIONS'
    ];

    /**
     * Override for API response key when it differs from the request path.
     *
     * Most endpoints return data under the same key as the path (e.g., GET /projects
     * returns {"projects": [...]}). Some endpoints differ - set this constant in the
     * child class to handle those cases.
     *
     * @var string|null Response key override, or NULL to use API_PATH
     *
     * @example TaskAssignment uses API_PATH='userstasks' but API_RESPONSE_KEY='taskassignments'
     */
    public const API_RESPONSE_KEY = null;

    /**
     * Current property values for this entity instance.
     *
     * Keys are property names as defined in PROP_TYPES.
     * Values are the current (potentially dirty) values.
     *
     * @var array<string, mixed> Associative array of property values
     */
    protected $props = [];

    /**
     * Storage for properties not defined in PROP_TYPES.
     *
     * When the API returns properties not defined in PROP_TYPES, or when
     * setting unknown properties, they're stored here rather than causing errors.
     * Useful for handling undocumented API fields.
     *
     * @var array<string, mixed> Associative array of unlisted property values
     */
    protected $unlisted = [];

    /**
     * Property values as they were after the last API load/save.
     *
     * Used for dirty tracking - comparing current props to loaded props
     * determines which values have changed and need to be sent in updates.
     *
     * @var array<string, mixed> Snapshot of property values after last API operation
     */
    protected $loaded = [];

    /**
     * Hydrated related entities (includes) for this resource.
     *
     * Keys are the include names (e.g., 'client', 'tasks').
     * Values are either AbstractResource instances (single relations)
     * or AbstractCollection instances (collection relations).
     *
     * @var array<string, AbstractResource|AbstractCollection> Related entities
     */
    protected $included = [];

    /**
     * Initialize a new resource instance.
     *
     * ## Constructor Options
     *
     * ```php
     * // Default connection
     * $project = new Project();
     *
     * // With API key
     * $project = new Project('api-key');
     *
     * // With existing connection
     * $project = new Project($paymoConnection);
     *
     * // With ID pre-populated
     * $project = new Project(null, 12345);
     *
     * // Configuration array (internal use)
     * $project = new Project(['connection' => $paymo, 'useCacheIfAvailable' => false]);
     * ```
     *
     * @param Paymo|string|array|null $paymo Connection specification:
     *                                       - null: Use default connection
     *                                       - string: API key
     *                                       - Paymo: Connection instance
     *                                       - array: Configuration array
     * @param int|null                $id    Optional ID to pre-populate
     *
     * @throws Exception If required constants are missing (dev mode only)
     * @throws Exception If connection cannot be established
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
     * Mark current property values as "clean" (saved state).
     *
     * Copies current props to loaded props, resetting dirty tracking.
     * Called automatically after fetch/create/update operations.
     *
     * ## Warning
     *
     * Calling this manually will make the SDK think all current values
     * have been saved, even if they haven't been sent to the API.
     *
     * ## Example
     *
     * ```php
     * $project->name = "Changed";
     * echo $project->isDirty();  // true
     *
     * $project->wash();
     * echo $project->isDirty();  // false (but NOT saved to API!)
     * ```
     *
     * @return AbstractResource Returns $this for method chaining
     */
    public function wash()
    {
        $this->loaded = $this->props;

        return $this;
    }

    /**
     * Factory method to create a new resource instance.
     *
     * Creates an instance using the class registered in EntityMap, allowing
     * for custom class overrides while maintaining consistent API.
     *
     * ## Example
     *
     * ```php
     * // Create new empty resource
     * $project = Project::new();
     *
     * // With connection
     * $project = Project::new('api-key');
     *
     * // With ID
     * $project = Project::new(null, 12345);
     * ```
     *
     * @param Paymo|string|array|null $paymo Connection specification
     * @param int|null                $id    Optional ID to pre-populate
     *
     * @return AbstractResource New instance of the appropriate class
     *
     * @throws Exception If EntityMap has no class for this entity
     */
    public static function new($paymo = null, $id = null)
    {
        return parent::newResource($paymo, $id);
    }

    /**
     * Create a collection for listing entities of this type.
     *
     * Returns an EntityCollection (or specialized subclass) configured
     * for fetching lists of this resource type.
     *
     * ## Example
     *
     * ```php
     * // Get all projects
     * $projects = Project::list()->fetch();
     *
     * // With filters
     * $activeProjects = Project::list()
     *     ->where(Project::where('active', true))
     *     ->fetch();
     *
     * // With includes
     * $projects = Project::list()
     *     ->include(['client', 'tasks'])
     *     ->fetch();
     * ```
     *
     * @param Paymo|string|array|null $paymo Connection specification
     *
     * @return EntityCollection Collection configured for this entity type
     *
     * @throws Exception If EntityMap has no collection class for this entity
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
     * Create a WHERE condition for filtering lists of this entity.
     *
     * Creates a RequestCondition object configured for this entity type,
     * with validation of property names and operators.
     *
     * ## Operators
     *
     * - `=` - Equals (default)
     * - `!=` - Not equals
     * - `<`, `<=`, `>`, `>=` - Comparisons
     * - `like`, `not like` - Pattern matching
     * - `in`, `not in` - Array membership
     * - `range` - Between two values
     *
     * ## Examples
     *
     * ```php
     * // Simple equality
     * Project::where('active', true);
     *
     * // With operator
     * Project::where('budget', 1000, '>');
     *
     * // Array membership
     * Project::where('status', ['active', 'completed'], 'in');
     *
     * // Pattern matching
     * Project::where('name', '%test%', 'like');
     *
     * // Date comparison
     * Project::where('created_on', '2024-01-01', '>=');
     * ```
     *
     * @param string $prop     Property name to filter on
     * @param mixed  $value    Value to compare against
     * @param string $operator Comparison operator (default: '=')
     * @param bool   $validate Whether to validate against entity schema (default: true)
     *
     * @return RequestCondition Configured condition for use with list()
     *
     * @throws Exception If property doesn't exist or operator not allowed (when $validate=true)
     *
     * @see AbstractCollection::where() For applying conditions to lists
     */
    public static function where($prop, $value, $operator = '=', $validate = true)
    {
        return RequestCondition::where($prop, $value, $operator, $validate, static::API_ENTITY);
    }

    /**
     * Create a HAS condition for filtering by relationship counts.
     *
     * HAS conditions filter entities based on the number of related entities
     * they have. Unlike WHERE, HAS conditions are applied after fetching
     * (they can't be sent to the API directly).
     *
     * ## Operators
     *
     * - `>` - More than (default)
     * - `>=` - At least
     * - `<` - Fewer than
     * - `<=` - At most
     * - `=` - Exactly
     * - `!=` - Not exactly
     *
     * ## Examples
     *
     * ```php
     * // Projects with at least one task
     * Project::has('tasks', 0, '>');
     *
     * // Projects with 5 or more tasks
     * Project::has('tasks', 5, '>=');
     *
     * // Projects with no milestones
     * Project::has('milestones', 0, '=');
     *
     * // Clients with between 2-5 projects
     * Client::has('projects', [2, 5], '>=<');
     * ```
     *
     * @param string    $include  Name of the includable relation to count
     * @param int|int[] $count    Count to compare against (or [min, max] for range)
     * @param string    $operator Comparison operator (default: '>')
     *
     * @return RequestCondition Configured condition for use with list()
     *
     * @throws Exception If include doesn't exist on this entity
     *
     * @see AbstractCollection::where() For applying conditions to lists
     */
    public static function has($include, $count = 0, $operator = '>')
    {
        return RequestCondition::has($include, $count, $operator, static::API_ENTITY);
    }

    /**
     * Delete an entity by ID without fetching it first.
     *
     * Convenience method that creates a temporary resource instance
     * with the given ID and calls delete() on it.
     *
     * ## Example
     *
     * ```php
     * // Delete project 12345
     * Project::deleteById(12345);
     *
     * // With specific connection
     * Project::deleteById(12345, 'api-key');
     * ```
     *
     * @param int                     $id    Entity ID to delete
     * @param Paymo|string|array|null $paymo Connection specification
     *
     * @return AbstractResource|null The resource instance after deletion (empty)
     *
     * @throws Exception If deletion fails
     *
     * @see self::delete() For instance method version
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
     * Delete this entity from Paymo.
     *
     * Permanently removes the entity from Paymo. Requires the entity
     * to have an ID set (either from fetch() or manual assignment).
     *
     * ## Warning
     *
     * This operation is **NOT REVERSIBLE**. The entity and potentially
     * related data will be permanently deleted.
     *
     * ## Example
     *
     * ```php
     * // Delete a fetched entity
     * $project = Project::new()->fetch(123);
     * $project->delete();
     *
     * // Delete by ID
     * $project = Project::new(null, 123);
     * $project->delete();
     * ```
     *
     * @return AbstractResource Returns $this (cleared of all data)
     *
     * @throws Exception If no ID is set on the entity
     *
     * @see self::deleteById() For static version
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
     * Clear all data from this entity instance.
     *
     * Resets the entity to an empty state while preserving the connection
     * and other configuration. Called automatically after delete().
     *
     * ## Example
     *
     * ```php
     * $project = Project::new()->fetch(123);
     * echo $project->name;  // "Some Project"
     *
     * $project->clear();
     * echo $project->name;  // null
     * echo $project->id;    // null
     * ```
     *
     * @return AbstractResource Returns $this for method chaining
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
     * Set one or more property values.
     *
     * Provides both single-property and bulk-property setting with validation.
     * An alternative to using magic setters when method chaining is desired.
     *
     * ## Examples
     *
     * ```php
     * // Single property
     * $project->set('name', 'New Name');
     *
     * // Multiple properties
     * $project->set([
     *     'name' => 'New Name',
     *     'description' => 'Description here',
     *     'client_id' => 123
     * ]);
     *
     * // Method chaining
     * $project = Project::new()
     *     ->set(['name' => 'Project', 'client_id' => 123])
     *     ->create();
     * ```
     *
     * @param string|array $key   Property name, or associative array of property => value
     * @param mixed        $value Value to set (ignored if $key is array)
     *
     * @return AbstractResource Returns $this for method chaining
     *
     * @throws Exception If property validation fails
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
     * Associate a related entity with this resource.
     *
     * @param string           $key    The include key to set
     * @param AbstractResource $object The related entity to associate
     * @param int|null         $index  Optional index for collection relations
     *
     * @return AbstractResource Returns $this for method chaining
     *
     * @todo Implementation pending - currently a placeholder
     */
    public function relate($key, $object, $index = null)
    {
        // Find the object type for $key if its an array include use the associative index
        return $this;
    }

    /**
     * Enable protection against overwriting dirty (unsaved) data.
     *
     * When enabled, calling fetch() on an entity with unsaved changes
     * will throw an exception instead of overwriting the changes.
     *
     * ## Example
     *
     * ```php
     * $project = Project::new()->fetch(123);
     * $project->name = "Unsaved change";
     *
     * // Without protection - changes lost!
     * $project->fetch(123);  // Overwrites $project->name
     *
     * // With protection
     * $project->protectDirtyOverwrites(true);
     * $project->fetch(123);  // Throws exception!
     * ```
     *
     * @param bool $protect TRUE to enable protection, FALSE to disable
     *
     * @return AbstractResource Returns $this for method chaining
     *
     * @see self::isDirty() For checking dirty state before fetch
     */
    public function protectDirtyOverwrites($protect = true)
    {
        $this->overwriteDirtyWithRequests = !$protect;

        return $this;
    }

    /**
     * Ignore cache for this entity's API calls.
     *
     * When enabled, fetch() calls bypass the cache and always make
     * fresh API requests. The response is still cached for other entities.
     *
     * ## Example
     *
     * ```php
     * // Always get fresh data for this instance
     * $project = Project::new();
     * $project->ignoreCache(true);
     * $project->fetch(123);  // Fresh from API
     *
     * // Re-enable caching
     * $project->ignoreCache(false);
     * $project->fetch(123);  // May use cache
     * ```
     *
     * @param bool $ignore TRUE to bypass cache, FALSE to use cache
     *
     * @return AbstractResource Returns $this for method chaining
     *
     * @see Cache For global cache configuration
     */
    public function ignoreCache($ignore = true)
    {
        $this->useCacheIfAvailable = !$ignore;

        return $this;
    }

    /**
     * Fetch an entity from the Paymo API.
     *
     * Retrieves a single entity by ID, optionally including related entities
     * and limiting returned properties.
     *
     * ## Examples
     *
     * ```php
     * // Basic fetch
     * $project = Project::new()->fetch(123);
     *
     * // With includes
     * $project = Project::new()->fetch(123, ['client', 'tasks']);
     *
     * // With specific properties
     * $project = Project::new()->fetch(123, ['name', 'description', 'client']);
     *
     * // Skip cache for fresh data
     * $project = Project::new()->fetch(123, [], ['skipCache' => true]);
     *
     * // Using existing ID
     * $project = Project::new(null, 123);
     * $project->fetch();  // Uses ID from constructor
     * ```
     *
     * ## Options
     *
     * | Key       | Type | Description                              |
     * |-----------|------|------------------------------------------|
     * | skipCache | bool | Bypass cache, fetch fresh from API       |
     *
     * @param int|null $id      Entity ID to fetch (uses existing if null)
     * @param string[] $fields  Properties and includes to return
     * @param array    $options Request options (see table above)
     *
     * @return AbstractResource Returns $this, hydrated with API data
     *
     * @throws Exception If no ID is available
     * @throws Exception If dirty protection is enabled and entity is dirty
     *
     * @see self::protectDirtyOverwrites() For protecting unsaved changes
     * @see self::ignoreCache() For skipping cache on all fetches
     */
    public function fetch($id = null, $fields = [], $options = [])
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
        $skipCache = isset($options['skipCache']) && !!$options['skipCache'];
        $respKey = $this->getResponseKey($this);
        $response = Request::fetch($this->connection, $this::API_PATH.$respKey, $id,
                                   ['select' => $select, 'include' => $include, 'skipCache'=>$skipCache]);
        if ($response->result) {
            $this->_hydrate($response->result, $id);
            // @todo Populate a response summary of data on the object (like if it came from live, timestamp of request, timestamp of data retrieved/cache, etc
        }

        return $this;
    }

    /**
     * Check if any properties have unsaved changes.
     *
     * Compares current property values to the values loaded from the API
     * to determine if there are unsaved modifications.
     *
     * ## Example
     *
     * ```php
     * $project = Project::new()->fetch(123);
     * echo $project->isDirty();  // false
     *
     * $project->name = "Changed";
     * echo $project->isDirty();  // true
     *
     * $project->update();
     * echo $project->isDirty();  // false
     * ```
     *
     * @param bool $checkRelations If TRUE, also checks included relations recursively
     *
     * @return bool TRUE if any properties have been modified
     *
     * @see self::getDirtyKeys() For list of modified properties
     * @see self::getDirtyValues() For original and current values
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
     * Get list of property names that have been modified.
     *
     * Returns an array of property keys whose current values differ
     * from their loaded (saved) values.
     *
     * ## Example
     *
     * ```php
     * $project = Project::new()->fetch(123);
     * $project->name = "New Name";
     * $project->description = "New Desc";
     *
     * $dirty = $project->getDirtyKeys();
     * // ['name', 'description']
     * ```
     *
     * @return string[] Array of modified property names
     *
     * @see self::isDirty() For simple boolean check
     * @see self::getDirtyValues() For values comparison
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
     * Populate this entity from an API response.
     *
     * Internal method that processes API response data and populates
     * this entity's properties and includes. Called automatically by
     * fetch(), create(), and update().
     *
     * ## Process
     *
     * 1. Clears existing data
     * 2. Enables hydration mode (allows setting READONLY props)
     * 3. Sets each property from response
     * 4. Hydrates included relations recursively
     * 5. Disables hydration mode
     * 6. Snapshots props for dirty tracking
     *
     * @param object   $responseObject API response data as stdClass
     * @param int|null $objectId       The ID of this entity
     *
     * @throws Exception If include hydration fails
     *
     * @internal Called automatically by CRUD methods
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
     * Hydrate an included (related) entity from API response.
     *
     * Creates appropriate resource or collection instances for
     * included relations and populates them from the response data.
     *
     * @param string       $entityKey Include key (e.g., 'client', 'tasks')
     * @param object|array $object    Response data for the include
     *
     * @throws Exception If entity class cannot be found
     *
     * @internal Called by _hydrate()
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
     * Create a new entity in Paymo.
     *
     * Sends the current property values to the API to create a new entity.
     * On success, this entity is populated with the API response (including
     * the assigned ID).
     *
     * ## Required Properties
     *
     * Each resource type defines required properties in REQUIRED_CREATE.
     * The method validates these are set before making the API call.
     *
     * ## CREATEONLY Properties
     *
     * Some properties can only be set during creation (like project_id on a task).
     * These are listed in the CREATEONLY constant.
     *
     * ## Example
     *
     * ```php
     * // Create a project
     * $project = Project::new()
     *     ->set([
     *         'name' => 'New Project',
     *         'client_id' => 123
     *     ])
     *     ->create();
     *
     * echo $project->id;  // New ID from Paymo
     * ```
     *
     * ## Options
     *
     * | Key           | Type   | Default | Description                           |
     * |---------------|--------|---------|---------------------------------------|
     * | stripReadonly | bool   | false   | Remove readonly props before sending  |
     * | cancelReadonly| bool   | true    | Skip create if readonly props exist   |
     * | cascade       | bool   | true    | Create related entities (future)      |
     * | dataMode      | string | 'json'  | Request mode: 'json' or 'multipart'   |
     * | uploadProps   | array  | []      | Properties that are file paths        |
     *
     * @param array $options Create options (see table above)
     *
     * @return AbstractResource Returns $this with new ID and API data
     *
     * @throws Exception If required properties are missing
     *
     * @see self::update() For modifying existing entities
     */
    public function create($options = [])
    {
        $cancelReadonly = $options['cancelReadonly'] ?? true;
        $stripReadonly = $options['stripReadonly'] ?? false;
        $cascade = $options['cascade'] ?? true;
        $dataMode = $options['dataMode'] ?? 'json';
        $uploadProps = $options['uploadProps'] ?? [];
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
                $isNewCreatable = (!$this->get('id') && in_array($p, static::CREATEONLY));
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
        if ($continueCreate && (!isset($createWith['id'])
                || (is_int($createWith['id']) && $createWith['id'] < 1)
                || (is_string($createWith['id']) && $createWith['id'] == '')
            )) {
            $uploads = [];
            foreach ($uploadProps as $p) {
                $uploads[$p] = $this->props[$p];
                unset($createWith[$p]);
            }
            $respKey = $this->getResponseKey($this);
            $response = Request::create($this->connection, $this::API_PATH.$respKey, $createWith, $uploads, $dataMode);
            if ($response->result) {
                $this->_hydrate($response->result);
                // @todo Populate a response summary of data on the object (like if it came from live, timestamp of request, timestamp of data retrieved/cache, etc
            }
            if ($cascade) {
                // Create any children that are possible, currently cant create relations on the standalone resource
                //@todo Cascade through included entities and create them as well
            }
        }

        return $this;
    }

    /**
     * Validate complex required property expressions.
     *
     * Handles the logic operators in REQUIRED_CREATE:
     * - `|` - OR (any one required)
     * - `||` - XOR (exactly one required)
     * - `&` - AND (all required)
     *
     * @param string $key Property key or expression to validate
     *
     * @return bool TRUE if requirement is satisfied
     *
     * @internal Called by create()
     */
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
     * Get one or more property values.
     *
     * Retrieves property values using the magic getter. Supports both
     * single property retrieval and bulk retrieval.
     *
     * ## Examples
     *
     * ```php
     * // Single property
     * $name = $project->get('name');
     *
     * // Multiple properties
     * $data = $project->get(['name', 'description', 'active']);
     * // Returns: ['name' => 'Project', 'description' => '...', 'active' => true]
     * ```
     *
     * @param string|string[] $key Property name or array of property names
     *
     * @return mixed|array|null Single value, array of values, or null if not found
     *
     * @throws Exception If magic getter throws
     */
    public function get($key)
    {
        if (is_string($key)) {
            return $this->__get($key);
        } elseif (is_array($key)) {
            $values = [];
            foreach ($key as $v) {
                if (is_string($v)) {
                    $values[$v] = $this->get($v);
                }
            }

            return $values;
        }

        return null;
    }

    /**
     * Magic getter for property access.
     *
     * Allows accessing properties directly on the object. Checks in order:
     * 1. Defined properties (PROP_TYPES)
     * 2. Unlisted properties
     * 3. Included relations
     *
     * ## Example
     *
     * ```php
     * $project = Project::new()->fetch(123, ['client']);
     *
     * // Property access
     * echo $project->name;
     *
     * // Include access
     * echo $project->client->name;
     * ```
     *
     * @param string $name Property name
     *
     * @return mixed|null Property value or null if not set
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
     * Magic setter for property assignment.
     *
     * Validates and sets property values. READONLY properties are
     * blocked unless in hydration mode. Unknown properties go to unlisted.
     *
     * ## Property Rules
     *
     * - **READONLY**: Cannot be set manually (e.g., id, created_on)
     * - **CREATEONLY**: Can only be set before create() (e.g., project_id on task)
     * - **Normal**: Can be set anytime
     *
     * ## Example
     *
     * ```php
     * $project = Project::new();
     *
     * // Normal property
     * $project->name = "New Project";  // Works
     *
     * // CREATEONLY (before create)
     * $task = Task::new();
     * $task->project_id = 123;  // Works
     *
     * // CREATEONLY (after create)
     * $task = Task::new()->fetch(456);
     * $task->project_id = 789;  // Silently ignored
     *
     * // READONLY
     * $project->id = 999;  // Silently ignored (unless hydrating)
     * ```
     *
     * @param string $name  Property name
     * @param mixed  $value Value to set
     *
     * @throws Exception If property validation fails
     */
    public function __set($name, $value)
    {
        if ($this::isProp($this::API_ENTITY, $name)) {
            $noIdCreateOnly = (!$this->get('id') && in_array($name, $this::CREATEONLY));
            $canSet = ($this->hydrationMode || !in_array($name, $this::READONLY) || $noIdCreateOnly);
            if ($canSet) {
                $this->props[$name] = $value;
            }
        } else {
            $this->unlisted[$name] = $value;
        }
        // allow setting of a child included value
    }

    /**
     * Update this entity in Paymo.
     *
     * Sends only the modified (dirty) properties to the API. Requires
     * the entity to have an ID.
     *
     * ## Example
     *
     * ```php
     * $project = Project::new()->fetch(123);
     * $project->name = "Updated Name";
     * $project->description = "Updated description";
     * $project->update();  // Only sends name and description
     * ```
     *
     * ## Options
     *
     * | Key             | Type | Default | Description                         |
     * |-----------------|------|---------|-------------------------------------|
     * | updateRelations | bool | true    | Update dirty related entities       |
     * | createRelations | bool | true    | Create new related entities         |
     *
     * @param array $options Update options (see table above)
     *
     * @return AbstractResource Returns $this with updated API data
     *
     * @throws Exception If no ID is set
     *
     * @see self::create() For creating new entities
     * @see self::isDirty() For checking what will be sent
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
     * Upload an image to this entity.
     *
     * Convenience wrapper for upload() with 'image' as the default property.
     *
     * ## Example
     *
     * ```php
     * $client = Client::new()->fetch(123);
     * $client->image('/path/to/logo.png');  // Uploads as 'image' property
     *
     * // With custom property name
     * $user = User::new()->fetch(456);
     * $user->image('/path/to/avatar.jpg', 'profile_image');
     * ```
     *
     * @param string $filepath  Path to the image file
     * @param string $propKey   Property name for the image (default: 'image')
     * @param bool   $isPropKey Whether to validate against PROP_TYPES
     *
     * @return AbstractResource Returns $this with updated API data
     *
     * @throws Exception If entity has no ID
     * @throws Exception If file doesn't exist
     *
     * @see self::file() For non-image file uploads
     * @see self::upload() For the underlying implementation
     */
    public function image($filepath, $propKey = 'image', $isPropKey = true)
    {
        return $this->upload($filepath, $propKey, $isPropKey);
    }

    /**
     * Upload a file attachment to this entity.
     *
     * Sends a file to the API as a multipart form upload.
     *
     * ## Requirements
     *
     * - Entity must have an ID (call fetch() or create() first)
     * - File must exist at the specified path
     *
     * @param string $filepath  Path to the file to upload
     * @param string $propKey   Form field name for the upload
     * @param bool   $isPropKey Whether to validate against PROP_TYPES
     *
     * @return AbstractResource Returns $this with updated API data
     *
     * @throws Exception If entity has no ID
     * @throws Exception If file doesn't exist
     *
     * @internal Use image() or file() public methods instead
     */
    protected function upload($filepath, $propKey, $isPropKey = true)
    {
        // If there is no valid prop for the image, ignore this method
        if (!$this->get('id') || $this->get('id') < 1) {
            throw new Exception("File [{$propKey}] for {static::API_ENTITY} requires an ID be set for uploading");
        }
        if (!file_exists($filepath)) {
            throw new Exception("Upload file not found at {$filepath}");
        }
        if (!$isPropKey || static::isProp(static::API_ENTITY, $propKey)) {
            $respKey = $this->getResponseKey($this);
            $response = Request::upload($this->connection, static::API_PATH.$respKey, $this->get('id'), $propKey,
                                        $filepath);
            if ($response->result) {
                $this->_hydrate($response->result);
                // @todo Populate a response summary of data on the object (like if it came from live, timestamp of request, timestamp of data retrieved/cache, etc
            }
        }

        return $this;
    }

    /**
     * Upload a file to this entity.
     *
     * Convenience wrapper for upload() with 'file' as the default property
     * and no property validation.
     *
     * ## Example
     *
     * ```php
     * $task = Task::new()->fetch(123);
     * $task->file('/path/to/document.pdf');
     * ```
     *
     * @param string $filepath  Path to the file to upload
     * @param string $propKey   Form field name (default: 'file')
     * @param bool   $isPropKey Whether to validate against PROP_TYPES (default: false)
     *
     * @return AbstractResource Returns $this with updated API data
     *
     * @throws Exception If entity has no ID
     * @throws Exception If file doesn't exist
     *
     * @see self::image() For image uploads
     */
    public function file($filepath, $propKey = 'file', $isPropKey = false)
    {
        return $this->upload($filepath, $propKey, $isPropKey);
    }

    /**
     * Get all unlisted (undocumented) properties.
     *
     * Returns properties that were set but aren't defined in PROP_TYPES.
     * Useful for accessing undocumented API fields.
     *
     * @return array<string, mixed> Associative array of unlisted properties
     */
    public function unlisted()
    {
        return $this->unlisted;
    }

    /**
     * Get all current property values.
     *
     * Returns the current state of all defined properties.
     *
     * ## Example
     *
     * ```php
     * $project = Project::new()->fetch(123);
     * $props = $project->props();
     * // ['id' => 123, 'name' => 'Project', ...]
     *
     * // Include all possible properties (null if not set)
     * $allProps = $project->props(true);
     * ```
     *
     * @param bool $includeAll If TRUE, includes all PROP_TYPES keys (null if unset)
     *
     * @return array<string, mixed> Associative array of property values
     */
    public function props($includeAll = false)
    {
        $props = $this->props;
        if ($includeAll) {
            foreach($this::PROP_TYPES as $k => $t) {
                if (!isset($props[$k])) {
                    $props[$k] = null;
                }
            }
        }

        return $props;
    }

    /**
     * Get original and current values for dirty properties.
     *
     * Returns detailed information about modified properties including
     * both the loaded (original) value and the current value.
     *
     * ## Example
     *
     * ```php
     * $project = Project::new()->fetch(123);
     * $project->name = "Changed Name";
     * $project->budget = 5000;
     *
     * $dirty = $project->getDirtyValues();
     * // [
     * //     'name' => ['original' => 'Old Name', 'current' => 'Changed Name'],
     * //     'budget' => ['original' => null, 'current' => 5000]
     * // ]
     * ```
     *
     * @return array<string, array{original: mixed, current: mixed}> Dirty property details
     *
     * @see self::getDirtyKeys() For just the property names
     * @see self::isDirty() For simple boolean check
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
     * Export entity as a plain stdClass object.
     *
     * Converts the entity and its included relations to a simple
     * object suitable for JSON encoding or other serialization.
     *
     * ## Example
     *
     * ```php
     * $project = Project::new()->fetch(123, ['client', 'tasks']);
     *
     * $data = $project->flatten();
     * // stdClass with all props and nested includes
     *
     * // Strip null values
     * $data = $project->flatten(['stripNull' => true]);
     *
     * // Convert to JSON
     * $json = json_encode($project->flatten());
     * ```
     *
     * ## Options
     *
     * | Key       | Type | Default | Description                    |
     * |-----------|------|---------|--------------------------------|
     * | stripNull | bool | false   | Omit properties with null values |
     *
     * @param array $options Flatten options (see table above)
     *
     * @return stdClass Plain object with properties and includes
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

}

<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 10:48 PM
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
 *
 * ======================================================================================
 * ABSTRACT COLLECTION - ITERABLE CONTAINER FOR PAYMO RESOURCE LISTS
 * ======================================================================================
 *
 * This abstract class serves as the base for all collection types in the Paymo API SDK.
 * Collections are iterable containers that hold multiple AbstractResource instances
 * of a specific entity type (e.g., a collection of Projects, Tasks, or Clients).
 *
 * KEY FEATURES:
 * -------------
 * - Implements PHP's Iterator, ArrayAccess, JsonSerializable, and Countable interfaces
 * - Automatic hydration of API response data into typed resource objects
 * - WHERE and HAS condition support for filtered API queries
 * - Field selection and relationship includes for optimized data retrieval
 * - Dirty tracking across all contained resources
 * - Request options including cache bypass
 * - Fluent interface for method chaining
 *
 * ARCHITECTURE:
 * -------------
 * AbstractCollection extends AbstractEntity, inheriting connection management,
 * validation utilities, and configuration handling. Each concrete collection
 * (e.g., ProjectCollection) specifies its entity type via the EntityMap system.
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * // Fetch all projects with specific fields
 * $projects = Project::collection()->fetch(['name', 'client_id']);
 *
 * // Fetch with conditions (WHERE clauses)
 * $activeProjects = Project::collection()->fetch(
 *     ['name', 'status'],
 *     [Project::WHERE('active', '=', true)]
 * );
 *
 * // Include related resources
 * $projects = Project::collection()->fetch(['name', 'include:tasklists']);
 *
 * // Iterate like a native array
 * foreach ($projects as $project) {
 *     echo $project->name . "\n";
 * }
 *
 * // Array-style access
 * $firstProject = $projects[0];
 * $count = count($projects->raw());
 *
 * // Convert to plain array of stdClass objects
 * $plainData = $projects->flatten();
 *
 * // Bypass cache for fresh data
 * $projects = Project::collection()
 *     ->options(['skipCache' => true])
 *     ->fetch();
 * ```
 *
 * ENTITY RELATIONSHIP:
 * --------------------
 * Each collection is tightly coupled to a specific resource type:
 * - ProjectCollection → Project resources
 * - TaskCollection → Task resources
 * - ClientCollection → Client resources
 * - etc.
 *
 * This relationship is established through the EntityMap which provides:
 * - The resource class name for instantiation
 * - The collection class name for factory methods
 * - API path and entity key mappings
 *
 * @package    Jcolombo\PaymoApiPhp\Entity
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractEntity Parent class providing connection and validation utilities
 * @see        AbstractResource The resource type contained within collections
 * @see        EntityMap Maps entity keys to resource and collection classes
 * @see        Request Handles the actual API communication
 */

namespace Jcolombo\PaymoApiPhp\Entity;

use ArrayAccess;
use Countable;
use Exception;
use Iterator;
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Request;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
use JsonSerializable;
use RuntimeException;
use stdClass;

/**
 * Abstract base class for all Paymo API collection types.
 *
 * AbstractCollection provides a powerful, iterable container for managing lists of
 * Paymo API resources. It implements both Iterator and ArrayAccess interfaces,
 * allowing collections to be used naturally in foreach loops and with array syntax.
 *
 * The class handles all aspects of list retrieval from the Paymo API, including:
 * - Building and executing list queries with field selection
 * - Applying WHERE conditions for server-side filtering
 * - Processing HAS conditions for client-side filtering of relationships
 * - Hydrating API responses into typed resource objects
 * - Managing request options like cache bypass
 *
 * DESIGN PATTERN:
 * ---------------
 * Collections follow the Repository pattern, acting as an in-memory collection
 * of domain objects (resources) that can be queried and iterated. They abstract
 * away the details of data retrieval and hydration.
 *
 * ITERATION BEHAVIOR:
 * -------------------
 * The internal data array is keyed by resource ID, not sequential indices.
 * This means array access uses resource IDs: $collection[123] returns the
 * resource with ID 123, not the 124th item.
 *
 * ```php
 * // Fetch and iterate
 * $tasks = Task::collection()->fetch();
 *
 * // Using foreach (most common)
 * foreach ($tasks as $task) {
 *     echo $task->id . ': ' . $task->name . "\n";
 * }
 *
 * // Using array access with known ID
 * $specificTask = $tasks[456]; // Get task with ID 456
 *
 * // Get raw array for count or array functions
 * $taskArray = $tasks->raw();
 * echo "Total tasks: " . count($taskArray);
 *
 * // Check if ID exists
 * if (isset($tasks[789])) {
 *     echo "Task 789 exists";
 * }
 * ```
 *
 * @package Jcolombo\PaymoApiPhp\Entity
 */
abstract class AbstractCollection extends AbstractEntity implements Iterator, ArrayAccess, JsonSerializable, Countable
{
    /**
     * The entity key identifying the type of resources this collection contains.
     *
     * The entity key is a short string identifier (e.g., 'project', 'task', 'client')
     * that maps to resource and collection classes via the EntityMap. This key is used
     * throughout the SDK to look up class names, API paths, and other entity metadata.
     *
     * RELATIONSHIP TO ENTITYMAP:
     * --------------------------
     * The entity key is the primary lookup key for the EntityMap system:
     * - EntityMap::resource($entityKey) → Returns the resource class name
     * - EntityMap::collection($entityKey) → Returns the collection class name
     * - EntityMap::path($entityKey) → Returns the API endpoint path
     *
     * EXAMPLE VALUES:
     * ---------------
     * - 'project' → Jcolombo\PaymoApiPhp\Entity\Resource\Project
     * - 'task' → Jcolombo\PaymoApiPhp\Entity\Resource\Task
     * - 'client' → Jcolombo\PaymoApiPhp\Entity\Resource\Client
     *
     * @var string|null The entity key string, or null if not yet initialized
     *
     * @see EntityMap For the complete list of valid entity keys
     */
    protected ?string $entityKey = null;

    /**
     * The fully-qualified class name of the resource type this collection contains.
     *
     * This property holds the actual PHP class name (e.g., 'Jcolombo\PaymoApiPhp\Entity\Resource\Project')
     * that will be instantiated when hydrating API response data. It's used for static method
     * calls to access class constants like API_ENTITY, API_PATH, and LABEL.
     *
     * USAGE CONTEXT:
     * --------------
     * - Creating new resource instances during hydration
     * - Accessing static class constants for API configuration
     * - Validating that operations are appropriate for this resource type
     *
     * ```php
     * // Internal usage example (within hydration)
     * $resClass = $this->entityClass;
     * $resource = new $resClass($this->getConfiguration());
     * $resource->_hydrate($data, $data->id);
     * ```
     *
     * @var string|null The fully-qualified resource class name, or null if not initialized
     *
     * @see EntityMap::resource() Method that provides this class name from an entity key
     */
    protected ?string $entityClass = null;

    /**
     * The fully-qualified class name of this collection's concrete type.
     *
     * While $entityClass refers to the resource type, $collectionClass refers to
     * the collection's own concrete class (e.g., 'Jcolombo\PaymoApiPhp\Entity\Collection\ProjectCollection').
     * This is used in factory methods that need to instantiate the correct collection type.
     *
     * SELF-REFERENCE PURPOSE:
     * -----------------------
     * This property enables collections to create new instances of themselves when needed,
     * such as when splitting a collection or creating a filtered subset.
     *
     * @var string|null The fully-qualified collection class name, or null if not initialized
     *
     * @see EntityMap::collection() Method that provides this class name from an entity key
     */
    protected ?string $collectionClass = null;

    /**
     * Array of keys for Iterator interface implementation.
     *
     * Since $data is keyed by resource ID (not sequential integers), we need
     * to store the keys separately to enable proper iteration. This array is
     * populated by rewind() and used by current(), key(), next(), and valid().
     *
     * @var array Array of resource IDs (keys from $data)
     */
    private array $iteratorKeys = [];

    /**
     * The current position pointer for Iterator interface implementation.
     *
     * This integer tracks the current position within the $iteratorKeys array.
     * Combined with $iteratorKeys, this enables proper foreach iteration over
     * the associative $data array.
     *
     * ITERATOR IMPLEMENTATION:
     * ------------------------
     * - rewind() populates $iteratorKeys and sets $index to 0
     * - current() returns $data[$iteratorKeys[$index]]
     * - key() returns $iteratorKeys[$index] (the resource ID)
     * - next() increments $index
     * - valid() checks if $index < count($iteratorKeys)
     *
     * @var int Current position in the $iteratorKeys array, starting at 0
     */
    private int $index = 0;

    /**
     * The internal storage array containing all hydrated resource instances.
     *
     * This is the primary data container for the collection, holding all AbstractResource
     * instances that have been loaded from the API. The array is indexed by resource ID,
     * allowing both iteration and direct ID-based access.
     *
     * DATA STRUCTURE:
     * ---------------
     * ```php
     * [
     *     123 => Project { id: 123, name: 'Project A', ... },
     *     456 => Project { id: 456, name: 'Project B', ... },
     *     789 => Project { id: 789, name: 'Project C', ... },
     * ]
     * ```
     *
     * ACCESS PATTERNS:
     * ----------------
     * - foreach ($collection as $resource) - Sequential iteration
     * - $collection[123] - Direct access by resource ID
     * - $collection->raw() - Get the underlying array
     * - count($collection->raw()) - Get the total count
     *
     * @var AbstractResource[] Associative array of resource instances, keyed by resource ID
     */
    private array $data;

    /**
     * Configuration options that modify request behavior.
     *
     * This array stores options that are passed to API requests made by this collection.
     * Options can be set via the options() method and are merged with per-request options.
     *
     * AVAILABLE OPTIONS:
     * ------------------
     * | Option     | Type | Description                                              |
     * |------------|------|----------------------------------------------------------|
     * | skipCache  | bool | If true, bypasses cache and fetches live API data        |
     *
     * USAGE EXAMPLE:
     * --------------
     * ```php
     * // Set options on the collection
     * $collection->options(['skipCache' => true]);
     *
     * // Options are applied to subsequent fetch calls
     * $collection->fetch(['name', 'status']);
     *
     * // Or pass options directly to fetch
     * $collection->fetch(['name'], [], ['skipCache' => true]);
     * ```
     *
     * OPTION VALIDATION:
     * ------------------
     * Options are validated against an internal schema. Invalid options or
     * incorrectly typed values are silently ignored during validation.
     *
     * @var array<string, mixed> Associative array of validated options
     *
     * @see options() Method to set these options with validation
     * @see mergeOptions() Method to combine options for requests
     * @see validateOptions() Internal method that validates against schema
     */
    protected array $options = [];

    /**
     * Construct a new collection instance for the specified entity type.
     *
     * Initializes the collection with connection configuration and entity type mappings.
     * The constructor looks up the resource and collection class names from the EntityMap
     * and prepares the collection for data retrieval.
     *
     * INITIALIZATION PROCESS:
     * -----------------------
     * 1. Calls parent constructor to establish API connection
     * 2. Stores the entity key for future reference
     * 3. Looks up the resource class from EntityMap
     * 4. Looks up the collection class from EntityMap
     * 5. Initializes the iterator index to 0
     * 6. Initializes the data array as empty
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Direct instantiation (not typical - use factory methods instead)
     * $collection = new ProjectCollection('project', $paymoConnection);
     *
     * // Preferred: Use the resource's collection() method
     * $projects = Project::collection();
     *
     * // With explicit API key
     * $projects = Project::collection('your-api-key-here');
     *
     * // With configuration from another entity
     * $tasks = Task::collection($existingProject->getConfiguration());
     * ```
     *
     * CONNECTION INHERITANCE:
     * -----------------------
     * When passing configuration from another entity, the new collection will
     * share the same Paymo connection, ensuring consistent API credentials
     * and caching behavior across related operations.
     *
     * @param string                  $entityKey       The entity key identifying the resource type
     *                                                 (e.g., 'project', 'task', 'client')
     * @param array|Paymo|string|null $paymo           Connection configuration, which can be:
     *                                                 - string: API key to create new connection
     *                                                 - Paymo: Existing connection instance to reuse
     *                                                 - array: Configuration from getConfiguration()
     *                                                 - null: Use first available connection
     *
     * @throws Exception If the entity key is not registered in the EntityMap
     * @throws Exception If connection cannot be established with provided credentials
     *
     * @see EntityMap::resource() Looks up the resource class for the entity key
     * @see EntityMap::collection() Looks up the collection class for the entity key
     * @see AbstractEntity::__construct() Parent constructor for connection setup
     */
    public function __construct($entityKey, $paymo = null)
    {
        parent::__construct($paymo);
        $this->entityKey = $entityKey;
        $this->entityClass = EntityMap::resource($entityKey);
        $this->collectionClass = EntityMap::collection($entityKey);
        $this->index = 0;
        $this->data = [];
    }

    /**
     * Static factory method to create a new collection instance of the calling class.
     *
     * This factory method creates a new collection instance using the EntityMap to
     * determine the correct concrete class. It's an alternative to using the resource
     * class's collection() method directly.
     *
     * FACTORY PATTERN:
     * ----------------
     * This method delegates to parent::newCollection() which uses the EntityMap
     * to instantiate the correct collection class for the calling entity type.
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Using the factory method
     * $projects = ProjectCollection::new();
     *
     * // Equivalent to using the resource's collection method
     * $projects = Project::collection();
     *
     * // With explicit API key
     * $clients = ClientCollection::new('your-api-key');
     *
     * // With configuration from another entity
     * $tasks = TaskCollection::new($existingProject->getConfiguration());
     * ```
     *
     * IDE TYPEHINTING NOTE:
     * ---------------------
     * Because this method returns AbstractCollection (not the specific subclass),
     * IDE autocompletion won't know the exact type. For better IDE support,
     * use the resource class's collection() method instead:
     *
     * ```php
     * // Better IDE support with resource::collection()
     * $projects = Project::collection(); // IDE knows this returns ProjectCollection
     * ```
     *
     * @param array|Paymo|string|null $paymo Connection configuration:
     *                                       - string: API key for new connection
     *                                       - Paymo: Existing connection to reuse
     *                                       - array: Configuration from getConfiguration()
     *                                       - null: Use first available connection
     *
     * @throws Exception If connection cannot be established
     * @throws Exception If entity type is not properly registered in EntityMap
     *
     * @return static Returns new instance of the calling collection class
     *
     * @see AbstractEntity::newCollection() Parent factory method implementation
     * @see AbstractResource::collection() Alternative factory with better IDE support
     */
    public static function new($paymo = null) : AbstractCollection
    {
        return parent::newCollection($paymo);
    }

    /**
     * Set request options for this collection.
     *
     * Configures options that affect how API requests are made. Options are validated
     * against an internal schema before being stored, with invalid options silently ignored.
     * This method supports fluent chaining for easy integration with fetch() calls.
     *
     * AVAILABLE OPTIONS:
     * ------------------
     * | Option     | Type | Default | Description                                     |
     * |------------|------|---------|-------------------------------------------------|
     * | skipCache  | bool | false   | Bypass cache and always fetch live API data     |
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Skip cache for fresh data
     * $projects = Project::collection()
     *     ->options(['skipCache' => true])
     *     ->fetch(['name', 'status']);
     *
     * // Options persist across multiple fetch calls
     * $collection = Task::collection()->options(['skipCache' => true]);
     * $collection->fetch(['name']);          // Skips cache
     * $collection->fetch(['description']);   // Also skips cache
     *
     * // Reset options by passing empty array
     * $collection->options([]);
     * ```
     *
     * VALIDATION BEHAVIOR:
     * --------------------
     * - Unknown option keys are silently ignored
     * - Incorrectly typed values are silently ignored
     * - Only valid, correctly-typed options are stored
     *
     * This permissive validation allows forward-compatibility when new options
     * are added in future versions.
     *
     * @param array<string, mixed> $options Associative array of options to set.
     *                                      Defaults to empty array to reset all options.
     *
     * @return $this Returns the collection instance for method chaining
     *
     * @see validateOptions() Internal method that performs option validation
     * @see mergeOptions() Combines stored options with per-request options
     * @see fetch() Primary method that uses these options
     */
    public function options(array $options = []) : AbstractCollection
    {
        $this->options = $this->validateOptions($options);

        return $this;
    }

    /**
     * Merge additional option arrays with the collection's stored options.
     *
     * Combines the collection's stored options with one or more additional option arrays.
     * This is primarily used internally by fetch() to merge per request options with
     * the collection's persistent options.
     *
     * MERGE BEHAVIOR:
     * ---------------
     * Options are merged in order using array_merge(), so later arrays
     * override earlier ones. The collection's stored options form the base, with
     * additional arrays overriding in order.
     *
     * MERGE ORDER:
     * ------------
     * 1. $this->options (collection's stored options, base)
     * 2. First passed array (overrides collection options)
     * 3. Second passed array (overrides first array)
     * 4. ... and so on
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Internal usage in fetch()
     * $collection->options(['skipCache' => false]);
     * $mergedOpts = $collection->mergeOptions(
     *     ['select' => 'name,status'],
     *     ['skipCache' => true]  // Overrides collection's false
     * );
     * // Result: ['skipCache' => true, 'select' => 'name,status']
     *
     * // Per request override pattern
     * $collection->options(['skipCache' => true]);  // Default to skip cache
     * $collection->fetch(['name'], [], ['skipCache' => false]); // But allow cache for this request
     * ```
     *
     * NOTE: This method does NOT modify $this->options. It returns a new merged
     * array, leaving the collection's stored options unchanged.
     *
     * @param array<string, mixed> ...$arrays One or more option arrays to merge with stored options.
     *                                        Later arrays override earlier ones.
     *
     * @return array<string, mixed> The combined options array
     *
     * @see options() Method to set the collection's base options
     * @see fetch() Primary consumer of this merge functionality
     */
    public function mergeOptions(array ...$arrays) : array
    {
        return array_merge($this->options, ...$arrays);
    }

    /**
     * Validate options against the internal schema, filtering out invalid entries.
     *
     * This private method validates option key-value pairs against a predefined schema.
     * Only options that exist in the schema AND have the correct type are included
     * in the returned array. Invalid options are silently filtered out.
     *
     * VALIDATION SCHEMA:
     * ------------------
     * The schema defines valid option keys and their expected types:
     *
     * | Option Key  | Expected Type | Description                              |
     * |-------------|---------------|------------------------------------------|
     * | skipCache   | boolean       | Whether to bypass the response cache     |
     *
     * VALIDATION RULES:
     * -----------------
     * 1. Option key must exist in the schema
     * 2. Option value must match the expected type exactly
     * 3. Type checking is strict (e.g., "true" string fails boolean check)
     *
     * EXTENSION POINT:
     * ----------------
     * To add new options, update the $schema array with the new key and type.
     * The isValidType() method handles the actual type verification.
     *
     * ```php
     * // Example schema extension
     * $schema = [
     *     'skipCache' => 'boolean',
     *     'timeout' => 'integer',      // New option
     *     'format' => 'string',         // Another new option
     * ];
     * ```
     *
     * @param array<string, mixed> $options Raw options array to validate
     *
     * @return array<string, mixed> Filtered array containing only valid options
     *
     * @see isValidType() Helper method for type checking
     * @see options() Public method that uses this for validation
     */
    private function validateOptions(array $options) : array
    {
        // Define the validation schema
        $schema = [
          'skipCache' => 'boolean',
            // Add new options here as needed
            // 'anotherOption' => 'string',
            // 'yetAnotherOption' => 'integer',
        ];

        // Initialize an array to store validated options
        $validatedOptions = [];

        // Iterate over the provided options
        foreach ($options as $key => $value) {
            // Check if the option exists in the schema
            if (array_key_exists($key, $schema)) {
                // Validate the option based on its expected type
                $expectedType = $schema[$key];
                if ($this->isValidType($value, $expectedType)) {
                    $validatedOptions[$key] = $value;
                }
            }
        }

        return $validatedOptions;
    }

    /**
     * Check whether a value matches the expected type string.
     *
     * This private helper method performs strict type checking for option validation.
     * It maps type name strings (from the validation schema) to PHP's native type
     * checking functions.
     *
     * SUPPORTED TYPES:
     * ----------------
     * | Type String | PHP Check    | Examples                    |
     * |-------------|--------------|------------------------------|
     * | 'boolean'   | is_bool()    | true, false                  |
     * | 'string'    | is_string()  | 'hello', '', "world"         |
     * | 'integer'   | is_int()     | 42, -1, 0                    |
     * | 'array'     | is_array()   | [], [1,2,3], ['key' => 'val']|
     *
     * STRICT TYPE CHECKING:
     * ---------------------
     * This method uses strict type checks, not loose comparisons:
     * - 'true' (string) is NOT a boolean
     * - '42' (string) is NOT an integer
     * - 1 (integer) is NOT a boolean
     *
     * EXTENSION:
     * ----------
     * To support additional types, add new cases to the switch statement:
     *
     * ```php
     * case 'float':
     *     return is_float($value);
     * case 'callable':
     *     return is_callable($value);
     * case 'object':
     *     return is_object($value);
     * ```
     *
     * @param mixed  $value        The value to type-check
     * @param string $expectedType The type name to check against
     *                             ('boolean', 'string', 'integer', 'array')
     *
     * @return bool True if value matches the expected type, false otherwise
     *              (including when expectedType is unknown)
     *
     * @see validateOptions() Primary consumer of this type checking
     */
    private function isValidType($value, string $expectedType) : bool
    {
        switch ($expectedType) {
            case 'boolean':
                return is_bool($value);
            case 'string':
                return is_string($value);
            case 'integer':
                return is_int($value);
            case 'array':
                return is_array($value);
            // Add other type checks as needed
            default:
                return false;
        }
    }

    /**
     * Fetch a list of resources from the Paymo API and populate this collection.
     *
     * This is the primary method for loading data into a collection. It builds and executes
     * an API list request, then hydrates the response into typed resource objects stored
     * in this collection. The method supports field selection, relationship includes,
     * WHERE conditions, and various request options.
     *
     * FIELD SELECTION:
     * ----------------
     * The $fields array controls which properties and relationships are returned:
     *
     * ```php
     * // Fetch all default fields
     * $projects = Project::collection()->fetch();
     *
     * // Fetch specific properties only
     * $projects = Project::collection()->fetch(['id', 'name', 'status']);
     *
     * // Include related resources using 'include:' prefix
     * $projects = Project::collection()->fetch([
     *     'name',
     *     'include:tasklists',
     *     'include:client'
     * ]);
     *
     * // Single field (will be converted to array)
     * $projects = Project::collection()->fetch('name');
     * ```
     *
     * WHERE CONDITIONS:
     * -----------------
     * Filter results using RequestCondition objects created via Resource::WHERE():
     *
     * ```php
     * // Single condition
     * $activeProjects = Project::collection()->fetch(
     *     ['name', 'status'],
     *     [Project::WHERE('active', '=', true)]
     * );
     *
     * // Multiple conditions (AND logic)
     * $filteredProjects = Project::collection()->fetch(
     *     ['name', 'budget'],
     *     [
     *         Project::WHERE('active', '=', true),
     *         Project::WHERE('budget', '>', 10000)
     *     ]
     * );
     *
     * // HAS conditions for relationship filtering
     * $projectsWithTasks = Project::collection()->fetch(
     *     ['name'],
     *     [Project::HAS('tasks', '>', 0)]
     * );
     * ```
     *
     * REQUEST OPTIONS:
     * ----------------
     * Pass options to modify request behavior:
     *
     * ```php
     * // Skip cache for fresh data
     * $projects = Project::collection()->fetch([], [], ['skipCache' => true]);
     *
     * // Or set options on the collection first
     * $projects = Project::collection()
     *     ->options(['skipCache' => true])
     *     ->fetch(['name']);
     * ```
     *
     * DIRTY PROTECTION:
     * -----------------
     * If the collection contains dirty (modified) resources and dirty protection
     * is enabled (overwriteDirtyWithRequests = false), fetch() will throw an
     * exception to prevent data loss. Either save changes first or disable
     * protection.
     *
     * RETURN VALUE:
     * -------------
     * Returns $this for method chaining. The collection is populated with
     * hydrated resource objects that can be iterated or accessed directly:
     *
     * ```php
     * $projects = Project::collection()->fetch(['name']);
     *
     * foreach ($projects as $project) {
     *     echo $project->name;
     * }
     *
     * $rawArray = $projects->raw();  // Get underlying array
     * $count = count($rawArray);     // Get total count
     * ```
     *
     * @param string[]|string     $fields  Fields and includes to return. Empty array returns
     *                                     all default fields. Strings are auto-converted to arrays.
     *                                     Use 'include:relationship' for related resources.
     * @param RequestCondition[]  $where   Optional conditions to filter results. Created via
     *                                     Resource::WHERE() or Resource::HAS() static methods.
     *                                     Single conditions are auto-converted to arrays.
     * @param array<string,mixed> $options Per-request options that merge with collection options.
     *                                     Keys: skipCache (bool)
     *
     * @throws Exception If collection has dirty resources and dirty protection is enabled
     * @throws Exception If connection is not established
     * @throws Exception If API request fails
     *
     * @return $this Returns the collection instance (now populated) for method chaining
     *
     * @see Request::list() Underlying method that executes the API call
     * @see _hydrate() Internal method that populates collection from response
     * @see isDirty() Checks if any resources have unsaved changes
     * @see AbstractResource::WHERE() Creates WHERE conditions
     * @see AbstractResource::HAS() Creates HAS conditions for relationships
     */
    public function fetch($fields = [], $where = [], array $options = []) : AbstractCollection
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        if (!is_array($where)) {
            $where = [$where];
        }
        $this->validateFetch($fields, $where);
        /** @var AbstractResource $resClass */
        $resClass = $this->entityClass;
        if (!$this->overwriteDirtyWithRequests && $this->isDirty()) {
            $label = $resClass::LABEL;
            throw new RuntimeException(
              "$label attempted to fetch new data while it had dirty entities and protection is enabled."
            );
        }
        $respKey = $this->getResponseKey($resClass);
        [$select, $include, $where] = static::cleanupForRequest($resClass::API_ENTITY, $fields, $where);

        $response = Request::list(
          $this->connection,
          $resClass::API_PATH.$respKey,
          $this->mergeOptions(['select' => $select, 'include' => $include, 'where' => $where], $options)
        );
        if ($response->result) {
            $this->_hydrate($response->result);
            // @todo Populate a response summary of data on the object (like if it came from live, timestamp of request, timestamp of data retrieved/cache, etc
        }

        return $this;
    }

    /**
     * Check if any resources in this collection have unsaved changes.
     *
     * Determines whether the collection contains any "dirty" resources - those that have
     * been modified since being loaded from the API. This is used by fetch() to prevent
     * accidental data loss when dirty protection is enabled.
     *
     * DIRTY STATE DETECTION:
     * ----------------------
     * A resource is considered dirty when:
     * - Property values have been changed via setters or magic __set
     * - The resource is new (not yet saved to the API)
     * - Related resources have been modified
     *
     * USAGE CONTEXT:
     * --------------
     * This method is primarily used internally by fetch() to determine if it's safe
     * to load new data:
     *
     * ```php
     * $projects = Project::collection()->fetch(['name']);
     *
     * // Modify a project
     * $projects[123]->name = 'New Name';
     *
     * // Check if collection is dirty
     * if ($projects->isDirty()) {
     *     echo "Collection has unsaved changes!";
     * }
     *
     * // This would throw if dirty protection is enabled
     * // $projects->fetch(['name']); // Exception!
     * ```
     *
     * IMPLEMENTATION NOTE:
     * --------------------
     *
     * @return bool True if any resource in the collection has unsaved changes, false otherwise
     *
     * @todo This method currently always returns false. The implementation should
     * iterate through all resources in $this->data and check each one's dirty state.
     *
     * FUTURE IMPLEMENTATION:
     * ----------------------
     * ```php
     * foreach ($this->data as $resource) {
     *     if ($resource->isDirty()) {
     *         return true;
     *     }
     * }
     * return false;
     * ```
     *
     * @see  AbstractResource::isDirty() Checks dirty state of individual resources
     * @see  fetch() Uses this to prevent accidental data loss
     * @see  $overwriteDirtyWithRequests Controls whether dirty protection is active
     */
    public function isDirty() : bool
    {
        // @todo Check the collection for any dirty entities
        return false;
    }

    /**
     * Populate the collection with resource instances from raw API response data.
     *
     * This internal method transforms an array of stdClass objects (from the API response)
     * into typed AbstractResource instances stored in this collection. Each raw object
     * is hydrated into the appropriate resource class and indexed by its ID.
     *
     * HYDRATION PROCESS:
     * ------------------
     * 1. Clears any existing data in the collection
     * 2. Enters hydration mode (disables dirty tracking)
     * 3. Iterates through each raw object in the response
     * 4. Creates a new resource instance for each object
     * 5. Calls the resource's _hydrate() to populate properties
     * 6. Stores the resource in $data array, keyed by ID
     * 7. Exits hydration mode
     *
     * HYDRATION MODE:
     * ---------------
     * During hydration, the $hydrationMode flag is set to true. This tells resources
     * not to track property changes as "dirty" since we're loading existing data,
     * not making modifications.
     *
     * DATA STRUCTURE AFTER HYDRATION:
     * -------------------------------
     * ```php
     * $this->data = [
     *     123 => Project { id: 123, name: 'Project A', ... },
     *     456 => Project { id: 456, name: 'Project B', ... },
     *     789 => Project { id: 789, name: 'Project C', ... },
     * ];
     * ```
     *
     * USAGE CONTEXT:
     * --------------
     * This method is called internally by fetch() after receiving a successful API
     * response. It should not typically be called directly in application code,
     * but is available for advanced use cases.
     *
     * ```php
     * // Direct usage (advanced/testing scenarios)
     * $collection = Project::collection();
     * $rawData = [
     *     (object)['id' => 1, 'name' => 'Project A'],
     *     (object)['id' => 2, 'name' => 'Project B'],
     * ];
     * $collection->_hydrate($rawData);
     * ```
     *
     * WARNING: Calling _hydrate() directly will clear all existing data in the
     * collection. Use with caution.
     *
     * @param stdClass[] $data Array of raw stdClass objects from API response.
     *                         Each object must have an 'id' property.
     *
     * @throws Exception If resource instantiation fails
     * @throws Exception If resource hydration fails
     *
     * @return void
     *
     * @see AbstractResource::_hydrate() Individual resource hydration
     * @see fetch() Primary caller of this method
     * @see clear() Called to reset collection before hydration
     */
    public function _hydrate(array $data) : void
    {
        /** @var AbstractResource $resClass */
        $resClass = $this->entityClass;
        $this->clear();
        $this->hydrationMode = true;
        foreach ($data as $o) {
            $tmp = new $resClass($this->getConfiguration());
            $tmp->_hydrate($o, $o->id);
            $this->data[$o->id] = $tmp;
        }
        $this->hydrationMode = false;
    }

    /**
     * Clear all resources from the collection, resetting it to an empty state.
     *
     * Removes all resource instances from the internal data array, resetting the
     * collection to its initial empty state. This is called automatically by
     * _hydrate() before populating with new data.
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Fetch projects
     * $projects = Project::collection()->fetch(['name']);
     * echo count($projects->raw()); // e.g., 25
     *
     * // Clear the collection
     * $projects->clear();
     * echo count($projects->raw()); // 0
     *
     * // Collection can be refetched
     * $projects->fetch(['name', 'status']);
     * ```
     *
     * SIDE EFFECTS:
     * -------------
     * - All resource references are removed
     * - Iterator index is NOT reset (call rewind() if needed)
     * - Any unsaved changes in resources are discarded
     *
     * @return void
     *
     * @see _hydrate() Calls clear() before populating with new data
     * @see raw() Returns the internal data array to check contents
     */
    public function clear() : void
    {
        $this->data = [];
    }

    /**
     * Sort the resources in this collection by specified criteria.
     *
     * This method enables client-side sorting of collection resources after they've
     * been fetched from the API. Unlike WHERE conditions which filter on the server,
     * sort operates on the local data.
     *
     * PLANNED FEATURES:
     * -----------------
     *
     * @param array $sortBy Array of sort conditions (not yet implemented)
     *
     * @return void
     * @todo This method is not yet implemented. Future implementation will include:
     *
     * - Static sort condition builders: `Resource::sort('prop', 'ASC')`
     * - Multi-field sorting: `[Resource::sort('name', 'ASC'), Resource::sort('date', 'DESC')]`
     * - Sortable field validation via new SORTABLE_ON constant
     * - Custom sort callbacks for complex sorting logic
     *
     * PROPOSED USAGE:
     * ---------------
     * ```php
     * // Sort by single field
     * $projects->sort([Project::sort('name', 'ASC')]);
     *
     * // Sort by multiple fields
     * $tasks->sort([
     *     Task::sort('priority', 'DESC'),
     *     Task::sort('name', 'ASC')
     * ]);
     * ```
     *
     * IMPLEMENTATION NOTES:
     * ---------------------
     * The implementation should follow the WHERE/HAS pattern:
     * - New SORTABLE_ON constant in resources (empty = all allowed, null = none, array = specific fields)
     * - CollectionSort utility class similar to RequestCondition
     * - Post-processing that modifies $this->data order
     *
     */
    public function sort(array $sortBy = []) : void
    {
        // @todo Add a sort by system similar to the WHERE calls that will post-process sort the list
        // Resource::sort('prop', 'direction=ASC') calls to CollectionSort::sort(...)
        // Add new constant SORTABLE_ON = []. If key not defined, allow. If null, not allowed.
        //     If string, call collection method.
    }

    /**
     * Convert all resources in the collection to plain stdClass objects.
     *
     * Transforms the collection into an array of simple stdClass objects by calling
     * flatten() on each contained resource. This is useful for JSON serialization,
     * debugging, or when you need to pass data to systems that don't understand
     * the SDK's resource classes.
     *
     * FLATTEN OPTIONS:
     * ----------------
     * The $options parameter is passed to each resource's flatten() method and
     * controls how the flattening is performed. See AbstractResource::flatten()
     * for available options.
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Fetch and flatten
     * $projects = Project::collection()->fetch(['name', 'status']);
     * $plainData = $projects->flatten();
     *
     * // Result is array of stdClass
     * foreach ($plainData as $id => $obj) {
     *     echo $obj->name;  // Access as plain object properties
     * }
     *
     * // JSON encode the flattened data
     * $json = json_encode($plainData, JSON_PRETTY_PRINT);
     *
     * // With flatten options
     * $plainData = $projects->flatten([
     *     'includeNulls' => false,
     *     'excludeProps' => ['internal_field']
     * ]);
     * ```
     *
     * RETURN STRUCTURE:
     * -----------------
     * Returns an associative array keyed by resource ID (cast to int):
     *
     * ```php
     * [
     *     123 => stdClass { name: 'Project A', status: 'active' },
     *     456 => stdClass { name: 'Project B', status: 'completed' },
     * ]
     * ```
     *
     * @param array $options Flatten options passed to each resource.
     *                       See AbstractResource::flatten() for details.
     *
     * @return stdClass[] Associative array of stdClass objects, keyed by resource ID
     *
     * @see AbstractResource::flatten() Individual resource flatten method with option details
     * @see raw() Returns the array of resource objects (not flattened)
     */
    public function flatten(array $options = []) : array
    {
        $data = [];
        foreach ($this->data as $k => $resource) {
            $data[(int)$k] = $resource->flatten($options);
        }

        return $data;
    }

    /**
     * Reset the iterator pointer to the beginning of the collection.
     *
     * Implements Iterator::rewind(). This method is called automatically by PHP
     * when starting a foreach loop, and can be called manually to restart iteration.
     *
     * IMPLEMENTATION NOTE:
     * --------------------
     * Since $data is keyed by resource IDs (not sequential integers), this method
     * populates $iteratorKeys with array_keys($data) and sets $index to 0.
     * This allows current(), key(), and valid() to properly traverse the associative array.
     *
     * USAGE IN FOREACH:
     * -----------------
     * ```php
     * // Automatic - rewind() called at start of foreach
     * foreach ($projects as $project) {
     *     echo $project->name;
     * }
     *
     * // Manual restart
     * $projects->rewind();
     * while ($projects->valid()) {
     *     echo $projects->current()->name;
     *     $projects->next();
     * }
     * ```
     *
     * @return void
     *
     * @see Iterator PHP's Iterator interface
     * @see current() Gets the element at current position
     * @see next() Advances the pointer
     * @see valid() Checks if current position is valid
     */
    public function rewind() : void
    {
        $this->iteratorKeys = array_keys($this->data);
        $this->index = 0;
    }

    /**
     * Get the resource at the current iterator position.
     *
     * Implements Iterator::current(). Returns the AbstractResource instance
     * at the current index position. This is called automatically during
     * foreach iteration to get each element.
     *
     * IMPLEMENTATION NOTE:
     * --------------------
     * Uses $iteratorKeys[$index] to get the actual resource ID, then looks up
     * that ID in $data. This is necessary because $data is keyed by resource IDs,
     * not sequential integers.
     *
     * USAGE:
     * ------
     * ```php
     * // In foreach (automatic)
     * foreach ($projects as $project) {
     *     // $project is the result of current() for each iteration
     *     echo $project->name;
     * }
     *
     * // Manual iteration
     * $projects->rewind();
     * $firstProject = $projects->current();
     * ```
     *
     * @return AbstractResource|mixed The resource at the current iterator position.
     *                                May return null if position is invalid.
     *
     * @see Iterator PHP's Iterator interface
     * @see rewind() Resets to first position
     * @see next() Moves to next position
     * @see key() Gets the current position/key
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        if (!isset($this->iteratorKeys[$this->index])) {
            return null;
        }
        $resourceId = $this->iteratorKeys[$this->index];
        return $this->data[$resourceId];
    }

    /**
     * Get the key (resource ID) at the current iterator position.
     *
     * Implements Iterator::key(). Returns the actual resource ID at the current
     * position, enabling proper `foreach ($collection as $id => $resource)` syntax.
     *
     * IMPLEMENTATION NOTE:
     * --------------------
     * Returns $iteratorKeys[$index], which is the actual resource ID stored in $data.
     * This ensures that `foreach ($projects as $id => $project)` provides the correct
     * resource ID as $id, not the sequential position.
     *
     * USAGE:
     * ------
     * ```php
     * // In foreach (automatic)
     * foreach ($projects as $id => $project) {
     *     // $id is the result of key() for each iteration - the actual resource ID
     *     echo "Project #{$id}: {$project->name}";
     * }
     *
     * // Manual iteration
     * $projects->rewind();
     * $firstId = $projects->key();  // Returns the ID of first resource
     * ```
     *
     * @return int|null The current resource ID, or null if position is invalid
     *
     * @see Iterator PHP's Iterator interface
     * @see current() Gets the element at current position
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->iteratorKeys[$this->index] ?? null;
    }

    /**
     * Advance the iterator to the next position.
     *
     * Implements Iterator::next(). Increments the internal index pointer
     * to move to the next resource in the collection. Called automatically
     * at the end of each foreach iteration.
     *
     * USAGE:
     * ------
     * ```php
     * // Automatic in foreach
     * foreach ($projects as $project) {
     *     // next() is called automatically after each iteration
     * }
     *
     * // Manual iteration
     * $projects->rewind();
     * while ($projects->valid()) {
     *     echo $projects->current()->name;
     *     $projects->next();  // Move to next
     * }
     * ```
     *
     * @return void
     *
     * @see Iterator PHP's Iterator interface
     * @see rewind() Resets to first position
     * @see valid() Checks if new position is valid
     */
    public function next() : void
    {
        ++$this->index;
    }

    /**
     * Check if the current iterator position contains a valid resource.
     *
     * Implements Iterator::valid(). Returns true if there is a resource at
     * the current index position. Used by PHP to determine when a foreach
     * loop should terminate.
     *
     * IMPLEMENTATION NOTE:
     * --------------------
     * Checks if $index is within bounds of $iteratorKeys array. This ensures
     * proper iteration termination since $data is keyed by resource IDs, not
     * sequential integers.
     *
     * USAGE:
     * ------
     * ```php
     * // Automatic in foreach (controls loop termination)
     * foreach ($projects as $project) {
     *     // valid() checked before each iteration
     * }
     *
     * // Manual iteration
     * $projects->rewind();
     * while ($projects->valid()) {  // Check validity
     *     echo $projects->current()->name;
     *     $projects->next();
     * }
     *
     * // Check before access
     * if ($projects->valid()) {
     *     $current = $projects->current();
     * }
     * ```
     *
     * @return bool True if current position has a resource, false otherwise
     *
     * @see Iterator PHP's Iterator interface
     * @see current() Gets element if valid
     * @see next() Advances position (may invalidate)
     */
    public function valid() : bool
    {
        return $this->index < count($this->iteratorKeys);
    }

    /**
     * Check if a resource exists at the specified offset (ID).
     *
     * Implements ArrayAccess::offsetExists(). Allows using isset() with array
     * syntax to check if a resource with a specific ID exists in the collection.
     *
     * USAGE:
     * ------
     * ```php
     * $projects = Project::collection()->fetch();
     *
     * // Check if resource with ID 123 exists
     * if (isset($projects[123])) {
     *     echo "Project 123 exists!";
     * }
     *
     * // Using offsetExists directly (less common)
     * if ($projects->offsetExists(456)) {
     *     echo "Project 456 exists!";
     * }
     * ```
     *
     * NOTE: The offset is typically the resource ID, not a sequential index.
     * After fetch(), the collection is keyed by resource ID.
     *
     * @param int|string $offset The offset (resource ID) to check
     *
     * @return bool True if a resource exists at the offset, false otherwise
     *
     * @see ArrayAccess PHP's ArrayAccess interface
     * @see offsetGet() Retrieves the resource at an offset
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset) : bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Get the resource at the specified offset (ID).
     *
     * Implements ArrayAccess::offsetGet(). Allows using array syntax to
     * retrieve resources by their ID from the collection.
     *
     * USAGE:
     * ------
     * ```php
     * $projects = Project::collection()->fetch(['name']);
     *
     * // Access by resource ID
     * $project = $projects[123];
     * echo $project->name;
     *
     * // With null coalescing for safety
     * $project = $projects[999] ?? null;
     *
     * // Using offsetGet directly (less common)
     * $project = $projects->offsetGet(456);
     * ```
     *
     * NOTE: Unlike typical arrays, the offset is the resource ID, not a
     * sequential 0-based index. Returns null if the offset doesn't exist.
     *
     * @param int|string $offset The offset (resource ID) to retrieve
     *
     * @return AbstractResource|null The resource at the offset, or null if not found
     *
     * @see ArrayAccess PHP's ArrayAccess interface
     * @see offsetExists() Checks if offset exists before accessing
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) : ?AbstractResource
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * Set a resource at the specified offset.
     *
     * Implements ArrayAccess::offsetSet(). Allows using array syntax to
     * add or replace resources in the collection. The offset must be numeric
     * (typically a resource ID) or null for append.
     *
     * USAGE:
     * ------
     * ```php
     * $projects = Project::collection();
     *
     * // Add a new project at a specific ID
     * $project = new Project();
     * $project->id = 123;
     * $projects[123] = $project;
     *
     * // Append without specifying ID
     * $projects[] = $anotherProject;  // Appends to end
     *
     * // Replace existing
     * $projects[123] = $updatedProject;  // Replaces project at 123
     * ```
     *
     * RESTRICTIONS:
     * -------------
     * - Only numeric (integer) offsets are allowed for direct assignment
     * - Null offset appends to the array (PHP default behavior)
     * - String offsets throw an Exception
     *
     * @param int|null         $offset The offset (resource ID) to set, or null to append
     * @param AbstractResource $value  The resource to store at the offset
     *
     * @throws Exception If attempting to use a non-numeric offset
     *
     * @return void
     *
     * @see ArrayAccess PHP's ArrayAccess interface
     * @see offsetGet() Retrieves resources by offset
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value) : void
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } elseif (is_int($offset)) {
            $this->data[$offset] = $value;
        } else {
            throw new RuntimeException("Attempting to set non-numeric index on EntityCollection data set");
        }
    }

    /**
     * Remove a resource at the specified offset (ID).
     *
     * Implements ArrayAccess::offsetUnset(). Allows using unset() with array
     * syntax to remove a resource from the collection.
     *
     * USAGE:
     * ------
     * ```php
     * $projects = Project::collection()->fetch();
     *
     * // Remove project with ID 123
     * unset($projects[123]);
     *
     * // Verify removal
     * if (!isset($projects[123])) {
     *     echo "Project 123 removed";
     * }
     *
     * // Using offsetUnset directly (less common)
     * $projects->offsetUnset(456);
     * ```
     *
     * NOTE: This only removes the resource from the local collection.
     * It does NOT delete the resource from the Paymo API. To delete
     * from the API, use the resource's delete() method.
     *
     * @param int|string $offset The offset (resource ID) to remove
     *
     * @return void
     *
     * @see ArrayAccess PHP's ArrayAccess interface
     * @see AbstractResource::delete() To delete from the API
     * @see clear() To remove all resources from collection
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset) : void
    {
        unset($this->data[$offset]);
    }

    /**
     * Get the raw internal array of resources.
     *
     * Returns the underlying associative array containing all AbstractResource
     * instances. This "unwraps" the collection, giving direct access to the
     * data array. Useful for operations that require a plain array, such as
     * count(), array_map(), or passing to functions expecting arrays.
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * $projects = Project::collection()->fetch(['name', 'status']);
     *
     * // Get count of resources
     * $count = count($projects->raw());
     * echo "Found {$count} projects";
     *
     * // Use with array functions
     * $names = array_map(function($p) {
     *     return $p->name;
     * }, $projects->raw());
     *
     * // Get array of IDs
     * $ids = array_keys($projects->raw());
     *
     * // Get first/last resource
     * $first = reset($projects->raw());
     * $last = end($projects->raw());
     *
     * // Check if empty
     * if (empty($projects->raw())) {
     *     echo "No projects found";
     * }
     * ```
     *
     * RETURN STRUCTURE:
     * -----------------
     * Returns an associative array keyed by resource ID:
     *
     * ```php
     * [
     *     123 => Project { id: 123, name: 'Project A', ... },
     *     456 => Project { id: 456, name: 'Project B', ... },
     *     789 => Project { id: 789, name: 'Project C', ... },
     * ]
     * ```
     *
     * MUTABILITY NOTE:
     * ----------------
     * Returns a reference to the internal array, not a copy. Modifying the
     * returned array will affect the collection's data. Use with care.
     *
     * @return AbstractResource[] Associative array of resource instances, keyed by resource ID
     *
     * @see flatten() Converts resources to plain stdClass objects
     * @see clear() Empties the internal data array
     */
    public function raw() : array
    {
        return $this->data;
    }

    /**
     * Get the number of resources in the collection.
     *
     * Implements Countable::count(). Allows using count() directly on the collection
     * instead of requiring count($collection->raw()).
     *
     * USAGE:
     * ------
     * ```php
     * $projects = Project::collection()->fetch(['name']);
     *
     * // Direct count on collection
     * echo count($projects);  // e.g., 25
     *
     * // Equivalent to:
     * echo count($projects->raw());  // Also 25
     * ```
     *
     * @return int The number of resources in the collection
     *
     * @see Countable PHP's Countable interface
     * @see raw() Returns the underlying array
     */
    public function count() : int
    {
        return count($this->data);
    }

    /**
     * Specify data for JSON serialization.
     *
     * Implements JsonSerializable::jsonSerialize(). This method is called automatically
     * by json_encode() when encoding the collection. Returns the flattened data so that
     * collections serialize as arrays of plain objects rather than empty objects.
     *
     * USAGE:
     * ------
     * ```php
     * $projects = Project::collection()->fetch(['id', 'name']);
     *
     * // Direct JSON encoding works correctly
     * $json = json_encode($projects);
     * // Result: [{"id": 123, "name": "Project A"}, {"id": 456, "name": "Project B"}]
     *
     * // Can be used directly in API responses
     * $data->paymoProjects = $projects;  // Will serialize properly
     * echo json_encode($data);
     * ```
     *
     * NOTE: Returns array_values() of flatten() to produce a JSON array with
     * sequential indices rather than an object with ID keys. This is the more
     * common expectation for JSON API responses.
     *
     * @return array Array of flattened resource data suitable for JSON encoding
     *
     * @see JsonSerializable PHP's JsonSerializable interface
     * @see flatten() The underlying method that converts resources to plain objects
     */
    public function jsonSerialize() : array
    {
        return array_values($this->flatten());
    }

    /**
     * Provide debug information for var_dump().
     *
     * This magic method controls what is shown when var_dump() is called on
     * the collection. Instead of showing internal implementation details,
     * it shows useful debugging information including the count and data.
     *
     * USAGE:
     * ------
     * ```php
     * $projects = Project::collection()->fetch(['id', 'name']);
     * var_dump($projects);
     * // Shows: entityKey, count, and the data array
     * ```
     *
     * @return array Debug information array
     */
    public function __debugInfo() : array
    {
        return [
            'entityKey' => $this->entityKey,
            'count' => count($this->data),
            'data' => $this->data
        ];
    }

}
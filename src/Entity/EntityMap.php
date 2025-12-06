<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/12/20, 11:07 AM
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
 * ENTITY MAP - REGISTRY FOR RESOURCE AND COLLECTION CLASS MAPPINGS
 * ======================================================================================
 *
 * This utility class serves as the central registry that maps entity keys (like 'project',
 * 'task', 'client') to their corresponding PHP class implementations. It's the backbone
 * of the SDK's entity resolution system, enabling dynamic instantiation of resource and
 * collection objects based on string identifiers.
 *
 * KEY RESPONSIBILITIES:
 * ---------------------
 * - Map entity keys to resource classes (e.g., 'project' → Project::class)
 * - Map entity keys to collection classes (e.g., 'project' → ProjectCollection::class)
 * - Allow runtime overloading of default class mappings for customization
 * - Handle key extraction from prefixed formats (e.g., 'resource:project' → 'project')
 * - Support key aliasing for related entities
 *
 * CONFIGURATION INTEGRATION:
 * --------------------------
 * EntityMap reads from and writes to the Configuration system. The entity mappings
 * are stored under 'classMap.entity.' in the configuration hierarchy:
 *
 * ```php
 * // Configuration structure
 * [
 *     'classMap' => [
 *         'entity' => [
 *             'project' => [
 *                 'type' => 'project',
 *                 'resource' => 'Jcolombo\PaymoApiPhp\Entity\Resource\Project',
 *                 'collection' => 'Jcolombo\PaymoApiPhp\Entity\Collection\ProjectCollection',
 *             ],
 *             'task' => [
 *                 'type' => 'task',
 *                 'resource' => 'Jcolombo\PaymoApiPhp\Entity\Resource\Task',
 *                 'collection' => true,  // Uses default collection
 *             ],
 *             // ... more entities
 *         ],
 *         'defaultCollection' => 'Jcolombo\PaymoApiPhp\Entity\Collection\EntityCollection',
 *     ],
 * ]
 * ```
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * // Get resource class for an entity key
 * $projectClass = EntityMap::resource('project');
 * // Returns: 'Jcolombo\PaymoApiPhp\Entity\Resource\Project'
 *
 * // Get collection class for an entity key
 * $collectionClass = EntityMap::collection('project');
 * // Returns: 'Jcolombo\PaymoApiPhp\Entity\Collection\ProjectCollection'
 *
 * // Check if an entity key exists
 * if (EntityMap::exists('project')) {
 *     $entity = EntityMap::entity('project');
 * }
 *
 * // Extract key from prefixed format
 * $key = EntityMap::extractKey('resource:project');
 * // Returns: 'project'
 *
 * // Overload with custom classes at runtime
 * EntityMap::overload('project', MyCustomProject::class, MyCustomProjectCollection::class);
 * ```
 *
 * CUSTOMIZATION:
 * --------------
 * The EntityMap supports two levels of customization:
 *
 * 1. Configuration Files: Create a custom config JSON that overrides class mappings
 * 2. Runtime Overloading: Use EntityMap::overload() to replace classes during execution
 *
 * This enables extending the SDK with custom resource classes that add business logic
 * while maintaining compatibility with the core SDK functionality.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        Configuration Provides the underlying storage for entity mappings
 * @see        AbstractResource Base class for all resource types
 * @see        AbstractCollection Base class for all collection types
 */

namespace Jcolombo\PaymoApiPhp\Entity;

use Exception;
use Jcolombo\PaymoApiPhp\Configuration;
use stdClass;

/**
 * Central registry for mapping entity keys to resource and collection classes.
 *
 * EntityMap provides a static interface for looking up and managing the relationships
 * between entity identifiers (like 'project', 'task') and their PHP class implementations.
 * This enables the SDK to dynamically create the correct object types based on API
 * responses and configuration.
 *
 * The class is designed with all static methods, acting as a global registry that
 * can be accessed from anywhere in the application without instantiation.
 *
 * KEY FEATURES:
 * -------------
 * - Static lookup methods for resource and collection classes
 * - Runtime class overloading for customization
 * - Key extraction from various formats (prefixed, dot notation)
 * - Aliasing support for related entities
 * - Strict mode for error handling
 *
 * ENTITY KEY CONCEPTS:
 * --------------------
 * - **Entity Key**: A simple string identifier (e.g., 'project', 'task')
 * - **Resource Class**: The PHP class for single entity operations
 * - **Collection Class**: The PHP class for list operations
 * - **Prefixed Key**: Format like 'resource:project' or 'collection:tasks'
 * - **Dot Notation Key**: Format like 'project.client' for nested entities
 *
 * @package Jcolombo\PaymoApiPhp\Entity
 */
class EntityMap
{
    /**
     * The configuration path prefix for entity mappings.
     *
     * This constant defines the root path in the Configuration system where all
     * entity class mappings are stored. All EntityMap methods use this prefix
     * when reading from or writing to configuration.
     *
     * CONFIGURATION STRUCTURE:
     * ------------------------
     * Under this path, each entity has its own configuration block:
     *
     * ```
     * classMap.entity.project.resource    → Project class
     * classMap.entity.project.collection  → ProjectCollection class
     * classMap.entity.project.type        → Entity type identifier
     * classMap.entity.project.resourceKey → Optional alias to another entity's resource
     * classMap.entity.project.collectionKey → Optional alias to another entity's collection
     * ```
     *
     * USAGE IN METHODS:
     * -----------------
     * ```php
     * // Internal usage to build full config paths
     * Configuration::get(self::CONFIG_PATH . 'project.resource');
     * // Equivalent to: Configuration::get('classMap.entity.project.resource')
     * ```
     *
     * @var string The dot-notation path prefix, ending with a dot for easy concatenation
     */
    public const CONFIG_PATH = 'classMap.entity.';

    /**
     * Override the default classes for an entity with custom implementations.
     *
     * This method allows runtime replacement of the resource and/or collection classes
     * for a specific entity key. The changes only persist for the current script execution
     * and are not saved permanently to configuration files.
     *
     * USE CASES:
     * ----------
     * - Extending resource classes with custom business logic
     * - Adding application-specific methods to entities
     * - Testing with mock implementations
     * - Integrating with application frameworks
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Override resource class only
     * EntityMap::overload('project', MyCustomProject::class);
     *
     * // Override both resource and collection
     * EntityMap::overload(
     *     'project',
     *     MyCustomProject::class,
     *     MyCustomProjectCollection::class
     * );
     *
     * // Use default collection with custom resource
     * EntityMap::overload('project', MyCustomProject::class, true);
     *
     * // Clear collection override (set to null)
     * EntityMap::overload('project', MyCustomProject::class, null);
     * ```
     *
     * CUSTOM CLASS REQUIREMENTS:
     * --------------------------
     * - Resource classes MUST extend AbstractResource
     * - Collection classes MUST extend AbstractCollection
     * - In dev mode, these requirements are validated and exceptions thrown
     *
     * DEV MODE VALIDATION:
     * --------------------
     * When Configuration::get('devMode') is true, the method validates that:
     * - The resource class exists and extends AbstractResource
     * - The collection class exists and extends AbstractCollection
     *
     * For permanent overrides, create a custom configuration file instead.
     *
     * @param string           $mapKey          The entity key to override (e.g., 'project', 'task')
     * @param string|null      $resourceClass   Fully-qualified class name for the resource.
     *                                          Must extend AbstractResource. Null to skip.
     * @param bool|string|null $collectionClass Collection class override:
     *                                          - string: Fully-qualified class name (must extend AbstractCollection)
     *                                          - true: Use the global default EntityCollection
     *                                          - null: Clear/reset the collection override
     *                                          - false: Skip collection override (default)
     *
     * @return void
     *
     * @throws Exception If resource class doesn't exist or doesn't extend AbstractResource (dev mode)
     * @throws Exception If collection class doesn't exist or doesn't extend AbstractCollection (dev mode)
     *
     * @see Configuration::set() Used to store the overridden class mappings
     * @see AbstractResource Base class that custom resources must extend
     * @see AbstractCollection Base class that custom collections must extend
     */
    public static function overload($mapKey, $resourceClass = null, $collectionClass = false)
    {
        // Set RESOURCE Class for $mapKey
        $resource = null;
        if (is_string($resourceClass)) {
            $resource = $resourceClass;
            if (Configuration::get('devMode') && class_exists($resourceClass)) {
                if (!is_subclass_of($resourceClass, "Jcolombo\PaymoApiPhp\Entity\AbstractResourcce")) {
                    throw new Exception("Overload [{$mapKey}] entity failed. {$resourceClass} does not extend PaymoApiPhp AbstractResource.");
                }
            } else {
                throw new Exception("Overloading an entity [{$mapKey}] requires a valid class name. Given: {$resourceClass}");
            }
        }
        if ($resource) {
            Configuration::set(self::CONFIG_PATH.$mapKey.'.resource', $resource);
        }

        // Set COLLECTION Class for $mapKey
        if ($collectionClass !== false && ($collectionClass === true || is_string($collectionClass) || is_null($collectionClass))) {
            $collection = $collectionClass;

            if (Configuration::get('devMode') && is_string($collectionClass)) {
                if (class_exists($collectionClass)) {
                    if (!is_subclass_of($collectionClass, "Jcolombo\PaymoApiPhp\Entity\AbstractCollection")) {
                        throw new Exception("Overload [{$mapKey}] collection failed. {$collectionClass} does not extend PaymoApiPhp AbstractCollection.");
                    }
                } else {
                    throw new Exception("Overloading a collection [{$mapKey}] requires a valid class name. Given: {$collectionClass}");
                }
            }

            if ($collection) {
                Configuration::set(self::CONFIG_PATH.$mapKey.'.collection', $collection);
            }
        }
    }

    /**
     * Get the complete entity configuration object for a given key.
     *
     * Returns a stdClass object containing all configuration information for the
     * specified entity, including the type identifier, mapped keys (for aliasing),
     * and resolved resource/collection class names.
     *
     * RETURNED OBJECT STRUCTURE:
     * --------------------------
     * ```php
     * stdClass {
     *     type: 'project',                                    // Entity type identifier
     *     mappedKeys: stdClass {                              // Key aliases (if any)
     *         resource: null,                                 // Or another entity key
     *         collection: null                                // Or another entity key
     *     },
     *     resource: 'Jcolombo\...\Project',                   // Resolved resource class
     *     collection: 'Jcolombo\...\ProjectCollection'        // Resolved collection class
     * }
     * ```
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Get entity configuration
     * $entity = EntityMap::entity('project');
     * if ($entity) {
     *     echo $entity->type;        // 'project'
     *     echo $entity->resource;    // Class name
     *     echo $entity->collection;  // Collection class name
     * }
     *
     * // Strict mode - throws if not found
     * try {
     *     $entity = EntityMap::entity('invalid', true);
     * } catch (Exception $e) {
     *     // Handle missing entity
     * }
     *
     * // Non-strict mode - returns null if not found
     * $entity = EntityMap::entity('maybe_exists', false);
     * if ($entity === null) {
     *     // Entity not configured
     * }
     * ```
     *
     * KEY ALIASING:
     * -------------
     * Some entities may use another entity's resource or collection class.
     * The mappedKeys property indicates these aliases, and the resource/collection
     * properties contain the final resolved class names.
     *
     * @param string $key    The entity key to look up (e.g., 'project', 'task')
     *                       Can include prefixes which will be stripped.
     * @param bool   $strict If true, throws an exception when entity not found.
     *                       If false (default), returns null when not found.
     *
     * @return stdClass|null Entity configuration object, or null if not found (non-strict mode)
     *
     * @throws Exception If strict mode is true and entity key is not configured
     *
     * @see extractKey() Strips prefixes from the key before lookup
     * @see mapKeys() Gets the key aliases for resource/collection
     * @see resource() Gets just the resource class
     * @see collection() Gets just the collection class
     */
    public static function entity($key, $strict = false)
    {
        $key = self::extractKey($key);
        if (is_string($key)) {
            if (Configuration::has(self::CONFIG_PATH.$key)) {
                $object = new stdClass();
                $object->type = Configuration::get(self::CONFIG_PATH.$key.'.type');
                $object->mappedKeys = self::mapKeys($key);
                $object->resource = self::resource($object->mappedKeys->resource ?? $key);
                $object->collection = self::collection($object->mappedKeys->collection ?? $key);

                return $object;
            }
        }
        if ($strict) {
            throw new Exception("[$key] does not have a configured entity defined");
        }

        return null;
    }

    /**
     * Extract the base entity key from various prefixed or dot-notation formats.
     *
     * This utility method normalizes entity key strings by stripping any prefixes
     * (like 'resource:' or 'collection:') and handling dot notation for nested
     * entity references. It's used internally throughout EntityMap to ensure
     * consistent key handling.
     *
     * SUPPORTED FORMATS:
     * ------------------
     * | Input Format          | Output        | Description                      |
     * |-----------------------|---------------|----------------------------------|
     * | 'project'             | 'project'     | Plain key (unchanged)            |
     * | 'resource:project'    | 'project'     | Type-prefixed key                |
     * | 'collection:projects' | 'projects'    | Collection-prefixed key          |
     * | 'project.client'      | 'client'      | Dot notation (last segment)      |
     * | 'project.tasks.user'  | 'user'        | Deep dot notation (last segment) |
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Strip type prefix
     * $key = EntityMap::extractKey('resource:project');
     * // Returns: 'project'
     *
     * // Strip collection prefix
     * $key = EntityMap::extractKey('collection:tasks');
     * // Returns: 'tasks'
     *
     * // Extract from dot notation
     * $key = EntityMap::extractKey('project.client');
     * // Returns: 'client' (if 'client' exists in EntityMap)
     *
     * // Plain key unchanged
     * $key = EntityMap::extractKey('project');
     * // Returns: 'project'
     *
     * // Non-string input
     * $key = EntityMap::extractKey(null);
     * // Returns: null
     * ```
     *
     * VALIDATION:
     * -----------
     * When extracting from prefixed or dot-notation formats, the method verifies
     * that the extracted key actually exists in the EntityMap before returning it.
     * If the extracted key doesn't exist, the original key is returned.
     *
     * @param string|mixed $key The key to normalize. Non-strings return null.
     *
     * @return string|null The extracted base entity key, or null if input was not a string
     *
     * @see exists() Used to validate extracted keys
     */
    public static function extractKey($key)
    {
        if (!is_string($key)) {
            return null;
        }
        if (strpos($key, ':')) {
            $parts = explode(':', $key, 2);
            if (self::exists(($parts[1]))) {
                return $parts[1];
            }
        }
        if (strpos($key, '.') > 0) {
            $k1 = array_pop(explode('.', $key));
            if (self::exists($k1)) {
                return $k1;
            }
        }

        return $key;
    }

    /**
     * Check if an entity key is registered in the configuration.
     *
     * Determines whether the given entity key has a corresponding configuration
     * entry in the EntityMap. This is useful for validating entity keys before
     * attempting to retrieve their class mappings.
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Check before accessing
     * if (EntityMap::exists('project')) {
     *     $entity = EntityMap::entity('project');
     * }
     *
     * // Works with prefixed keys too
     * if (EntityMap::exists('resource:project')) {
     *     // The prefix is stripped before checking
     * }
     *
     * // Validate user input
     * $entityType = $_GET['type'];
     * if (EntityMap::exists($entityType)) {
     *     // Safe to proceed
     * } else {
     *     throw new InvalidArgumentException("Unknown entity type");
     * }
     * ```
     *
     * KEY NORMALIZATION:
     * ------------------
     * The key is passed through extractKey() first, so prefixed and dot-notation
     * keys work correctly:
     * - 'project' → checks for 'project'
     * - 'resource:project' → checks for 'project'
     * - 'project.client' → checks for 'client'
     *
     * @param string $key The entity key to check for existence.
     *                    Can include prefixes which will be stripped.
     *
     * @return bool True if the entity is configured, false otherwise
     *
     * @see extractKey() Normalizes the key before checking
     * @see Configuration::has() Performs the actual configuration lookup
     */
    public static function exists($key)
    {
        $key = self::extractKey($key);
        if (!is_string($key)) {
            return false;
        }

        return Configuration::has(self::CONFIG_PATH.$key);
    }

    /**
     * Get the key aliases for an entity's resource and collection classes.
     *
     * Some entities may reference another entity's class for their resource or
     * collection implementation. This method returns those alias keys, allowing
     * the actual class lookup to follow the reference chain.
     *
     * ALIASING CONCEPT:
     * -----------------
     * Aliasing is useful when:
     * - Multiple entity keys should share the same class implementation
     * - An entity uses a generic collection but has a specific resource
     * - Singular/plural forms should map to the same classes
     *
     * RETURNED OBJECT STRUCTURE:
     * --------------------------
     * ```php
     * stdClass {
     *     resource: 'other_entity',    // Or null if no alias
     *     collection: 'other_entity'   // Or null if no alias
     * }
     * ```
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Check for aliases
     * $aliases = EntityMap::mapKeys('projects');
     * if ($aliases->resource) {
     *     // 'projects' uses another entity's resource class
     *     $actualKey = $aliases->resource;  // e.g., 'project'
     * }
     *
     * // Example: plural form aliasing singular
     * // Config: 'projects' has resourceKey: 'project'
     * $aliases = EntityMap::mapKeys('projects');
     * echo $aliases->resource;  // 'project'
     * echo $aliases->collection;  // null (uses its own)
     * ```
     *
     * CONFIGURATION:
     * --------------
     * Aliases are configured in the entity's config block:
     *
     * ```json
     * {
     *     "classMap": {
     *         "entity": {
     *             "projects": {
     *                 "resourceKey": "project",
     *                 "collectionKey": null
     *             }
     *         }
     *     }
     * }
     * ```
     *
     * @param string $key The entity key to look up aliases for
     *
     * @return stdClass Object with 'resource' and 'collection' properties,
     *                  each containing the alias key (string) or null
     *
     * @see resource() Uses this to follow resource key aliases
     * @see collection() Uses this to follow collection key aliases
     * @see entity() Includes mapKeys in the returned entity configuration
     */
    public static function mapKeys($key)
    {
        $map = new stdClass();
        $map->resource = null;
        $map->collection = null;
        $key = self::extractKey($key);
        if (is_string($key)) {
            if (Configuration::has(self::CONFIG_PATH.$key)) {
                $map = new stdClass();
                $map->resource = Configuration::get(self::CONFIG_PATH.$key.'.resourceKey');
                $map->collection = Configuration::get(self::CONFIG_PATH.$key.'.collectionKey');
            }
        }

        return $map;
    }

    /**
     * Get the resource class name for a given entity key.
     *
     * Returns the fully-qualified class name for the resource (single entity) class
     * associated with the given entity key. This is the class used for operations
     * on individual records like fetch(), create(), update(), and delete().
     *
     * ALIAS RESOLUTION:
     * -----------------
     * If the entity has a 'resourceKey' alias configured, this method follows the
     * alias to retrieve the actual class name. This enables multiple keys to share
     * the same resource implementation.
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Get resource class
     * $className = EntityMap::resource('project');
     * // Returns: 'Jcolombo\PaymoApiPhp\Entity\Resource\Project'
     *
     * // Instantiate dynamically
     * $class = EntityMap::resource('task');
     * $task = new $class($connection);
     *
     * // Strict mode - throws if not found
     * try {
     *     $class = EntityMap::resource('invalid', true);
     * } catch (Exception $e) {
     *     // Handle missing resource class
     * }
     *
     * // Non-strict mode - returns null if not found
     * $class = EntityMap::resource('maybe_exists');
     * if ($class === null) {
     *     // No resource class configured
     * }
     * ```
     *
     * COMMON USE CASES:
     * -----------------
     * - Dynamic class instantiation based on entity type
     * - Factory pattern implementations
     * - Type checking and validation
     * - Reflection-based operations
     *
     * @param string $key    The entity key to look up (e.g., 'project', 'task').
     *                       Can include prefixes which will be stripped.
     * @param bool   $strict If true, throws exception when resource not found.
     *                       If false (default), returns null when not found.
     *
     * @return string|null The fully-qualified resource class name, or null if not found (non-strict mode)
     *
     * @throws Exception If strict mode is true and no resource class is configured
     *
     * @see extractKey() Normalizes the key before lookup
     * @see collection() Get the collection class for an entity
     * @see mapKeys() Get resource/collection key aliases
     */
    public static function resource($key, $strict = false)
    {
        $key = self::extractKey($key);
        $resourceKey = Configuration::get(self::CONFIG_PATH.$key.'.resourceKey');
        $resource = Configuration::has(self::CONFIG_PATH.$key.'.resource');
        if (!$resource && $resourceKey) {
            $key = $resourceKey;
        }
        if ($strict && (!is_string($key) || !Configuration::has(self::CONFIG_PATH.$key.'.resource'))) {
            throw new Exception("[$key] does not have a configured resource class defined");
        }

        return Configuration::get(self::CONFIG_PATH.$key.'.resource');
    }

    /**
     * Get the collection class name for a given entity key.
     *
     * Returns the fully-qualified class name for the collection class associated
     * with the given entity key. Collections are used for list operations that
     * retrieve multiple records of the same type.
     *
     * DEFAULT COLLECTION SUPPORT:
     * ---------------------------
     * If the entity's collection is configured as boolean `true` instead of a
     * class name, the global default collection class is returned. This allows
     * entities to use the generic EntityCollection without custom implementations.
     *
     * ALIAS RESOLUTION:
     * -----------------
     * If the entity has a 'collectionKey' alias configured, this method follows
     * the alias recursively to retrieve the actual class name.
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Get collection class
     * $className = EntityMap::collection('project');
     * // Returns: 'Jcolombo\PaymoApiPhp\Entity\Collection\ProjectCollection'
     *
     * // Instantiate dynamically
     * $class = EntityMap::collection('task');
     * $tasks = new $class('task', $connection);
     *
     * // Entity using default collection
     * $class = EntityMap::collection('webhook');
     * // Returns: 'Jcolombo\PaymoApiPhp\Entity\Collection\EntityCollection'
     *
     * // Strict mode - throws if not found
     * try {
     *     $class = EntityMap::collection('invalid', true);
     * } catch (Exception $e) {
     *     // Handle missing collection class
     * }
     *
     * // Non-strict mode - returns null if not found
     * $class = EntityMap::collection('maybe_exists');
     * if ($class === null) {
     *     // No collection class configured
     * }
     * ```
     *
     * COLLECTION CONFIGURATION OPTIONS:
     * ---------------------------------
     * - **String**: Fully-qualified collection class name
     * - **true**: Use the global default EntityCollection
     * - **null**: No collection support for this entity
     * - **collectionKey**: Alias to another entity's collection
     *
     * @param string $key    The entity key to look up (e.g., 'project', 'task').
     *                       Can include prefixes which will be stripped.
     * @param bool   $strict If true, throws exception when collection not found.
     *                       If false (default), returns null when not found.
     *
     * @return string|null The fully-qualified collection class name, or null if not found (non-strict mode)
     *
     * @throws Exception If strict mode is true and no collection class is configured
     *
     * @see extractKey() Normalizes the key before lookup
     * @see resource() Get the resource class for an entity
     * @see mapKeys() Get resource/collection key aliases
     * @see Configuration::get('classMap.defaultCollection') Global default collection class
     */
    public static function collection($key, $strict = false)
    {
        $key = self::extractKey($key);
        $cClass = null;
        if (is_string($key)) {
            $cClass = Configuration::get(self::CONFIG_PATH.$key.'.collection');
            if ($cClass === true) {
                $cClass = Configuration::get('classMap.defaultCollection');
            }
        }
        $collectionKey = Configuration::get(self::CONFIG_PATH.$key.'.collectionKey');
        if (!$cClass && $collectionKey) {
            $cClass = self::collection($collectionKey);
        }
        if ($strict && !$cClass) {
            throw new Exception("[$key] does not have a configured collection class defined");
        }

        return $cClass;
    }

    /**
     * Split a dot-notation key into its entity path and property components.
     *
     * Parses a dot-notation string to separate the entity reference path from
     * the final property or include name. This is useful when processing field
     * selections or includes that reference nested entity properties.
     *
     * PARSING BEHAVIOR:
     * -----------------
     * The method splits on the LAST dot, treating everything before it as the
     * entity path and everything after as the property name.
     *
     * EXAMPLES:
     * ---------
     * | Input                    | Output                              |
     * |--------------------------|-------------------------------------|
     * | 'project.name'           | ['project', 'name']                 |
     * | 'project.client.name'    | ['project.client', 'name']          |
     * | 'project.tasks.user.id'  | ['project.tasks.user', 'id']        |
     * | 'project'                | ['project', null]                   |
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Simple entity.property
     * [$entity, $prop] = EntityMap::extractResourceProp('project.name');
     * // $entity = 'project', $prop = 'name'
     *
     * // Nested entity path
     * [$entity, $prop] = EntityMap::extractResourceProp('project.client.name');
     * // $entity = 'project.client', $prop = 'name'
     *
     * // No property (just entity)
     * [$entity, $prop] = EntityMap::extractResourceProp('project');
     * // $entity = 'project', $prop = null
     *
     * // Use in field processing
     * $field = 'include:project.tasks';
     * $field = str_replace('include:', '', $field);
     * [$entityPath, $relationship] = EntityMap::extractResourceProp($field);
     * ```
     *
     * COMMON USE CASES:
     * -----------------
     * - Processing include specifications with paths
     * - Validating field selections against entity properties
     * - Building nested resource queries
     * - Parsing relationship chains
     *
     * @param string $fullKey A dot-notation string representing an entity path
     *                        with an optional property at the end
     *
     * @return array{0: string, 1: string|null} Two-element array where:
     *               - [0] is the entity path (everything before last dot, or the full key if no dots)
     *               - [1] is the property name (after last dot), or null if no dots present
     */
    public static function extractResourceProp($fullKey)
    {
        if (strpos($fullKey, '.') === false) {
            return [$fullKey, null];
        }
        $pts = explode('.', $fullKey);
        $prop = array_pop($pts);

        return [implode('.', $pts), $prop];
    }

}
<?php
/**
 * Paymo API PHP SDK - Request Builder
 *
 * Provides static methods for building and executing API requests to Paymo.
 * Handles CRUD operations (Create, Read, Update, Delete) for all entity types.
 *
 * @package    Jcolombo\PaymoApiPhp
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

namespace Jcolombo\PaymoApiPhp;

use Adbar\Dot;
use Exception;
use Jcolombo\PaymoApiPhp\Utility\Converter;
use Jcolombo\PaymoApiPhp\Utility\RequestAbstraction;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
use Jcolombo\PaymoApiPhp\Utility\RequestResponse;
use JsonException;
use RuntimeException;
use stdClass;

/**
 * Request Builder and Executor
 *
 * Static utility class responsible for constructing and executing all API requests
 * to the Paymo REST API. This class serves as the bridge between entity classes
 * (like Project, Task, Client) and the Paymo connection layer.
 *
 * ## Architecture Role
 *
 * ```
 * Entity Classes (Project, Task, etc.)
 *        ↓ calls
 * Request (this class) - builds request parameters
 *        ↓ calls
 * Paymo->execute() - performs HTTP request
 *        ↓ returns
 * RequestResponse - structured response data
 * ```
 *
 * ## Supported Operations
 *
 * - **fetch()** - Retrieve a single entity by ID
 * - **list()** - Retrieve multiple entities with filtering
 * - **create()** - Create a new entity
 * - **update()** - Update an existing entity
 * - **delete()** - Remove an entity
 * - **upload()** - Attach files to an entity
 *
 * ## Request Features
 *
 * - Query string compilation for WHERE filters and INCLUDE relations
 * - Response body scrubbing to remove unexpected properties
 * - Post-response HAS filtering for relationship counts
 * - Cache key generation for response caching
 * - Support for multipart file uploads
 *
 * ## Usage Note
 *
 * This class is primarily used internally by entity classes. Application code
 * should typically use entity methods like `Project::new()->fetch(123)` rather
 * than calling Request methods directly.
 *
 * @package Jcolombo\PaymoApiPhp
 * @author  Joel Colombo <jc-dev@360psg.com>
 * @since   0.1.0
 *
 * @internal This class is used internally by entity classes
 * @see AbstractResource For the entity class that uses this
 * @see AbstractCollection For the collection class that uses this
 */
class Request
{

    /**
     * Fetch a single entity from the Paymo API by ID.
     *
     * Retrieves a single resource entity from the API, optionally including
     * related entities and limiting the returned properties.
     *
     * ## How It Works
     *
     * 1. Validates the provided ID (must be positive integer or -1 for ID-less entities)
     * 2. Compiles `include` parameter from select and include options
     * 3. Checks cache if enabled and not explicitly skipped
     * 4. Executes GET request to the API endpoint
     * 5. Optionally scrubs response to remove unexpected properties
     * 6. Returns structured RequestResponse with result
     *
     * ## Options Array
     *
     * | Key        | Type     | Description                                           |
     * |------------|----------|-------------------------------------------------------|
     * | select     | string[] | Properties to return on the main entity               |
     * | include    | string[] | Related entities to include (e.g., ['client', 'tasks']) |
     * | scrub      | bool     | Remove unexpected properties from response            |
     * | skipCache  | bool     | Force API call, ignoring cached response              |
     *
     * ## Example (Internal Usage)
     *
     * ```php
     * // Called internally by AbstractResource::fetch()
     * $response = Request::fetch($connection, 'projects', 12345, [
     *     'select' => ['name', 'description'],
     *     'include' => ['client', 'tasks.name'],
     *     'skipCache' => false
     * ]);
     *
     * if ($response->success) {
     *     $projectData = $response->result;
     * }
     * ```
     *
     * @param Paymo  $connection Active Paymo connection instance for executing the request
     * @param string $objectKey  API resource path (e.g., "projects", "tasks")
     *                           Can include response key override: "path:responseKey"
     * @param int    $id         Entity ID to fetch. Use -1 for singleton entities (like Company)
     * @param array  $options    Request options (see table above)
     *
     * @throws Exception If ID is invalid (not positive integer and not -1)
     *
     * @return RequestResponse Response object with:
     *                         - success: TRUE if request succeeded
     *                         - result: The fetched entity data (stdClass)
     *                         - responseCode: HTTP status code
     *                         - body: Full response body
     *
     * @see AbstractResource::fetch() The entity method that calls this
     */
    public static function fetch(Paymo $connection, string $objectKey, $id, array $options) : RequestResponse
    {
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
        $checkId = !($id === -1);
        if ($checkId && $id < 1) {
            throw new RuntimeException("Attempting to fetch a resource without an integer ID");
        }
        $scrub = isset($options['scrub']) ? !!$options['scrub'] : false;
        $select = $options['select'] ?? [];
        $include = $options['include'] ?? [];
        if (!is_array($select)) {
            $select = !is_null($select) ? [$select] : [];
        }
        if (!is_array($include)) {
            $include = !is_null($include) ? [$include] : [];
        }
        $request = new RequestAbstraction();
        $request->method = 'GET';
        $request->resourceUrl = $id > 0 ? $pathKey."/$id" : $pathKey;
        $request->include = self::compileIncludeParameter(array_merge($select, $include));
        $skipCache = isset($options['skipCache']) && $options['skipCache'];
        $response = $connection->execute($request, ['skipCache'=>$skipCache]);
        if ($response->body && $response->validBody($responseKey, 1)) {
            $object = is_array($response->body->$responseKey) ? $response->body->$responseKey[0] : $response->body->$responseKey;
            $response->result = $scrub ? self::scrubBody($object, $select, $include) : $object;
        }

        return $response;
    }

    /**
     * Parse an object key to extract API path and response key.
     *
     * Some Paymo API endpoints return data under a different key than the request path.
     * This method handles the format "path:responseKey" to support these cases.
     *
     * ## Examples
     *
     * ```php
     * // Standard case - same path and response key
     * [$path, $key] = Request::getObjectReponseKeys('projects');
     * // Returns: ['projects', 'projects']
     *
     * // Custom response key
     * [$path, $key] = Request::getObjectReponseKeys('userstasks:taskassignments');
     * // Returns: ['userstasks', 'taskassignments']
     * ```
     *
     * @param string $key Object key, optionally with colon separator for alternate response key
     *
     * @return string[] Two-element array: [apiPath, responseKey]
     */
    public static function getObjectReponseKeys(string $key) : array
    {
        $parts = explode(':', $key);
        $pathKey = $responseKey = $key;
        if (count($parts) === 2) {
            [$pathKey, $responseKey] = $parts;
        }

        return [$pathKey, $responseKey];
    }

    /**
     * Combine include parameters into a sorted, comma-separated string.
     *
     * Paymo API accepts a single `include` query parameter with comma-separated values.
     * This method deduplicates and sorts the includes to ensure consistent cache keys.
     *
     * ## Why Sorting Matters
     *
     * Cache keys are generated from request parameters. Without sorting, these requests
     * would have different cache keys despite being identical:
     * - `?include=client,tasks`
     * - `?include=tasks,client`
     *
     * ## Examples
     *
     * ```php
     * // Standard include compilation
     * $include = Request::compileIncludeParameter(['client', 'tasks', 'milestones']);
     * // Returns: "client,milestones,tasks"
     *
     * // With dot notation for nested properties
     * $include = Request::compileIncludeParameter(['client.name', 'tasks.id', 'tasks.name']);
     * // Returns: "client.name,tasks.id,tasks.name"
     *
     * // Empty array returns null
     * $include = Request::compileIncludeParameter([]);
     * // Returns: null
     * ```
     *
     * @param string[] $include Array of include entity names and/or property paths
     *
     * @return string|null Comma-separated string or NULL if no includes
     */
    public static function compileIncludeParameter(array $include) : ?string
    {
        if (!$include || count($include) < 1) {
            return null;
        }
      $array_unique = array_unique($include);
      sort($array_unique);

        return implode(',', $array_unique);
    }

    /**
     * Scrub response body to remove unexpected properties.
     *
     * Filters the API response to contain only the requested properties and includes.
     * Useful when you need strict control over returned data or when the API returns
     * more properties than documented.
     *
     * ## Behavior
     *
     * - If `$select` is empty, all properties are kept (no scrubbing)
     * - `id` property is always preserved regardless of selection
     * - Included entities (relationships) are preserved as whole objects
     * - Works recursively on both single objects and arrays
     *
     * ## Example
     *
     * ```php
     * // API returns: {id: 1, name: "Project", description: "...", secret_field: "xyz"}
     * // After scrub with select=['name']:
     * // Result: {id: 1, name: "Project"}
     *
     * $scrubbed = Request::scrubBody(
     *     $apiResponse,
     *     ['name', 'description'],  // select only these props
     *     ['client']                // but keep the client include
     * );
     * ```
     *
     * @param stdClass|stdClass[] $objects Response object(s) from the API
     * @param string[]            $select  Properties to keep on the main entity
     * @param string[]            $include Included relations to preserve
     *
     * @return stdClass|stdClass[] Scrubbed version of the input object(s)
     */
    public static function scrubBody($objects, array $select, array $include)
    {
        $isList = is_array($objects);
        if ($isList) {
            $objList = $objects;
        } else {
            $objList = [$objects];
        }
        $includedEntities = [];
        foreach ($include as $i) {
            $incEntity = explode('.', $i)[0];
            if (!in_array($incEntity, $includedEntities, true)) {
                $includedEntities[] = $incEntity;
            }
        }
        $validProps = array_merge($select, $includedEntities);
        $selectAll = count($select) === 0;
        if (!$selectAll) {
            foreach ($objList as $e) {
                foreach ($e as $k => $v) {
                    if (!($k === 'id' || in_array($k, $validProps, true))) {
                        unset($e->$k);
                    }
                }
            }
        }

        return $isList ? $objList : $objList[0];
    }

    /**
     * Create a new entity in the Paymo API.
     *
     * Sends a POST request to create a new resource with the provided data.
     * Supports both JSON body and multipart form data for file uploads.
     *
     * ## Request Modes
     *
     * - **json** (default): Sends data as JSON body
     * - **multipart**: Sends data as multipart/form-data (required for file uploads)
     *
     * The mode is automatically switched to multipart if files are provided.
     *
     * ## Example (Internal Usage)
     *
     * ```php
     * // Create a project
     * $response = Request::create($connection, 'projects', [
     *     'name' => 'New Project',
     *     'description' => 'Project description',
     *     'client_id' => 123
     * ]);
     *
     * // Create with file upload
     * $response = Request::create($connection, 'files', [
     *     'project_id' => 123,
     *     'description' => 'Attachment'
     * ], [
     *     'file' => '/path/to/document.pdf'
     * ], 'multipart');
     * ```
     *
     * @param Paymo  $connection Active Paymo connection instance
     * @param string $objectKey  API resource path (e.g., "projects", "tasks")
     * @param array  $data       Entity data as associative array
     * @param array  $uploads    Optional file uploads: ['fieldName' => '/path/to/file']
     * @param string $mode       Request mode: 'json' or 'multipart'
     *
     * @throws JsonException
     * @return RequestResponse Response with the created entity in result
     *
     * @see AbstractResource::create() The entity method that calls this
     */
    public static function create(Paymo $connection, string $objectKey, array $data, array $uploads = [], string $mode = 'json') : RequestResponse
    {
        $useMode = $mode === 'multipart' ? $mode : 'json';
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
        $request = new RequestAbstraction();
        $request->method = 'POST';
        $request->resourceUrl = $pathKey;
        $request->data = $data;
        if ($uploads && is_array($uploads) && count($uploads) > 0) {
            $request->mode = 'multipart';
            $request->files = $uploads;
        } else {
            $request->mode = $useMode;
        }
        $response = $connection->execute($request);
        if ($response->body && $response->validBody($responseKey, 1)) {
            $response->result = $response->body->$responseKey[0];
        }

        return $response;
    }

    /**
     * Update an existing entity in the Paymo API.
     *
     * Sends a PUT request to update a specific entity. Only the provided
     * fields are updated; other fields remain unchanged (partial update).
     *
     * ## Example (Internal Usage)
     *
     * ```php
     * // Update project name
     * $response = Request::update($connection, 'projects', 12345, [
     *     'name' => 'Updated Project Name'
     * ]);
     *
     * if ($response->success) {
     *     echo "Project updated successfully";
     * }
     * ```
     *
     * @param Paymo  $connection Active Paymo connection instance
     * @param string $objectKey  API resource path (e.g., "projects", "tasks")
     * @param int    $id         Entity ID to update. Use -1 for singleton entities.
     * @param array  $data       Fields to update as associative array
     *
     * @throws Exception If ID is invalid (not positive integer and not -1)
     *
     * @return RequestResponse Response with the updated entity in result
     *
     * @see AbstractResource::update() The entity method that calls this
     */
    public static function update(Paymo $connection, string $objectKey, $id, array $data) : RequestResponse
    {
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
        $checkId = !($id === -1);
        if ($checkId && $id < 1) {
            throw new RuntimeException("Attempting to update a resource without an integer ID");
        }
        $request = new RequestAbstraction();
        $request->method = 'PUT';
        $request->resourceUrl = $id > 0 ? $pathKey.'/'.$id : $pathKey;
        $request->data = $data;
        $response = $connection->execute($request);
        if ($response->body && $response->validBody($responseKey, 1)) {
            $response->result = is_array($response->body->$responseKey) ? $response->body->$responseKey[0] : $response->body->$responseKey;
        }

        return $response;
    }

    /**
     * Upload a file to an existing entity.
     *
     * Sends a POST request with a file attachment to update an entity's
     * file property. Used for uploading images, documents, and other files.
     *
     * ## Common Use Cases
     *
     * - Uploading a client logo
     * - Attaching files to a task
     * - Setting a user avatar
     *
     * ## Example (Internal Usage)
     *
     * ```php
     * // Upload a logo to a client
     * $response = Request::upload($connection, 'clients', 123, 'image', '/path/to/logo.png');
     *
     * // Attach a file to a project
     * $response = Request::upload($connection, 'files', -1, 'file', '/path/to/document.pdf');
     * ```
     *
     * @param Paymo  $connection Active Paymo connection instance
     * @param string $objectKey  API resource path (e.g., "clients", "files")
     * @param int    $id         Entity ID to attach the file to. Use -1 for creating new files.
     * @param string $prop       Property name for the file (e.g., "image", "file")
     * @param string $filepath   Full filesystem path to the file to upload
     *
     * @throws Exception If ID is invalid (not positive integer and not -1)
     *
     * @return RequestResponse Response with the updated entity in result
     *
     * @see AbstractResource::image() The entity method for image uploads
     * @see AbstractResource::file() The entity method for file uploads
     */
    public static function upload(Paymo $connection, string $objectKey, $id, string $prop, string $filepath) : RequestResponse
    {
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
        $checkId = !($id === -1);
        if ($checkId && $id < 1) {
            throw new RuntimeException("Attempting to upload a file without an integer ID");
        }
        $request = new RequestAbstraction();
        $request->method = 'POST';
        $request->resourceUrl = $id > 0 ? $pathKey.'/'.$id : $pathKey;
        $request->files = [$prop => $filepath];
        $response = $connection->execute($request);
        if ($response->body && $response->validBody($responseKey, 1)) {
            $response->result = is_array($response->body->$responseKey) ? $response->body->$responseKey[0] : $response->body->$responseKey;
        }

        return $response;
    }

    /**
     * Delete an entity from the Paymo API.
     *
     * Sends a DELETE request to permanently remove an entity.
     *
     * ## WARNING
     *
     * This operation is **NOT REVERSIBLE**. The entity and all associated data
     * will be permanently deleted. Always verify the ID before calling.
     *
     * ## Example (Internal Usage)
     *
     * ```php
     * // Delete a project (and all its tasks, time entries, etc.)
     * $response = Request::delete($connection, 'projects', 12345);
     *
     * if ($response->success) {
     *     echo "Project deleted";
     * }
     * ```
     *
     * @param Paymo  $connection Active Paymo connection instance
     * @param string $objectKey  API resource path (e.g., "projects", "tasks")
     * @param int    $id         Entity ID to delete (must be positive integer)
     *
     * @throws Exception If ID is not a positive integer
     *
     * @return RequestResponse Response indicating success/failure (no result body for deletes)
     *
     * @see AbstractResource::delete() The entity method that calls this
     */
    public static function delete(Paymo $connection, string $objectKey, $id) : RequestResponse
    {
        if ($id < 1) {
            throw new RuntimeException("Attempting to delete a resource without a integer ID");
        }
        [$pathKey,] = static::getObjectReponseKeys($objectKey);
        $request = new RequestAbstraction();
        $request->method = 'DELETE';
        $request->resourceUrl = $pathKey.'/'.$id;

        return $connection->execute($request);
    }

    /**
     * Fetch a list of entities from the Paymo API.
     *
     * Retrieves multiple entities with support for filtering via WHERE conditions
     * and including related entities. Results can be further filtered using HAS
     * conditions which are applied post-response.
     *
     * ## Query Building
     *
     * The method supports two types of filters:
     *
     * 1. **WHERE conditions** - Sent to API, filters on entity properties
     * 2. **HAS conditions** - Applied after response, filters by relationship counts
     *
     * ## Options Array
     *
     * | Key        | Type               | Description                                    |
     * |------------|--------------------|------------------------------------------------|
     * | select     | string[]           | Properties to return on main entities          |
     * | include    | string[]           | Related entities to include                    |
     * | where      | RequestCondition[] | Filter conditions (WHERE and HAS)              |
     * | scrub      | bool               | Remove unexpected properties from response     |
     * | skipCache  | bool               | Force API call, ignoring cache                 |
     *
     * ## Example (Internal Usage)
     *
     * ```php
     * // Get all active projects with their tasks
     * $response = Request::list($connection, 'projects', [
     *     'where' => [
     *         RequestCondition::where('active', true)
     *     ],
     *     'include' => ['tasks', 'client'],
     *     'select' => ['name', 'description']
     * ]);
     *
     * foreach ($response->result as $project) {
     *     echo $project->name;
     * }
     * ```
     *
     * @param Paymo  $connection Active Paymo connection instance
     * @param string $objectKey  API resource path (e.g., "projects", "tasks")
     * @param array  $options    Request options (see table above)
     *
     * @throws JsonException
     * @return RequestResponse Response with array of entities in result
     *
     * @see RequestCondition::where() For creating WHERE conditions
     * @see RequestCondition::has() For creating HAS conditions
     * @see AbstractCollection::fetch() The collection method that calls this
     */
    public static function list(Paymo $connection, string $objectKey, array $options) : RequestResponse
    {
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
        $scrub = isset($options['scrub']) && $options['scrub'];
        $select = $options['select'] ?? [];
        $include = $options['include'] ?? [];
        $where = $options['where'] ?? [];
        if (!is_array($select)) {
            $select = !is_null($select) ? [$select] : [];
        }
        if (!is_array($include)) {
            $include = !is_null($include) ? [$include] : [];
        }
        $request = new RequestAbstraction();
        $request->method = 'GET';
        $request->resourceUrl = $pathKey;
        $request->include = self::compileIncludeParameter(array_merge($select, $include));
        $request->where = self::compileWhereParameter($where);

        // Options
        $skipCache = isset($options['skipCache']) && $options['skipCache'];

        $response = $connection->execute($request, ['skipCache'=>$skipCache]);

        if ($response->body && $response->validBody($responseKey)) {
            $response->body->$responseKey = self::postResponseFilter($response->body->$responseKey, $where);
            $response->result = $scrub ? self::scrubBody($response->body->$responseKey, $select,
                                                         $include) : $response->body->$responseKey;
        }

        return $response;
    }

    /**
     * Compile WHERE conditions into an API query string value.
     *
     * Converts an array of RequestCondition objects (type='where') into a
     * single string suitable for the API's `?where=` query parameter.
     *
     * ## WHERE Syntax
     *
     * Paymo API uses a specific WHERE syntax:
     * ```
     * property operator value [and property operator value ...]
     * ```
     *
     * ## Examples
     *
     * ```php
     * $conditions = [
     *     RequestCondition::where('active', true),
     *     RequestCondition::where('client_id', 123)
     * ];
     *
     * $where = Request::compileWhereParameter($conditions);
     * // Returns: "active=true and client_id=123"
     * ```
     *
     * @param RequestCondition[] $where Array of RequestCondition objects
     *
     * @return string|null The compiled WHERE string or NULL if no valid conditions
     *
     * @see Converter::convertOperatorValue() For the actual condition formatting
     */
    public static function compileWhereParameter(array $where) : ?string
    {
        if (!$where || count($where) < 1) {
            return null;
        }
        $conditions = [];
        foreach ($where as $w) {
            if ($w->type === 'where') {
                $filter = Converter::convertOperatorValue($w);
                if (!is_null($filter)) {
                    $conditions[] = $filter;
                }
            }
        }
        sort($conditions);

        return implode(' and ', $conditions);
    }

    /**
     * Apply HAS filters to API response results.
     *
     * HAS filters cannot be sent to the API - they must be applied after
     * receiving the response. This method filters out entities that don't
     * meet the specified relationship count requirements.
     *
     * ## How HAS Works
     *
     * HAS filters check the count of included relationship arrays:
     * - `Project::has('tasks', 0, '>')` - Projects with at least 1 task
     * - `Project::has('tasks', 5, '>=')` - Projects with 5 or more tasks
     * - `Client::has('projects', [2, 5], '>=<')` - Clients with 2-5 projects
     *
     * ## Example
     *
     * ```php
     * // API returns all projects
     * $apiResults = [...];
     *
     * // Filter to only projects with tasks
     * $conditions = [
     *     RequestCondition::has('tasks', 0, '>')
     * ];
     *
     * $filtered = Request::postResponseFilter($apiResults, $conditions);
     * // Only projects with at least one task remain
     * ```
     *
     * @param stdClass|stdClass[] $objects API response objects to filter
     * @param RequestCondition[]  $where   Conditions including HAS filters to apply
     *
     * @return stdClass|stdClass[] Filtered results (only entities meeting HAS requirements)
     *
     * @see RequestCondition::has() For creating HAS conditions
     * @see self::filterHas() For the recursive filtering logic
     */
    public static function postResponseFilter($objects, array $where)
    {
        $newObjects = null;
        if (count($where) > 0) {
            $hasFilter = new Dot();
            foreach ($where as $d) {
                if ($d->type === 'has') {
                    $value = $hasFilter->get($d->prop.'._has', []);
                    $op = ['operator' => $d->operator, 'value' => $d->value];
                    $value[] = $op;
                    $hasFilter->set($d->prop.'._has', $value);
                }
            }
            if (count($hasFilter) > 0) {
                $newObjects = static::filterHas($objects, $hasFilter->all());
            }
        }

        return $newObjects ?? $objects;
    }

    /**
     * Recursively filter objects by HAS conditions.
     *
     * Internal method that traverses the object hierarchy and removes
     * entities that don't meet the specified count requirements for
     * their included relationships.
     *
     * ## Algorithm
     *
     * For each object in the array:
     * 1. Recursively process nested includes (depth-first)
     * 2. Count remaining items in each included array
     * 3. Check counts against HAS requirements
     * 4. Remove objects that fail any HAS check
     *
     * ## Cascade Behavior
     *
     * Filtering cascades from deepest level up:
     * - Child items are filtered first
     * - Parent counts are then based on filtered children
     * - Parents are filtered based on updated counts
     *
     * @param stdClass[] $objects Objects to filter
     * @param array      $keys    HAS filter rules as multi-dimensional array
     *
     * @return stdClass[] Filtered array of objects
     *
     * @internal Called by postResponseFilter
     */
    public static function filterHas(array $objects, array $keys) : array
    {
        foreach ($objects as $i => $o) {
            $keepIt = true;
            foreach ($keys as $k => $deepKey) {
                if ($k === '_has') {
                    continue;
                }
                $cnt = 0;
                if (isset($o->$k) && is_array($o->$k)) {
                    $o->$k = static::filterHas($o->$k, $deepKey);
                    $cnt = count($o->$k);
                }
                $has = isset($deepKey['_has']) && count($deepKey['_has']) > 0 ? $deepKey['_has'] : [];
                foreach ($has as $h) {
                    $keepIt = RequestCondition::checkHas($cnt, $h['operator'], $h['value']);
                    if (!$keepIt) {
                        break;
                    }
                }
            }
            if (!$keepIt) {
                unset($objects[$i]);
            }
        }

        return $objects;
    }

}

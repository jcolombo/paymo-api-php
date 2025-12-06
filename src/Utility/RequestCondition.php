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
 * REQUEST CONDITION - WHERE AND HAS FILTER BUILDERS FOR API QUERIES
 * ======================================================================================
 *
 * This utility class provides factory methods for creating filter conditions used in
 * Paymo API queries. It supports two types of filtering:
 *
 * 1. WHERE CONDITIONS - Server-side filtering sent to the API
 *    These conditions are included in the API request and filter results before they
 *    are returned. Supported by the Paymo API's where parameter.
 *
 * 2. HAS CONDITIONS - Client-side filtering after API response
 *    These conditions filter results based on the count of included relationships.
 *    Applied locally after data is returned from the API.
 *
 * WHERE CONDITION EXAMPLES:
 * -------------------------
 * ```php
 * // Basic equality
 * Project::WHERE('active', '=', true)
 *
 * // Comparison operators
 * Task::WHERE('budget', '>', 1000)
 * Invoice::WHERE('date', '>=', '2024-01-01')
 *
 * // String matching
 * Client::WHERE('name', 'like', '%Acme%')
 *
 * // Value in set
 * Project::WHERE('status_code', 'in', [1, 2, 3])
 *
 * // Range filtering
 * Task::WHERE('price', 'range', [100, 500])
 * ```
 *
 * HAS CONDITION EXAMPLES:
 * -----------------------
 * ```php
 * // Projects with at least one task
 * Project::HAS('tasks', '>', 0)
 *
 * // Clients with exactly 5 projects
 * Client::HAS('projects', '=', 5)
 *
 * // Projects with between 3 and 10 tasks (inclusive)
 * Project::HAS('tasks', '=>=<=', [3, 10])
 * ```
 *
 * USAGE IN FETCH:
 * ---------------
 * ```php
 * $projects = Project::collection()->fetch(
 *     ['name', 'status', 'include:tasks'],
 *     [
 *         Project::WHERE('active', '=', true),
 *         Project::HAS('tasks', '>', 0)
 *     ]
 * );
 * ```
 *
 * @package    Jcolombo\PaymoApiPhp\Utility
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource::WHERE() Shortcut method for creating WHERE conditions
 * @see        AbstractResource::HAS() Shortcut method for creating HAS conditions
 * @see        Request Processes these conditions when building API queries
 */

namespace Jcolombo\PaymoApiPhp\Utility;

use Exception;
use Jcolombo\PaymoApiPhp\Entity\AbstractEntity;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
use Jcolombo\PaymoApiPhp\Entity\EntityMap;
use RuntimeException;

/**
 * Factory class for creating WHERE and HAS filter conditions.
 *
 * RequestCondition encapsulates filter logic for Paymo API queries. It provides
 * validated, type-safe condition objects that can be passed to collection fetch()
 * methods to filter results.
 *
 * The class supports two condition types:
 * - **WHERE**: Server-side filtering using the Paymo API's where parameter
 * - **HAS**: Client-side filtering based on included relationship counts
 *
 * Instances are typically created via the static factory methods where() and has(),
 * or more commonly through the shortcut methods on resource classes (Project::WHERE()).
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class RequestCondition
{
    /**
     * Valid operators for HAS conditions with their required value types.
     *
     * HAS conditions filter based on the count of included relationships.
     * Each operator maps to the type of value it expects:
     * - 'integer': Single integer count value
     * - 'integer[]': Array of two integers for range comparisons
     *
     * OPERATOR MEANINGS:
     * ------------------
     * | Operator | Meaning                    | Value Type  | Example                  |
     * |----------|----------------------------|-------------|--------------------------|
     * | =        | Equal to count             | integer     | has('tasks', '=', 5)     |
     * | <        | Less than count            | integer     | has('tasks', '<', 10)    |
     * | <=       | Less than or equal         | integer     | has('tasks', '<=', 5)    |
     * | >        | Greater than count         | integer     | has('tasks', '>', 0)     |
     * | >=       | Greater than or equal      | integer     | has('tasks', '>=', 3)    |
     * | !=       | Not equal to count         | integer     | has('tasks', '!=', 0)    |
     * | >=<      | Between exclusive (> and <)| integer[]   | has('tasks', '>=<', [1,10]) |
     * | =><=     | Between inclusive          | integer[]   | has('tasks', '=><=', [1,10])|
     * | =><=<    | >= low, < high             | integer[]   | has('tasks', '=>=<', [1,10])|
     * | >=<=     | > low, <= high             | integer[]   | has('tasks', '>=<=', [1,10])|
     *
     * @var array<string, string> Operator => expected value type mapping
     */
    public const HAS_OPERATORS = [
      '='     => 'integer',
      '<'     => 'integer',
      '<='    => 'integer',
      '>'     => 'integer',
      '>='    => 'integer',
      '!='    => 'integer',
      '>=<'   => 'integer[]',
      '=>=<=' => 'integer[]',
      '=>=<'  => 'integer[]',
      '>=<='  => 'integer[]'
    ];

    /**
     * Valid operators for WHERE conditions.
     *
     * References the VALID_OPERATORS constant from AbstractEntity to maintain
     * consistency across the SDK. These operators are used in server-side
     * filtering through the Paymo API's where parameter.
     *
     * SUPPORTED OPERATORS:
     * --------------------
     * - '=' : Equal to
     * - '<' : Less than
     * - '<=' : Less than or equal
     * - '>' : Greater than
     * - '>=' : Greater than or equal
     * - '!=' : Not equal to
     * - 'like' : Pattern matching (use % as wildcard)
     * - 'not like' : Negative pattern matching
     * - 'in' : Value in array
     * - 'not in' : Value not in array
     * - 'range' : Value within range [min, max]
     *
     * @var array<int, string> List of valid WHERE operators
     *
     * @see AbstractEntity::VALID_OPERATORS Source of the operator list
     */
    public const WHERE_OPERATORS = AbstractEntity::VALID_OPERATORS;

    /**
     * The type of condition: 'where' or 'has'.
     *
     * - 'where': Server-side filtering sent to the Paymo API
     * - 'has': Client-side filtering based on included relationship counts
     *
     * @var string Either 'where' or 'has'
     */
    public string $type = 'where';

    /**
     * The property or include path being filtered.
     *
     * For WHERE conditions: The resource property name (e.g., 'name', 'active', 'client_id')
     * For HAS conditions: The include relationship name (e.g., 'tasks', 'projects')
     *
     * Can use dot notation for nested filtering: 'client.name', 'project.tasks'
     *
     * @var string The property/include path to filter on
     */
    public string $prop;

    /**
     * The data type of the property being filtered.
     *
     * Populated during validation to enable type-safe value conversion when
     * building the API query string. Types come from resource class constants.
     *
     * Example types: 'integer', 'text', 'boolean', 'datetime', 'decimal', etc.
     *
     * @var string|null The property's data type, or null if not validated
     */
    public ?string $dataType = null;

    /**
     * The value to compare against the property.
     *
     * The expected type depends on the operator:
     * - Single operators (=, <, >, etc.): scalar value matching property type
     * - 'in'/'not in' operators: array of values
     * - 'range' operator: array of [min, max, inclusive?]
     * - HAS operators: integer or [int, int] for range operators
     *
     * @var mixed The comparison value
     */
    public $value;

    /**
     * The comparison operator for this condition.
     *
     * For WHERE: One of WHERE_OPERATORS (=, <, >, like, in, range, etc.)
     * For HAS: One of HAS_OPERATORS (=, <, >, >=<, etc.)
     *
     * @var string The operator, defaults to '='
     */
    public string $operator = '=';

    /**
     * Whether to validate this condition against the entity's property definitions.
     *
     * When true, the condition will be validated to ensure:
     * - The property exists on the entity
     * - The operator is allowed for this property
     * - The value type matches the property type
     *
     * Set to false to skip validation (conditions may be silently stripped if invalid).
     *
     * @var bool Whether validation is enabled, defaults to true
     */
    public bool $validate = true;

    /**
     * Create a validated WHERE condition for API filtering.
     *
     * Creates a RequestCondition object for server-side filtering using the Paymo API's
     * where parameter. The condition is validated against the entity's property definitions
     * when an entity base is provided.
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Using via resource class (recommended)
     * $condition = Project::WHERE('active', '=', true);
     * $condition = Task::WHERE('budget', '>', 1000);
     * $condition = Client::WHERE('name', 'like', '%Acme%');
     *
     * // Direct usage (less common)
     * $condition = RequestCondition::where('name', 'Test', '=', true, 'project');
     *
     * // Multiple conditions
     * $projects = Project::collection()->fetch(['name'], [
     *     Project::WHERE('active', '=', true),
     *     Project::WHERE('budget', '>', 0)
     * ]);
     *
     * // Using 'in' operator
     * $condition = Task::WHERE('status', 'in', ['open', 'in_progress']);
     *
     * // Using 'range' operator
     * $condition = Invoice::WHERE('total', 'range', [100, 500]);
     * ```
     *
     * VALIDATION:
     * -----------
     * When $entityBase is provided and $validate is true:
     * 1. Checks that the property exists on the entity
     * 2. Validates the operator is allowed for this property
     * 3. Verifies the value type matches expectations
     *
     * If validation fails, an exception is thrown immediately. Without validation,
     * invalid conditions may be silently stripped during request compilation.
     *
     * @param string      $prop       The property name to filter on (e.g., 'name', 'active', 'client_id').
     *                                Supports dot notation for nested properties.
     * @param mixed       $value      The value to compare against. Type depends on operator:
     *                                - scalar: for =, <, >, like operators
     *                                - array: for 'in', 'not in', 'range' operators
     * @param string      $operator   Comparison operator. One of: =, <, <=, >, >=, !=,
     *                                like, not like, in, not in, range. Defaults to '='.
     * @param bool        $validate   Whether to validate against entity definitions.
     *                                Defaults to true.
     * @param string|null $entityBase Optional entity key for validation (e.g., 'project').
     *                                Usually provided by Resource::WHERE() shortcuts.
     *
     * @throws Exception If operator is not in WHERE_OPERATORS
     * @throws Exception If 'range' operator used without array value
     * @throws Exception If property doesn't exist on entity (when validating)
     * @throws Exception If operator not allowed for property (when validating)
     *
     * @return RequestCondition A new condition instance ready for use in fetch()
     *
     * @see AbstractResource::WHERE() Preferred way to create WHERE conditions
     * @see has() For filtering based on relationship counts
     */
    public static function where(
      string $prop,
      $value,
      string $operator = '=',
      bool $validate = true,
      string $entityBase = null
    ) : RequestCondition {
        if (!in_array($operator, static::WHERE_OPERATORS)) {
            throw new RuntimeException(
              "Invalid operator '$operator' sent for $prop. Must be one of ".implode(
                ', ',
                static::WHERE_OPERATORS
              )
            );
        }
        if (!is_array($value) && in_array($operator, ['in', 'not in', 'range'])) {
            if ($operator === 'range') {
                throw new RuntimeException('Range operator requires a valid array value passed');
            }
            $value = [$value];
        }
        if (!is_null($entityBase) && $validate) {
            /** @var AbstractResource $resource Just the class name of the resource type, labeled here for IDE static call below */
            $resource = EntityMap::resource($entityBase);
            if (!$resource) {
                throw new RuntimeException("No class is defined for entity resource '$entityBase'");
            }
            $pts = explode('.', $prop);
            $isProp = AbstractEntity::isProp($entityBase, $pts[0]);
            if (!$isProp) {
                $isInclude = AbstractEntity::isIncludable($entityBase, $pts[0]);
                if (!$isInclude) {
                    throw new RuntimeException(
                      "Attempting to limit '$entityBase' relation results on '$prop' which is not a valid include relation"
                    );
                }
                throw new RuntimeException(
                  "Attempting to limit '$entityBase' results on '$prop' which is not a valid prop"
                );
            }
            $allowProp = strpos($prop, '.') === false ? $entityBase.'.'.$prop : $prop;
            $error = $resource::allowWhere($allowProp, $operator, $value);
            if ($error !== true) {
                throw new RuntimeException($error);
            }
        }
        $w = new self();
        $w->prop = $prop;
        $w->value = $value;
        $w->operator = $operator;
        $w->validate = $validate;

        return $w;
    }

    /**
     * Create a HAS condition for filtering based on included relationship counts.
     *
     * HAS conditions provide client-side filtering based on the count of included
     * relationships. Unlike WHERE conditions (server-side), HAS conditions are
     * applied after the API response is received, filtering results locally.
     *
     * USE CASES:
     * ----------
     * - Filter to resources with at least N related items
     * - Exclude resources with no related items
     * - Filter to resources with exactly N related items
     * - Filter to resources with related item counts in a range
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Projects with at least one task
     * Project::HAS('tasks', '>', 0)
     *
     * // Clients with no projects
     * Client::HAS('projects', '=', 0)
     *
     * // Projects with exactly 5 tasks
     * Project::HAS('tasks', '=', 5)
     *
     * // Clients with 3-10 projects (inclusive)
     * Client::HAS('projects', '=>=<=', [3, 10])
     *
     * // Combined with WHERE in fetch
     * $projects = Project::collection()->fetch(
     *     ['name', 'include:tasks'],
     *     [
     *         Project::WHERE('active', '=', true),
     *         Project::HAS('tasks', '>', 0)
     *     ]
     * );
     *
     * // Nested HAS conditions
     * Project::HAS('tasklists.tasks', '>', 5)
     * ```
     *
     * OPERATOR REFERENCE:
     * -------------------
     * | Operator | Value Type | Meaning                    |
     * |----------|------------|----------------------------|
     * | =        | int        | Count equals value         |
     * | >        | int        | Count greater than value   |
     * | <        | int        | Count less than value      |
     * | >=       | int        | Count >= value             |
     * | <=       | int        | Count <= value             |
     * | !=       | int        | Count not equal to value   |
     * | >=<      | [int,int]  | low < count < high         |
     * | =><=     | [int,int]  | low <= count <= high       |
     * | =><=<    | [int,int]  | low <= count < high        |
     * | >=<=     | [int,int]  | low < count <= high        |
     *
     * IMPORTANT: The included relationship must be requested in the fetch fields
     * for HAS filtering to work. Use 'include:relationship' in the fields array.
     *
     * @param string    $include      The include relationship path to check count on.
     *                                Can be nested with dots: 'tasks', 'tasklists.tasks'
     * @param int|int[] $count        The count value(s) to compare against:
     *                                - integer: for single-value operators (=, >, <, etc.)
     *                                - [int, int]: for range operators (>=<, =><=, etc.)
     * @param string    $operator     The comparison operator. Defaults to '>'.
     *                                Must be one of HAS_OPERATORS.
     *
     * @throws Exception If operator is not in HAS_OPERATORS
     * @throws Exception If range operator used without 2-element array
     * @throws Exception If include path references non-includable relationship
     *
     * @return RequestCondition A new HAS condition instance ready for use in fetch()
     *
     * @todo Add support for entity key for validation as string|null $baseEntity last parameter
     *
     * @see  AbstractResource::HAS() Preferred way to create HAS conditions
     * @see  checkHas() Static method that evaluates HAS conditions
     * @see  where() For server-side property filtering
     *
     * @todo Wire this into deep resource include checks. For now this is only applied to list searches
     */
    public static function has(string $include, $count = 0, string $operator = '>') : RequestCondition
    {
        if (!isset(static::HAS_OPERATORS[$operator])) {
            throw new RuntimeException(
              "Invalid operator '$operator' sent for $include. Must be one of ".implode(
                ', ',
                array_keys(static::HAS_OPERATORS)
              )
            );
        }
        if (static::HAS_OPERATORS[$operator] === 'integer[]' && (!is_array($count) || count($count) !== 2)) {
            throw new RuntimeException(
              "Operator '$operator' requires a count parameter that is a 2 element array of integers."
            );
        }
        if (strpos($include, '.')) {
            [$key, $prop] = EntityMap::extractResourceProp($include);
            $isInclude = AbstractEntity::isIncludable($key, $prop);
            if (!$isInclude) {
                throw new RuntimeException("Attempting to compare HAS results for '$include' on a non-included key");
            }
        }
        $w = new self();
        $w->type = 'has';
        $w->prop = $include;
        $w->value = $count;
        $w->operator = $operator;

        return $w;
    }

    /**
     * Evaluate a HAS condition against an actual count value.
     *
     * This static utility method performs the actual comparison logic for HAS conditions.
     * It's used internally by the SDK to filter results after API responses are received.
     *
     * COMPARISON LOGIC:
     * -----------------
     * | Operator | Formula                    | Description                  |
     * |----------|----------------------------|------------------------------|
     * | =        | cnt == amt                 | Exact match                  |
     * | >        | cnt > amt                  | Greater than                 |
     * | <        | cnt < amt                  | Less than                    |
     * | >=       | cnt >= amt                 | Greater than or equal        |
     * | <=       | cnt <= amt                 | Less than or equal           |
     * | !=       | cnt != amt                 | Not equal                    |
     * | >.<      | cnt > amt[0] && cnt < amt[1] | Exclusive between          |
     * | =>.<=    | cnt >= amt[0] && cnt <= amt[1] | Inclusive between        |
     * | =>.<     | cnt >= amt[0] && cnt < amt[1] | Left-inclusive between    |
     * | >.<=     | cnt > amt[0] && cnt <= amt[1] | Right-inclusive between   |
     * | <\|>     | cnt < amt[0] \|\| cnt > amt[1] | Outside range            |
     * | <=\|=>   | cnt <= amt[0] \|\| cnt >= amt[1] | Outside or equal range |
     * | <\|=>    | cnt < amt[0] \|\| cnt >= amt[1] | Left-outside range       |
     * | <=\|>    | cnt <= amt[0] \|\| cnt > amt[1] | Right-outside range      |
     *
     * USAGE EXAMPLES:
     * ---------------
     * ```php
     * // Check if count is greater than 5
     * RequestCondition::checkHas(10, '>', 5);  // true
     *
     * // Check if count is between 3 and 7 (inclusive)
     * RequestCondition::checkHas(5, '=>.<=', [3, 7]);  // true
     *
     * // Check if count is exactly 0
     * RequestCondition::checkHas(0, '=', 0);  // true
     * ```
     *
     * @param int       $cnt      The actual count value to check
     * @param string    $operator The comparison operator (one of HAS_OPERATORS keys)
     * @param int|int[] $amt      The threshold value(s):
     *                            - integer: for single-value operators
     *                            - [int, int]: for range operators (low, high)
     *
     * @return bool True if the count passes the operator check, false otherwise.
     *              Returns false for unknown operators.
     *
     * @see has() Creates HAS conditions that use this method
     * @see HAS_OPERATORS List of valid operators
     */
    public static function checkHas(int $cnt, string $operator, $amt) : bool
    {
        switch ($operator) {
            case('='):
                return $cnt === $amt;
            case('>'):
                return $cnt > $amt;
            case('<'):
                return $cnt < $amt;
            case('>='):
                return $cnt >= $amt;
            case('<='):
                return $cnt <= $amt;
            case('!='):
                return $cnt !== $amt;
            case('>.<'):
                return $cnt > $amt[0] && $cnt < $amt[1];
            case('=>.<='):
                return $cnt >= $amt[0] && $cnt <= $amt[1];
            case('=>.<'):
                return $cnt >= $amt[0] && $cnt < $amt[1];
            case('>.<='):
                return $cnt > $amt[0] && $cnt <= $amt[1];
            case('<|>'):
                return $cnt < $amt[0] || $cnt > $amt[1];
            case('<=|=>'):
                return $cnt <= $amt[0] || $cnt >= $amt[1];
            case('<|=>'):
                return $cnt < $amt[0] || $cnt >= $amt[1];
            case('<=|>'):
                return $cnt <= $amt[0] || $cnt > $amt[1];
            default:
                return false;
        }
    }

}
<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 9:23 PM
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
 * TASK ASSIGNMENT COLLECTION - USER-TASK RELATIONSHIP COLLECTION
 * ======================================================================================
 *
 * This specialized collection class handles Paymo task assignment entities. Task
 * assignments represent the many-to-many relationship between users and tasks,
 * tracking which users are assigned to work on which tasks.
 *
 * API FILTER REQUIREMENTS:
 * ------------------------
 * The Paymo API requires at least one filter when fetching task assignment lists.
 * This prevents unbounded queries that could return massive result sets.
 *
 * REQUIRED FILTERS (at least one):
 * --------------------------------
 * Primary Filters:
 * - task_id: Filter assignments by specific task
 * - user_id: Filter assignments by specific user
 *
 * Additional Filters (behavior may vary - see API docs):
 * - booking_date: Filter by scheduled booking date
 * - has_bookings: Filter by whether assignments have bookings
 * - task_dates: Filter by task date range
 * - task_complete: Filter by task completion status
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Entity\Resource\TaskAssignment;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Get all assignments for a specific task
 * $assignments = TaskAssignment::list($connection, [
 *     'where' => [
 *         RequestCondition::where('task_id', 12345),
 *     ]
 * ]);
 *
 * // Get all task assignments for a specific user
 * $userAssignments = TaskAssignment::list($connection, [
 *     'where' => [
 *         RequestCondition::where('user_id', 67890),
 *     ]
 * ]);
 *
 * // Get assignments with bookings for a user
 * $withBookings = TaskAssignment::list($connection, [
 *     'where' => [
 *         RequestCondition::where('user_id', 67890),
 *         RequestCondition::where('has_bookings', true),
 *     ]
 * ]);
 *
 * // This will throw an Exception - missing required filter!
 * $assignments = TaskAssignment::list($connection); // FAILS
 * ```
 *
 * ERROR HANDLING:
 * ---------------
 * If no valid filter is provided, an Exception is thrown before the API request
 * is made, providing clear guidance on required filters.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Collection
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        EntityCollection Parent collection class
 * @see        \Jcolombo\PaymoApiPhp\Entity\Resource\TaskAssignment The task assignment resource
 * @see        RequestCondition For building filter conditions
 */

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Exception;
use RuntimeException;

/**
 * Specialized collection for Paymo task assignment entities.
 *
 * Enforces Paymo API requirements for task assignment list fetches, which require
 * at least one filter to scope the query (task_id, user_id, or related filters).
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Collection
 */
class TaskAssignmentCollection extends EntityCollection
{
    /**
     * Validate that required filter conditions are present before fetching.
     *
     * The Paymo API requires task assignment list requests to include at least
     * one of the specified filter conditions. This prevents unbounded queries
     * that could return excessive data.
     *
     * ACCEPTED FILTERS:
     * -----------------
     * - task_id: Fetch assignments for a specific task
     * - user_id: Fetch assignments for a specific user
     * - booking_date: Filter by booking date (undocumented behavior)
     * - has_bookings: Filter by booking presence (undocumented behavior)
     * - task_dates: Filter by task date range (undocumented behavior)
     * - task_complete: Filter by completion status (undocumented behavior)
     *
     * @param array $fields Optional fields parameter (passed to parent)
     * @param array $where  Array of RequestCondition objects to validate
     *
     * @throws Exception If no required filter condition is found.
     *                   Message includes list of acceptable filter options.
     *
     * @return bool Returns true if validation passes (from parent)
     *
     * @see  AbstractCollection::validateFetch() Parent validation method
     *
     * @todo Investigate undocumented filter behaviors: booking_date, has_bookings,
     *       task_dates, task_complete - these are accepted but behavior is unknown
     */
    protected function validateFetch($fields = [], $where = []) : bool
    {
        // @todo Find out what these do (undocumented)... 'booking_date', 'has_bookings', 'task_dates', 'task_complete'
        $needOne = ['task_id', 'user_id', 'booking_date', 'has_bookings', 'task_dates', 'task_complete'];
        $foundOne = false;
        foreach ($where as $w) {
            if (in_array($w->prop, $needOne, true)) {
                $foundOne = true;
                break;
            }
        }
        if (!$foundOne) {
            throw new RuntimeException(
              "Task Assigment collections require at least one of the following be set as a filter : ".implode(
                ', ',
                $needOne
              )
            );
        }

        return parent::validateFetch($fields, $where);
    }
}
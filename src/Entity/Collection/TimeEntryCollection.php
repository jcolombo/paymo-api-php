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
 * TIME ENTRY COLLECTION - TRACKED TIME RECORDS
 * ======================================================================================
 *
 * This specialized collection class handles Paymo time entry entities. Time entries
 * are the core time tracking records that log work performed on tasks, including
 * duration, timestamps, descriptions, and billing information.
 *
 * API FILTER REQUIREMENTS:
 * ------------------------
 * The Paymo API requires at least one scoping filter when fetching time entry lists.
 * This prevents unbounded queries across all tracked time in the account.
 *
 * REQUIRED FILTERS (at least one):
 * --------------------------------
 * Resource Filters:
 * - task_id: Time entries for a specific task
 * - project_id: Time entries for a specific project
 * - user_id: Time entries by a specific user
 * - client_id: Time entries for a specific client's projects
 *
 * Range/Limit Filters:
 * - time_interval: Date range filter (e.g., "2024-01-01,2024-01-31")
 * - limit: Maximum number of entries to return
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Entity\Resource\TimeEntry;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Get time entries for a specific project
 * $entries = TimeEntry::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 12345),
 *     ]
 * ]);
 *
 * // Get time entries for a user within a date range
 * $userEntries = TimeEntry::list($connection, [
 *     'where' => [
 *         RequestCondition::where('user_id', 67890),
 *         RequestCondition::where('time_interval', '2024-01-01,2024-01-31'),
 *     ]
 * ]);
 *
 * // Get time entries for a client
 * $clientEntries = TimeEntry::list($connection, [
 *     'where' => [
 *         RequestCondition::where('client_id', 11111),
 *     ]
 * ]);
 *
 * // Use limit to get recent entries
 * $recentEntries = TimeEntry::list($connection, [
 *     'where' => [
 *         RequestCondition::where('limit', 100),
 *     ]
 * ]);
 *
 * // Calculate total hours
 * $totalHours = 0;
 * foreach ($entries as $entry) {
 *     $totalHours += $entry->duration / 3600; // duration is in seconds
 * }
 *
 * // This will throw an Exception - missing required filter!
 * $entries = TimeEntry::list($connection); // FAILS
 * ```
 *
 * TIME INTERVAL FORMAT:
 * ---------------------
 * The time_interval filter uses comma-separated ISO dates:
 * - Format: "YYYY-MM-DD,YYYY-MM-DD"
 * - Example: "2024-01-01,2024-01-31" (January 2024)
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
 * @see        \Jcolombo\PaymoApiPhp\Entity\Resource\TimeEntry The time entry resource
 * @see        RequestCondition For building filter conditions
 */

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Exception;
use RuntimeException;

/**
 * Specialized collection for Paymo time entry entities.
 *
 * Enforces Paymo API requirements for time entry list fetches, which require
 * at least one scoping filter (task_id, project_id, user_id, client_id,
 * time_interval, or limit).
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Collection
 */
class TimeEntryCollection extends EntityCollection
{
    /**
     * Validate that required filter conditions are present before fetching.
     *
     * The Paymo API requires time entry list requests to include at least
     * one scoping filter. This prevents queries that could return extremely
     * large result sets spanning all time tracking data.
     *
     * ACCEPTED FILTERS:
     * -----------------
     * - task_id: Filter by specific task
     * - project_id: Filter by specific project
     * - user_id: Filter by specific user
     * - client_id: Filter by specific client
     * - time_interval: Filter by date range (comma-separated dates)
     * - limit: Limit the number of results returned
     *
     * @param array $fields Optional fields parameter (passed to parent)
     * @param array $where  Array of RequestCondition objects to validate
     *
     * @throws Exception If no required filter condition is found.
     *                   Message includes list of acceptable filter options.
     *
     * @return bool Returns true if validation passes (from parent)
     *
     * @see AbstractCollection::validateFetch() Parent validation method
     */
    protected function validateFetch($fields = [], $where = []) : bool
    {
        $needOne = ['task_id', 'project_id', 'user_id', 'client_id', 'time_interval', 'limit'];
        $foundOne = false;
        foreach ($where as $w) {
            if (in_array($w->prop, $needOne, true)) {
                $foundOne = true;
                break;
            }
        }
        if (!$foundOne) {
            throw new RuntimeException(
              "Time Entry collections require at least one of the following be set as a filter : ".implode(
                ', ',
                $needOne
              )
            );
        }

        return parent::validateFetch($fields, $where);
    }
}
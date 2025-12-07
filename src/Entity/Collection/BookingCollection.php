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
 * BOOKING COLLECTION - RESOURCE SCHEDULING COLLECTION
 * ======================================================================================
 *
 * This specialized collection class handles Paymo booking (resource scheduling) entities.
 * Bookings represent scheduled time allocations for users on tasks, used for resource
 * planning and workload management.
 *
 * API FILTER REQUIREMENTS:
 * ------------------------
 * The Paymo API requires specific filters when fetching booking lists to prevent
 * unbounded queries. This collection enforces one of three filter patterns:
 *
 * OPTION 1 - Date Interval Filter (per API docs):
 * - date_interval with "in" operator: date_interval in ("2024-01-01","2024-01-31")
 * - Official Paymo API method for date range filtering
 *
 * OPTION 2 - Date Range Filter (SDK convenience):
 * - start_date AND end_date must BOTH be specified
 * - Alternative approach that also works with Paymo API
 *
 * OPTION 3 - Resource Association Filter:
 * - At least ONE of: user_task_id, task_id, project_id, or user_id
 * - Used for fetching bookings related to specific resources
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Booking;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Option 1: Filter by date range
 * $bookings = Booking::list($connection, [
 *     'where' => [
 *         RequestCondition::where('start_date', '2024-01-01', '>='),
 *         RequestCondition::where('end_date', '2024-01-31', '<='),
 *     ]
 * ]);
 *
 * // Option 2: Filter by project
 * $projectBookings = Booking::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 12345),
 *     ]
 * ]);
 *
 * // Option 2: Filter by user
 * $userBookings = Booking::list($connection, [
 *     'where' => [
 *         RequestCondition::where('user_id', 67890),
 *     ]
 * ]);
 *
 * // This will throw an Exception - missing required filters!
 * $bookings = Booking::list($connection); // FAILS
 * ```
 *
 * ERROR HANDLING:
 * ---------------
 * If neither filter pattern is satisfied, an Exception is thrown before the
 * API request is made, providing clear guidance on required filters.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Collection
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        EntityCollection Parent collection class
 * @see        \Jcolombo\PaymoApiPhp\Entity\Resource\Booking The booking resource class
 * @see        RequestCondition For building filter conditions
 */

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Exception;
use RuntimeException;

/**
 * Specialized collection for Paymo booking (resource scheduling) entities.
 *
 * Enforces Paymo API requirements for booking list fetches, which require either
 * a date range (start_date AND end_date) or a resource association filter
 * (user_task_id, task_id, project_id, or user_id).
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Collection
 */
class BookingCollection extends EntityCollection
{
    /**
     * Validate that required filter conditions are present before fetching.
     *
     * The Paymo API requires booking list requests to include either:
     * - BOTH start_date AND end_date filters, OR
     * - At least ONE of: user_task_id, task_id, project_id, user_id
     *
     * This validation runs before any API request is made, providing early
     * failure with clear error messages rather than API-level errors.
     *
     * VALIDATION LOGIC:
     * -----------------
     * 1. Scans all WHERE conditions for matching property names
     * 2. Checks if both date filters are present (date range pattern)
     * 3. Checks if any resource ID filter is present (association pattern)
     * 4. Throws Exception if neither pattern is satisfied
     *
     * @param array $fields Optional fields parameter (passed to parent)
     * @param array $where  Array of RequestCondition objects to validate
     *
     * @throws Exception If required filter conditions are not met.
     *                   Message includes list of acceptable filter options.
     *
     * @return bool Returns true if validation passes (from parent)
     *
     * @see AbstractCollection::validateFetch() Parent validation method
     */
    protected function validateFetch($fields = [], $where = []) : bool
    {
        $needOne = ['user_task_id', 'task_id', 'project_id', 'user_id', 'date_interval'];
        $date1 = $date2 = false;
        $foundOne = false;
        foreach ($where as $w) {
            if (in_array($w->prop, $needOne, true)) {
                $foundOne = true;
            } elseif ($w->prop === 'start_date') {
                $date1 = true;
            } elseif ($w->prop === 'end_date') {
                $date2 = true;
            }
        }
        $datesMet = $date1 && $date2;
        if (!$foundOne && !$datesMet) {
            throw new RuntimeException(
              "Booking collections require a start_date and end_date OR at least one of the following be set as a filter : ".implode(
                ', ',
                $needOne
              )
            );
        }

        return parent::validateFetch($fields, $where);
    }
}
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
 * BOOKING RESOURCE - PAYMO RESOURCE SCHEDULING
 * ======================================================================================
 *
 * This resource class represents a Paymo booking (resource scheduling entry).
 * Bookings are used to schedule team members for work on tasks over a date range,
 * enabling resource planning and capacity management.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - User-task assignment scheduling
 * - Date range allocation (start_date to end_date)
 * - Hours per day specification
 * - Automatic booked_hours calculation
 * - Resource planning and capacity management
 *
 * RELATIONSHIP TO OTHER ENTITIES:
 * --------------------------------
 * Bookings are linked through TaskAssignment (usertask):
 * - user_task_id: Links to a TaskAssignment (user + task combination)
 * - user_id: The user being scheduled (read-only, derived)
 * - creator_id: The user who created the booking (read-only)
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique booking identifier (read-only)
 * - user_task_id: TaskAssignment reference (required)
 * - start_date: Booking start date (required)
 * - end_date: Booking end date (required)
 * - hours_per_day: Hours scheduled per day (required)
 * - description: Booking description/notes
 *
 * Computed Properties (read-only):
 * - creator_id: User who created the booking
 * - user_id: Scheduled user (from task assignment)
 * - start_time: Start time detail
 * - end_time: End time detail
 * - booked_hours: Total calculated hours
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Booking;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new booking (schedule user for a task)
 * $booking = new Booking();
 * $booking->user_task_id = 12345; // TaskAssignment ID
 * $booking->start_date = '2024-01-15';
 * $booking->end_date = '2024-01-19';
 * $booking->hours_per_day = 4; // 4 hours/day for 5 days = 20 hours total
 * $booking->description = 'Development sprint 1';
 * $booking->create($connection);
 *
 * // Fetch booking with task assignment details
 * $booking = Booking::fetch($connection, 67890, [
 *     'include' => ['usertask']
 * ]);
 *
 * // List bookings for a date range (requires BookingCollection)
 * use Jcolombo\PaymoApiPhp\Entity\Collection\BookingCollection;
 *
 * $bookings = BookingCollection::list($connection, [
 *     'where' => [
 *         RequestCondition::where('start_date', '2024-01-01', '>='),
 *         RequestCondition::where('end_date', '2024-01-31', '<='),
 *     ]
 * ]);
 *
 * // List bookings for a specific user
 * $bookings = BookingCollection::list($connection, [
 *     'where' => [
 *         RequestCondition::where('user_id', 11111),
 *     ]
 * ]);
 *
 * // Update booking dates
 * $booking->end_date = '2024-01-26';
 * $booking->hours_per_day = 6;
 * $booking->update($connection);
 *
 * // Delete booking
 * $booking->delete();
 * ```
 *
 * COLLECTION NOTE:
 * ----------------
 * When listing bookings, use BookingCollection which enforces required
 * filter conditions (date range or user/task filters).
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        TaskAssignment User-task assignment linkage
 * @see        BookingCollection Collection with required filters
 * @see        User Scheduled team members
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Booking resource for resource scheduling operations.
 *
 * Bookings schedule team members for work on tasks over date ranges,
 * enabling resource planning and capacity management. This class provides
 * full CRUD operations with TaskAssignment integration.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id            Unique booking ID (read-only)
 * @property int    $user_task_id  TaskAssignment ID (required)
 * @property string $start_date    Booking start date (required)
 * @property string $end_date      Booking end date (required)
 * @property int    $hours_per_day Hours per day (required)
 * @property string $description   Booking description/notes
 * @property int    $creator_id    Creator user ID (read-only)
 * @property int    $user_id       Scheduled user ID (read-only)
 * @property string $start_time    Start time detail (read-only)
 * @property string $end_time      End time detail (read-only)
 * @property float  $booked_hours  Total booked hours (read-only)
 * @property string $created_on    Creation timestamp (read-only)
 * @property string $updated_on    Last update timestamp (read-only)
 */
class Booking extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Booking';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'booking';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'bookings';

    /**
     * Properties required when creating a new booking.
     *
     * All four properties are required:
     * - user_task_id: The task assignment being scheduled
     * - start_date: When the booking begins
     * - end_date: When the booking ends
     * - hours_per_day: Daily allocation
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['user_task_id', 'start_date', 'end_date', 'hours_per_day'];

    /**
     * Properties that cannot be modified via API.
     *
     * These are set by the server and returned in responses but cannot
     * be included in create or update requests. User/creator info and
     * calculated totals are derived automatically.
     *
     * @var array<string>
     */
    public const READONLY = [
      'id',
      'created_on',
      'updated_on',
      'creator_id',
      'user_id',
      'start_time',
      'end_time',
      'booked_hours',
      // Filter-only props: valid for WHERE filters but not resource properties
      'project_id',
      'task_id',
      'date_interval'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for bookings - all writable properties can be updated.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls.
     * - usertask: The task assignment (user + task combination)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = ['usertask' => false];

    /**
     * Property type definitions for validation and hydration.
     *
     * Dates use 'date' type (YYYY-MM-DD format).
     * hours_per_day is integer (whole hours per day).
     * booked_hours is calculated decimal (total hours).
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'            => 'integer',
      'created_on'    => 'datetime',
      'updated_on'    => 'datetime',
      'user_task_id'  => 'resource:usertask',
      'start_date'    => 'date',
      'end_date'      => 'date',
      'hours_per_day' => 'integer',
      'description'   => 'text',
        // Undocumented Props
      'creator_id'    => 'resource:user',
      'user_id'       => 'resource:user',
      'start_time'    => 'text', // Unsure what this datatype is
      'end_time'      => 'text',   // Unsure what this datatype is
      'booked_hours'  => 'decimal',
        // Filter-only props: valid for WHERE but not returned in response
        // Per API docs: Bookings can be filtered by project_id, task_id, or date_interval
      'project_id'    => 'resource:project',
      'task_id'       => 'resource:task',
      'date_interval' => 'text'  // Filter format: in ("YYYY-MM-DD","YYYY-MM-DD")
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for bookings.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}
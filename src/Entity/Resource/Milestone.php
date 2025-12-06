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
 * MILESTONE RESOURCE - PAYMO PROJECT MILESTONES
 * ======================================================================================
 *
 * This resource class represents a Paymo milestone. Milestones are important dates
 * or deadlines within a project, used to track major deliverables, phases, or
 * key events. They can be linked to tasklists for organized progress tracking.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Project association (required)
 * - Due date tracking
 * - Completion status
 * - Optional reminder notifications
 * - Tasklist linkage for organized deliverables
 * - User assignment (responsible person)
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique milestone identifier (read-only)
 * - name: Milestone name (required)
 * - project_id: Parent project (required)
 * - due_date: Target completion date (required)
 *
 * Status Properties:
 * - complete: Whether milestone is completed
 * - reminder_sent: Whether reminder was sent (read-only)
 * - send_reminder: Hours before due date to send reminder (0 = no reminder)
 *
 * Association Properties:
 * - user_id: Responsible user
 * - linked_tasklists: Associated tasklists
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Milestone;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new milestone
 * $milestone = new Milestone();
 * $milestone->name = 'Beta Release';
 * $milestone->project_id = 12345;
 * $milestone->due_date = '2024-03-15';
 * $milestone->user_id = 67890; // Responsible person
 * $milestone->send_reminder = 24; // Remind 24 hours before
 * $milestone->create($connection);
 *
 * // Fetch milestone with related data
 * $milestone = Milestone::fetch($connection, 11111, [
 *     'include' => ['project', 'user', 'tasklists']
 * ]);
 *
 * // List milestones for a project
 * $milestones = Milestone::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 12345),
 *     ]
 * ]);
 *
 * // List incomplete milestones
 * $incomplete = Milestone::list($connection, [
 *     'where' => [
 *         RequestCondition::where('complete', false),
 *     ]
 * ]);
 *
 * // Mark milestone as complete
 * $milestone->complete = true;
 * $milestone->update($connection);
 *
 * // Extend due date
 * $milestone->due_date = '2024-03-22';
 * $milestone->update($connection);
 * ```
 *
 * REMINDERS:
 * ----------
 * The send_reminder property accepts an integer value:
 * - 0: No reminder will be sent
 * - N: Send reminder N hours before due_date
 * - reminder_sent becomes true once the reminder is sent
 *
 * TASKLIST LINKING:
 * -----------------
 * Milestones can be linked to tasklists to group related work items.
 * When a milestone is complete, all linked tasklists are typically done.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Project Parent project resource
 * @see        Tasklist Linked tasklists
 * @see        User Responsible user
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Milestone resource for project milestone management.
 *
 * Milestones represent key dates and deliverables within projects.
 * This class provides full CRUD operations with project and
 * tasklist associations.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                Unique milestone ID (read-only)
 * @property string $name              Milestone name (required)
 * @property int    $project_id        Parent project ID (required)
 * @property int    $user_id           Responsible user ID
 * @property string $due_date          Due date (required)
 * @property int    $send_reminder     Hours before due date to remind (0=none)
 * @property bool   $reminder_sent     Whether reminder was sent (read-only)
 * @property bool   $complete          Whether milestone is completed
 * @property array  $linked_tasklists  Associated tasklist IDs
 * @property string $created_on        Creation timestamp (read-only)
 * @property string $updated_on        Last update timestamp (read-only)
 */
class Milestone extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Milestone';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'milestone';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'milestones';

    /**
     * Properties required when creating a new milestone.
     *
     * All three properties are required:
     * - name: What the milestone represents
     * - project_id: Which project it belongs to
     * - due_date: When it should be completed
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name', 'project_id', 'due_date'];

    /**
     * Properties that cannot be modified via API.
     *
     * reminder_sent is set automatically when the reminder is triggered.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'reminder_sent'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for milestones - all writable properties can be updated.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - project: Parent project (single)
     * - user: Responsible user (single)
     * - tasklists: Linked tasklists (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'project'   => false,
      'user'      => false,
      'tasklists' => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * send_reminder is an integer representing hours before due_date
     * to send the reminder (0 = no reminder).
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'               => 'integer',
      'created_on'       => 'datetime',
      'updated_on'       => 'datetime',
      'name'             => 'text',
      'project_id'       => 'resource:project',
      'user_id'          => 'resource:user',
      'due_date'         => 'date',
      'send_reminder'    => 'integer',  // 0 will not send reminder, otherwise send in X hours
      'reminder_sent'    => 'boolean',
      'complete'         => 'boolean',
      'linked_tasklists' => 'collection:tasklists'
        // Undocumented Props
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for milestones.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}
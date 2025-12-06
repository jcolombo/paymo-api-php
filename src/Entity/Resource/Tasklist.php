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
 * TASKLIST RESOURCE - PAYMO TASK ORGANIZATION
 * ======================================================================================
 *
 * This resource class represents a Paymo tasklist. Tasklists are containers for
 * organizing tasks within a project, typically representing phases, categories,
 * or work streams. Each project can have multiple tasklists, and each task
 * belongs to exactly one tasklist.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Project association (required)
 * - Sequential ordering within project
 * - Milestone association
 * - Task count tracking
 *
 * HIERARCHY:
 * ----------
 * Project → Tasklist → Task
 *
 * Projects contain tasklists, and tasklists contain tasks. This organizational
 * structure allows for logical grouping of work items.
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique tasklist identifier (read-only)
 * - name: Tasklist name (required for creation)
 * - seq: Sequence order within project
 * - project_id: Parent project (required, create-only)
 *
 * Association Properties:
 * - milestone_id: Associated milestone
 *
 * Computed Properties:
 * - tasks_count: Object with 'incomplete' and 'completed' counts (read-only)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Tasklist;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new tasklist
 * $tasklist = new Tasklist();
 * $tasklist->name = 'Development Phase 1';
 * $tasklist->project_id = 12345;
 * $tasklist->create($connection);
 *
 * // Create with milestone
 * $tasklist = new Tasklist();
 * $tasklist->name = 'Sprint 1';
 * $tasklist->project_id = 12345;
 * $tasklist->milestone_id = 67890;
 * $tasklist->create($connection);
 *
 * // Fetch tasklist with tasks
 * $tasklist = Tasklist::fetch($connection, 11111, [
 *     'include' => ['tasks', 'project']
 * ]);
 *
 * // List tasklists for a project
 * $tasklists = Tasklist::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 12345),
 *     ]
 * ]);
 *
 * // Check task counts
 * foreach ($tasklists as $list) {
 *     echo $list->name . ": ";
 *     echo $list->tasks_count['incomplete'] . " incomplete, ";
 *     echo $list->tasks_count['completed'] . " completed\n";
 * }
 *
 * // Rename tasklist
 * $tasklist->name = 'Development Phase 1 - Complete';
 * $tasklist->update($connection);
 *
 * // Reorder (change sequence)
 * $tasklist->seq = 1;
 * $tasklist->update($connection);
 * ```
 *
 * ORDERING:
 * ---------
 * The 'seq' property controls the display order of tasklists within a project.
 * Lower numbers appear first. You can also reorder via the Project's
 * tasklists_order property.
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
 * @see        Task Tasks within tasklist
 * @see        Milestone Associated milestones
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Tasklist resource for task organization within projects.
 *
 * Tasklists are containers that group tasks within a project. This class
 * provides full CRUD operations and supports related entity includes.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id           Unique tasklist ID (read-only)
 * @property string $name         Tasklist name (required)
 * @property int    $seq          Sequence order in project
 * @property int    $project_id   Parent project ID (required, create-only)
 * @property int    $milestone_id Associated milestone ID
 * @property array  $tasks_count  Task counts {incomplete, completed} (read-only)
 * @property string $created_on   Creation timestamp (read-only)
 * @property string $updated_on   Last update timestamp (read-only)
 */
class Tasklist extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Tasklist';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'tasklist';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'tasklists';

    /**
     * Properties required when creating a new tasklist.
     *
     * Both 'name' and 'project_id' are required.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name', 'project_id'];

    /**
     * Properties that cannot be modified via API.
     *
     * project_id cannot be changed after creation (tasklists cannot
     * be moved between projects). tasks_count is computed.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'project_id', 'tasks_count'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * project_id must be set at creation time and cannot be changed.
     *
     * @var array<string>
     */
    public const CREATEONLY = ['project_id'];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'project'    => false,
      'miletstone' => false,
      'tasks'      => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * tasks_count is a special nested object type.
     *
     * @var array<string, string|array>
     */
    public const PROP_TYPES = [
      'id'           => 'integer',
      'created_on'   => 'datetime',
      'updated_on'   => 'datetime',
      'name'         => 'text',
      'seq'          => 'integer',
      'project_id'   => 'resource:project',
      'milestone_id' => 'resource:milestone',
        // Undocumented Props
      'tasks_count'  => ['incomplete' => 'integer', 'completed' => 'integer']
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * @var array<string, array<string>|null>
     *
     * @todo tasks_count filtering requires nested object property support
     */
    public const WHERE_OPERATIONS = [
      'tasks_count' => null
    ];
}
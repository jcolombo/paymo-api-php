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
 * PROJECT STATUS RESOURCE - PAYMO PROJECT STATUS MANAGEMENT
 * ======================================================================================
 *
 * This resource class represents a Paymo project status. Project statuses are
 * customizable labels that categorize projects by their current state (e.g.,
 * Active, On Hold, Completed, Archived). They help organize and filter projects.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Custom status labels
 * - Active/inactive toggle
 * - Sequential ordering
 * - System-protected readonly statuses
 *
 * DEFAULT STATUSES:
 * -----------------
 * Paymo accounts typically come with default statuses:
 * - Active: Projects currently in progress
 * - On Hold: Temporarily paused projects
 * - Done: Completed projects
 * - Archived: Old/inactive projects
 *
 * Some default statuses are marked as 'readonly' and cannot be deleted.
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique status identifier (read-only)
 * - name: Status label (required)
 * - active: Whether status is available for use
 * - seq: Display sequence order
 * - readonly: System-protected status (read-only)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\ProjectStatus;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new project status
 * $status = new ProjectStatus();
 * $status->name = 'Pending Approval';
 * $status->active = true;
 * $status->seq = 5;
 * $status->create($connection);
 *
 * // List all available statuses
 * $statuses = ProjectStatus::list($connection);
 *
 * // List only active statuses
 * $activeStatuses = ProjectStatus::list($connection, [
 *     'where' => [
 *         RequestCondition::where('active', true),
 *     ]
 * ]);
 *
 * // Fetch status with projects using it
 * $status = ProjectStatus::fetch($connection, 12345, [
 *     'include' => ['projects']
 * ]);
 *
 * // Check how many projects use this status
 * if ($status->hasInclude('projects')) {
 *     echo "Projects with this status: " . count($status->getInclude('projects'));
 * }
 *
 * // Rename a status
 * $status->name = 'Review Required';
 * $status->update($connection);
 *
 * // Deactivate a status (hide from dropdowns)
 * $status->active = false;
 * $status->update($connection);
 *
 * // Reorder status
 * $status->seq = 3;
 * $status->update($connection);
 * ```
 *
 * ORDERING:
 * ---------
 * The 'seq' property controls display order:
 * - Lower numbers appear first
 * - Sequence can be updated at any time
 *
 * READONLY STATUSES:
 * ------------------
 * Some system statuses have readonly=true:
 * - These are Paymo's default statuses
 * - They can be updated (renamed) but not deleted
 * - Custom statuses have readonly=false
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Project Projects using this status
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo ProjectStatus resource for project categorization.
 *
 * Project statuses categorize projects by state (Active, On Hold, etc.).
 * This class provides full CRUD operations for managing custom statuses.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id         Unique status ID (read-only)
 * @property string $name       Status label (required)
 * @property bool   $active     Whether status is available
 * @property int    $seq        Display sequence order
 * @property bool   $readonly   System-protected flag (read-only)
 * @property string $created_on Creation timestamp (read-only)
 * @property string $updated_on Last update timestamp (read-only)
 */
class ProjectStatus extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Project Status';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'projectstatus';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'projectstatuses';

    /**
     * Properties required when creating a new project status.
     *
     * Only 'name' is required to create a status.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name'];

    /**
     * Properties that cannot be modified via API.
     *
     * The 'readonly' flag indicates system-protected statuses that
     * cannot be deleted but can be renamed.
     *
     * @var array<string>
     */
    public const READONLY = [
      'id',
      'created_on',
      'updated_on',
      'readonly',
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for project statuses - all writable properties
     * can be updated after creation.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - projects: Projects using this status (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'projects' => true,
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'         => 'integer',
      'name'       => 'text',
      'active'     => 'boolean',
      'seq'        => 'integer',
      'readonly'   => 'boolean',
      'created_on' => 'datetime',
      'updated_on' => 'datetime',
        // Undocumented Props
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Defines restrictions on filtering:
     * - active: Only equality comparisons
     * - name: Supports equality and pattern matching
     * - readonly: Only equality comparisons
     * - seq: Excludes pattern matching (numeric only)
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [
      'active'   => ['=', '!='],
      'name'     => ['=', 'like', 'not like'],
      'readonly' => ['=', '!='],
      '!seq'     => ['like', 'not like']
    ];
}
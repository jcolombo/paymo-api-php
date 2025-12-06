<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/15/20, 11:31 PM
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
 * PROJECT TEMPLATE TASKLIST RESOURCE - PAYMO TEMPLATE TASKLIST SECTIONS
 * ======================================================================================
 *
 * This resource class represents a Paymo project template tasklist. Template
 * tasklists are the section containers within a project template that hold
 * template tasks. When a project is created from the template, these become
 * actual tasklists in the new project.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Template association (required)
 * - Sequence ordering
 * - Task containers
 * - Milestone association (optional)
 *
 * TEMPLATE HIERARCHY:
 * -------------------
 * - ProjectTemplate: Container
 *   - ProjectTemplateTasklist: Sections (this class)
 *     - ProjectTemplateTask: Individual template tasks
 *
 * RESPONSE KEY MAPPING:
 * ---------------------
 * This resource uses 'project_templates_tasklists' as the API response key
 * instead of the standard 'projecttemplatestasklists' endpoint name.
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique tasklist identifier (read-only)
 * - name: Tasklist name (required)
 * - template_id: Parent template (required)
 * - seq: Display order
 * - milestone_id: Associated milestone (undocumented)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\ProjectTemplateTasklist;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new template tasklist
 * $tasklist = new ProjectTemplateTasklist();
 * $tasklist->name = 'Development Tasks';
 * $tasklist->template_id = 12345;
 * $tasklist->seq = 1;
 * $tasklist->create($connection);
 *
 * // Create another tasklist for testing
 * $tasklist = new ProjectTemplateTasklist();
 * $tasklist->name = 'QA & Testing';
 * $tasklist->template_id = 12345;
 * $tasklist->seq = 2;
 * $tasklist->create($connection);
 *
 * // Fetch tasklist with template and tasks
 * $tasklist = ProjectTemplateTasklist::fetch($connection, 67890, [
 *     'include' => ['projecttemplate', 'projecttemplatestasks']
 * ]);
 *
 * // List tasklists for a template
 * $tasklists = ProjectTemplateTasklist::list($connection, [
 *     'where' => [
 *         RequestCondition::where('template_id', 12345),
 *     ]
 * ]);
 *
 * // Update tasklist
 * $tasklist->name = 'Backend Development';
 * $tasklist->update($connection);
 *
 * // Delete tasklist (also removes contained tasks)
 * $tasklist->delete();
 * ```
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        ProjectTemplate Parent template container
 * @see        ProjectTemplateTask Template tasks within tasklists
 * @see        Tasklist Actual tasklists in projects
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo ProjectTemplateTasklist resource for template sections.
 *
 * Template tasklists are section containers within project templates.
 * This class provides full CRUD operations with template association.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id           Unique tasklist ID (read-only)
 * @property string $name         Tasklist name (required)
 * @property int    $template_id  Parent template ID (required)
 * @property int    $seq          Display order
 * @property int    $milestone_id Associated milestone ID
 * @property string $created_on   Creation timestamp (read-only)
 * @property string $updated_on   Last update timestamp (read-only)
 */
class ProjectTemplateTasklist extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Project Template Tasklist';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'projecttemplatestasklist';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'projecttemplatestasklists';

    /**
     * Alternative response key for processing API results.
     *
     * The API returns 'project_templates_tasklists' instead of
     * 'projecttemplatestasklists'.
     *
     * @var string
     */
    public const API_RESPONSE_KEY = 'project_templates_tasklists';

    /**
     * Properties required when creating a new template tasklist.
     *
     * Both 'name' and 'template_id' are required.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name', 'template_id'];

    /**
     * Properties that cannot be modified via API.
     *
     * Standard timestamp fields are read-only.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for template tasklists.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - projecttemplate: Parent template (single)
     * - projecttemplatestasks: Template tasks in this list (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'projecttemplate'       => false,
      'projecttemplatestasks' => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Note: milestone_id is undocumented but functional.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'           => 'integer',
      'created_on'   => 'datetime',
      'updated_on'   => 'datetime',
      'name'         => 'text',
      'seq'          => 'integer',
      'template_id'  => 'resource:projecttemplate',
        // Undocumented Props
      'milestone_id' => 'resource:milestone'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for template tasklists.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}
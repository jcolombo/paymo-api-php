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
 * PROJECT TEMPLATE RESOURCE - PAYMO PROJECT STRUCTURE TEMPLATES
 * ======================================================================================
 *
 * This resource class represents a Paymo project template. Project templates
 * define reusable project structures including tasklists and tasks that can
 * be applied when creating new projects for consistent project setup.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Create from existing project (project_id)
 * - Tasklist structure templates
 * - Task templates with assignments
 * - Reusable project scaffolding
 *
 * TEMPLATE HIERARCHY:
 * -------------------
 * - ProjectTemplate: Container with name
 *   - ProjectTemplateTasklist: Template tasklist sections
 *     - ProjectTemplateTask: Template tasks with details
 *
 * RESPONSE KEY MAPPING:
 * ---------------------
 * This resource uses 'project_templates' as the API response key
 * instead of the standard 'projecttemplates' endpoint name.
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique template identifier (read-only)
 * - name: Template name (required)
 * - project_id: Source project for template (create-only)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\ProjectTemplate;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new empty template
 * $template = new ProjectTemplate();
 * $template->name = 'Website Development Template';
 * $template->create($connection);
 *
 * // Create template from existing project
 * $template = new ProjectTemplate();
 * $template->name = 'Standard Web Project';
 * $template->project_id = 12345; // Copy structure from this project
 * $template->create($connection);
 *
 * // Fetch template with all tasklists and tasks
 * $template = ProjectTemplate::fetch($connection, 67890, [
 *     'include' => ['projecttemplatestasklists', 'projecttemplatestasks']
 * ]);
 *
 * // Access template structure
 * if ($template->hasInclude('projecttemplatestasklists')) {
 *     $tasklists = $template->getInclude('projecttemplatestasklists');
 *     foreach ($tasklists as $list) {
 *         echo "Tasklist: " . $list->name . "\n";
 *     }
 * }
 *
 * // List all templates
 * $templates = ProjectTemplate::list($connection);
 *
 * // Update template name
 * $template->name = 'Updated Template Name';
 * $template->update($connection);
 *
 * // Delete template
 * $template->delete();
 * ```
 *
 * USING TEMPLATES:
 * ----------------
 * To create a project from a template, set project_template_id on the Project
 * resource when creating a new project. This copies the template structure.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        ProjectTemplateTasklist Template tasklists
 * @see        ProjectTemplateTask Template tasks
 * @see        Project Projects using templates
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo ProjectTemplate resource for reusable project structures.
 *
 * Project templates define reusable project structures. This class
 * provides full CRUD operations with tasklist and task template associations.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id         Unique template ID (read-only)
 * @property string $name       Template name (required)
 * @property int    $project_id Source project ID (create-only)
 * @property string $created_on Creation timestamp (read-only)
 * @property string $updated_on Last update timestamp (read-only)
 */
class ProjectTemplate extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Project Template';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'projecttemplate';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'projecttemplates';

    /**
     * Alternative response key for processing API results.
     *
     * The API returns 'project_templates' instead of 'projecttemplates'.
     *
     * @var string
     */
    public const API_RESPONSE_KEY = 'project_templates';

    /**
     * Properties required when creating a new project template.
     *
     * Only 'name' is required. Optionally set 'project_id' to copy
     * structure from an existing project.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name'];

    /**
     * Properties that cannot be modified via API.
     *
     * project_id is in READONLY but also in CREATEONLY, allowing
     * it to be set during creation to copy from an existing project.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'project_id'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * project_id can be set to copy structure from an existing project
     * but cannot be changed after template creation.
     *
     * @var array<string>
     */
    public const CREATEONLY = ['project_id'];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - projecttemplatestasklists: Template tasklists (collection)
     * - projecttemplatestasks: Template tasks (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'projecttemplatestasklists' => true,
      'projecttemplatestasks'     => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'         => 'integer',
      'created_on' => 'datetime',
      'updated_on' => 'datetime',
      'name'       => 'text'
        // Undocumented Props
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for project templates.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}
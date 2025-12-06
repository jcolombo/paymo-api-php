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
 * DISCUSSION RESOURCE - PAYMO PROJECT DISCUSSIONS
 * ======================================================================================
 *
 * This resource class represents a Paymo discussion. Discussions are threaded
 * conversation topics within a project, allowing team members to communicate
 * and collaborate on specific subjects separate from task comments.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Project association (required)
 * - HTML content support for descriptions
 * - Comment thread integration
 * - File attachments
 * - User tracking (creator)
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique discussion identifier (read-only)
 * - name: Discussion title (required)
 * - description: Discussion content (HTML supported)
 * - project_id: Parent project (required)
 * - user_id: Creator user (read-only)
 * - comments_count: Number of comments (read-only)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Discussion;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new discussion
 * $discussion = new Discussion();
 * $discussion->name = 'Project Architecture Decisions';
 * $discussion->description = '<p>Let\'s discuss the technical architecture...</p>';
 * $discussion->project_id = 12345;
 * $discussion->create($connection);
 *
 * // Fetch a discussion with comments and files
 * $discussion = Discussion::fetch($connection, 67890, [
 *     'include' => ['project', 'user', 'thread', 'files']
 * ]);
 *
 * // List discussions for a project
 * $discussions = Discussion::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 12345),
 *     ]
 * ]);
 *
 * // Update discussion content
 * $discussion->description = '<p>Updated content with more details...</p>';
 * $discussion->update($connection);
 *
 * // Check comment count
 * echo "Comments: " . $discussion->comments_count;
 *
 * // Delete discussion
 * $discussion->delete();
 * ```
 *
 * HTML CONTENT:
 * -------------
 * The description field supports HTML formatting:
 * - Use <p> tags for paragraphs
 * - Basic formatting like <b>, <i>, <u>
 * - Links with <a href="...">
 * - Lists with <ul>/<ol> and <li>
 *
 * THREAD INTEGRATION:
 * -------------------
 * Discussions have an associated comment thread accessible via the 'thread' include.
 * Comments on discussions work the same as comments on tasks.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Project Parent project resource
 * @see        CommentThread Associated comment thread
 * @see        File Attached files
 * @see        User Discussion creator
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Discussion resource for project conversations.
 *
 * Discussions are threaded conversations within projects. This class
 * provides full CRUD operations with comment thread and file associations.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id             Unique discussion ID (read-only)
 * @property string $name           Discussion title (required)
 * @property string $description    Discussion content (HTML supported)
 * @property int    $project_id     Parent project ID (required)
 * @property int    $user_id        Creator user ID (read-only)
 * @property int    $comments_count Number of comments (read-only)
 * @property string $created_on     Creation timestamp (read-only)
 * @property string $updated_on     Last update timestamp (read-only)
 */
class Discussion extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Discussion';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'discussion';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'discussions';

    /**
     * Properties required when creating a new discussion.
     *
     * Both 'name' and 'project_id' are required.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name', 'project_id'];

    /**
     * Properties that cannot be modified via API.
     *
     * User and comments_count are set/calculated by the server.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'user_id', 'comments_count'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for discussions - all writable properties can be updated.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - project: Parent project (single)
     * - user: Creator user (single)
     * - thread: Comment thread (single)
     * - files: Attached files (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'project' => false,
      'user'    => false,
      'thread'  => false,
      'files'   => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Description uses 'html' type for formatted content.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'             => 'integer',
      'created_on'     => 'datetime',
      'updated_on'     => 'datetime',
      'name'           => 'text',
      'description'    => 'html',
      'project_id'     => 'resource:project',
      'user_id'        => 'resource:user',
        // Undocumented Props
      'comments_count' => 'integer'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for discussions.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}
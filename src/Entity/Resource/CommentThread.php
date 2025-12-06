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
 * COMMENT THREAD RESOURCE - PAYMO COMMENT THREAD CONTAINER
 * ======================================================================================
 *
 * This resource class represents a Paymo comment thread. Threads are containers
 * that group comments together, associated with tasks, discussions, or files.
 * Threads are automatically created when the first comment is added.
 *
 * RESTRICTED OPERATIONS:
 * ----------------------
 * This resource has restricted operations:
 * - fetch(): Supported - retrieve thread with comments
 * - list(): Supported - list threads (use CommentThreadCollection)
 * - delete(): Supported - delete thread and all comments
 * - create(): NOT SUPPORTED - threads are created automatically
 * - update(): NOT SUPPORTED - threads have no updatable properties
 *
 * KEY FEATURES:
 * -------------
 * - Read and list operations
 * - Delete operation (removes all comments)
 * - Association with tasks, discussions, or files
 * - Comment aggregation
 *
 * THREAD ASSOCIATIONS:
 * --------------------
 * Each thread is associated with exactly one of:
 * - task_id: Task thread
 * - discussion_id: Discussion thread
 * - file_id: File thread
 * - project_id: Project context (derived)
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties (all read-only):
 * - id: Unique thread identifier
 * - project_id: Parent project
 * - task_id: Associated task (if task thread)
 * - discussion_id: Associated discussion (if discussion thread)
 * - file_id: Associated file (if file thread)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\CommentThread;
 * use Jcolombo\PaymoApiPhp\Entity\Collection\CommentThreadCollection;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Fetch a thread with all comments
 * $thread = CommentThread::fetch($connection, 12345, [
 *     'include' => ['comments', 'project', 'task']
 * ]);
 *
 * // Access thread comments
 * if ($thread->hasInclude('comments')) {
 *     $comments = $thread->getInclude('comments');
 *     foreach ($comments as $comment) {
 *         echo $comment->content . "\n";
 *     }
 * }
 *
 * // List threads for a project
 * $threads = CommentThreadCollection::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 67890),
 *     ]
 * ]);
 *
 * // List threads for a task
 * $threads = CommentThreadCollection::list($connection, [
 *     'where' => [
 *         RequestCondition::where('task_id', 11111),
 *     ]
 * ]);
 *
 * // Delete thread and all its comments
 * $thread->delete();
 *
 * // WILL THROW EXCEPTION:
 * // $thread = new CommentThread();
 * // $thread->create($connection);  // Cannot create directly
 * ```
 *
 * CREATING THREADS:
 * -----------------
 * Threads cannot be created directly via API. Instead, create a Comment
 * with a task_id, discussion_id, or file_id - the thread will be created
 * automatically if one doesn't exist.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Comment Comments within threads
 * @see        Task Task threads
 * @see        Discussion Discussion threads
 * @see        File File threads
 * @see        CommentThreadCollection Collection with required filters
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Exception;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
use RuntimeException;

/**
 * Paymo CommentThread resource for comment organization.
 *
 * Comment threads group comments associated with tasks, discussions, or files.
 * Threads are automatically created when comments are added. This class
 * supports fetch, list, and delete operations (create is not allowed).
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id            Unique thread ID (read-only)
 * @property int    $project_id    Parent project ID (read-only)
 * @property int    $task_id       Associated task ID (read-only)
 * @property int    $discussion_id Associated discussion ID (read-only)
 * @property int    $file_id       Associated file ID (read-only)
 * @property string $created_on    Creation timestamp (read-only)
 * @property string $updated_on    Last update timestamp (read-only)
 */
class CommentThread extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Comment Thread';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'thread';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'threads';

    /**
     * Properties required when creating a new thread.
     *
     * Empty because threads cannot be created directly via API.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = [];

    /**
     * Properties that cannot be modified via API.
     *
     * All thread properties are read-only - threads are created
     * automatically when comments are added to entities.
     *
     * @var array<string>
     */
    public const READONLY = [
      'id',
      'created_on',
      'updated_on',
      'project_id',
      'discussion_id',
      'task_id',
      'file_id'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Empty because threads cannot be created directly.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - project: Parent project (single)
     * - discussion: Associated discussion (single)
     * - task: Associated task (single)
     * - file: Associated file (single)
     * - comments: Thread comments (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'project'    => false,
      'discussion' => false,
      'task'       => false,
      'file'       => false,
      'comments'   => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'            => 'integer',
      'created_on'    => 'datetime',
      'updated_on'    => 'datetime',
      'project_id'    => 'resource:project',
      'discussion_id' => 'resource:discussion',
      'task_id'       => 'resource:task',
      'file_id'       => 'resource:file'
        // Undocumented Props
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for threads.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];

    /**
     * Create operation is not supported for CommentThread.
     *
     * Threads are automatically created when comments are added to tasks,
     * discussions, or files. To start a thread, create a Comment with
     * the appropriate entity ID (task_id, discussion_id, or file_id).
     *
     * @param array $options Create options (ignored)
     *
     * @throws Exception Always throws - threads cannot be created directly
     * @return void
     */
    public function create($options = []) : void
    {
        throw new RuntimeException(
          "Threads cannot be directly created with the API. Use new comment resources to start threads."
        );
    }
}
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
 * COMMENT RESOURCE - PAYMO COMMENTS AND REPLIES
 * ======================================================================================
 *
 * This resource class represents a Paymo comment. Comments are messages that can
 * be attached to tasks, discussions, or files, enabling team collaboration and
 * communication around work items.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Attachment to tasks, discussions, or files
 * - Thread-based organization
 * - File attachments to comments
 * - User tracking (author)
 *
 * COMMENT TARGETS:
 * ----------------
 * Comments can be attached to various entities using one of:
 * - thread_id: Attach to an existing comment thread
 * - task_id: Attach to a task (creates/uses task's thread)
 * - discussion_id: Attach to a discussion (creates/uses discussion's thread)
 * - file_id: Attach to a file (creates/uses file's thread)
 *
 * At least ONE of these must be specified when creating a comment.
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique comment identifier (read-only)
 * - content: Comment text (required)
 * - thread_id: Parent thread
 * - user_id: Comment author
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Comment;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Add a comment to a task
 * $comment = new Comment();
 * $comment->content = 'This task needs more details on the requirements.';
 * $comment->task_id = 12345;
 * $comment->create($connection);
 *
 * // Add a comment to a discussion
 * $comment = new Comment();
 * $comment->content = 'I agree with the proposed approach.';
 * $comment->discussion_id = 67890;
 * $comment->create($connection);
 *
 * // Reply to an existing thread
 * $comment = new Comment();
 * $comment->content = 'Thanks for the update!';
 * $comment->thread_id = 11111;
 * $comment->create($connection);
 *
 * // Fetch a comment with related data
 * $comment = Comment::fetch($connection, 22222, [
 *     'include' => ['thread', 'user', 'project', 'files']
 * ]);
 *
 * // Update comment text
 * $comment->content = 'Updated: Added more context to my previous comment.';
 * $comment->update($connection);
 *
 * // Delete a comment
 * $comment->delete();
 * ```
 *
 * THREADS:
 * --------
 * Comments belong to threads. When you create a comment on a task/discussion/file,
 * a thread is automatically created or the existing thread is used. You can then
 * use thread_id for subsequent replies.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        CommentThread Comment thread container
 * @see        Task Task comments
 * @see        Discussion Discussion comments
 * @see        File File comments
 * @see        User Comment authors
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Comment resource for team collaboration.
 *
 * Comments enable communication on tasks, discussions, and files.
 * This class provides full CRUD operations with thread integration.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id            Unique comment ID (read-only)
 * @property string $content       Comment text (required)
 * @property int    $thread_id     Parent thread ID (read-only)
 * @property int    $user_id       Author user ID (read-only)
 * @property int    $task_id       Task to comment on (create-only)
 * @property int    $discussion_id Discussion to comment on (create-only)
 * @property int    $file_id       File to comment on (create-only)
 * @property string $created_on    Creation timestamp (read-only)
 * @property string $updated_on    Last update timestamp (read-only)
 */
class Comment extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Comment';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'comment';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'comments';

    /**
     * Properties required when creating a new comment.
     *
     * Requires 'content' AND at least one of:
     * - thread_id: Existing thread to add comment to
     * - task_id: Task to comment on
     * - discussion_id: Discussion to comment on
     * - file_id: File to comment on
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['content', 'thread_id||task_id||discussion_id||file_id'];

    /**
     * Properties that cannot be modified via API.
     *
     * thread_id and user_id are set by the server based on the target
     * (task_id/discussion_id/file_id) and authenticated user.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'thread_id', 'user_id'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * task_id, discussion_id, and file_id specify the comment target
     * during creation. Once created, the comment belongs to a thread.
     *
     * @var array<string>
     */
    public const CREATEONLY = ['task_id', 'discussion_id', 'file_id'];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - thread: Parent thread (single)
     * - user: Author user (single)
     * - project: Related project (single)
     * - files: Attached files (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'thread'  => false,
      'user'    => false,
      'project' => false,
      'files'   => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * task_id, discussion_id, and file_id are create-only properties
     * used to specify where the comment is attached. After creation,
     * only thread_id is available on the response.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'            => 'integer',
      'created_on'    => 'datetime',
      'updated_on'    => 'datetime',
      'content'       => 'text',
      'thread_id'     => 'resource:thread',
      'user_id'       => 'resource:user',
        // Create-only target properties (specify where to attach comment)
      'task_id'       => 'resource:task',
      'discussion_id' => 'resource:discussion',
      'file_id'       => 'resource:file'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for comments.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}
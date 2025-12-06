<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 10:48 PM
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
 * FILE RESOURCE - PAYMO FILE ATTACHMENT MANAGEMENT
 * ======================================================================================
 *
 * This resource class represents a Paymo file attachment. Files can be attached
 * to various entities including projects, tasks, discussions, and comments.
 * The file upload process uses multipart form data.
 *
 * KEY FEATURES:
 * -------------
 * - File upload via multipart form data
 * - Attachment to projects, tasks, discussions, or comments
 * - Image thumbnail generation
 * - File metadata (size, mime type, filename)
 * - Download URL generation
 * - External service integration
 *
 * ATTACHMENT HIERARCHY:
 * ---------------------
 * Files can be attached to ONE of the following (mutually exclusive):
 * - project_id: Attach to a project
 * - task_id: Attach to a specific task
 * - discussion_id: Attach to a discussion thread
 * - comment_id: Attach to a comment
 * - None: Standalone file (account-level)
 *
 * If multiple parent IDs are provided during creation, an exception is thrown.
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique file identifier (read-only)
 * - file: File path/URL (required for upload, read-only after)
 * - original_filename: Original uploaded filename
 * - description: File description
 * - size: File size in bytes (read-only)
 * - token: Access token (read-only)
 *
 * Attachment Properties (create-only):
 * - project_id: Parent project
 * - task_id: Parent task
 * - discussion_id: Parent discussion
 * - comment_id: Parent comment
 * - user_id: Uploader user
 *
 * Image Properties (read-only, for image files):
 * - image_thumb_small: Small thumbnail URL
 * - image_thumb_medium: Medium thumbnail URL
 * - image_thumb_large: Large thumbnail URL
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\File;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Upload a file to a project
 * $file = new File();
 * $file->file = '/path/to/local/document.pdf';
 * $file->project_id = 12345;
 * $file->description = 'Project requirements document';
 * $file->create($connection);
 *
 * // Upload a file to a task
 * $file = new File();
 * $file->file = '/path/to/screenshot.png';
 * $file->task_id = 67890;
 * $file->create($connection);
 *
 * // Fetch a file with related entities
 * $file = File::fetch($connection, 11111, [
 *     'include' => ['project', 'user', 'task']
 * ]);
 *
 * // Access file properties
 * echo "Filename: " . $file->original_filename . "\n";
 * echo "Size: " . $file->size . " bytes\n";
 * echo "Download: " . $file->download_url . "\n";
 *
 * // For image files, access thumbnails
 * if ($file->image_thumb_medium) {
 *     echo "Thumbnail: " . $file->image_thumb_medium;
 * }
 *
 * // List files for a project (requires FileCollection)
 * use Jcolombo\PaymoApiPhp\Entity\Collection\FileCollection;
 *
 * $files = FileCollection::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 12345),
 *     ]
 * ]);
 *
 * // Update file description
 * $file->description = 'Updated description';
 * $file->original_filename = 'renamed-document.pdf';
 * $file->update($connection);
 *
 * // Delete a file
 * $file->delete();
 * ```
 *
 * UPLOAD NOTES:
 * -------------
 * - The 'file' property should be a local file path during creation
 * - Files are uploaded using multipart form data
 * - Only ONE parent entity can be specified (project/task/discussion/comment)
 * - After upload, file property becomes the Paymo file URL
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        FileCollection Collection with required filters
 * @see        Project Project file attachments
 * @see        Task Task file attachments
 * @see        Discussion Discussion file attachments
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Exception;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
use RuntimeException;

/**
 * Paymo File resource for file attachment operations.
 *
 * Files can be attached to projects, tasks, discussions, or comments.
 * This class handles file uploads via multipart form data and provides
 * access to file metadata and thumbnails.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                  Unique file ID (read-only)
 * @property string $file                File path/URL (required)
 * @property string $original_filename   Original filename
 * @property string $description         File description
 * @property int    $size                File size in bytes (read-only)
 * @property string $token               Access token (read-only)
 * @property int    $user_id             Uploader user ID (create-only)
 * @property int    $project_id          Parent project ID (create-only)
 * @property int    $task_id             Parent task ID (create-only)
 * @property int    $discussion_id       Parent discussion ID (create-only)
 * @property int    $comment_id          Parent comment ID (create-only)
 * @property string $image_thumb_small   Small thumbnail URL (read-only)
 * @property string $image_thumb_medium  Medium thumbnail URL (read-only)
 * @property string $image_thumb_large   Large thumbnail URL (read-only)
 * @property string $mime                MIME type (read-only)
 * @property string $download_url        Download URL (read-only)
 * @property string $external_url        External service URL (read-only)
 * @property string $external_service    External service name (read-only)
 * @property array  $tags                File tags
 * @property string $created_on          Creation timestamp (read-only)
 * @property string $updated_on          Last update timestamp (read-only)
 */
class File extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'File';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'file';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'files';

    /**
     * Properties required when creating a new file.
     *
     * Only 'file' (the local file path) is required. Parent entity
     * (project_id, task_id, etc.) is optional.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['file'];

    /**
     * Properties that cannot be modified via API.
     *
     * Most file properties are read-only after upload, including
     * the parent entity associations and file metadata.
     *
     * @var array<string>
     */
    public const READONLY = [
      'id',
      'created_on',
      'updated_on',
      'user_id',
      'project_id',
      'discussion_id',
      'task_id',
      'comment_id',
      'token',
      'file',
      'size',
      'image_thumb_small',
      'image_thumb_medium',
      'image_thumb_large',
        //Undocumented
      'mime',
      'external_url',
      'external_service',
      'download_url'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * File path and parent entity associations are set at upload time
     * and cannot be changed afterward.
     *
     * @var array<string>
     */
    public const CREATEONLY = [
      'file',
      'user_id',
      'project_id',
      'discussion_id',
      'task_id',
      'comment_id'
    ];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls.
     * All related entities are single objects (not collections).
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'project'    => false,
      'user'       => false,
      'task'       => false,
      'discussion' => false,
      'comment'    => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Image thumbnails are generated automatically for image file types.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'                 => 'integer',
      'created_on'         => 'datetime',
      'updated_on'         => 'datetime',
      'original_filename'  => 'text',
      'description'        => 'text',
      'user_id'            => 'resource:user',
      'project_id'         => 'resource:project',
      'discussion_id'      => 'resource:discussion',
      'task_id'            => 'resource:task',
      'comment_id'         => 'resource:comment',
      'token'              => 'text',
      'size'               => 'integer',
      'file'               => 'text',
      'image_thumb_small'  => 'url',
      'image_thumb_medium' => 'url',
      'image_thumb_large'  => 'url',
        // Undocumented Props
      'mime'               => 'text',
      'external_url'       => 'url',
      'external_service'   => 'text',
      'download_url'       => 'url',
      'tags'               => 'array'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for files.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];

    /**
     * Create a new file with validation for mutually exclusive parent entities.
     *
     * Files can only be attached to ONE parent entity (project, discussion,
     * task, or comment). This method validates that constraint and configures
     * the multipart upload settings before calling the parent create method.
     *
     * @param array $options Create options passed to parent method
     *
     * @throws Exception When multiple parent IDs are set (only 0 or 1 allowed)
     * @return AbstractResource The created file resource
     */
    public function create($options = []) : AbstractResource
    {
        $onlyOneList = ['project_id', 'discussion_id', 'task_id', 'comment_id'];
        $foundCount = 0;
        foreach ($onlyOneList as $limitKey) {
            if (isset($this->props[$limitKey])) {
                $foundCount++;
            }
        }
        if ($foundCount > 1) {
            throw new RuntimeException(
              "Only zero or one of the following fields (".implode(
                ', ',
                $onlyOneList
              ).") can be set to create a new file ($foundCount Found)."
            );
        }

        $options['dataMode'] = 'multipart';
        $options['uploadProps'] = ['file'];

        return parent::create($options);
    }

}
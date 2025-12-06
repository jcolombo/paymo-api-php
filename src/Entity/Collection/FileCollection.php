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
 * FILE COLLECTION - ATTACHED FILE RESOURCES
 * ======================================================================================
 *
 * This specialized collection class handles Paymo file entities. Files represent
 * uploaded attachments associated with various Paymo resources such as tasks,
 * projects, discussions, or comments.
 *
 * API FILTER REQUIREMENTS:
 * ------------------------
 * The Paymo API requires files to be fetched in the context of a parent resource.
 * You cannot fetch all files across the entire account - you must specify which
 * resource's files you want.
 *
 * REQUIRED FILTERS (at least one):
 * --------------------------------
 * - task_id: Files attached to a task
 * - project_id: Files attached to a project
 * - discussion_id: Files attached to a discussion
 * - comment_id: Files attached to a comment
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Entity\Resource\File;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Get all files attached to a task
 * $files = File::list($connection, [
 *     'where' => [
 *         RequestCondition::where('task_id', 12345),
 *     ]
 * ]);
 *
 * // Get files for a project
 * $projectFiles = File::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 67890),
 *     ]
 * ]);
 *
 * // Get files from a discussion
 * $discussionFiles = File::list($connection, [
 *     'where' => [
 *         RequestCondition::where('discussion_id', 11111),
 *     ]
 * ]);
 *
 * // Get files attached to a comment
 * $commentFiles = File::list($connection, [
 *     'where' => [
 *         RequestCondition::where('comment_id', 22222),
 *     ]
 * ]);
 *
 * // Iterate through files
 * foreach ($files as $file) {
 *     echo $file->original_filename . " (" . $file->size . " bytes)\n";
 *     echo "URL: " . $file->file . "\n";
 * }
 *
 * // This will throw an Exception - missing required filter!
 * $files = File::list($connection); // FAILS
 * ```
 *
 * FILE UPLOAD:
 * ------------
 * To upload files, use the File resource's create method with multipart mode:
 *
 * ```php
 * $file = new File();
 * $file->task_id = 12345;
 * $file->file = '/path/to/local/document.pdf';
 * $file->create($connection);
 * ```
 *
 * ERROR HANDLING:
 * ---------------
 * If no valid parent resource filter is provided, an Exception is thrown before
 * the API request is made, providing clear guidance on required filters.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Collection
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        EntityCollection Parent collection class
 * @see        \Jcolombo\PaymoApiPhp\Entity\Resource\File The file resource class
 * @see        RequestCondition For building filter conditions
 */

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Exception;
use RuntimeException;

/**
 * Specialized collection for Paymo file entities.
 *
 * Enforces Paymo API requirements for file list fetches, which require at least
 * one parent resource filter (task_id, project_id, discussion_id, or comment_id).
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Collection
 */
class FileCollection extends EntityCollection
{
    /**
     * Validate that required filter conditions are present before fetching.
     *
     * The Paymo API requires file list requests to include at least one parent
     * resource filter. Files must always be fetched in the context of their
     * parent resource (task, project, discussion, or comment).
     *
     * ACCEPTED FILTERS:
     * -----------------
     * - task_id: Fetch files attached to a task
     * - project_id: Fetch files attached to a project
     * - discussion_id: Fetch files attached to a discussion
     * - comment_id: Fetch files attached to a comment
     *
     * @param array $fields Optional fields parameter (passed to parent)
     * @param array $where  Array of RequestCondition objects to validate
     *
     * @throws Exception If no parent resource filter is found.
     *                   Message includes list of acceptable filter options.
     *
     * @return bool Returns true if validation passes (from parent)
     *
     * @see AbstractCollection::validateFetch() Parent validation method
     */
    protected function validateFetch($fields = [], $where = []) : bool
    {
        $needOne = ['task_id', 'project_id', 'discussion_id', 'comment_id'];
        $foundOne = false;
        foreach ($where as $w) {
            if (in_array($w->prop, $needOne, true)) {
                $foundOne = true;
                break;
            }
        }
        if (!$foundOne) {
            throw new RuntimeException(
              "File collections require one of the following be set as a filter : ".implode(
                ', ',
                $needOne
              )
            );
        }

        return parent::validateFetch($fields, $where);
    }
}
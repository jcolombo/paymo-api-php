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
 * COMMENT THREAD COLLECTION - DISCUSSION COMMENTS
 * ======================================================================================
 *
 * This specialized collection class handles Paymo comment thread entities. Comment
 * threads represent discussions attached to various Paymo resources like projects,
 * tasks, discussions, or files.
 *
 * API FILTER REQUIREMENTS:
 * ------------------------
 * The Paymo API requires comment threads to be fetched in the context of a parent
 * resource. You cannot fetch all comment threads globally - you must specify
 * which resource's comments you want.
 *
 * REQUIRED FILTERS (at least one):
 * --------------------------------
 * - project_id: Comments on a project
 * - task_id: Comments on a task
 * - discussion_id: Comments in a discussion thread
 * - file_id: Comments on a file
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Entity\Resource\CommentThread;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Get all comment threads for a task
 * $comments = CommentThread::list($connection, [
 *     'where' => [
 *         RequestCondition::where('task_id', 12345),
 *     ]
 * ]);
 *
 * // Get comments for a project
 * $projectComments = CommentThread::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 67890),
 *     ]
 * ]);
 *
 * // Get comments for a discussion
 * $discussionComments = CommentThread::list($connection, [
 *     'where' => [
 *         RequestCondition::where('discussion_id', 11111),
 *     ]
 * ]);
 *
 * // Get comments on a file
 * $fileComments = CommentThread::list($connection, [
 *     'where' => [
 *         RequestCondition::where('file_id', 22222),
 *     ]
 * ]);
 *
 * // Iterate through comments
 * foreach ($comments as $thread) {
 *     echo $thread->content . "\n";
 *     echo "By user: " . $thread->user_id . "\n";
 * }
 *
 * // This will throw an Exception - missing required filter!
 * $comments = CommentThread::list($connection); // FAILS
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
 * @see        \Jcolombo\PaymoApiPhp\Entity\Resource\CommentThread The comment thread resource
 * @see        RequestCondition For building filter conditions
 */

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Exception;
use RuntimeException;

/**
 * Specialized collection for Paymo comment thread entities.
 *
 * Enforces Paymo API requirements for comment thread list fetches, which require
 * at least one parent resource filter (project_id, task_id, discussion_id, or
 * file_id).
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Collection
 */
class CommentThreadCollection extends EntityCollection
{
    /**
     * Validate that required filter conditions are present before fetching.
     *
     * The Paymo API requires comment thread list requests to include at least
     * one parent resource filter. Comments must always be fetched in the context
     * of their parent resource (project, task, discussion, or file).
     *
     * ACCEPTED FILTERS:
     * -----------------
     * - project_id: Fetch comments attached to a project
     * - task_id: Fetch comments attached to a task
     * - discussion_id: Fetch comments in a discussion
     * - file_id: Fetch comments attached to a file
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
        $needOne = ['project_id', 'task_id', 'discussion_id', 'file_id'];
        $foundOne = false;
        foreach ($where as $w) {
            if (in_array($w->prop, $needOne, true)) {
                $foundOne = true;
                break;
            }
        }
        if (!$foundOne) {
            throw new RuntimeException(
              "Comment thread collections require at least one of the following be set as a filter : ".implode(
                ', ',
                $needOne
              )
            );
        }

        return parent::validateFetch($fields, $where);
    }
}
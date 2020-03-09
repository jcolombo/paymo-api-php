<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/9/20, 12:50 PM
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
 */

namespace Jcolombo\PaymoApiPhp\Cache;

/**
 * Class ScrubCache
 * Store compiled versions of pre-audited cache for scrubbed lists so that revalidation does not have to be rerun for
 * the same calls of included data (avoid re running class prop and include values saving loops and cycles used multiple
 * times in a single script call.
 *
 * @package Jcolombo\PaymoApiPhp\Cache
 */
class ScrubCache
{
    /**
     * Static storage for single instance of this class
     *
     * @var ScrubCache | null
     */
    public static $instance = null;

    /**
     * The in-memory storage array of the pre-cached validation of includes
     *
     * @var array
     */
    protected $scrubs = [];

    /**
     * Attempt to retrieve the static instance of this class or create it if its the first call
     *
     * @return ScrubCache|null
     * @todo : Allow for file based caching later (for now just stores scrub cache for a single PHP memory thread)
     */
    public static function cache()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Return the pre-scrubbed list of includes that was already stored from an earlier push call
     *
     * @param string   $entity        The entity being checked against for an original list of included data
     * @param string[] $original_list The list of include keys that are being checked for validation
     *
     * @return string[] | null
     */
    public function get($entity, $original_list)
    {
        $key = $this->key($entity, $original_list);
        if (isset($this->scrubs[$key])) {
            return $this->scrubs[$key];
        }

        return null;
    }

    /**
     * Generate a consistent MD5 key (used as the cache key index for the stored array)
     *
     * @param string   $entity The entity key being checked against
     * @param string[] $list   The list of include keys originally requested
     *
     * @return string
     */
    protected function key($entity, $list)
    {
        sort($list);

        return md5($entity.implode('|', $list));
    }

    /**
     * Create and store the scrubbed ($final_list) when a call is made to an original $entity plus $original_list
     *
     * @param string   $entity        The entity key being checked against
     * @param string[] $original_list The original list that was checked against
     * @param string[] $final_list    The final already scrubbed list that should be returned when the original list is
     *                                asked for with the get() call
     */
    public function push($entity, $original_list, $final_list)
    {
        $key = $this->key($entity, $original_list);
        $this->scrubs[$key] = $final_list;
    }

}
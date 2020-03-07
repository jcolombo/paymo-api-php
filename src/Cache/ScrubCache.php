<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/6/20, 11:45 PM
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

class ScrubCache
{
    static $instance = null;

    protected $scrubs = [];

    public static function cache() {
        if (is_null(self::$instance)) {
            self::$instance = new static();
            // @todo : Allow for file based caching later (for now just stores scrub cache for a single thread)
        }
        return self::$instance;
    }

    public function get($entity, $original_list) {
        $key = $this->key($entity, $original_list);
        if (isset($this->scrubs[$key])) {
            return $this->scrubs[$key];
        }
        return null;
    }

    public function push($entity, $original_list, $final_list) {
        $key = $this->key($entity, $original_list);
        $this->scrubs[$key] = $final_list;
    }

    protected function key($entity, $list) {
        sort($list);
        return md5($entity.implode('|', $list));
    }

}
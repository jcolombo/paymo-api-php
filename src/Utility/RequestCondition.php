<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/9/20, 12:09 AM
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

namespace Jcolombo\PaymoApiPhp\Utility;

use Exception;
use Jcolombo\PaymoApiPhp\Entity\AbstractEntity;
use Jcolombo\PaymoApiPhp\Entity\EntityMap;

/**
 * Class RequestCondition
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class RequestCondition
{
    public $type = 'where';
    /**
     * @var string
     */
    public $prop;
    /**
     * @var string
     */
    public $dataType = null;
    /**
     * @var mixed
     */
    public $value;
    /**
     * @var string
     */
    public $operator = '=';
    /**
     * @var bool
     */
    public $validate = true;

    /**
     * @param string $prop
     * @param mixed  $value
     * @param string $operator
     * @param bool   $validate
     *
     * @throws Exception
     * @return RequestCondition
     */
    public static function where($prop, $value, $operator = '=', $validate = true)
    {
        $isProp = AbstractEntity::isProp($prop);
        if ($validate) {
            if (!$isProp) {
                throw new Exception("Attempting to limit results on '{$prop}' which is not a valid prop");
            }
            $error = AbstractEntity::allowWhere($prop, $operator, $value);
            if ($error !== true) {
                throw new Exception($error);
            }
        }
        $w = new RequestCondition();
        $w->prop = $prop;
        $w->value = $value;
        $w->operator = $operator;
        $w->validate = $validate;

        return $w;
    }

    /**
     * @param        $include
     * @param int    $count
     * @param string $operator
     *
     * @throws Exception
     * @return RequestCondition
     */
    public static function has($include, $count = 0, $operator = '>')
    {
        // operators    INT) =, <, <=, >, >=, !=    ARRAY) >=<, =>=<=, =>=<, >=<=
        if (strpos($include, '.')) {
            [$key, $prop] = EntityMap::extractResourceProp($include);
            $isInclude = AbstractEntity::isIncludable($key, $prop);
            if (!$isInclude) {
                throw new Exception("Attempting to compare HAS results for '{$include}' on a non-included key");
            }
        }
        $w = new RequestCondition();
        $w->type = 'has';
        $w->prop = $include;
        $w->value = $count;
        $w->operator = $operator;

        return $w;
    }

    public static function filterHas($objects, $keys)
    {
        foreach ($objects as $i => $o) {
            $keepIt = true;
            foreach ($keys as $k => $deepKey) {
                if ($k === '_has') {
                    continue;
                }
                $cnt = 0;
                if (isset($o->$k) && is_array($o->$k)) {
                    $o->$k = static::filterHas($o->$k, $deepKey);
                    $cnt = count($o->$k);
                }
                $has = isset($deepKey['_has']) && count($deepKey['_has']) > 0 ? $deepKey['_has'] : [];
                foreach ($has as $h) {
                    $keepIt = RequestCondition::checkHas($cnt, $h['operator'], $h['value']);
                    if (!$keepIt) {
                        break;
                    }
                }
            }
            if (!$keepIt) {
                unset($objects[$i]);
            }
        }

        return $objects;
    }

    /**
     * @param $cnt
     * @param $operator
     * @param $amt
     *
     * @return bool
     */
    public static function checkHas($cnt, $operator, $amt)
    {
        switch ($operator) {
            case('='):
                return $cnt == $amt;
                break;
            case('>'):
                return $cnt > $amt;
                break;
            case('<'):
                return $cnt < $amt;
                break;
            case('>='):
                return $cnt >= $amt;
                break;
            case('<='):
                return $cnt <= $amt;
                break;
            case('!='):
                return $cnt != $amt;
                break;
            case('>.<'):
                return $cnt > $amt[0] && $cnt < $amt[1];
                break;
            case('=>.<='):
                return $cnt >= $amt[0] && $cnt <= $amt[1];
                break;
            case('=>.<'):
                return $cnt >= $amt[0] && $cnt < $amt[1];
                break;
            case('>.<='):
                return $cnt > $amt[0] && $cnt <= $amt[1];
                break;
            case('<|>'):
                return $cnt < $amt[0] || $cnt > $amt[1];
                break;
            case('<=|=>'):
                return $cnt <= $amt[0] || $cnt >= $amt[1];
                break;
            case('<|=>'):
                return $cnt < $amt[0] || $cnt >= $amt[1];
                break;
            case('<=|>'):
                return $cnt <= $amt[0] || $cnt > $amt[1];
                break;
        }

        return false;
    }
}
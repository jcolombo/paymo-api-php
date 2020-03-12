<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/12/20, 1:34 PM
 * .
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * .
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * .
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
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
use Jcolombo\PaymoApiPhp\Entity\EntityMap;

/**
 * Class RequestCondition
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class RequestCondition
{
    /**
     * All valid operators when making a "has" call to the static class
     */
    public const HAS_OPERATORS = [
        '=' => 'integer',
        '<' => 'integer',
        '<=' => 'integer',
        '>' => 'integer',
        '>=' => 'integer',
        '!=' => 'integer',
        '>=<' => 'integer[]',
        '=>=<=' => 'integer[]',
        '=>=<' => 'integer[]',
        '>=<=' => 'integer[]'
    ];

    /**
     * All valid operators when making a "where" call to the static class (imported from the AbstractEntity constant)
     */
    public const WHERE_OPERATORS = AbstractEntity::VALID_OPERATORS;

    /**
     * Stores the type of condition ("where" is sent to the API, "has" filters out results that dont meet a certain
     * include count threshold after results are returned.
     *
     * @var string Must be 'where' or 'has'
     */
    public $type = 'where';

    /**
     * The resource prop or include that is checked against the condition
     *
     * @var string
     */
    public $prop;

    /**
     * The scrubbed dataType of the prop from the extended AbstractEntity class constant (for validation)
     *
     * @var string
     */
    public $dataType = null;

    /**
     * The value to be used in the where or has condition checking
     *
     * @var mixed
     */
    public $value;

    /**
     * The operator to compare the value with against the prop / include
     *
     * @var string
     */
    public $operator = '=';

    /**
     * Should this condition do validation downstream to make sure all values are of the right types
     *
     * @var bool
     */
    public $validate = true;

    /**
     * Create a structured and validated WHERE condition to be passed to the API "where" call.
     * This method creates a validated check on the conditions to make sure they meet defined prop allowances
     * Not intended to set the $entityBase manually. The WHERE condition should be set using the specific resource
     * class
     * For example Project::where(..) instead of RequestCondition::where(...) But it can be done. Validation will only
     * work at the time of adding this condition if this parameter is set. If not, when the request compiles, if it
     * does
     * not meet the resources requirements, it will be stripped out silently.
     *
     * @param string        $prop       The entity collection property being filtered on
     * @param mixed         $value      The value to be sent to the where condition (must match datatype expected for
     *                                  the prop)
     * @param string        $operator   The operator to use in comparison, must meet allowed operators for the prop
     *                                  definition
     * @param bool          $validate   True or false if the prop type should be checked or not. Valid operators are
     *                                  ALWAYS checked but they wont be checked against the specific prop allowance if
     *                                  this is false
     * @param string | null $entityBase The entity for the root type of entity being filtered on
     *
     * @throws Exception
     * @return RequestCondition An instance of the populated (and potentially validated) where condition to be used by
     *                          the Request object when compiling the WHERE condition for lists
     */
    public static function where($prop, $value, $operator = '=', $validate = true, $entityBase = null)
    {
        if (!in_array($operator, static::WHERE_OPERATORS)) {
            throw new Exception("Invalid operator '{$operator}' sent for {$prop}. Must be one of ".implode(', ',
                                                                                                           static::WHERE_OPERATORS));
        }
        if (in_array($operator, ['in', 'not in', 'range']) && !is_array($value)) {
            if ($operator === 'range') {
                throw new Exception('Range operator requires a valid array value passed');
            }
            $value = [$value];
        }
        if (!is_null($entityBase) && $validate) {
            /** @var AbstractResource $resource Just the class name of the resource type, labeled here for IDE static call below */
            $resource = EntityMap::resource($entityBase);
            if (!$resource) {
                throw new Exception("No class is defined for entity resource '{$entityBase}'");
            }
            $pts = explode('.', $prop);
            $isProp = AbstractEntity::isProp($entityBase, $pts[0]);
            if (!$isProp) {
                $isInclude = AbstractEntity::isIncludable($entityBase, $pts[0]);
                if (!$isInclude) {
                    throw new Exception("Attempting to limit '{$entityBase}' relation results on '{$prop}' which is not a valid include relation");
                } elseif(!$isProp && !$isInclude) {
                    throw new Exception("Attempting to limit '{$entityBase}' results on '{$prop}' which is not a valid prop");
                }
            }
            $allowProp = strpos($prop, '.') === false ? $entityBase.'.'.$prop : $prop;
            $error = $resource::allowWhere($allowProp, $operator, $value);
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
     * Create a HAS condition on a list that will only return results that meet deeper included conditions. This is
     * post
     * processed on the results after the API returns them.
     * EX: Selecting all clients and their project lists... but eliminate any clients from the list if their project
     * list comes back with ZERO projects included... ::has('projects', 0, '>') EX2: Select all clients with projects
     * <= 5 OR projects > 10... ::has('projects', [5,10], '<=|>') EX3: Select all clients with exactly 5 to 10
     * projects... ::has('projects', [5,10], '>=<') This can go deeper and cascade as well... A.B > 5... cascades from
     * deepest down (an additional deeper condition could eliminate an item from a parent which then itself is being
     * checked and it then is eliminated with a second rule. etc. Rules always eliminate from the deepest point then up
     * until it reaches the root elements.
     *
     * @param string      $include    The "include" parameter of a list of items that will have their "count" checked
     *                                against
     * @param int | int[] $count      The count to use in the comparison operator evaluation
     * @param string      $operator   The operator to use when checking the count. Different operators require either a
     *                                single integer or an array of 2 integers (for range based checks)
     * @param null        $baseEntity The optional base entity code for eventual validation of allowed includes
     *
     * @throws Exception
     * @return RequestCondition An instance of the populated and validated has condition to apply to the successful
     *                          results of an API call
     * @todo Wire this into deep resource include checks. For now this is only applied to list searches
     */
    public static function has($include, $count = 0, $operator = '>', $baseEntity = null)
    {
        if (!isset(static::HAS_OPERATORS[$operator])) {
            throw new Exception("Invalid operator '{$operator}' sent for {$include}. Must be one of ".implode(', ',
                                                                                                              array_keys(static::HAS_OPERATORS)));
        }
        if (static::HAS_OPERATORS[$operator] === 'integer[]' && (!is_array($count) || count($count) != 2)) {
            throw new Exception("Operator '{$operator}' requires a count parameter that is a 2 element array of integers.");
        }
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

    /**
     * Run the proper comparison of the specific operator
     *
     * @param int         $cnt      The quantity count to be checked against the $amt required
     * @param string      $operator The operator to use in the comparison check
     * @param int | int[] $amt      The values to use in the formula (single in for some, array of 2 ints for others)
     *
     * @return bool True if the $cnt passes the restrictions check of the $operator/$amt formula
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
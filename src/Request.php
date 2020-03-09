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

namespace Jcolombo\PaymoApiPhp;

use Adbar\Dot;
use GuzzleHttp\Exception\GuzzleException;
use Jcolombo\PaymoApiPhp\Entity\AbstractEntity;
use Jcolombo\PaymoApiPhp\Utility\RequestAbstraction;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
use Jcolombo\PaymoApiPhp\Utility\RequestResponse;
use stdClass;

/**
 * Static class for generating proper request objects to be sent in to connection instances for executing
 *
 * @package Jcolombo\PaymoApiPhp
 */
class Request
{

    /**
     * Compile and execute a single entity fetch request from the API
     *
     * @param Paymo  $connection A valid Paymo Connection object instance
     * @param string $objectKey  The API path tacked on to connections base URL
     * @param int    $id         The ID to be loaded from the API at the path sent in as $objectKey
     * @param array  $options    The set of options for this request
     *                           [select] = string[] : The list of properties to select for this request
     *                           [include] = string[] : The list of related entities and their respective properties to
     *                           request
     *                           [scrub] = bool : Manually process the result through the clean up utility to strip any
     *                           [where] = RequestCondition[] : A set of request conditions for filtering lists
     *                           excess response properties (in case API response added more than was requested)
     *
     * @throws GuzzleException
     * @return RequestResponse Returns an object on success or a boolean FALSE on failure to load entity
     */
    public static function fetch(Paymo $connection, $objectKey, $id, $options)
    {
        $scrub = !!$options['scrub'];
        $select = $options['select'] ?? [];
        $include = $options['include'] ?? [];
        if (!is_array($select)) {
            $select = !is_null($select) ? [$select] : [];
        }
        if (!is_array($include)) {
            $include = !is_null($include) ? [$include] : [];
        }
        $request = new RequestAbstraction();
        $request->method = 'GET';
        $request->resourceUrl = $objectKey."/{$id}";
        $request->include = Request::compileIncludeParameter(array_merge($select, $include));
        $response = $connection->execute($request);
        if ($response->body && $response->validBody($objectKey, 1)) {
            $response->result = $scrub ? self::scrubBody($response->body->$objectKey[0], $select,
                                                         $include) : $response->body->$objectKey[0];
        }

        return $response;
    }

    /**
     * Combine any "include" parameters into a single comma joined string value for the query string
     * All includes are sorted so that the caching key will be generated correctly regardless of the
     * order the include parameters were passed to the request
     *
     * @param string[] $include An array of string include entities and entity props
     *
     * @return string | null The combined include prop or NULL if no includes were in the passed array
     */
    public static function compileIncludeParameter($include)
    {
        if (!$include || !is_array($include) || count($include) < 1) {
            return null;
        }
        sort(array_unique($include));

        return join(',', $include);
    }

    /**
     * @param                    $objects
     * @param                    $select
     * @param                    $include
     * @param RequestCondition[] $where
     *
     * @return array|mixed
     */
    public static function scrubBody($objects, $select, $include)
    {
        $isList = is_array($objects);
        if ($isList) {
            $objList = $objects;
        } else {
            $objList = [$objects];
        }
        $includedEntities = [];
        foreach ($include as $i) {
            $incEntity = explode('.', $i)[0];
            if (!in_array($incEntity, $includedEntities)) {
                $includedEntities[] = $incEntity;
            }
        }
        $validProps = array_merge($select, $includedEntities);
        $selectAll = count($select) === 0;
        if (!$selectAll) {
            foreach ($objList as $index => $e) {
                foreach ($e as $k => $v) {
                    if (!($k === 'id' || in_array($k, $validProps))) {
                        unset($objList[$index]->$k);
                    }
                }
            }
        }

        return $isList ? $objList : $objList[0];
    }

    public static function list(Paymo $connection, $objectKey, $options)
    {
        $scrub = !!$options['scrub'];
        $select = $options['select'] ?? [];
        $include = $options['include'] ?? [];
        $where = $options['where'] ?? [];
        if (!is_array($select)) {
            $select = !is_null($select) ? [$select] : [];
        }
        if (!is_array($include)) {
            $include = !is_null($include) ? [$include] : [];
        }
        $request = new RequestAbstraction();
        $request->method = 'GET';
        $request->resourceUrl = $objectKey;
        $request->include = Request::compileIncludeParameter(array_merge($select, $include));
        $request->where = Request::compileWhereParameter($where);

        //var_dump($where);
        //var_dump($request); //exit;

        $response = $connection->execute($request);

        if ($response->body && $response->validBody($objectKey, 0)) {
            $response->body->$objectKey = self::postResponseFilter($response->body->$objectKey, $where);
            $response->result = $scrub ? self::scrubBody($response->body->$objectKey, $select,
                                                         $include) : $response->body->$objectKey;
        }

//        echo "\n\nRESPONSE DUMP...\n";
//        var_dump($response);
//        exit;

        return $response;
    }

    /**
     * @param RequestCondition[] $where A collection of where conditions to apply to the request
     *
     * @return string | null
     */
    public static function compileWhereParameter($where)
    {
        if (!$where || !is_array($where) || count($where) < 1) {
            return null;
        }
        $conditions = [];
        foreach ($where as $w) {
            if ($w->type === 'where') {
                $filter = Request::convertOperatorValue($w);
                if (!is_null($filter)) {
                    $conditions[] = $filter;
                }
            }
        }
        sort($conditions);

        return join(' and ', $conditions);
    }

    public static function convertOperatorValue(RequestCondition $w)
    {
        //var_dump($w);
        $value = self::convertValueForFilter($w->dataType, $w->value);
        $ops = AbstractEntity::VALID_OPERATORS;
        $operator = $w->operator;
        if (in_array($operator, $ops)) {
            switch ($operator) {
                case('range'):
                    $capOp = $value[2] ? '<=' : '<';

                    return "{$w->prop}>={$value[0]} and {$w->prop}{$capOp}{$value[1]}";
                    break;
                case('in'):
                case('not in'):
                    return "{$w->prop} {$operator} ({$value})";
                    break;
                case('like'):
                case('not like'):
                    return "{$w->prop} {$operator} \"{$value}\"";
                    break;
                case('='):
                case('<='):
                case('<'):
                case('>='):
                case('>'):
                case('!='):
                default:
                    return "{$w->prop}{$operator}{$value}";
                    break;
            }
        }

        return null;
    }


    // DataTypes:
    // text, integer, resource:*, collection:*, boolean, datetime, email, url, decimal, array

    public static function convertValueForFilter($type, $value)
    {
        if (strpos($type, 'resource:') !== false) {
            $type = 'integer';
        } elseif (strpos($type, 'collection:') !== false) {
            $type = 'integer';
        }
        switch ($type) {
            case('datetime'):
                $cast = is_array($value) ? 'timestamp[]' : 'timestamp';
                break;
            case('array'):
            case('integer'):
                $cast = is_array($value) ? 'integer[]' : 'integer';
                break;
            case('double'):
            case('decimal'):
                $cast = is_array($value) ? 'double[]' : 'double';
                break;
            case('boolean'):
                $cast = 'boolean';
                break;
            case('email'):
            case('url'):
            case('text'):
            default:
                $cast = is_array($value) ? 'string[]' : 'string';
                break;
        }

        switch ($cast) {
            case('timestamp'):
                $value = is_int($value) ? $value : strtotime((string) $value);
                break;
            case('timestamp[]'):
                $value = [
                    is_int($value[0]) ? $value : strtotime((string) $value[0]),
                    is_int($value[1]) ? $value : strtotime((string) $value[1]),
                    isset($value[2]) && is_bool($value[2]) ? $value[2] : false
                ];
                break;
            case('double[]'):
                array_walk($value, function (&$i) { $i = (double) $i; });
                $value = implode(',', $value);
                break;
            case('integer[]'):
                array_walk($value, function (&$i) { $i = (int) $i; });
                $value = implode(',', $value);
                break;
            case('string[]'):
                array_walk($value, function (&$i) { $i = (string) $i; });
                $value = '"'.implode('","', $value).'"';
                break;
            case('boolean'):
                $value = $value !== false && $value !== 'false' ? 'true' : 'false';
                break;
            case('double'):
                $value = (double) $value;
                break;
            case('integer'):
                $value = (int) $value;
                break;
            case('string'):
            default:
                $value = (string) $value;
                break;
        }

        return $value;
    }

    /**
     * @param stdClass[]         $objects Array of objects returned in the body of the API response
     * @param RequestCondition[] $where   The send of where conditions added to the original request
     *
     * @return stdClass | stdClass[]
     */
    public static function postResponseFilter($objects, $where)
    {
        //var_dump($where); exit; // operators    INT) =, <, <=, >, >=, !=    ARRAY) >=<, =>=<=, =>=<, >=<=
        //$demo = ['projects', 'clients.something.extra', 'clients.something', 'filler.beta'];

        $newObjects = null;
        if (count($where) > 0) {
            $hasFilter = new Dot();
            foreach ($where as $d) {
                if ($d->type === 'has') {
                    $value = $hasFilter->get($d->prop.'._has', []);
                    $op = ['operator' => $d->operator, 'value' => $d->value];
                    $value[] = $op;
                    $hasFilter->set($d->prop.'._has', $value);
                }
            }
            if (count($hasFilter) > 0) {
                $newObjects = RequestCondition::filterHas($objects, $hasFilter->all());
            }
        }

        return $newObjects ?? $objects;
    }

    // Casts: integer, integer[], string, string[], range[], double, float[]
    // text, integer, resource:*, collection:*, boolean, datetime, email, url, decimal, array

    public static function getPrimitiveType($type)
    {
        if (strpos($type, 'resource:') !== false) {
            $type = 'integer';
        } elseif (strpos($type, 'collection:') !== false) {
            $type = 'integer';
        }
        switch ($type) {
            case('datetime'):
                $cast = 'timestamp';
                break;
            case('array'):
            case('integer'):
                $cast = 'integer';
                break;
            case('double'):
            case('decimal'):
                $cast = 'double';
                break;
            case('boolean'):
                $cast = 'boolean';
                break;
            case('email'):
            case('url'):
            case('text'):
            default:
                $cast = 'string';
                break;
        }

        return $cast;
    }

}
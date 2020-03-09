<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/9/20, 6:20 PM
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

namespace Jcolombo\PaymoApiPhp;

use Adbar\Dot;
use Braintree\Exception;
use GuzzleHttp\Exception\GuzzleException;
use Jcolombo\PaymoApiPhp\Utility\Converter;
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
     * Manually clean up the body response from the API to scrub out any excess properties or values that were not
     * expected This will map back to the resource property and include definitions as well as the requested
     * select/include needs for this request. This would mostly come in useful when you need to make sure ONLY specific
     * props or includes are set and the API has updated to send back more than you were expecting
     *
     * @param stdClass | stdClass[] $objects The JSON body object or array of objects from the API remote / cache call
     * @param string[]              $select  The list of properties expected on the root resource objects requested
     * @param string[]              $include The list of included related objects or object lists expected from your
     *                                       request
     *
     * @return stdClass[] | stdClass The manually scrubbed version of the originally passed in $objects property
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

    /**
     * Create a new object of $objectKey in the Paymo API
     *
     * @param Paymo  $connection A valid Paymo Connection object instance
     * @param string $objectKey  The API path tacked on to connections base URL
     * @param array  $data       The raw data to create the new entry with
     *
     * @throws GuzzleException
     * @return RequestResponse
     */
    public static function create(Paymo $connection, $objectKey, $data)
    {
        $request = new RequestAbstraction();
        $request->method = 'POST';
        $request->resourceUrl = $objectKey;
        $request->data = $data;
        $response = $connection->execute($request);
        if ($response->body && $response->validBody($objectKey, 1)) {
            $response->result = $response->body->$objectKey[0];
        }

        //var_dump($response); exit;

        return $response;
    }

    /**
     * Delete an object of $objectKey with $id in the Paymo API
     * WARNING: THIS IS NOT REVERSIBLE. Be SURE the ID matches the one you wish to delete. There is NO confirmation.
     *
     * @param Paymo  $connection A valid Paymo Connection object instance
     * @param string $objectKey  The API path tacked on to connections base URL
     * @param int    $id         The ID of the entity to delete
     *
     * @throws GuzzleException
     * @throws Exception
     * @return RequestResponse
     */
    public static function delete(Paymo $connection, $objectKey, $id)
    {
        if ((int) $id < 1) {
            throw new Exception("Attempting to delete a resource without a integer ID");
        }
        $request = new RequestAbstraction();
        $request->method = 'DELETE';
        $request->resourceUrl = $objectKey.'/'.$id;

        return $connection->execute($request);
    }

    /**
     * Compile and run an expected list of multiple items of one resource type from the remote API
     *
     * @param Paymo  $connection A valid Paymo Connection object instance
     * @param string $objectKey  The resource path to attach to the end of the base URL for the request
     * @param array  $options    {@see fetch}
     *
     * @throws GuzzleException
     * @return RequestResponse Returns an object on success or a boolean FALSE on failure to load list of entities
     */
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
     * Compile the set of WHERE conditions sent for the request into a single string value to be added to the remote
     * query
     *
     * @param RequestCondition[] $where A collection of where conditions to apply to the request
     *
     * @return string | null The api ready WHERE value for the remote request ?where=
     */
    public static function compileWhereParameter($where)
    {
        if (!$where || !is_array($where) || count($where) < 1) {
            return null;
        }
        $conditions = [];
        foreach ($where as $w) {
            if ($w->type === 'where') {
                $filter = Converter::convertOperatorValue($w);
                if (!is_null($filter)) {
                    $conditions[] = $filter;
                }
            }
        }
        sort($conditions);

        return join(' and ', $conditions);
    }

    /**
     * Filter the list of returned objects if the WHERE parameter had any "HAS" conditions
     * After the response comes back, use the rules sent with type->has to eliminate any items (and sub-items) that do
     * not contain the required number of results for its list
     *
     * @param stdClass | stdClass[] $objects Array of objects returned in the body of the API response
     * @param RequestCondition[]    $where   The send of where conditions added to the original request
     *
     * @return stdClass | stdClass[] A scrubbed response of the original $objects parameter filtered by any "has"
     *                  conditions
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
                $newObjects = static::filterHas($objects, $hasFilter->all());
            }
        }

        return $newObjects ?? $objects;
    }

    /**
     * Recursively check the list of objects against the multidimensional list of HAS keys valid for this request
     * This method is only called by the postResponseFilter method or itself (recursively)
     *
     * @param stdClass[] $objects An array of stdClass generic objects to be filtered against (raw JSON body from API
     *                            call)
     * @param array      $keys    The Dot() formatted multi-dimensional array of valid "has" keys
     *
     * @return stdClass[] Returned a scrubbed list of $objects
     */
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

}
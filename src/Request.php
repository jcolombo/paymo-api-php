<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/6/20, 12:11 PM
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

use GuzzleHttp\Exception\GuzzleException;
use Jcolombo\PaymoApiPhp\Utility\RequestAbstraction;

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
     * @param Paymo    $connection A valid Paymo Connection object instance
     * @param string   $objectKey  The API path tacked on to connections base URL
     * @param int      $id         The ID to be loaded from the API at the path sent in as $objectKey
     * @param string[] $select     An array of valid props to filter the response with before sending it back
     * @param string[] $include    An array of valid include entities and sub-entity props to return with base object
     *
     * @throws GuzzleException
     * @return bool | object Returns an object on success or a boolean FALSE on failure to load entity
     */
    public static function fetch(Paymo $connection, $objectKey, $id, $select, $include)
    {
        if (!is_array($select)) {
            $select = !is_null($select) ? [$select] : [];
        }
        if (!is_array($include)) {
            $include = !is_null($include) ? [$include] : [];
        }

        $request = new RequestAbstraction();
        $request->method = 'GET';
        $request->resourceUrl = $objectKey."/{$id}";
        $request->includeEntities = Request::compileIncludeParameter($include);
        $response = $connection->execute($request);

        if ($response->validBody($objectKey, 1)) {
            return self::scrubBody($response->body->$objectKey[0], $select, $include);
        }

        return false;
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
        sort($include);

        return join(',', $include);
    }

    /**
     * @param $objects
     * @param $select
     * @param $include
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

    /**
     * @param Paymo $connection
     * @param       $objectKey
     * @param       $select
     * @param       $include
     * @param       $where
     */
    public static function list(Paymo $connection, $objectKey, $select, $include, $where)
    {
    }

}
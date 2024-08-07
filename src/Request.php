<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 10:48 PM
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
use Exception;
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
     *                           [skipCache] = boolean : If set to true, will NEVER check cache and force API call
     *
     * @throws Exception
     * @return RequestResponse Returns an object on success or a boolean FALSE on failure to load entity
     */
    public static function fetch(Paymo $connection, $objectKey, $id, $options)
    {
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
        $checkId = $id === -1 ? false : true;
        if ($checkId && (int) $id < 1) {
            throw new Exception("Attempting to fetch a resource without an integer ID");
        }
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
        $request->resourceUrl = $id > 0 ? $pathKey."/{$id}" : $pathKey;
        $request->include = Request::compileIncludeParameter(array_merge($select, $include));
        $skipCache = isset($options['skipCache']) && !!$options['skipCache'];
        $response = $connection->execute($request, ['skipCache'=>$skipCache]);
        if ($response->body && $response->validBody($responseKey, 1)) {
            $object = is_array($response->body->$responseKey) ? $response->body->$responseKey[0] : $response->body->$responseKey;
            $response->result = $scrub ? self::scrubBody($object, $select, $include) : $object;
        }

        return $response;
    }

    /**
     * Explode the object keys to check if there is an alternative response and path key
     *
     * @param string $key A possible object API path key. If it contains a ":" it has an alternative response path
     *
     * @return string[] Returns a two element array with the API path first and the object response path second
     */
    public static function getObjectReponseKeys($key)
    {
        $parts = explode(':', $key);
        $pathKey = $responseKey = $key;
        if (count($parts) == 2) {
            $pathKey = $parts[0];
            $responseKey = $parts[1];
        }

        return [$pathKey, $responseKey];
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
      $array_unique = array_unique($include);
      sort($array_unique);

        return join(',', $array_unique);
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
     * @param array  $uploads    An associative array of files to be uploaded (key => filepath)
     * @param string $mode       Either json or multipart to tell the request how to upload the data
     *
     * @return RequestResponse
     */
    public static function create(Paymo $connection, $objectKey, $data, $uploads = [], $mode = 'json')
    {
        $useMode = $mode === 'multipart' ? $mode : 'json';
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
        $request = new RequestAbstraction();
        $request->method = 'POST';
        $request->resourceUrl = $pathKey;
        $request->data = $data;
        if ($uploads && is_array($uploads) && count($uploads) > 0) {
            $request->mode = 'multipart';
            $request->files = $uploads;
        } else {
            $request->mode = $useMode;
        }
        $response = $connection->execute($request);
        if ($response->body && $response->validBody($responseKey, 1)) {
            $response->result = $response->body->$responseKey[0];
        }

        //var_dump($response); exit;

        return $response;
    }

    /**
     * Update an existing entity with ID patching just the modified data
     *
     * @param Paymo  $connection A valid Paymo Connection object instance
     * @param string $objectKey  The API path tacked on to connections base URL
     * @param int    $id         The ID of the resource to be updated
     * @param array  $data       The raw data to update the entity with ID
     *
     * @throws Exception
     * @return RequestResponse
     */
    public static function update(Paymo $connection, $objectKey, $id, $data)
    {
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
        $checkId = $id === -1 ? false : true;
        if ($checkId && (int) $id < 1) {
            throw new Exception("Attempting to update a resource without an integer ID");
        }
        $request = new RequestAbstraction();
        $request->method = 'PUT';
        $request->resourceUrl = $id > 0 ? $pathKey.'/'.$id : $pathKey;
        $request->data = $data;
        $response = $connection->execute($request);
        if ($response->body && $response->validBody($responseKey, 1)) {
            $response->result = is_array($response->body->$responseKey) ? $response->body->$responseKey[0] : $response->body->$responseKey;
        }

        return $response;
    }

    /**
     * @param Paymo  $connection A valid Paymo Connection object instance
     * @param string $objectKey  The API path tacked on to connections base URL
     * @param int    $id         The ID of the resource to attach the file to
     * @param string $prop       The property on the resource to attach the file to
     * @param string $filepath   An existing full file path that can be read by PHP on the filesystem
     *
     * @throws Exception
     * @return RequestResponse
     */
    public static function upload(Paymo $connection, $objectKey, $id, $prop, $filepath)
    {
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
        $checkId = $id === -1 ? false : true;
        if ($checkId && (int) $id < 1) {
            throw new Exception("Attempting to upload a file without an integer ID");
        }
        $request = new RequestAbstraction();
        $request->method = 'POST';
        $request->resourceUrl = $id > 0 ? $pathKey.'/'.$id : $pathKey;
        $request->files = [$prop => $filepath];
        $response = $connection->execute($request);
        if ($response->body && $response->validBody($responseKey, 1)) {
            $response->result = is_array($response->body->$responseKey) ? $response->body->$responseKey[0] : $response->body->$responseKey;
        }

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
     * @throws Exception
     * @return RequestResponse
     */
    public static function delete(Paymo $connection, $objectKey, $id)
    {
        if ((int) $id < 1) {
            throw new Exception("Attempting to delete a resource without a integer ID");
        }
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
        $request = new RequestAbstraction();
        $request->method = 'DELETE';
        $request->resourceUrl = $pathKey.'/'.$id;

        return $connection->execute($request);
    }

    /**
     * Compile and run an expected list of multiple items of one resource type from the remote API
     *
     * @param Paymo  $connection A valid Paymo Connection object instance
     * @param string $objectKey  The resource path to attach to the end of the base URL for the request
     * @param array  $options    {@see fetch}
     *
     * @return RequestResponse Returns an object on success or a boolean FALSE on failure to load list of entities
     */
    public static function list(Paymo $connection, $objectKey, $options)
    {
        [$pathKey, $responseKey] = static::getObjectReponseKeys($objectKey);
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
        $request->resourceUrl = $pathKey;
        $request->include = Request::compileIncludeParameter(array_merge($select, $include));
        $request->where = Request::compileWhereParameter($where);

        //var_dump($where);
        //var_dump($request); //exit;

        // Options
        $skipCache = isset($options['skipCache']) && !!$options['skipCache'];

        $response = $connection->execute($request, ['skipCache'=>$skipCache]);

        if ($response->body && $response->validBody($responseKey, 0)) {
            $response->body->$responseKey = self::postResponseFilter($response->body->$responseKey, $where);
            $response->result = $scrub ? self::scrubBody($response->body->$responseKey, $select,
                                                         $include) : $response->body->$responseKey;
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
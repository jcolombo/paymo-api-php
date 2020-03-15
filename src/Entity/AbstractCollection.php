<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/15/20, 1:42 PM
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

namespace Jcolombo\PaymoApiPhp\Entity;

use ArrayAccess;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Iterator;
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Request;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
use stdClass;

/**
 * Class AbstractCollection
 *
 * @package Jcolombo\PaymoApiPhp\Entity
 */
abstract class AbstractCollection extends AbstractEntity implements Iterator, ArrayAccess
{
    /**
     * The resource key used for populating the results with
     *
     * @var string|null
     */
    protected $entityKey = null;

    /**
     * The actual class name of the $entityKey for use in static method calls and validation
     *
     * @var string|null
     */
    protected $entityClass = null;

    /**
     * The collection class for the entityKey
     *
     * @var string|null
     */
    protected $collectionClass = null;

    /**
     * The internal pointer for the $data array index (for Array features)
     *
     * @var int
     */
    private $index = 0;

    /**
     * The storage variable for the array of results populated with instances of the $entityClass
     *
     * @var AbstractResource[]
     */
    private $data = [];

    /**
     * EntityCollection constructor.
     *
     * @param string                        $entityKey The entity key from the entityMap to indicate what type of
     *                                                 resources are contained
     * @param array | Paymo | string | null $paymo     Either an API Key, Paymo Connection, config settings array (from
     *                                                 another entitied getConfiguration call), or null to get first
     *                                                 connection available
     *
     * @throws Exception
     */
    public function __construct($entityKey, $paymo = null)
    {
        parent::__construct($paymo);
        $this->entityKey = $entityKey;
        $this->entityClass = EntityMap::resource($entityKey);
        $this->collectionClass = EntityMap::collection($entityKey);
        $this->index = 0;
        $this->data = [];
    }

    /**
     * Static method to always create a resource or collection using the currently configured mapped class in Entity
     * Map
     * NOTE: Using this method to factory create your class will void IDE typehinting when developing (as it doesnt
     * know what class will return)
     *
     * @param array | Paymo | string | null $paymo Either an API Key,
     *                                             Paymo Connection, config settings array (from another entitied
     *                                             getConfiguration call), or null to get first connection available
     *
     * @throws Exception
     * @return AbstractCollection
     */
    public static function new($paymo = null)
    {
        return parent::newCollection($paymo);
    }

    /**
     * Fetch the list of a specific resource with requested fields, includes, and conditional limits
     *
     * @param string[]           $fields A list of props and includes to return from the API call. An empty[] simply
     *                                   returns all props for the list of base resources
     * @param RequestCondition[] $where  Optional set of conditions to limit the result set to (via API where or post
     *                                   processing HAS clauses)
     *
     * @throws GuzzleException
     * @throws Exception
     * @return AbstractCollection $this Returns itself for chaining methods. The object will be populated with the
     *                            collection of resources before returning itself
     */
    public function fetch($fields = [], $where = [])
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        if (!is_array($where)) {
            $where = [$where];
        }
        $this->validateFetch($fields, $where);
        /** @var AbstractResource $resClass */
        $resClass = $this->entityClass;
        if (!$this->overwriteDirtyWithRequests && $this->isDirty()) {
            $label = $resClass::LABEL;
            throw new Exception("{$label} attempted to fetch new data while it had dirty entities and protection is enabled.");
        }
        [$select, $include, $where] = static::cleanupForRequest($resClass::API_ENTITY, $fields, $where);
        $response = Request::list($this->connection, $resClass::API_PATH,
                                  ['select' => $select, 'include' => $include, 'where' => $where]);
        if ($response->result) {
            $this->_hydrate($response->result);
            // @todo Populate a response summary of data on the object (like if it came from live, timestamp of request, timestamp of data retrieved/cache, etc
        }

        return $this;
    }

    /**
     * Check if the collection has any dirty entries or new entries that need to be created still
     *
     * @return bool
     */
    public function isDirty()
    {
        // @todo Check the collection for any dirty entities
        return false;
    }

    /**
     * Populate the list with instances of the specific resource type for this collection.
     *
     * @param stdClass[] $data An array of standard objects to use in populating the list (cascade hydrates each object
     *                         into its specific resource type)
     *
     * @throws Exception
     */
    public function _hydrate($data)
    {
        /** @var AbstractResource $resClass */
        $resClass = $this->entityClass;
        if (is_array($data)) {
            $this->clear();
            $this->hydrationMode = true;
            foreach ($data as $o) {
                /** @var AbstractResource $tmp */
                $tmp = new $resClass($this->getConfiguration());
                $tmp->_hydrate($o, $o->id);
                $this->data[$o->id] = $tmp;
            }
            $this->hydrationMode = false;
        }
    }

    /**
     * Wipe out the data in this collection reset to an empty array list of resources
     */
    public function clear()
    {
        $this->data = [];
    }

    public function sort($sortBy = [])
    {
        // @todo Add a sort by system similar to the WHERE calls that will post-process sort the list
        // Resource::sort('prop', 'direction=ASC') calls to CollectionSort::sort(...)
        // Add new constant SORTABLE_ON = []. If key not defined, allow. If null, not allowed.
        //     If string, call collection method.
    }

    /**
     * Return an array of stdClass objects that are also flattened using the same options cascaded to each
     *
     * @param array $options {@see AbstractResource::flatten()}
     *
     * @return stdClass[]
     */
    public function flatten($options = [])
    {
        $data = [];
        foreach ($this->data as $k => $resource) {
            $data[(int) $k] = $resource->flatten($options);
        }

        return $data;
    }

    /**
     * Reset the pointer to index zero
     *
     * @inheritDoc
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * Get the current item at the set index
     *
     * @inheritDoc
     * @return mixed
     */
    public function current()
    {
        return $this->data[$this->index];
    }

    /**
     * Return the index of the pointer in the data array
     *
     * @inheritDoc
     * @return int
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * Increment the index of the data array
     *
     * @inheritDoc
     */
    public function next()
    {
        ++$this->index;
    }

    /**
     * Check if the current index has a valid value set
     *
     * @inheritDoc
     * @return bool
     */
    public function valid()
    {
        return isset($this->data[$this->index]);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } elseif (is_int($offset)) {
            $this->data[$offset] = $value;
        } else {
            throw new Exception("Attempting to set non-numeric index on EntityCollection data set");
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Return the direct array of resources stored in the $data array (Breaks the value out of the collection wrapper
     * class)
     *
     * @return AbstractResource[]
     */
    public function raw()
    {
        return $this->data;
    }

}
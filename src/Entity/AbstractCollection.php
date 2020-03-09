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

namespace Jcolombo\PaymoApiPhp\Entity;

use ArrayAccess;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Iterator;
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Request;

abstract class AbstractCollection extends AbstractEntity implements Iterator, ArrayAccess
{
    protected $entityKey = null;
    /**
     * @var string|null
     */
    protected $entityClass = null;
    /**
     * @var string|null
     */
    protected $collectionClass = null;
    /**
     * @var int
     */
    private $index = 0;
    /**
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
     * @param null $paymo                          * @param array | Paymo | string | null $paymo Either an API Key,
     *                                             Paymo Connection, config settings array (from another entitied
     *                                             getConfiguration call), or null to get first connection available
     *
     * @throws Exception
     * @return AbstractCollection
     */
    public static function new($paymo = null)
    {
        return parent::new($paymo);
    }

    /**
     * @param array $fields
     * @param array $where
     * @param bool  $validate
     *
     * @throws GuzzleException
     * @throws Exception
     * @return $this
     */
    public function fetch($fields = [], $where = [], $validate = true)
    {
        /** @var AbstractResource $resClass */
        $resClass = $this->entityClass;
        /** @var AbstractCollection $resClass */
        $colClass = $this->collectionClass;
        echo "FETCH LIST HERE\n\n";

        //$label = $resClass::LABEL;
        if (!$this->overwriteDirtyWithRequests && $this->isDirty()) {
            $label = $resClass::LABEL;
            throw new Exception("{$label} attempted to fetch new data while it had dirty entities and protection is enabled.");
        }
        //$s = microtime(true);
        [$select, $include, $where] = static::cleanupForRequest($resClass::API_ENTITY, $fields, $where);
        //var_dump($select, $include); exit;
        //$e = microtime(true);
        //$scrub = $e - $s;

        $response = Request::list($this->connection, $resClass::API_PATH,
                                  ['select' => $select, 'include' => $include, 'where' => $where]);

        //var_dump($response); exit;

//        echo "SCRUB TIME: {$scrub}\n";
//        echo "REQUEST TIME: {$response->responseTime}\n";
//        var_dump($response->responseTime);
        if ($response->result) {
            $this->_hydrate($response->result);
        }

        return $this;

        // $where = [
        //   'prop' => string (key)
        //   'value' => any (validated against the operator)
        //   'operator' => valid operator defaults:"="
        //   'skipValidation' = boolean. if true, let any operator/value be used for this key
        //  ]

        // Call REQUEST (GET) with $fields and limit conditions set with WHERE
        // Return new hydrated collection array
        //return [];
        return $this;
    }

    public function isDirty()
    {
        // @todo Check the collection for any dirty entities
        return false;
    }

    /**
     * @param $data
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
                $tmp->_hydrate($o->id, $o);
                $this->data[$o->id] = $tmp;
            }
            $this->hydrationMode = false;
        }
    }

    public function clear()
    {
        $this->data = [];
    }

    /**
     *
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->data[$this->index];
    }

    /**
     * @return bool|float|int|string|null
     */
    public function key()
    {
        return $this->index;
    }

    /**
     *
     */
    public function next()
    {
        ++$this->index;
    }

    /**
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
     * @return AbstractResource[]
     */
    public function raw()
    {
        return $this->data;
    }
}
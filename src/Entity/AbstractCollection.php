<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/6/20, 3:37 PM
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
use Iterator;
use Jcolombo\PaymoApiPhp\Paymo;

abstract class AbstractCollection extends AbstractEntity implements Iterator, ArrayAccess
{
    /**
     * @var int
     */
    private $index = 0;
    /**
     * @var AbstractResource[]
     */
    private $data = [];

    protected $entityKey = null;

    /**
     * EntityCollection constructor.
     *
     * @param   string   $entityKey The entity key from the entityMap to indicate what type of resources are contained
     * @param array | Paymo | string | null $paymo Either an API Key, Paymo Connection, config settings array (from
     *                                             another entitied getConfiguration call), or null to get first
     *                                             connection available
     *
     * @throws Exception
     */
    public function __construct($entityKey, $paymo=null)
    {
        parent::__construct($paymo);
        $this->entityKey = $entityKey;
        $this->index = 0;
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

    public function clear()
    {
        $this->data = [];
    }
}
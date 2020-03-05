<?php

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use ArrayAccess;
use Exception;
use Iterator;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Class EntityCollection
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Collection
 */
class EntityCollection implements Iterator, ArrayAccess
{

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
     * @param AbstractResource[] $collection A standard array comprised of just Entity objects
     */
    public function __construct($collection = [])
    {
        $this->index = 0;
        $this->data = $collection;
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
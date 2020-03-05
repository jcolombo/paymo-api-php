<?php

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Iterator;
use Jcolombo\PaymoApiPhp\Entity\AbstractEntity;

class EntityCollection implements Iterator
{

    private $index = 0;
    private $data = [];

    public function __construct(AbstractEntity $collection)
    {
        $this->index = 0;
        $this->data = $collection;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function current()
    {
        return $this->data[$this->index];
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        ++$this->index;
    }

    public function valid()
    {
        return isset($this->data[$this->index]);
    }
}
<?php

namespace Jcolombo\PaymoApiPhp\Utility;

/**
 * Class RequestAbstraction
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class RequestAbstraction
{
    /**
     * @var string
     */
    public $method = 'GET';
    /**
     * @var null
     */
    public $resourceUrl = null;
    /**
     * @var null
     */
    public $includeEntities = null;
}
<?php

namespace Jcolombo\PaymoApiPhp\Utility;

/**
 * Class RequestCondition
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class RequestCondition
{
    /**
     * @var
     */
    public $prop;
    /**
     * @var
     */
    public $value;
    /**
     * @var string
     */
    public $operator = '=';
    /**
     * @var bool
     */
    public $validate = true;
}
<?php


namespace Jcolombo\PaymoApiPhp\Entity;


/**
 * Class EntityMap
 *
 * @package Jcolombo\PaymoApiPhp\Entity
 */
class EntityMap
{
    const DEFAULT_COLLECTION_CLASS = 'Jcolombo\PaymoApiPhp\Entity\Collection\EntityCollection';

    private $map = [
        'project' => [
            'entity' => 'Jcolombo\PaymoApiPhp\Entity\Resource\Project',
            'collection' => null
        ],
        'projects' => [
            'entity' => 'Jcolombo\PaymoApiPhp\Entity\Resource\Project',
            'collection' => self::DEFAULT_COLLECTION_CLASS
        ],
        'client' => [
            'entity' => 'Jcolombo\PaymoApiPhp\Entity\Resource\Client',
            'collection' => null
        ],
        'clients' => [
            'entity' => 'Jcolombo\PaymoApiPhp\Entity\Resource\Client',
            'collection' => self::DEFAULT_COLLECTION_CLASS
        ],
        'projectstatus' => [
            'entity' => 'Jcolombo\PaymoApiPhp\Entity\Resource\ProjectStatus',
            'collection' => null
        ],
        'projectstatuses' => [
            'entity' => 'Jcolombo\PaymoApiPhp\Entity\Resource\ProjectStatus',
            'collection' => self::DEFAULT_COLLECTION_CLASS
        ],
    ];

    private $defaultMap = null;

    private static $instance = null;

    public static function map($overload=null) {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        if (!is_null($overload)) {
            self::$instance->overload($overload);
        }
        return self::$instance;
    }

    public function overload($overload) {
        //@todo Apply overload instructions to modify default object map (for project specific extending of entities)
        // $overload is array of settings to overload.
    }

    public function reset() {
        $this->map = $this->defaultMap;
    }

    public function exists($key) {
        return isset($this->map[$key]);
    }

    public function getEntity($key) {
        if (isset($this->map[$key])) {
            return $this->map[$key]['entity'];
        }
        return null;
    }
    public function getCollection($key) {
        if (isset($this->map[$key])) {
            return $this->map[$key]['collection'];
        }
        return null;
    }

    public function getConfiguration($key) {
        if (isset($this->map[$key])) {
            return $this->map[$key];
        }
        return null;
    }

    private function __construct() {
        $this->defaultMap = $this->map;
    }


}
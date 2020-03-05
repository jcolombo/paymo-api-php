<?php

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractEntity;

/**
 * Class ProjectStatus
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class ProjectStatus extends AbstractEntity
{

    /**
     * The user friendly name for error displays, messages, alerts, and logging
     */
    public const LABEL = 'Project Status';

    /**
     * The entity key for associations and references between the package code classes
     */
    public const API_ENTITY = 'projectstatus';

    /**
     * The path that is attached to the API base URL for the api call
     */
    public const API_PATH = 'projectstatuses';

    /**
     * The minimum properties that must be set in order to create a new entry via the API
     */
    public const REQUIRED_CREATE = ['name'];

    /**
     * The object properties that can only be read and never set, updated, or added to the creation
     */
    public const READONLY = [
        'id',
        'created_on',
        'updated_on',
        'readonly',
    ];

    /**
     * Valid relationship entities that can be loaded or attached to this entity
     * TRUE = the include is a list of multiple entities. FALSE = a single object is associated with the entity
     */
    public const INCLUDE_TYPES = [
        'projects' => true,
    ];

    /**
     * Valid property types returned from the API json object for this entity
     */
    public const PROP_TYPES = [
        'id' => 'integer',
        'name' => 'text',
        'active' => 'boolean',
        'seq' => 'integer',
        'readonly' => 'boolean',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        // Undocumented Props

    ];

    /**
     * Allowable operators for list() calls on specific properties
     */
    public const WHERE_OPERATIONS = [
        'active' => ['='],
        'name' => ['=', 'like', 'not like'],
        'readonly' => ['=']
    ];
}
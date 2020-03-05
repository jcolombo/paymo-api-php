<?php

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractEntity;

/**
 * Class Project
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class Project extends AbstractEntity
{

    /**
     * The user friendly name for error displays, messages, alerts, and logging
     */
    public const LABEL = 'Project';

    /**
     * The entity key for associations and references between the package code classes
     */
    public const API_ENTITY = 'project';

    /**
     * The path that is attached to the API base URL for the api call
     */
    public const API_PATH = 'projects';

    /**
     * The minimum properties that must be set in order to create a new entry via the API
     */
    public const REQUIRED_CREATE = ['name'];

    /**
     * The object properties that can only be read and never set, updated, or added to the creation
     */
    public const READONLY = ['id', 'task_code_increment', 'created_on', 'updated_on', 'billing_type'];

    /**
     * Valid relationship entities that can be loaded or attached to this entity
     * TRUE = the include is a list of multiple entities. FALSE = a single object is associated with the entity
     */
    public const INCLUDE_TYPES = [
        'client' => false,
        'projectstatus' => false,
        'tasklists' => true,
        'tasks' => true,
        'milestones' => true,
        'discussions' => true,
        'files' => true,
        'invoiceitem' => false,
        'workflow' => false
    ];

    /**
     * Valid property types returned from the API json object for this entity
     */
    public const PROP_TYPES = [
        'id' => 'integer',
        'name' => 'text',
        'code' => 'text',
        'task_code_increment' => 'integer',
        'description' => 'text',
        'client_id' => 'entity:client',
        'status_id' => 'entity:projectstatus',
        'active' => 'boolean',
        'color' => 'text',
        'users' => 'entityList:users',
        'managers' => 'entityList:managers',
        'billable' => 'boolean',
        'flat_billing' => 'boolean',
        'price_per_hour' => 'decimal',
        'price' => 'decimal',
        'estimated_price' => 'decimal',
        'hourly_billing_mode' => 'text',
        'budget_hours' => 'decimal',
        'adjustable_hours' => 'boolean',
        'invoiced' => 'boolean',
        'invoice_item_id' => 'entity:invoiceitem',
        'workflow_id' => 'entity:milestone',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        // Undocumented Props
        'billing_type' => 'text',
    ];

    /**
     * Allowable operators for list() calls on specific properties
     */
    public const WHERE_OPERATIONS = [
        'client_id' => ['='],
        'active' => ['='],
        'users' => ['=', 'in', 'not in'],
        'managers' => ['=', 'in', 'not in'],
        'billable' => ['='],
    ];
}